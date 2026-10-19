import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../../core/theme/app_dimensions.dart';
import '../../../../core/utils/formatters.dart';
import '../../../../core/widgets/state_views.dart';
import '../../../../core/widgets/ui_kit.dart';
import '../../domain/finance_models.dart';
import '../../l10n/finance_strings.dart';
import '../providers.dart';
import '../widgets/finance_forms.dart';
import '../widgets/finance_widgets.dart';

class UktScreen extends ConsumerWidget {
  const UktScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final f = context.fin;
    final value = ref.watch(uktProvider);
    final text = Theme.of(context).textTheme;
    return Scaffold(
      appBar: AppBar(title: Text(f.ukt)),
      body: RefreshIndicator(
        onRefresh: () async {
          ref.invalidate(uktProvider);
          try {
            await ref.read(uktProvider.future);
          } catch (_) {
            // shown by the error view
          }
        },
        child: AsyncValueView<UktInfo>(
          value: value,
          onRetry: () => ref.invalidate(uktProvider),
          data: (u) => FinanceBody(
            child: ListView(
              physics: const AlwaysScrollableScrollPhysics(),
              padding: const EdgeInsets.all(AppSpacing.lg),
              children: [
                AppSurfaceCard(
                  child: u.current == null
                      ? Text(f.noUktCurrent)
                      : Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Text(f.uktCurrent, style: text.bodySmall),
                            Text(u.current!.group.name, style: text.titleLarge),
                            if (u.current!.decreeNumber != null)
                              InfoRow(
                                label: f.decreeNumber,
                                value: u.current!.decreeNumber!,
                              ),
                            if (u.current!.effectiveFrom != null)
                              InfoRow(
                                label: f.effectiveFrom,
                                value: context.formatDate(
                                  u.current!.effectiveFrom,
                                ),
                              ),
                          ],
                        ),
                ),
                const SizedBox(height: AppSpacing.md),
                if (u.hasPendingRequest)
                  AppBanner(message: f.uktPendingNote, tone: StatusTone.info)
                else
                  FilledButton.icon(
                    onPressed: () => showUktRequestSheet(context, u),
                    icon: const Icon(Icons.rate_review_outlined),
                    label: Text(f.requestUkt),
                  ),
                SectionHeader(
                  title: f.uktRequests,
                  padding: const EdgeInsets.only(
                    top: AppSpacing.xl,
                    bottom: AppSpacing.sm,
                  ),
                ),
                if (u.requests.isEmpty)
                  Padding(
                    padding: const EdgeInsets.all(AppSpacing.lg),
                    child: Text(f.noUktRequests),
                  ),
                for (final r in u.requests)
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
                                  context.formatDate(r.createdAt),
                                  style: text.titleSmall,
                                ),
                              ),
                              StatusChip(
                                label: f.uktStatus(r.status, r.statusLabel),
                                tone: genericTone(r.status),
                                dense: true,
                              ),
                            ],
                          ),
                          if (r.requestedGroup != null)
                            Text(
                              '${f.requestedGroup}: ${r.requestedGroup!.name}',
                            ),
                          if (r.decidedGroup != null)
                            Text('${f.decidedGroup}: ${r.decidedGroup!.name}'),
                          if (r.reason != null)
                            Text(r.reason!, style: text.bodySmall),
                          if (r.decisionNote != null)
                            Text('${f.decisionNote}: ${r.decisionNote}'),
                        ],
                      ),
                    ),
                  ),
              ],
            ),
          ),
        ),
      ),
    );
  }
}
