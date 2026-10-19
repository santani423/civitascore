import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../../core/l10n/core_strings.dart';
import '../../../../core/network/api_failure.dart';
import '../../../../core/theme/app_dimensions.dart';
import '../../../../core/utils/file_actions.dart';
import '../../../../core/utils/formatters.dart';
import '../../../../core/utils/validators.dart';
import '../../../../core/widgets/app_feedback.dart';
import '../../data/finance_api.dart';
import '../../domain/finance_models.dart';
import '../../l10n/finance_strings.dart';
import '../providers.dart';
import 'finance_widgets.dart';
import 'transaction_sheet.dart';

String isoDate(DateTime d) =>
    '${d.year.toString().padLeft(4, '0')}-${d.month.toString().padLeft(2, '0')}-${d.day.toString().padLeft(2, '0')}';

String _plain(String v) => v.trim().replaceAll(',', '.');

String? _positive(FinanceStrings f, String? v, {bool required = true}) {
  if (v == null || v.trim().isEmpty) return required ? f.invalidAmount : null;
  final n = double.tryParse(_plain(v));
  return (n == null || n <= 0) ? f.invalidAmount : null;
}

/// Base for submit-and-close form sheets with server field errors.
abstract class _FormState<T extends ConsumerStatefulWidget>
    extends ConsumerState<T> {
  final formKey = GlobalKey<FormState>();
  bool busy = false;
  Map<String, String> serverErrors = {};

  Future<String?> send();
  void afterSuccess() {}

  Future<void> submit() async {
    if (!formKey.currentState!.validate()) return;
    setState(() {
      busy = true;
      serverErrors = {};
    });
    try {
      final message = await send();
      afterSuccess();
      if (!mounted) return;
      Navigator.of(context).pop();
      showAppSnack(context, message ?? context.l10n.savedSuccessfully);
    } on ApiFailure catch (e) {
      if (!mounted) return;
      setState(() {
        serverErrors = {
          for (final k in e.fieldErrors.keys) k: e.fieldError(k) ?? '',
        };
      });
      showErrorSnack(context, e);
    } catch (e) {
      if (mounted) showErrorSnack(context, e);
    } finally {
      if (mounted) setState(() => busy = false);
    }
  }
}

// ---------------------------------------------------------------------------
// Refund
// ---------------------------------------------------------------------------

Future<void> showRefundSheet(BuildContext context, DepositInfo deposit) =>
    showFinanceSheet<void>(
      context,
      builder: (_) => _RefundForm(deposit: deposit),
    );

const _refundReasons = [
  'OVERPAYMENT',
  'DUPLICATE_PAYMENT',
  'CANCELLED_INVOICE',
  'WITHDRAWAL',
  'LEAVE',
  'DECEASED',
  'GRADUATED_BALANCE',
  'OTHER',
];

class _RefundForm extends ConsumerStatefulWidget {
  const _RefundForm({required this.deposit});
  final DepositInfo deposit;
  @override
  ConsumerState<_RefundForm> createState() => _RefundFormState();
}

class _RefundFormState extends _FormState<_RefundForm> {
  final amount = TextEditingController();
  final note = TextEditingController();
  final bank = TextEditingController();
  final account = TextEditingController();
  final holder = TextEditingController();
  String reason = 'OVERPAYMENT';
  String relation = 'self';

  @override
  void dispose() {
    for (final c in [amount, note, bank, account, holder]) {
      c.dispose();
    }
    super.dispose();
  }

  @override
  Future<String?> send() => ref
      .read(financeApiProvider)
      .requestRefund(
        amount: _plain(amount.text),
        reasonCode: reason,
        reasonNote: note.text.trim(),
        bank: bank.text.trim(),
        account: account.text.trim(),
        name: holder.text.trim(),
        relation: relation,
      );

  @override
  void afterSuccess() => invalidateFinance(ref);

