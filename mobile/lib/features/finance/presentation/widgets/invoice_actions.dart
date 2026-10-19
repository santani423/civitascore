import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../../core/l10n/core_strings.dart';
import '../../../../core/network/api_failure.dart';
import '../../../../core/theme/app_dimensions.dart';
import '../../../../core/utils/formatters.dart';
import '../../../../core/utils/validators.dart';
import '../../../../core/widgets/app_feedback.dart';
import '../../../../core/widgets/state_views.dart';
import '../../../../core/widgets/ui_kit.dart';
import '../../data/finance_api.dart';
import '../../domain/finance_models.dart';
import '../../l10n/finance_strings.dart';
import '../providers.dart';
import 'finance_widgets.dart';

/// Equal split of 100 percent in [n] terms, 30 days apart (last term absorbs
/// the rounding remainder so the total is exactly 100).
List<Map<String, dynamic>> buildEqualTerms(int n) {
  final base = (10000 ~/ n) / 100;
  var used = 0.0;
  return [
    for (var i = 0; i < n; i++)
      () {
        final percent = i == n - 1
            ? double.parse((100 - used).toStringAsFixed(2))
            : base;
        used += base;
        return {'percent': percent, 'due_offset_days': i * 30};
      }(),
  ];
}

Future<void> showDepositSheet(BuildContext context, Invoice invoice) =>
    showFinanceSheet<void>(
      context,
      builder: (_) =>
          _SingleFieldForm(invoice: invoice, mode: _FieldMode.deposit),
    );

Future<void> showVoucherSheet(BuildContext context, Invoice invoice) =>
    showFinanceSheet<void>(
      context,
      builder: (_) =>
          _SingleFieldForm(invoice: invoice, mode: _FieldMode.voucher),
    );

Future<void> showInstallmentSheet(BuildContext context, Invoice invoice) =>
    showFinanceSheet<void>(
      context,
      builder: (_) => _InstallmentForm(invoice: invoice),
    );

enum _FieldMode { deposit, voucher }

class _SingleFieldForm extends ConsumerStatefulWidget {
  const _SingleFieldForm({required this.invoice, required this.mode});
  final Invoice invoice;
  final _FieldMode mode;

  @override
  ConsumerState<_SingleFieldForm> createState() => _SingleFieldFormState();
}

class _SingleFieldFormState extends ConsumerState<_SingleFieldForm> {
  final key = GlobalKey<FormState>();
  final controller = TextEditingController();
  bool busy = false;
  String? serverError;

  @override
  void dispose() {
    controller.dispose();
    super.dispose();
  }

  Future<void> _submit() async {
    if (!key.currentState!.validate()) return;
    final api = ref.read(financeApiProvider);
    setState(() {
      busy = true;
      serverError = null;
    });
    try {
      final String? message;
      if (widget.mode == _FieldMode.voucher) {
        message = await api.redeemVoucher(
          widget.invoice.id,
          controller.text.trim().toUpperCase(),
        );
      } else {
        final raw = controller.text.trim().replaceAll(',', '.');
        message = await api.applyDeposit(
          widget.invoice.id,
          amount: raw.isEmpty ? null : raw,
        );
      }
      invalidateFinance(ref, invoiceId: widget.invoice.id);
      if (!mounted) return;
      final nav = Navigator.of(context);
      final messenger = context;
      nav.pop();
      if (messenger.mounted) {
        showAppSnack(messenger, message ?? context.l10n.savedSuccessfully);
      }
    } on ApiFailure catch (e) {
      if (mounted) {
        setState(
          () => serverError =
              e.fieldError('code') ?? e.fieldError('amount') ?? e.serverDetail,
        );
      }
    } catch (e) {
      if (mounted) showErrorSnack(context, e);
    } finally {
      if (mounted) setState(() => busy = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    final f = context.fin;
    final s = context.l10n;
    final voucher = widget.mode == _FieldMode.voucher;
    return Form(
      key: key,
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(
            voucher ? f.useVoucher : f.useDeposit,
            style: Theme.of(context).textTheme.titleLarge,
          ),
          const SizedBox(height: AppSpacing.sm),
          Text(
            '${widget.invoice.invoiceNumber} · ${f.remaining} ${context.formatMoney(widget.invoice.outstanding)}',
            style: Theme.of(context).textTheme.bodySmall,
          ),
          if (!voucher) ...[
            const SizedBox(height: AppSpacing.sm),
            Text(f.depositConfirm),
          ],
          const SizedBox(height: AppSpacing.md),
          TextFormField(
            controller: controller,
            textCapitalization: voucher
                ? TextCapitalization.characters
                : TextCapitalization.none,
            keyboardType: voucher
                ? TextInputType.text
                : const TextInputType.numberWithOptions(decimal: true),
            decoration: InputDecoration(
              labelText: voucher ? f.voucherLabel : f.depositAmountLabel,
              helperText: voucher ? f.voucherHint : null,
              errorText: serverError,
            ),
            validator: voucher
                ? Validators.compose([
                    Validators.required(s),
                    (v) {
                      final n = v?.trim().length ?? 0;
                      return (n < 4 || n > 30) ? f.voucherHint : null;
                    },
                  ])
                : (v) {
                    if (v == null || v.trim().isEmpty) return null;
                    final n = double.tryParse(v.trim().replaceAll(',', '.'));
                    return (n == null || n <= 0) ? f.invalidAmount : null;
                  },
          ),
          const SizedBox(height: AppSpacing.lg),
          FilledButton(
            onPressed: busy ? null : _submit,
            child: Text(
              busy ? f.submitting : (voucher ? f.apply : f.useDeposit),
            ),
          ),
        ],
      ),
    );
  }
}

