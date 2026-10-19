import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../../core/theme/app_dimensions.dart';
import '../../../../core/utils/formatters.dart';
import '../../../../core/widgets/state_views.dart';
import '../../../../core/widgets/ui_kit.dart';
import '../../domain/krs_models.dart';
import '../../l10n/krs_strings.dart';
import '../providers.dart';
import '../widgets/krs_widgets.dart';

class KrsHistoryScreen extends ConsumerWidget {
  const KrsHistoryScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final k = context.krs;
    final value = ref.watch(krsHistoryProvider);
    return Scaffold(
      appBar: AppBar(title: Text(k.historyTitle)),
      body: AsyncValueView<List<KrsHistoryEntry>>(
        value: value,
        onRetry: () => ref.invalidate(krsHistoryProvider),
        isEmpty: (d) => d.isEmpty,
        empty: EmptyView(
          icon: Icons.history,
          title: k.historyEmptyTitle,
          message: k.historyEmptyBody,
        ),
        data: (list) => RefreshIndicator(
          onRefresh: () => ref.refresh(krsHistoryProvider.future),
          child: KrsConstrained(
            child: ListView.builder(
              physics: const AlwaysScrollableScrollPhysics(),
              padding: const EdgeInsets.all(AppSpacing.lg),
              itemCount: list.length,
              itemBuilder: (_, i) => _entry(context, list[i]),
            ),
          ),
        ),
      ),
    );
  }

  Widget _entry(BuildContext context, KrsHistoryEntry e) {
    final k = context.krs;
    final theme = Theme.of(context);
    return Padding(
      padding: const EdgeInsets.only(bottom: AppSpacing.md),
      child: AppSurfaceCard(
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              children: [
                Expanded(
                  child: Text(
                    e.term?.label ?? '—',
                    style: theme.textTheme.titleSmall,
                  ),
                ),
                StatusChip(
                  label: k.statusLabel(e.status, e.statusLabel),
                  tone: krsStatusTone(e.status),
                  dense: true,
                ),
              ],
            ),
            Text(
              '${k.courseCount(e.courseCount)} · ${e.totalCredits} ${k.credits}',
              style: theme.textTheme.bodySmall,
            ),
            if (e.submission?.decisionNote != null)
              MetaLine(
                icon: Icons.comment_outlined,
                text: '${k.advisorNote}: ${e.submission!.decisionNote}',
              ),
            if (e.submission?.decidedAt != null)
              MetaLine(
                icon: Icons.event_available_outlined,
                text:
                    '${k.decidedAt}: ${context.formatDate(e.submission!.decidedAt)}',
              ),
            const Divider(height: AppSpacing.xl),
            for (final it in e.items)
              Padding(
                padding: const EdgeInsets.only(bottom: AppSpacing.sm),
                child: Row(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Expanded(
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Text(
                            it.courseName,
                            maxLines: 2,
                            overflow: TextOverflow.ellipsis,
                          ),
                          Text(
                            '${it.courseCode}${it.classCode == null ? '' : ' · ${it.classCode}'} · ${it.credits} ${k.credits}',
                            style: theme.textTheme.bodySmall,
                          ),
                        ],
                      ),
                    ),
                    if (it.letterGrade != null)
                      StatusChip(
                        label: '${k.grade} ${it.letterGrade}',
                        tone: StatusTone.primary,
                        dense: true,
                      ),
                  ],
                ),
              ),
          ],
        ),
      ),
    );
  }
}
