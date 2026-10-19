import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../../core/l10n/core_strings.dart';
import '../../../../core/theme/app_dimensions.dart';
import '../../../../core/utils/formatters.dart';
import '../../../../core/widgets/app_feedback.dart';
import '../../../../core/widgets/ui_kit.dart';
import '../../domain/finance_models.dart';
import '../../l10n/finance_strings.dart';
import '../providers.dart';

StatusTone txTone(String status) => switch (status) {
  'success' => StatusTone.success,
  'pending' || 'created' => StatusTone.warning,
  'awaiting_verification' => StatusTone.info,
  'failed' || 'expired' => StatusTone.danger,
  _ => StatusTone.neutral,
};

StatusTone invoiceTone(String status, {bool overdue = false}) {
  if (overdue) return StatusTone.danger;
  return switch (status) {
    'paid' => StatusTone.success,
    'partial' => StatusTone.info,
    'unpaid' => StatusTone.warning,
    _ => StatusTone.neutral,
  };
}

StatusTone genericTone(String status) => switch (status) {
  'approved' ||
  'active' ||
  'completed' ||
  'paid' ||
  'success' => StatusTone.success,
  'rejected' || 'failed' || 'revoked' || 'expired' => StatusTone.danger,
  'pending' || 'pending_approval' || 'submitted' => StatusTone.warning,
  _ => StatusTone.neutral,
};

/// Constrains content on wide screens.
class FinanceBody extends StatelessWidget {
  const FinanceBody({super.key, required this.child});
  final Widget child;

  @override
  Widget build(BuildContext context) => Center(
    child: ConstrainedBox(
      constraints: const BoxConstraints(maxWidth: 720),
      child: child,
    ),
  );
}

/// Refreshes every finance read model after a mutation.
void invalidateFinance(WidgetRef ref, {String? invoiceId}) {
  ref.invalidate(financeSummaryProvider);
  ref.invalidate(invoicesProvider);
  ref.invalidate(transactionsProvider);
  ref.invalidate(depositProvider);
  ref.invalidate(statementProvider);
  if (invoiceId != null) ref.invalidate(invoiceDetailProvider(invoiceId));
}

Future<void> copyText(BuildContext context, String text) async {
  await Clipboard.setData(ClipboardData(text: text));
  if (context.mounted) showAppSnack(context, context.fin.copiedToClipboard);
}

/// Modal bottom sheet that grows with the keyboard and scrolls.
Future<T?> showFinanceSheet<T>(
  BuildContext context, {
  required WidgetBuilder builder,
}) => showModalBottomSheet<T>(
  context: context,
  isScrollControlled: true,
  useSafeArea: true,
  showDragHandle: true,
  builder: (ctx) => Padding(
    padding: EdgeInsets.only(bottom: MediaQuery.viewInsetsOf(ctx).bottom),
    child: FinanceBody(
      child: SingleChildScrollView(
        padding: const EdgeInsets.fromLTRB(
          AppSpacing.lg,
          0,
          AppSpacing.lg,
          AppSpacing.xl,
        ),
        child: builder(ctx),
      ),
    ),
  ),
);

/// Row with a label, value and a copy button.
class CopyRow extends StatelessWidget {
  const CopyRow({
    super.key,
    required this.label,
    required this.value,
    this.strong = false,
    this.copyValue,
    this.tooltip,
  });
  final String label;
  final String value;
  final bool strong;
  final String? copyValue;
  final String? tooltip;

  @override
  Widget build(BuildContext context) {
    final text = Theme.of(context).textTheme;
    return Row(
      children: [
        Expanded(
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text(
                label,
                style: text.bodySmall?.copyWith(
                  color: context.palette.mutedText,
                ),
              ),
              Text(value, style: strong ? text.titleLarge : text.bodyLarge),
            ],
          ),
        ),
        IconButton(
          tooltip: tooltip ?? context.l10n.copy,
          icon: const Icon(Icons.copy_rounded, size: 20),
          onPressed: () => copyText(context, copyValue ?? value),
        ),
      ],
    );
  }
}

class InvoiceCard extends StatelessWidget {
  const InvoiceCard({super.key, required this.invoice, required this.onTap});
  final Invoice invoice;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    final f = context.fin;
    final text = Theme.of(context).textTheme;
    return AppSurfaceCard(
      onTap: onTap,
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              Expanded(
                child: Text(
                  invoice.billGroupLabel ?? invoice.invoiceNumber,
                  style: text.titleSmall,
                  maxLines: 1,
                  overflow: TextOverflow.ellipsis,
                ),
              ),
              const SizedBox(width: AppSpacing.sm),
              StatusChip(
                label: invoice.isOverdue && invoice.isOpen
                    ? f.overdue
                    : f.invoiceStatus(invoice.status, invoice.statusLabel),
                tone: invoiceTone(
                  invoice.status,
                  overdue: invoice.isOverdue && invoice.isOpen,
                ),
                dense: true,
              ),
            ],
          ),
          const SizedBox(height: 2),
          Text(
            [invoice.invoiceNumber, ?invoice.period].join(' · '),
            style: text.bodySmall,
            maxLines: 1,
            overflow: TextOverflow.ellipsis,
          ),
          const SizedBox(height: AppSpacing.sm),
          Row(
            children: [
              Expanded(
                child: Text(
                  context.formatMoney(
                    invoice.isOpen ? invoice.outstanding : invoice.amount,
                  ),
                  style: text.titleMedium,
                ),
              ),
              if (invoice.dueDate != null)
                Flexible(
                  child: Text(
                    f.dueOn(context.formatDate(invoice.dueDate)),
                    style: text.bodySmall,
                    overflow: TextOverflow.ellipsis,
                  ),
                ),
            ],
          ),
        ],
      ),
    );
  }
}

class TransactionTile extends StatelessWidget {
  const TransactionTile({super.key, required this.tx, required this.onTap});
  final FinanceTransaction tx;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    final f = context.fin;
    final text = Theme.of(context).textTheme;
    return AppSurfaceCard(
      onTap: onTap,
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              Expanded(
                child: Text(
                  tx.transactionNumber,
                  style: text.titleSmall,
                  maxLines: 1,
                  overflow: TextOverflow.ellipsis,
                ),
              ),
              const SizedBox(width: AppSpacing.sm),
              StatusChip(
                label: f.txStatus(tx.status, tx.statusLabel),
                tone: txTone(tx.status),
                dense: true,
              ),
            ],
          ),
          const SizedBox(height: 2),
          Text(
            [
              f.txMethod(tx.method, tx.methodLabel),
              ?tx.channelName,
            ].join(' · '),
            style: text.bodySmall,
            maxLines: 1,
            overflow: TextOverflow.ellipsis,
          ),
          const SizedBox(height: AppSpacing.sm),
          Row(
            children: [
              Expanded(
                child: Text(
                  context.formatMoney(tx.amount),
                  style: text.titleMedium,
                ),
              ),
              Text(
                context.formatDate(tx.paidAt ?? tx.createdAt),
                style: text.bodySmall,
              ),
            ],
          ),
        ],
      ),
    );
  }
}