class _InstallmentForm extends ConsumerStatefulWidget {
  const _InstallmentForm({required this.invoice});
  final Invoice invoice;

  @override
  ConsumerState<_InstallmentForm> createState() => _InstallmentFormState();
}

class _InstallmentFormState extends ConsumerState<_InstallmentForm> {
  final key = GlobalKey<FormState>();
  final reason = TextEditingController();
  final termsCtl = TextEditingController(text: '3');
  String? planId; // null = custom
  bool custom = false;
  bool busy = false;
  String? serverError;

  @override
  void dispose() {
    reason.dispose();
    termsCtl.dispose();
    super.dispose();
  }

  Future<void> _submit() async {
    final f = context.fin;
    if (!custom && planId == null) return;
    if (!key.currentState!.validate()) return;
    setState(() {
      busy = true;
      serverError = null;
    });
    try {
      final message = await ref
          .read(financeApiProvider)
          .requestInstallment(
            widget.invoice.id,
            planId: custom ? null : planId,
            terms: custom
                ? buildEqualTerms(int.parse(termsCtl.text.trim()))
                : null,
            reason: reason.text.trim(),
            kind: widget.invoice.activeInstallments.isEmpty
                ? 'new'
                : 'reschedule',
          );
      invalidateFinance(ref, invoiceId: widget.invoice.id);
      if (!mounted) return;
      Navigator.of(context).pop();
      showAppSnack(context, message ?? f.apply);
    } on ApiFailure catch (e) {
      if (mounted) setState(() => serverError = e.serverDetail);
    } catch (e) {
      if (mounted) showErrorSnack(context, e);
    } finally {
      if (mounted) setState(() => busy = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    final f = context.fin;
    final s = context.l10n;
    final plans = ref.watch(installmentPlansProvider(widget.invoice.id));
    return Form(
      key: key,
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(
            f.requestInstallment,
            style: Theme.of(context).textTheme.titleLarge,
          ),
          const SizedBox(height: AppSpacing.sm),
          Text(f.chooseScheme, style: Theme.of(context).textTheme.titleSmall),
          plans.when(
            loading: () => const Padding(
              padding: EdgeInsets.all(AppSpacing.lg),
              child: Center(child: CircularProgressIndicator()),
            ),
            error: (e, _) => ErrorView(
              error: e,
              onRetry: () =>
                  ref.invalidate(installmentPlansProvider(widget.invoice.id)),
            ),
            data: (list) => RadioGroup<String>(
              groupValue: custom ? '__custom__' : planId,
              onChanged: (v) => setState(() {
                custom = v == '__custom__';
                planId = custom ? null : v;
              }),
              child: Column(
                children: [
                  if (list.isEmpty)
                    Padding(
                      padding: const EdgeInsets.symmetric(
                        vertical: AppSpacing.sm,
                      ),
                      child: Text(f.noPlans),
                    ),
                  for (final p in list)
                    RadioListTile<String>(
                      value: p.id,
                      contentPadding: EdgeInsets.zero,
                      title: Text(p.name),
                      subtitle: Text(
                        [
                          '${p.terms.length}x',
                          if (p.autoApprove) f.planAuto,
                        ].join(' · '),
                      ),
                    ),
                  RadioListTile<String>(
                    value: '__custom__',
                    contentPadding: EdgeInsets.zero,
                    title: Text(f.customScheme),
                  ),
                ],
              ),
            ),
          ),
          if (custom) ...[
            TextFormField(
              controller: termsCtl,
              keyboardType: TextInputType.number,
              decoration: InputDecoration(labelText: f.termsLabel),
              validator: Validators.compose([
                Validators.required(s),
                Validators.numberInRange(s, 2, 12),
              ]),
            ),
            const SizedBox(height: AppSpacing.sm),
            TextFormField(
              controller: reason,
              maxLines: 3,
              maxLength: 1000,
              decoration: InputDecoration(labelText: f.reasonLabel),
              validator: Validators.required(s),
            ),
          ] else if (planId != null)
            TextFormField(
              controller: reason,
              maxLines: 2,
              maxLength: 1000,
              decoration: InputDecoration(
                labelText: '${f.reasonLabel} (${s.optional})',
              ),
            ),
          if (serverError != null)
            Padding(
              padding: const EdgeInsets.only(bottom: AppSpacing.sm),
              child: AppBanner(message: serverError!, tone: StatusTone.danger),
            ),
          const SizedBox(height: AppSpacing.sm),
          FilledButton(
            onPressed: busy || (!custom && planId == null) ? null : _submit,
            child: Text(busy ? f.submitting : f.requestInstallment),
          ),
        ],
      ),
    );
  }
}
