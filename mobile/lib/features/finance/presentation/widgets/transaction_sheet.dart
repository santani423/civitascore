import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../../core/l10n/core_strings.dart';
import '../../../../core/network/api_failure.dart';
import '../../../../core/theme/app_dimensions.dart';
import '../../../../core/utils/file_actions.dart';
import '../../../../core/utils/formatters.dart';
import '../../../../core/utils/validators.dart';
import '../../../../core/widgets/app_feedback.dart';
import '../../../../core/widgets/ui_kit.dart';
import '../../data/finance_api.dart';
import '../../domain/finance_models.dart';
import '../../l10n/finance_strings.dart';
import '../providers.dart';
import 'finance_widgets.dart';

/// Shows payment instructions / status / proof upload for one transaction.
Future<void> showTransactionSheet(
  BuildContext context,
  FinanceTransaction tx, {
  String? invoiceId,
}) => showFinanceSheet<void>(
  context,
  builder: (_) => TransactionPanel(initial: tx, invoiceId: invoiceId),
);

class TransactionPanel extends ConsumerStatefulWidget {
  const TransactionPanel({super.key, required this.initial, this.invoiceId});
  final FinanceTransaction initial;
  final String? invoiceId;

  @override
  ConsumerState<TransactionPanel> createState() => _TransactionPanelState();
}

class _TransactionPanelState extends ConsumerState<TransactionPanel> {
  late FinanceTransaction tx = widget.initial;
  bool busy = false;
  bool showProofForm = false;

  final formKey = GlobalKey<FormState>();
  final bank = TextEditingController();
  final name = TextEditingController();
  late final amount = TextEditingController(text: _plainAmount(tx.amount));
  DateTime? date;
  PickedUpload? file;
  Map<String, String> serverErrors = {};

  static String _plainAmount(String v) {
    final d = double.tryParse(v);
    if (d == null) return v;
    return d == d.roundToDouble() ? d.toStringAsFixed(0) : d.toStringAsFixed(2);
  }

  @override
  void dispose() {
    bank.dispose();
    name.dispose();
    amount.dispose();
    super.dispose();
  }

  void _done(FinanceTransaction updated) {
    setState(() => tx = updated);
    invalidateFinance(ref, invoiceId: widget.invoiceId);
  }

  Future<void> _run(Future<void> Function() action) async {
    setState(() => busy = true);
    try {
      await action();
    } catch (e) {
      if (mounted) showErrorSnack(context, e);
    } finally {
      if (mounted) setState(() => busy = false);
    }
  }

  Future<void> _openPage() async {
    final url = tx.redirectUrl;
    if (url == null) return;
    final ok = await ref.read(externalLauncherProvider)(Uri.parse(url));
    if (!ok && mounted) {
      showAppSnack(context, context.fin.cannotOpenLink, isError: true);
    }
  }

  Future<void> _refresh() => _run(() async {
    final before = tx.status;
    final updated = await ref.read(financeApiProvider).refresh(tx.id);
    _done(updated);
    if (mounted && updated.status == before && updated.status == 'pending') {
      showAppSnack(context, context.fin.statusUnchanged);
    }
  });

  Future<void> _cancel() async {
    final f = context.fin;
    final ok = await confirmAction(
      context,
      title: f.cancelTx,
      message: f.cancelTxConfirm,
      destructive: true,
    );
    if (!ok) return;
    await _run(() async {
      final updated = await ref.read(financeApiProvider).cancel(tx.id);
      _done(updated);
      if (mounted) showAppSnack(context, f.cancelTxDone);
    });
  }

  Future<void> _pickFile() async {
    final picked = await ref.read(uploadPickerProvider)(context, maxMb: 2);
    if (picked != null) setState(() => file = picked);
  }

  Future<void> _pickDate() async {
    final now = DateTime.now();
    final d = await showDatePicker(
      context: context,
      initialDate: date ?? now,
      firstDate: now.subtract(const Duration(days: 60)),
      lastDate: now,
    );
    if (d != null) setState(() => date = d);
  }

  String _iso(DateTime d) =>
      '${d.year.toString().padLeft(4, '0')}-${d.month.toString().padLeft(2, '0')}-${d.day.toString().padLeft(2, '0')}';

