import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:uuid/uuid.dart';

import '../../../../core/l10n/core_strings.dart';
import '../../../../core/network/api_failure.dart';
import '../../../../core/theme/app_dimensions.dart';
import '../../../../core/utils/formatters.dart';
import '../../../../core/widgets/app_feedback.dart';
import '../../../../core/widgets/state_views.dart';
import '../../../../core/widgets/ui_kit.dart';
import '../../data/finance_api.dart';
import '../../domain/finance_models.dart';
import '../../l10n/finance_strings.dart';
import '../providers.dart';
import '../widgets/finance_widgets.dart';
import '../widgets/transaction_sheet.dart';

/// Pay an invoice (or one installment): online via hosted checkout, or
/// manual transfer with proof upload.
class PayScreen extends ConsumerStatefulWidget {
  const PayScreen({super.key, required this.invoiceId, this.installmentId});
  final String invoiceId;
  final String? installmentId;

  @override
  ConsumerState<PayScreen> createState() => _PayScreenState();
}

class _PayScreenState extends ConsumerState<PayScreen> {
  String? installmentId;
  String? channelId;
  bool busy = false;
  bool channelUnavailable = false;
  String? error;

  // Idempotency-Key is reused while retrying the SAME attempt (same channel
  // and target) so a retry never creates a second invoice at the provider.
  String? _key;
  String? _keySignature;

  final manualAmount = TextEditingController();
  final manualKey = GlobalKey<FormState>();

  @override
  void initState() {
    super.initState();
    installmentId = widget.installmentId;
  }

  @override
  void dispose() {
    manualAmount.dispose();
    super.dispose();
  }

  String _idempotencyKey(String channel, String target) {
    final signature = '$channel|$target';
    if (_keySignature != signature || _key == null) {
      _keySignature = signature;
      _key = const Uuid().v4();
    }
    return _key!;
  }

  (String type, String id) _target(Invoice inv) => installmentId == null
      ? ('invoice', inv.id)
      : ('installment', installmentId!);

  Future<void> _payOnline(Invoice inv) async {
    final channel = channelId;
    if (channel == null) return;
    final (type, id) = _target(inv);
    setState(() {
      busy = true;
      error = null;
    });
    try {
      final tx = await ref
          .read(financeApiProvider)
          .payOnline(
            channelId: channel,
            targetType: type,
            targetId: id,
            idempotencyKey: _idempotencyKey(channel, '$type:$id'),
          );
      _key = null; // a later attempt is a new request
      invalidateFinance(ref, invoiceId: inv.id);
      if (!mounted) return;
      final url = tx.redirectUrl;
      if (url != null) {
        final ok = await ref.read(externalLauncherProvider)(Uri.parse(url));
        if (!ok && mounted) {
          showAppSnack(context, context.fin.cannotOpenLink, isError: true);
        }
      }
      if (!mounted) return;
      setState(() => busy = false);
      await showTransactionSheet(context, tx, invoiceId: inv.id);
    } on ApiFailure catch (e) {
      if (!mounted) return;
      setState(() {
        if (e.code == 'CHANNEL_NOT_CONNECTED') {
          channelUnavailable = true;
          channelId = null;
        } else {
          error = context.l10n.errorFor(e);
        }
      });
    } catch (e) {
      if (mounted) setState(() => error = context.l10n.errorFor(e));
    } finally {
      if (mounted) setState(() => busy = false);
    }
  }

