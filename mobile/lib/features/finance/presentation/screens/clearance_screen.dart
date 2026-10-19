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
import '../widgets/finance_forms.dart';
import '../widgets/finance_widgets.dart';

/// Clearance + cekal: what is blocked, why, and dispensation requests.
class ClearanceScreen extends ConsumerWidget {
  const ClearanceScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final f = context.fin;
    final value = ref.watch(financeSummaryProvider);
    return Scaffold(
      appBar: AppBar(title: Text(f.clearance)),
      body: RefreshIndicator(
        onRefresh: () async {
          ref.invalidate(financeSummaryProvider);
          try {
            await ref.read(financeSummaryProvider.future);
          } catch (_) {
            // shown by the error view
          }
        },
        child: AsyncValueView<FinanceSummary>(
          value: value,
          onRetry: () => ref.invalidate(financeSummaryProvider),
          data: (s) => _Body(summary: s),
        ),
      ),
    );
  }
}

class _Body extends StatelessWidget {
  const _Body({required this.summary});
  final FinanceSummary summary;

  @override
  Widget build(BuildContext context) {
    final f = context.fin;
    final text = Theme.of(context).textTheme;
    final c = summary.clearance;
    final blocks = summary.activeBlocks;
    return FinanceBody(
      child: ListView(
        physics: const AlwaysScrollableScrollPhysics(),
        padding: const EdgeInsets.all(AppSpacing.lg),
        children: [
          if (c != null)
            AppSurfaceCard(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Row(
                    children: [
                      Expanded(
                        child: Text(
                          f.clearanceStatusTitle,
                          style: text.titleSmall,
                        ),
                      ),
                      StatusChip(
                        label: c.clear ? f.clearYes : f.clearNo,
                        tone: c.clear ? StatusTone.success : StatusTone.warning,
                      ),
                    ],
                  ),
                  const Divider(),
                  InfoRow(
                    label: f.outstanding,
                    value: context.formatMoney(c.outstanding),
                  ),
                  InfoRow(
                    label: f.accruingPenalty,
                    value: context.formatMoney(c.accruingPenalty),
                  ),
                  if (c.pendingVerification > 0)
                    InfoRow(
                      label: f.pendingVerification(c.pendingVerification),
                      value: '',
                    ),
                ],
              ),
            ),
          if (summary.services.isNotEmpty) ...[
            SectionHeader(
              title: f.servicesTitle,
              padding: const EdgeInsets.only(
                top: AppSpacing.xl,
                bottom: AppSpacing.sm,
              ),
            ),
            Wrap(
              spacing: AppSpacing.sm,
              runSpacing: AppSpacing.sm,
              children: [
                for (final sv in summary.services)
                  StatusChip(
                    icon: sv.blocked
                        ? Icons.lock_outline
                        : Icons.lock_open_outlined,
                    label:
                        '${f.serviceName(sv.service, sv.label)}: ${sv.blocked ? f.serviceBlocked : f.serviceOpen}',
                    tone: sv.blocked ? StatusTone.danger : StatusTone.success,
                  ),
              ],
            ),
          ],
          SectionHeader(
            title: f.activeBlocks,
            padding: const EdgeInsets.only(
              top: AppSpacing.xl,
              bottom: AppSpacing.sm,
            ),
          ),
          if (blocks.isEmpty)
            AppBanner(message: f.noBlocks, tone: StatusTone.success)
          else ...[
            AppBanner(message: f.resolveHint, tone: StatusTone.warning),
            const SizedBox(height: AppSpacing.sm),
            for (final b in blocks) _BlockCard(block: b),
            OutlinedButton.icon(
              onPressed: () => context.push(RoutePaths.studentInvoices),
              icon: const Icon(Icons.receipt_long_outlined),
              label: Text(f.goToInvoices),
            ),
          ],
        ],
      ),
    );
  }
}

class _BlockCard extends StatelessWidget {
  const _BlockCard({required this.block});
  final AcademicBlock block;

  @override
  Widget build(BuildContext context) {
    final f = context.fin;
    final text = Theme.of(context).textTheme;
    final exempted = block.status == 'exempted';
    return Padding(
      padding: const EdgeInsets.only(bottom: AppSpacing.md),
      child: AppSurfaceCard(
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              children: [
                Expanded(child: Text(f.activeBlocks, style: text.titleSmall)),
                StatusChip(
                  label: exempted ? f.blockExempted : f.blockActive,
                  tone: exempted ? StatusTone.info : StatusTone.danger,
                  dense: true,
                ),
              ],
            ),
            if (block.reason != null) ...[
              const SizedBox(height: 4),
              Text(block.reason!),
            ],
            const Divider(),
            InfoRow(
              label: f.restrictedServices,
              value: block.services.map((s) => f.serviceName(s, s)).join(', '),
            ),
            InfoRow(
              label: f.shortfall,
              value: context.formatMoney(block.shortfall),
            ),
            if (block.effectiveFrom != null)
              InfoRow(
                label: f.since,
                value: context.formatDate(block.effectiveFrom),
              ),
            if (block.exemptions.isNotEmpty) ...[
              const SizedBox(height: AppSpacing.sm),
              Text(f.dispensations, style: text.labelLarge),
              for (final e in block.exemptions)
                ListTile(
                  dense: true,
                  contentPadding: EdgeInsets.zero,
                  title: Text(f.serviceName(e.service, e.service)),
                  subtitle: Text(
                    [
                      if (e.validUntil != null)
                        context.formatDate(e.validUntil),
                      ?e.decisionNote,
                    ].join(' · '),
                  ),
                  trailing: StatusChip(
                    label: f.dispStatus(e.status, e.statusLabel),
                    tone: genericTone(
                      e.status == 'pending' ? 'pending' : e.status,
                    ),
                    dense: true,
                  ),
                ),
            ],
            const SizedBox(height: AppSpacing.sm),
            OutlinedButton.icon(
              onPressed: () => showDispensationSheet(context, block),
              icon: const Icon(Icons.assignment_late_outlined),
              label: Text(f.requestDispensation),
            ),
          ],
        ),
      ),
    );
  }
}