  Future<void> _submitProof() async {
    final f = context.fin;
    if (!formKey.currentState!.validate()) return;
    if (file == null) {
      showAppSnack(context, f.proofFileRequired, isError: true);
      return;
    }
    if (date == null) {
      showAppSnack(context, f.dateRequired, isError: true);
      return;
    }
    setState(() {
      busy = true;
      serverErrors = {};
    });
    try {
      final api = ref.read(financeApiProvider);
      final fileId = await ref.read(fileUploaderProvider)(file!);
      final updated = await api.submitProof(
        tx.id,
        fileUploadId: fileId,
        senderBank: bank.text.trim(),
        senderName: name.text.trim(),
        transferDate: _iso(date!),
        transferredAmount: amount.text.trim().replaceAll(',', '.'),
      );
      _done(updated);
      if (mounted) {
        setState(() {
          showProofForm = false;
          file = null;
        });
        showAppSnack(context, f.proofSent);
      }
    } on ApiFailure catch (e) {
      if (mounted) {
        setState(() {
          serverErrors = {
            for (final k in e.fieldErrors.keys) k: e.fieldError(k) ?? '',
          };
        });
        showErrorSnack(context, e);
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
    final text = Theme.of(context).textTheme;
    final proof = tx.proof;
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Row(
          children: [
            Expanded(child: Text(f.transactionTitle, style: text.titleLarge)),
            StatusChip(
              label: f.txStatus(tx.status, tx.statusLabel),
              tone: txTone(tx.status),
            ),
          ],
        ),
        const SizedBox(height: AppSpacing.xs),
        Text(tx.transactionNumber, style: text.bodySmall),
        const SizedBox(height: AppSpacing.md),
        if (tx.status == 'awaiting_verification')
          AppBanner(message: f.proofSent, tone: StatusTone.info),
        if (tx.status == 'failed' && (tx.failureReason?.isNotEmpty ?? false))
          AppBanner(
            title: f.failureReason,
            message: tx.failureReason!,
            tone: StatusTone.danger,
          ),
        if (proof != null && proof.status == 'rejected') ...[
          const SizedBox(height: AppSpacing.sm),
          AppBanner(
            title: f.rejectedReason,
            message: [
              if (proof.reasonCode != null) f.proofReason(proof.reasonCode!),
              ?proof.note,
            ].join(' - '),
            tone: StatusTone.danger,
          ),
        ],
        const SizedBox(height: AppSpacing.md),
        InfoRow(label: f.method, value: f.txMethod(tx.method, tx.methodLabel)),
        if (tx.channelName != null)
          InfoRow(label: f.channel, value: tx.channelName!),
        const Divider(),
        CopyRow(
          label: f.amountToPay,
          value: context.formatMoney(tx.amount),
          copyValue: _plainAmount(tx.amount),
          strong: true,
          tooltip: f.copyAmount,
        ),
        if (tx.isManual && (tx.uniqueCode?.isNotEmpty ?? false))
          Text(f.uniqueCodeNote, style: text.bodySmall),
        if (tx.expiresAt != null && (tx.isCancellable))
          CopyRow(
            label: f.deadline,
            value: context.formatDateTime(tx.expiresAt),
            tooltip: f.copyDeadline,
          ),
        if (tx.vaNumber != null && tx.isCancellable)
          CopyRow(
            label: f.vaNumber,
            value: tx.vaNumber!,
            tooltip: f.copyNumber,
          ),
        if (tx.isManual && tx.isCancellable && tx.bankAccounts.isNotEmpty) ...[
          const SizedBox(height: AppSpacing.sm),
          Text(f.bankAccounts, style: text.titleSmall),
          for (final a in tx.bankAccounts) _BankCard(account: a),
        ],
        if (tx.isOnline && tx.isCancellable) ...[
          const SizedBox(height: AppSpacing.md),
          if (tx.redirectUrl != null)
            FilledButton.icon(
              onPressed: busy ? null : _openPage,
              icon: const Icon(Icons.open_in_new),
              label: Text(f.openPaymentPage),
            ),
          const SizedBox(height: AppSpacing.sm),
          if (tx.canRefresh)
            OutlinedButton.icon(
              onPressed: busy ? null : _refresh,
              icon: const Icon(Icons.refresh),
              label: Text(f.checkStatus),
            ),
          if (tx.steps.isNotEmpty) ...[
            const SizedBox(height: AppSpacing.md),
            Text(f.howToPay, style: text.titleSmall),
            for (final g in tx.steps) ...[
              if (g.title.isNotEmpty)
                Padding(
                  padding: const EdgeInsets.only(top: AppSpacing.sm),
                  child: Text(g.title, style: text.labelLarge),
                ),
              for (var i = 0; i < g.steps.length; i++)
                Padding(
                  padding: const EdgeInsets.only(top: 2),
                  child: Text('${i + 1}. ${g.steps[i]}'),
                ),
            ],
          ],
        ],
        if (proof != null) ...[
          const SizedBox(height: AppSpacing.md),
          Text(f.proofTitle, style: text.titleSmall),
          InfoRow(
            label: f.proofTitle,
            value: f.proofStatus(proof.status, proof.statusLabel),
          ),
          if (proof.senderBank != null)
            InfoRow(label: f.senderBank, value: proof.senderBank!),
          if (proof.senderName != null)
            InfoRow(label: f.senderName, value: proof.senderName!),
          if (proof.transferredAmount != null)
            InfoRow(
              label: f.transferredAmount,
              value: context.formatMoney(proof.transferredAmount),
            ),
          if (proof.duplicateWarning)
            AppBanner(message: f.duplicateWarning, tone: StatusTone.warning),
        ],
        if (tx.canUploadProof) ...[
          const SizedBox(height: AppSpacing.md),
          if (!showProofForm)
            FilledButton.icon(
              onPressed: busy
                  ? null
                  : () => setState(() => showProofForm = true),
              icon: const Icon(Icons.upload_file),
              label: Text(
                proof?.status == 'rejected' || tx.status == 'failed'
                    ? f.reuploadProof
                    : f.uploadProof,
              ),
            )
          else
            _proofForm(f),
        ],
        if (tx.hasReceipt) ...[
          const SizedBox(height: AppSpacing.md),
          OutlinedButton.icon(
            onPressed: () => downloadAndOpen(
              context,
              ref,
              path: '/student/finance/transactions/${tx.id}/receipt',
              filename: '${tx.receiptNumber}.pdf',
            ),
            icon: const Icon(Icons.receipt_long_outlined),
            label: Text('${f.receipt} ${tx.receiptNumber}'),
          ),
        ],
        if (tx.isCancellable) ...[
          const SizedBox(height: AppSpacing.sm),
          TextButton.icon(
            onPressed: busy ? null : _cancel,
            icon: const Icon(Icons.cancel_outlined),
            label: Text(f.cancelTx),
            style: TextButton.styleFrom(
              foregroundColor: Theme.of(context).colorScheme.error,
            ),
          ),
        ],
      ],
    );
  }

