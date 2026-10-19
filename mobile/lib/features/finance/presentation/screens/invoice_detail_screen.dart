import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import '../../../../core/router/route_paths.dart';
import '../../../../core/theme/app_dimensions.dart';
import '../../../../core/utils/formatters.dart';
import '../../../../core/widgets/state_views.dart';
import '../../../../core/widgets/ui_kit.dart';
import '../../domain/finance_models.dart';
import '../../l10n/finance_strings.dart';
import '../providers.dart';
import '../widgets/finance_widgets.dart';
import '../widgets/invoice_actions.dart';
import '../widgets/transaction_sheet.dart';

class InvoiceDetailScreen extends ConsumerWidget {
  const InvoiceDetailScreen({super.key, required this.invoiceId});
  final String invoiceId;

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final f = context.fin;
    final value = ref.watch(invoiceDetailProvider(invoiceId));
    return Scaffold(
      appBar: AppBar(title: Text(f.invoiceDetail)),
      body: RefreshIndicator(
        onRefresh: () async {
          ref.invalidate(invoiceDetailProvider(invoiceId));
          try {
            await ref.read(invoiceDetailProvider(invoiceId).future);
          } catch (_) {
            // surfaced by the error view
          }
        },
        child: AsyncValueView<Invoice>(
          value: value,
          onRetry: () => ref.invalidate(invoiceDetailProvider(invoiceId)),
          data: (inv) => _Body(invoice: inv),
        ),
      ),
    );
  }
}

class _Body extends StatelessWidget {
  const _Body({required this.invoice});
  final Invoice invoice;