  @override
  Widget build(BuildContext context) {
    final f = context.fin;
    final s = context.l10n;
    final gap = const SizedBox(height: AppSpacing.sm);
    return Form(
      key: formKey,
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(f.requestRefund, style: Theme.of(context).textTheme.titleLarge),
          const SizedBox(height: 4),
          Text(
            '${f.depositAvailable}: ${context.formatMoney(widget.deposit.available)}. ${f.refundHoldNote}',
            style: Theme.of(context).textTheme.bodySmall,
          ),
          gap,
          TextFormField(
            controller: amount,
            keyboardType: const TextInputType.numberWithOptions(decimal: true),
            decoration: InputDecoration(
              labelText: f.refundAmount,
              errorText: serverErrors['amount'],
            ),
            validator: (v) => _positive(f, v),
          ),
          gap,
          DropdownButtonFormField<String>(
            initialValue: reason,
            isExpanded: true,
            decoration: InputDecoration(labelText: f.refundReason),
            items: [
              for (final r in _refundReasons)
                DropdownMenuItem(value: r, child: Text(f.refundReasonLabel(r))),
            ],
            onChanged: (v) => setState(() => reason = v ?? reason),
          ),
          gap,
          TextFormField(
            controller: note,
            maxLines: 3,
            maxLength: 1000,
            decoration: InputDecoration(
              labelText: f.refundNote,
              errorText: serverErrors['reason_note'],
            ),
            validator: (v) =>
                (v ?? '').trim().length < 10 ? f.minChars10 : null,
          ),
          TextFormField(
            controller: bank,
            decoration: InputDecoration(
              labelText: f.beneficiaryBank,
              errorText: serverErrors['beneficiary_bank'],
            ),
            validator: Validators.compose([
              Validators.required(s),
              Validators.maxLength(s, 60),
            ]),
          ),
          gap,
          TextFormField(
            controller: account,
            keyboardType: TextInputType.number,
            decoration: InputDecoration(
              labelText: f.beneficiaryAccount,
              errorText: serverErrors['beneficiary_account'],
            ),
            validator: (v) =>
                RegExp(r'^[0-9]{6,20}$').hasMatch((v ?? '').trim())
                ? null
                : f.invalidAccount,
          ),
          gap,
          TextFormField(
            controller: holder,
            decoration: InputDecoration(
              labelText: f.beneficiaryName,
              errorText: serverErrors['beneficiary_name'],
            ),
            validator: Validators.compose([
              Validators.required(s),
              Validators.maxLength(s, 150),
            ]),
          ),
          gap,
          DropdownButtonFormField<String>(
            initialValue: relation,
            isExpanded: true,
            decoration: InputDecoration(labelText: f.beneficiaryRelation),
            items: [
              DropdownMenuItem(value: 'self', child: Text(f.relationSelf)),
              DropdownMenuItem(value: 'parent', child: Text(f.relationParent)),
            ],
            onChanged: (v) => setState(() => relation = v ?? relation),
          ),
          const SizedBox(height: AppSpacing.lg),
          FilledButton(
            onPressed: busy ? null : submit,
            child: Text(busy ? f.submitting : f.requestRefund),
          ),
        ],
      ),
    );
  }
}

// ---------------------------------------------------------------------------
// Deposit top-up (manual transfer targeting the deposit)
// ---------------------------------------------------------------------------

Future<void> showTopUpSheet(BuildContext context) =>
    showFinanceSheet<void>(context, builder: (_) => const _TopUpForm());

class _TopUpForm extends ConsumerStatefulWidget {
  const _TopUpForm();
  @override
  ConsumerState<_TopUpForm> createState() => _TopUpFormState();
}

class _TopUpFormState extends ConsumerState<_TopUpForm> {
  final key = GlobalKey<FormState>();
  final amount = TextEditingController();
  bool busy = false;

  @override
  void dispose() {
    amount.dispose();
    super.dispose();
  }

  Future<void> _submit() async {
    if (!key.currentState!.validate()) return;
    setState(() => busy = true);
    try {
      final tx = await ref
          .read(financeApiProvider)
          .payManual(targetType: 'deposit', amount: _plain(amount.text));
      invalidateFinance(ref);
      if (!mounted) return;
      final nav = Navigator.of(context);
      final host = nav.context;
      nav.pop();
      if (host.mounted) await showTransactionSheet(host, tx);
    } catch (e) {
      if (mounted) showErrorSnack(context, e);
    } finally {
      if (mounted) setState(() => busy = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    final f = context.fin;
    return Form(
      key: key,
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(f.topUp, style: Theme.of(context).textTheme.titleLarge),
          const SizedBox(height: 4),
          Text(f.manualHint, style: Theme.of(context).textTheme.bodySmall),
          const SizedBox(height: AppSpacing.md),
          TextFormField(
            controller: amount,
            keyboardType: const TextInputType.numberWithOptions(decimal: true),
            decoration: InputDecoration(labelText: f.topUpAmount),
            validator: (v) => _positive(f, v),
          ),
          const SizedBox(height: AppSpacing.lg),
          FilledButton(
            onPressed: busy ? null : _submit,
            child: Text(busy ? f.submitting : f.createInstruction),
          ),
        ],
      ),
    );
  }
}