  Future<void> _payManual(Invoice inv) async {
    final f = context.fin;
    if (!manualKey.currentState!.validate()) return;
    final (type, id) = _target(inv);
    final raw = manualAmount.text.trim().replaceAll(',', '.');
    setState(() {
      busy = true;
      error = null;
    });
    try {
      final tx = await ref
          .read(financeApiProvider)
          .payManual(
            targetType: type,
            targetId: id,
            amount: raw.isEmpty ? null : raw,
          );
      invalidateFinance(ref, invoiceId: inv.id);
      if (!mounted) return;
      setState(() => busy = false);
      showAppSnack(context, f.manualHint);
      await showTransactionSheet(context, tx, invoiceId: inv.id);
    } catch (e) {
      if (mounted) setState(() => error = context.l10n.errorFor(e));
    } finally {
      if (mounted) setState(() => busy = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    final f = context.fin;
    final invoice = ref.watch(invoiceDetailProvider(widget.invoiceId));
    return Scaffold(
      appBar: AppBar(title: Text(f.payTitle)),
      body: AsyncValueView<Invoice>(
        value: invoice,
        onRetry: () => ref.invalidate(invoiceDetailProvider(widget.invoiceId)),
        data: _content,
      ),
    );
  }

  Widget _content(Invoice inv) {
    final f = context.fin;
    final text = Theme.of(context).textTheme;
    final channels = ref.watch(paymentChannelsProvider);
    final payable = inv.activeInstallments.where((i) => i.isPayable).toList();
    Installment? selected;
    for (final i in payable) {
      if (i.id == installmentId) selected = i;
    }
    final amount = selected?.remaining ?? inv.outstanding;
    return FinanceBody(
      child: ListView(
        padding: const EdgeInsets.all(AppSpacing.lg),
        children: [
          if (!inv.canPay)
            AppBanner(message: f.nothingToPay, tone: StatusTone.info)
          else ...[
            AppSurfaceCard(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(inv.invoiceNumber, style: text.bodySmall),
                  const SizedBox(height: 2),
                  Text(context.formatMoney(amount), style: text.headlineSmall),
                  if (payable.isNotEmpty) ...[
                    const SizedBox(height: AppSpacing.sm),
                    Text(f.payTarget, style: text.labelLarge),
                    RadioGroup<String?>(
                      groupValue: installmentId,
                      onChanged: (v) => setState(() {
                        installmentId = v;
                        error = null;
                      }),
                      child: Column(
                        children: [
                          RadioListTile<String?>(
                            value: null,
                            contentPadding: EdgeInsets.zero,
                            title: Text(
                              '${f.payWholeInvoice} (${context.formatMoney(inv.outstanding)})',
                            ),
                          ),
                          for (final i in payable)
                            RadioListTile<String?>(
                              value: i.id,
                              contentPadding: EdgeInsets.zero,
                              title: Text(
                                '${f.installmentLabel(i.seq)} (${context.formatMoney(i.remaining)})',
                              ),
                              subtitle: Text(
                                f.dueOn(context.formatDate(i.dueDate)),
                              ),
                            ),
                        ],
                      ),
                    ),
                  ],
                ],
              ),
            ),
            if (error != null) ...[
              const SizedBox(height: AppSpacing.md),
              AppBanner(message: error!, tone: StatusTone.danger),
            ],
            SectionHeader(
              title: f.onlineTitle,
              padding: const EdgeInsets.only(
                top: AppSpacing.xl,
                bottom: AppSpacing.sm,
              ),
            ),
            channels.when(
              loading: () => const Padding(
                padding: EdgeInsets.all(AppSpacing.lg),
                child: Center(child: CircularProgressIndicator()),
              ),
              error: (e, _) => ErrorView(
                error: e,
                onRetry: () => ref.invalidate(paymentChannelsProvider),
              ),
              data: (c) => _online(inv, c),
            ),
            channels.maybeWhen(
              data: (c) => _manual(inv, c),
              orElse: () => const SizedBox.shrink(),
            ),
          ],
        ],
      ),
    );
  }

  String _methodLabel(String m) {
    final f = context.fin;
    return switch (m) {
      'va' => f.methodVa,
      'qris' => f.methodQris,
      'ewallet' => f.methodEwallet,
      'retail' => f.methodRetail,
      _ => m,
    };
  }

  Widget _online(Invoice inv, PaymentChannels c) {
    final f = context.fin;
    if (c.online.isEmpty || channelUnavailable) {
      return AppBanner(
        tone: StatusTone.warning,
        message: channelUnavailable
            ? f.channelNotConnected
            : f.noOnlineChannels,
      );
    }
    final methods = <String>{for (final ch in c.online) ch.method};
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text(f.onlineHint, style: Theme.of(context).textTheme.bodySmall),
        RadioGroup<String>(
          groupValue: channelId,
          onChanged: (v) => setState(() => channelId = v),
          child: Column(
            children: [
              for (final m in methods) ...[
                Padding(
                  padding: const EdgeInsets.only(top: AppSpacing.sm),
                  child: Align(
                    alignment: Alignment.centerLeft,
                    child: Text(
                      _methodLabel(m),
                      style: Theme.of(context).textTheme.labelLarge,
                    ),
                  ),
                ),
                for (final ch in c.online.where((x) => x.method == m))
                  RadioListTile<String>(
                    value: ch.id,
                    contentPadding: EdgeInsets.zero,
                    title: Text(ch.name),
                    subtitle: ch.studentPaysFee
                        ? Text(f.feeInfo(context.formatMoney(ch.feeAmount)))
                        : null,
                  ),
              ],
            ],
          ),
        ),
        const SizedBox(height: AppSpacing.sm),
        FilledButton.icon(
          onPressed: busy || channelId == null ? null : () => _payOnline(inv),
          icon: const Icon(Icons.open_in_new),
          label: Text(busy ? f.submitting : f.payNow),
        ),
      ],
    );
  }

  Widget _manual(Invoice inv, PaymentChannels c) {
    final f = context.fin;
    final s = context.l10n;
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        SectionHeader(
          title: f.manualTitle,
          padding: const EdgeInsets.only(
            top: AppSpacing.xl,
            bottom: AppSpacing.sm,
          ),
        ),
        if (!c.manualEnabled)
          AppBanner(message: f.manualDisabled, tone: StatusTone.warning)
        else ...[
          Text(f.manualHint, style: Theme.of(context).textTheme.bodySmall),
          const SizedBox(height: AppSpacing.sm),
          Form(
            key: manualKey,
            child: TextFormField(
              controller: manualAmount,
              keyboardType: const TextInputType.numberWithOptions(
                decimal: true,
              ),
              decoration: InputDecoration(labelText: f.manualAmountLabel),
              validator: (v) {
                if (v == null || v.trim().isEmpty) return null;
                final n = double.tryParse(v.trim().replaceAll(',', '.'));
                return (n == null || n <= 0) ? f.invalidAmount : null;
              },
            ),
          ),
          const SizedBox(height: AppSpacing.sm),
          OutlinedButton.icon(
            onPressed: busy ? null : () => _payManual(inv),
            icon: const Icon(Icons.account_balance_outlined),
            label: Text(busy ? s.loading : f.createInstruction),
          ),
        ],
      ],
    );
  }
}