  @override
  Widget build(BuildContext context) {
    final f = context.fin;
    final text = Theme.of(context).textTheme;
    final inv = invoice;
    final overdue = inv.isOverdue && inv.isOpen;
    final next = inv.nextInstallment;
    return FinanceBody(
      child: ListView(
        physics: const AlwaysScrollableScrollPhysics(),
        padding: const EdgeInsets.all(AppSpacing.lg),
        children: [
          AppSurfaceCard(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Row(
                  children: [
                    Expanded(
                      child: Text(
                        inv.billGroupLabel ?? inv.invoiceNumber,
                        style: text.titleMedium,
                      ),
                    ),
                    StatusChip(
                      label: overdue
                          ? f.overdue
                          : f.invoiceStatus(inv.status, inv.statusLabel),
                      tone: invoiceTone(inv.status, overdue: overdue),
                    ),
                  ],
                ),
                Text(inv.invoiceNumber, style: text.bodySmall),
                const Divider(),
                if (inv.period != null)
                  InfoRow(label: f.period, value: inv.period!),
                if (inv.dueDate != null)
                  InfoRow(
                    label: f.dueDateLabel,
                    value: context.formatDateLong(inv.dueDate),
                  ),
                InfoRow(
                  label: f.grossAmount,
                  value: context.formatMoney(inv.grossAmount),
                ),
                if (moneyValue(inv.discountAmount) > 0)
                  InfoRow(
                    label: f.discount,
                    value: '- ${context.formatMoney(inv.discountAmount)}',
                  ),
                InfoRow(
                  label: f.totalAmount,
                  value: context.formatMoney(inv.amount),
                  valueStyle: text.titleSmall,
                ),
                InfoRow(
                  label: f.paidAmount,
                  value: context.formatMoney(inv.paidAmount),
                ),
                InfoRow(
                  label: f.remaining,
                  value: context.formatMoney(inv.outstanding),
                  valueStyle: text.titleMedium?.copyWith(
                    color: moneyValue(inv.outstanding) > 0
                        ? Theme.of(context).colorScheme.error
                        : null,
                  ),
                ),
                if (inv.cancelReason != null)
                  InfoRow(label: f.cancelledReason, value: inv.cancelReason!),
              ],
            ),
          ),
          if (inv.canPay) ...[
            const SizedBox(height: AppSpacing.md),
            FilledButton.icon(
              onPressed: () =>
                  context.push(RoutePaths.studentInvoicePay(inv.id)),
              icon: const Icon(Icons.payments_outlined),
              label: Text(f.payNow),
            ),
            if (next != null) ...[
              const SizedBox(height: AppSpacing.sm),
              OutlinedButton.icon(
                onPressed: () => context.push(
                  '${RoutePaths.studentInvoicePay(inv.id)}?installment=${next.id}',
                ),
                icon: const Icon(Icons.event_available_outlined),
                label: Text(
                  '${f.payInstallment} (${context.formatMoney(next.remaining)})',
                  overflow: TextOverflow.ellipsis,
                ),
              ),
            ],
            const SizedBox(height: AppSpacing.sm),
            Wrap(
              spacing: AppSpacing.sm,
              runSpacing: AppSpacing.xs,
              children: [
                ActionChip(
                  avatar: const Icon(
                    Icons.account_balance_wallet_outlined,
                    size: 18,
                  ),
                  label: Text(f.useDeposit),
                  onPressed: () => showDepositSheet(context, inv),
                ),
                ActionChip(
                  avatar: const Icon(
                    Icons.confirmation_number_outlined,
                    size: 18,
                  ),
                  label: Text(f.useVoucher),
                  onPressed: () => showVoucherSheet(context, inv),
                ),
                ActionChip(
                  avatar: const Icon(Icons.calendar_month_outlined, size: 18),
                  label: Text(f.requestInstallment),
                  onPressed: () => showInstallmentSheet(context, inv),
                ),
              ],
            ),
          ],
          if (inv.pendingTransactions.isNotEmpty) ...[
            SectionHeader(
              title: f.pendingTxTitle,
              padding: const EdgeInsets.only(
                top: AppSpacing.xl,
                bottom: AppSpacing.sm,
              ),
            ),
            for (final t in inv.pendingTransactions)
              Padding(
                padding: const EdgeInsets.only(bottom: AppSpacing.sm),
                child: TransactionTile(
                  tx: t,
                  onTap: () =>
                      showTransactionSheet(context, t, invoiceId: inv.id),
                ),
              ),
          ],
          if (inv.items.isNotEmpty) ...[
            SectionHeader(
              title: f.components,
              padding: const EdgeInsets.only(
                top: AppSpacing.xl,
                bottom: AppSpacing.sm,
              ),
            ),
            AppSurfaceCard(
              child: Column(
                children: [
                  for (final i in inv.items)
                    Padding(
                      padding: const EdgeInsets.symmetric(vertical: 4),
                      child: Row(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Expanded(
                            child: Column(
                              crossAxisAlignment: CrossAxisAlignment.start,
                              children: [
                                Text(i.description),
                                if (i.lineTypeLabel != null)
                                  Text(i.lineTypeLabel!, style: text.bodySmall),
                              ],
                            ),
                          ),
                          const SizedBox(width: AppSpacing.md),
                          Text(
                            '${i.isDeduction ? '- ' : ''}${context.formatMoney(i.amount)}',
                            style: text.bodyMedium?.copyWith(
                              color: i.isDeduction
                                  ? context.toneColors(StatusTone.success).$1
                                  : null,
                            ),
                          ),
                        ],
                      ),
                    ),
                ],
              ),
            ),
          ],
          if (inv.activeInstallments.isNotEmpty) ...[
            SectionHeader(
              title: f.installmentsTitle,
              padding: const EdgeInsets.only(
                top: AppSpacing.xl,
                bottom: AppSpacing.sm,
              ),
            ),
            for (final i in inv.activeInstallments)
              Padding(
                padding: const EdgeInsets.only(bottom: AppSpacing.sm),
                child: AppSurfaceCard(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Row(
                        children: [
                          Expanded(
                            child: Text(
                              f.installmentLabel(i.seq),
                              style: text.titleSmall,
                            ),
                          ),
                          StatusChip(
                            label: f.installmentStatus(
                              i.status,
                              i.isOverdue,
                              i.statusLabel,
                            ),
                            tone: i.isOverdue
                                ? StatusTone.danger
                                : genericTone(
                                    i.status == 'scheduled'
                                        ? 'pending'
                                        : i.status,
                                  ),
                            dense: true,
                          ),
                        ],
                      ),
                      const SizedBox(height: 4),
                      Text(
                        '${context.formatMoney(i.amount)} · ${f.dueOn(context.formatDate(i.dueDate))}',
                      ),
                      if (moneyValue(i.paidAmount) > 0)
                        Text(
                          '${f.paidAmount}: ${context.formatMoney(i.paidAmount)}',
                          style: text.bodySmall,
                        ),
                    ],
                  ),
                ),
              ),
          ],
          if (inv.allocations.isNotEmpty) ...[
            SectionHeader(
              title: f.allocationsTitle,
              padding: const EdgeInsets.only(
                top: AppSpacing.xl,
                bottom: AppSpacing.sm,
              ),
            ),
            AppSurfaceCard(
              child: Column(
                children: [
                  for (final a in inv.allocations)
                    ListTile(
                      dense: true,
                      contentPadding: EdgeInsets.zero,
                      title: Text(context.formatMoney(a.amount)),
                      subtitle: Text(
                        [
                          context.formatDate(a.paidAt),
                          ?a.reference,
                        ].join(' · '),
                      ),
                      trailing: a.reversalReason != null
                          ? Text(a.reversalReason!)
                          : null,
                    ),
                ],
              ),
            ),
          ],
          if (inv.penaltyInvoices.isNotEmpty) ...[
            SectionHeader(
              title: f.penaltiesTitle,
              padding: const EdgeInsets.only(
                top: AppSpacing.xl,
                bottom: AppSpacing.sm,
              ),
            ),
            for (final p in inv.penaltyInvoices)
              Padding(
                padding: const EdgeInsets.only(bottom: AppSpacing.sm),
                child: InvoiceCard(
                  invoice: p,
                  onTap: () => context.push(RoutePaths.studentInvoice(p.id)),
                ),
              ),
          ],
        ],
      ),
    );
  }
}
