import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import '../../../../core/router/route_paths.dart';
import '../../../../core/theme/app_dimensions.dart';
import '../../../../core/utils/formatters.dart';
import '../../../../core/widgets/notification_bell.dart';
import '../../../../core/widgets/state_views.dart';
import '../../../../core/widgets/ui_kit.dart';
import '../../domain/finance_models.dart';
import '../../l10n/finance_strings.dart';
import '../providers.dart';
import '../widgets/finance_widgets.dart';

/// "Keuangan" tab: summary + shortcuts.
class FinanceHomeScreen extends ConsumerWidget {
  const FinanceHomeScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final f = context.fin;
    final summary = ref.watch(financeSummaryProvider);
    return Scaffold(
      appBar: AppBar(
        title: Text(f.title),
        actions: const [NotificationBellButton()],
      ),
      body: RefreshIndicator(
        onRefresh: () async {
          ref.invalidate(financeSummaryProvider);
          try {
            await ref.read(financeSummaryProvider.future);
          } catch (_) {
            // the error view shows the failure
          }
        },
        child: AsyncValueView<FinanceSummary>(
          value: summary,
          onRetry: () => ref.invalidate(financeSummaryProvider),
          data: (s) => _Content(summary: s),
        ),
      ),
    );
  }
}

class _Content extends StatelessWidget {
  const _Content({required this.summary});
  final FinanceSummary summary;

  @override
  Widget build(BuildContext context) {
    final f = context.fin;
    final s = summary;
    final next = s.nextDue;
    final blocks = s.activeBlocks;
    return FinanceBody(
      child: ListView(
        physics: const AlwaysScrollableScrollPhysics(),
        padding: const EdgeInsets.all(AppSpacing.lg),
        children: [
          if (blocks.isNotEmpty) ...[
            AppBanner(
              tone: StatusTone.danger,
              title: f.activeBlocks,
              message: f.blockedBanner,
              action: TextButton(
                onPressed: () => context.push(RoutePaths.studentClearance),
                child: Text(f.seeDetails),
              ),
            ),
            const SizedBox(height: AppSpacing.md),
          ],
          GridView(
            shrinkWrap: true,
            physics: const NeverScrollableScrollPhysics(),
            gridDelegate: const SliverGridDelegateWithFixedCrossAxisCount(
              crossAxisCount: 2,
              mainAxisSpacing: AppSpacing.md,
              crossAxisSpacing: AppSpacing.md,
              mainAxisExtent: 156,
            ),
            children: [
              StatTile(
                icon: Icons.receipt_long_outlined,
                label: '${f.outstanding} · ${f.openInvoices(s.openInvoices)}',
                value: context.formatMoney(s.outstanding),
                onTap: () => context.push(RoutePaths.studentInvoices),
              ),
              StatTile(
                icon: Icons.warning_amber_rounded,
                tone: s.hasArrears ? StatusTone.danger : StatusTone.success,
                label: f.arrears,
                value: context.formatMoney(s.arrears),
                onTap: () => context.push(RoutePaths.studentInvoices),
              ),
              StatTile(
                icon: Icons.event_outlined,
                tone: StatusTone.warning,
                label: next == null
                    ? f.nextDue
                    : [
                        f.nextDue,
                        if (next.seq != null) f.nextDueInstallment(next.seq!),
                        if (next.date != null) context.formatDate(next.date),
                      ].join(' · '),
                value: next == null ? '—' : context.formatMoney(next.amount),
                onTap: next?.invoiceId == null
                    ? null
                    : () => context.push(
                        RoutePaths.studentInvoice(next!.invoiceId!),
                      ),
              ),
              StatTile(
                icon: Icons.account_balance_wallet_outlined,
                tone: StatusTone.accent,
                label: f.depositBalance,
                value: context.formatMoney(s.depositAvailable),
                onTap: () => context.push(RoutePaths.studentDeposit),
              ),
            ],
          ),
          if (s.openInvoices == 0 && !s.hasArrears) ...[
            const SizedBox(height: AppSpacing.md),
            AppBanner(message: f.allClear, tone: StatusTone.success),
          ],
          if (s.pendingVerification > 0) ...[
            const SizedBox(height: AppSpacing.md),
            AppBanner(
              message: f.pendingVerification(s.pendingVerification),
              tone: StatusTone.info,
            ),
          ],
          SectionHeader(
            title: f.shortcuts,
            padding: const EdgeInsets.only(
              top: AppSpacing.xl,
              bottom: AppSpacing.sm,
            ),
          ),
          Card(
            child: Column(
              children: [
                MenuTile(
                  icon: Icons.receipt_long_outlined,
                  title: f.invoices,
                  subtitle: f.invoicesSub,
                  onTap: () => context.push(RoutePaths.studentInvoices),
                ),
                MenuTile(
                  icon: Icons.history,
                  title: f.transactions,
                  subtitle: f.transactionsSub,
                  onTap: () => context.push(RoutePaths.studentTransactions),
                ),
                MenuTile(
                  icon: Icons.account_balance_wallet_outlined,
                  title: f.deposit,
                  subtitle: f.depositSub,
                  onTap: () => context.push(RoutePaths.studentDeposit),
                ),
                MenuTile(
                  icon: Icons.list_alt_outlined,
                  title: f.statement,
                  subtitle: f.statementSub,
                  onTap: () => context.push(RoutePaths.studentStatement),
                ),
                MenuTile(
                  icon: Icons.school_outlined,
                  title: f.ukt,
                  subtitle: f.uktSub,
                  onTap: () => context.push(RoutePaths.studentUkt),
                ),
                MenuTile(
                  icon: Icons.workspace_premium_outlined,
                  title: f.awards,
                  subtitle: f.awardsSub,
                  onTap: () => context.push(RoutePaths.studentAwards),
                ),
                MenuTile(
                  icon: Icons.gpp_maybe_outlined,
                  title: f.clearance,
                  subtitle: f.clearanceSub,
                  badge: blocks.isEmpty ? null : '${blocks.length}',
                  onTap: () => context.push(RoutePaths.studentClearance),
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }
}
