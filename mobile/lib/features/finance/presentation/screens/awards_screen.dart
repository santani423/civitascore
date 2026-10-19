import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../../core/pending/pending_feature_screen.dart';
import '../../../../core/pending/pending_features.dart';
import '../../../../core/theme/app_dimensions.dart';
import '../../../../core/utils/formatters.dart';
import '../../../../core/widgets/state_views.dart';
import '../../../../core/widgets/ui_kit.dart';
import '../../domain/finance_models.dart';
import '../../l10n/finance_strings.dart';
import '../providers.dart';
import '../widgets/finance_widgets.dart';

class AwardsScreen extends ConsumerWidget {
  const AwardsScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final f = context.fin;
    final value = ref.watch(awardsProvider);
    final text = Theme.of(context).textTheme;
    final pending = pendingFeatureByKey('scholarships');
    return Scaffold(
      appBar: AppBar(title: Text(f.awards)),
      body: RefreshIndicator(
        onRefresh: () async {
          ref.invalidate(awardsProvider);
          try {
            await ref.read(awardsProvider.future);
          } catch (_) {
            // shown by the error view
          }
        },
        child: AsyncValueView<List<Award>>(
          value: value,
          onRetry: () => ref.invalidate(awardsProvider),
          data: (list) => FinanceBody(
            child: ListView(
              physics: const AlwaysScrollableScrollPhysics(),
              padding: const EdgeInsets.all(AppSpacing.lg),
              children: [
                if (list.isEmpty)
                  SizedBox(
                    height: 260,
                    child: EmptyView(
                      icon: Icons.workspace_premium_outlined,
                      title: f.noAwards,
                      message: f.noAwardsBody,
                    ),
                  ),
                for (final a in list)
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
                                  a.policyName ?? '—',
                                  style: text.titleSmall,
                                ),
                              ),
                              StatusChip(
                                label: f.awardStatus(a.status, a.statusLabel),
                                tone: genericTone(a.status),
                                dense: true,
                              ),
                            ],
                          ),
                          if (a.kindLabel != null)
                            Text(a.kindLabel!, style: text.bodySmall),
                          const Divider(),
                          if (a.value != null)
                            InfoRow(
                              label: f.awardValue,
                              value: a.calcTypeLabel == null
                                  ? a.value!
                                  : '${a.value} (${a.calcTypeLabel})',
                            ),
                          if (a.maxAmount != null)
                            InfoRow(
                              label: f.awardMax,
                              value: context.formatMoney(a.maxAmount),
                            ),
                          if (a.startTerm != null || a.endTerm != null)
                            InfoRow(
                              label: f.awardTerms,
                              value: [?a.startTerm, ?a.endTerm].join(' - '),
                            ),
                          if (a.revokeReason != null)
                            InfoRow(
                              label: f.revokeReason,
                              value: a.revokeReason!,
                            ),
                          if (a.decisionNote != null)
                            InfoRow(
                              label: f.decisionNote,
                              value: a.decisionNote!,
                            ),
                        ],
                      ),
                    ),
                  ),
                if (pending != null) PendingFeatureTile(feature: pending),
              ],
            ),
          ),
        ),
      ),
    );
  }
}