  Widget _proofForm(FinanceStrings f) {
    final s = context.l10n;
    return Form(
      key: formKey,
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(f.uploadProof, style: Theme.of(context).textTheme.titleSmall),
          const SizedBox(height: AppSpacing.sm),
          TextFormField(
            controller: bank,
            decoration: InputDecoration(
              labelText: f.senderBank,
              errorText: serverErrors['sender_bank'],
            ),
            validator: Validators.compose([
              Validators.required(s),
              Validators.maxLength(s, 60),
            ]),
          ),
          const SizedBox(height: AppSpacing.sm),
          TextFormField(
            controller: name,
            decoration: InputDecoration(
              labelText: f.senderName,
              errorText: serverErrors['sender_name'],
            ),
            validator: Validators.compose([
              Validators.required(s),
              Validators.maxLength(s, 150),
            ]),
          ),
          const SizedBox(height: AppSpacing.sm),
          TextFormField(
            controller: amount,
            keyboardType: const TextInputType.numberWithOptions(decimal: true),
            decoration: InputDecoration(
              labelText: f.transferredAmount,
              errorText: serverErrors['transferred_amount'],
            ),
            validator: (v) {
              final req = Validators.required(s)(v);
              if (req != null) return req;
              final n = double.tryParse(v!.trim().replaceAll(',', '.'));
              return (n == null || n <= 0) ? f.invalidAmount : null;
            },
          ),
          const SizedBox(height: AppSpacing.sm),
          OutlinedButton.icon(
            onPressed: _pickDate,
            icon: const Icon(Icons.event),
            label: Text(
              date == null
                  ? '${f.transferDate}: ${f.pickDate}'
                  : '${f.transferDate}: ${context.formatDate(date)}',
            ),
          ),
          if (serverErrors['transfer_date'] != null)
            Text(
              serverErrors['transfer_date']!,
              style: TextStyle(color: Theme.of(context).colorScheme.error),
            ),
          const SizedBox(height: AppSpacing.sm),
          OutlinedButton.icon(
            onPressed: _pickFile,
            icon: const Icon(Icons.attach_file),
            label: Text(
              file?.name ?? f.proofFile,
              overflow: TextOverflow.ellipsis,
            ),
          ),
          if (serverErrors['file_upload_id'] != null)
            Text(
              serverErrors['file_upload_id']!,
              style: TextStyle(color: Theme.of(context).colorScheme.error),
            ),
          const SizedBox(height: AppSpacing.md),
          FilledButton(
            onPressed: busy ? null : _submitProof,
            child: Text(busy ? f.submitting : f.uploadProof),
          ),
        ],
      ),
    );
  }
}

class _BankCard extends StatelessWidget {
  const _BankCard({required this.account});
  final BankAccount account;

  @override
  Widget build(BuildContext context) {
    final f = context.fin;
    final text = Theme.of(context).textTheme;
    final number = account.number;
    return AppSurfaceCard(
      padding: const EdgeInsets.fromLTRB(
        AppSpacing.md,
        AppSpacing.sm,
        AppSpacing.xs,
        AppSpacing.sm,
      ),
      child: Row(
        children: [
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                if (account.bank != null)
                  Text(account.bank!, style: text.titleSmall),
                if (number != null) Text(number, style: text.titleMedium),
                if (account.holder != null)
                  Text(
                    '${f.accountHolder} ${account.holder}',
                    style: text.bodySmall,
                  ),
                if (account.text != null) Text(account.text!),
              ],
            ),
          ),
          if (number != null)
            IconButton(
              tooltip: f.copyNumber,
              icon: const Icon(Icons.copy_rounded, size: 20),
              onPressed: () => copyText(context, number),
            ),
        ],
      ),
    );
  }
}