// ---------------------------------------------------------------------------
// UKT change request
// ---------------------------------------------------------------------------

Future<void> showUktRequestSheet(BuildContext context, UktInfo info) =>
    showFinanceSheet<void>(context, builder: (_) => _UktForm(info: info));

class _UktForm extends ConsumerStatefulWidget {
  const _UktForm({required this.info});
  final UktInfo info;
  @override
  ConsumerState<_UktForm> createState() => _UktFormState();
}

class _UktFormState extends _FormState<_UktForm> {
  final reason = TextEditingController();
  String? groupId;
  PickedUpload? evidence;
  String? fileError;

  @override
  void dispose() {
    reason.dispose();
    super.dispose();
  }

  Future<void> _pick() async {
    final picked = await ref.read(uploadPickerProvider)(context, maxMb: 5);
    if (picked != null) {
      setState(() {
        evidence = picked;
        fileError = null;
      });
    }
  }

  @override
  Future<String?> send() async {
    final fileId = await ref.read(fileUploaderProvider)(evidence!);
    return ref
        .read(financeApiProvider)
        .requestUktChange(
          requestedGroupId: groupId,
          reason: reason.text.trim(),
          evidenceFileId: fileId,
        );
  }

  @override
  void afterSuccess() => ref.invalidate(uktProvider);

  @override
  Future<void> submit() async {
    if (evidence == null) {
      setState(() => fileError = context.fin.evidenceRequired);
      formKey.currentState!.validate();
      return;
    }
    await super.submit();
  }

  @override
  Widget build(BuildContext context) {
    final f = context.fin;
    return Form(
      key: formKey,
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(f.requestUkt, style: Theme.of(context).textTheme.titleLarge),
          const SizedBox(height: AppSpacing.md),
          DropdownButtonFormField<String?>(
            initialValue: groupId,
            isExpanded: true,
            decoration: InputDecoration(labelText: f.requestedGroup),
            items: [
              const DropdownMenuItem<String?>(value: null, child: Text('—')),
              for (final g in widget.info.groups)
                DropdownMenuItem<String?>(
                  value: g.id,
                  child: Text(g.name, overflow: TextOverflow.ellipsis),
                ),
            ],
            onChanged: (v) => setState(() => groupId = v),
          ),
          const SizedBox(height: AppSpacing.sm),
          TextFormField(
            controller: reason,
            maxLines: 4,
            maxLength: 2000,
            decoration: InputDecoration(
              labelText: f.uktReason,
              errorText: serverErrors['reason'],
            ),
            validator: (v) =>
                (v ?? '').trim().length < 20 ? f.minChars20 : null,
          ),
          OutlinedButton.icon(
            onPressed: _pick,
            icon: const Icon(Icons.attach_file),
            label: Text(
              evidence?.name ?? f.evidence,
              overflow: TextOverflow.ellipsis,
            ),
          ),
          if (fileError != null || serverErrors['evidence_file_id'] != null)
            Padding(
              padding: const EdgeInsets.only(top: 4),
              child: Text(
                fileError ?? serverErrors['evidence_file_id']!,
                style: TextStyle(color: Theme.of(context).colorScheme.error),
              ),
            ),
          const SizedBox(height: AppSpacing.lg),
          FilledButton(
            onPressed: busy ? null : submit,
            child: Text(busy ? f.submitting : f.requestUkt),
          ),
        ],
      ),
    );
  }
}

// ---------------------------------------------------------------------------
// Dispensation
// ---------------------------------------------------------------------------

Future<void> showDispensationSheet(BuildContext context, AcademicBlock block) =>
    showFinanceSheet<void>(
      context,
      builder: (_) => _DispensationForm(block: block),
    );

class _DispensationForm extends ConsumerStatefulWidget {
  const _DispensationForm({required this.block});
  final AcademicBlock block;
  @override
  ConsumerState<_DispensationForm> createState() => _DispensationFormState();
}

