import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../../core/theme/app_dimensions.dart';
import '../../../../core/utils/formatters.dart';
import '../../../../core/widgets/state_views.dart';
import '../../../../core/widgets/ui_kit.dart';
import '../../domain/academic_models.dart';
import '../../l10n/academic_records_strings.dart';
import '../providers.dart';
import '../widgets/academic_summary_card.dart';
import '../widgets/common.dart';

class AcademicHistoryScreen extends ConsumerWidget {
  const AcademicHistoryScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final a = context.acadL10n;
    final history = ref.watch(academicHistoryProvider);
    final summary = ref.watch(academicSummaryProvider);
    final text = Theme.of(context).textTheme;
    final scheme = Theme.of(context).colorScheme;

    String typeLabel(String t) => switch (t) {
      'admission' => a.typeAdmission,
      'term' => a.typeTerm,
      'status' => a.typeStatus,
      'graduation' => a.typeGraduation,
      _ => t,
    };
    IconData icon(String t) => switch (t) {
      'admission' => Icons.login_outlined,
      'term' => Icons.event_note_outlined,
      'status' => Icons.swap_horiz,
      'graduation' => Icons.workspace_premium_outlined,
      _ => Icons.circle_outlined,
    };

    Future<void> refresh() async {
      ref.invalidate(academicSummaryProvider);
      ref.invalidate(academicHistoryProvider);
      await ref.read(academicHistoryProvider.future);
    }

    return Scaffold(
      appBar: AppBar(title: Text(a.historyTitle)),
      body: AsyncValueView<List<HistoryEntry>>(
        value: history,
        onRetry: refresh,
        data: (items) => RefreshList(
          onRefresh: refresh,
          children: [
            if (summary.valueOrNull != null)
              AcademicSummaryCard(summary: summary.value!, detailed: true),
            const SizedBox(height: AppSpacing.lg),
            if (items.isEmpty)
              EmptyView(message: a.historyEmpty)
            else
              for (final e in items)
                Padding(
                  padding: const EdgeInsets.only(bottom: AppSpacing.sm),
                  child: AppSurfaceCard(
                    child: Row(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Icon(icon(e.type), color: scheme.primary),
                        const SizedBox(width: AppSpacing.md),
                        Expanded(
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              Text(e.title, style: text.titleSmall),
                              Text(
                                '${typeLabel(e.type)} · ${context.formatDate(e.date)}',
                                style: text.bodySmall?.copyWith(
                                  color: scheme.onSurfaceVariant,
                                ),
                              ),
                              if (e.description != null)
                                Padding(
                                  padding: const EdgeInsets.only(top: 4),
                                  child: Text(
                                    e.description!,
                                    style: text.bodyMedium,
                                  ),
                                ),
                            ],
                          ),
                        ),
                      ],
                    ),
                  ),
                ),
          ],
        ),
      ),
    );
  }
}