class _DispensationFormState extends _FormState<_DispensationForm> {
  final reason = TextEditingController();
  final promiseAmount = TextEditingController();
  late String service = widget.block.services.isEmpty
      ? 'krs'
      : widget.block.services.first;
  DateTime? validUntil;
  DateTime? promiseDate;
  String? dateError;

  @override
  void dispose() {
    reason.dispose();
    promiseAmount.dispose();
    super.dispose();
  }

  Future<DateTime?> _pick(DateTime first, DateTime last) => showDatePicker(
    context: context,
    initialDate: first,
    firstDate: first,
    lastDate: last,
  );

  @override
  Future<String?> send() => ref
      .read(financeApiProvider)
      .requestDispensation(
        widget.block.id,
        service: service,
        validUntil: isoDate(validUntil!),
        reason: reason.text.trim(),
        promiseAmount: promiseAmount.text.trim().isEmpty
            ? null
            : _plain(promiseAmount.text),
        promiseDate: promiseDate == null ? null : isoDate(promiseDate!),
      );

  @override
  void afterSuccess() => invalidateFinance(ref);

  @override
  Future<void> submit() async {
    if (validUntil == null) {
      setState(() => dateError = context.fin.dateRequired);
      formKey.currentState!.validate();
      return;
    }
    setState(() => dateError = null);
    await super.submit();
  }

  @override
  Widget build(BuildContext context) {
    final f = context.fin;
    final now = DateTime.now();
    final services = widget.block.services.isEmpty
        ? const ['krs', 'exam']
        : widget.block.services;
    return Form(
      key: formKey,
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(
            f.requestDispensation,
            style: Theme.of(context).textTheme.titleLarge,
          ),
          const SizedBox(height: AppSpacing.md),
          DropdownButtonFormField<String>(
            initialValue: services.contains(service) ? service : services.first,
            isExpanded: true,
            decoration: InputDecoration(
              labelText: f.dispService,
              errorText: serverErrors['service'],
            ),
            items: [
              for (final s in services)
                DropdownMenuItem(value: s, child: Text(f.serviceName(s, s))),
            ],
            onChanged: (v) => setState(() => service = v ?? service),
          ),
          const SizedBox(height: AppSpacing.sm),
          OutlinedButton.icon(
            onPressed: () async {
              final d = await _pick(
                now.add(const Duration(days: 1)),
                now.add(const Duration(days: 30)),
              );
              if (d != null) setState(() => validUntil = d);
            },
            icon: const Icon(Icons.event),
            label: Text(
              validUntil == null
                  ? f.validUntil
                  : '${f.validUntil}: ${context.formatDate(validUntil)}',
              overflow: TextOverflow.ellipsis,
            ),
          ),
          if (dateError != null || serverErrors['valid_until'] != null)
            Text(
              dateError ?? serverErrors['valid_until']!,
              style: TextStyle(color: Theme.of(context).colorScheme.error),
            ),
          const SizedBox(height: AppSpacing.sm),
          TextFormField(
            controller: reason,
            maxLines: 4,
            maxLength: 1000,
            decoration: InputDecoration(
              labelText: f.dispReason,
              errorText: serverErrors['reason'],
            ),
            validator: (v) =>
                (v ?? '').trim().length < 20 ? f.minChars20 : null,
          ),
          TextFormField(
            controller: promiseAmount,
            keyboardType: const TextInputType.numberWithOptions(decimal: true),
            decoration: InputDecoration(labelText: f.promiseAmount),
            validator: (v) => _positive(f, v, required: false),
          ),
          const SizedBox(height: AppSpacing.sm),
          OutlinedButton.icon(
            onPressed: () async {
              final d = await _pick(now, now.add(const Duration(days: 90)));
              if (d != null) setState(() => promiseDate = d);
            },
            icon: const Icon(Icons.event_available),
            label: Text(
              promiseDate == null
                  ? f.promiseDate
                  : '${f.promiseDate}: ${context.formatDate(promiseDate)}',
              overflow: TextOverflow.ellipsis,
            ),
          ),
          const SizedBox(height: AppSpacing.lg),
          FilledButton(
            onPressed: busy ? null : submit,
            child: Text(busy ? f.submitting : f.requestDispensation),
          ),
        ],
      ),
    );
  }
}
