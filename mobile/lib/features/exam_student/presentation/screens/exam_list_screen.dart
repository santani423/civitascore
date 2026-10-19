import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import '../../../../core/router/route_paths.dart';
import '../../../../core/theme/app_dimensions.dart';
import '../../../../core/utils/formatters.dart';
import '../../../../core/widgets/state_views.dart';
import '../../../../core/widgets/ui_kit.dart';
import '../../domain/exam_models.dart';
import '../../l10n/exam_student_strings.dart';
import '../providers.dart';
import '../widgets/exam_layout.dart';

class ExamListScreen extends ConsumerWidget {
  const ExamListScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final s = context.examL10n;
    final exams = ref.watch(studentExamsProvider);
    return Scaffold(
      appBar: AppBar(title: Text(s.title)),
      body: ExamConstrained(
        child: RefreshIndicator(
          onRefresh: () async {
            ref.invalidate(studentExamsProvider);
            try {
              await ref.read(studentExamsProvider.future);
            } catch (_) {}
          },
          child: AsyncValueView<List<StudentExam>>(
            value: exams,
            onRetry: () => ref.invalidate(studentExamsProvider),
            isEmpty: (d) => d.isEmpty,
            empty: ListView(
              children: [
                SizedBox(
                  height: 420,
                  child: EmptyView(
                    title: s.emptyTitle,
                    message: s.emptyBody,
                    icon: Icons.quiz_outlined,
                  ),
                ),
              ],
            ),
            data: (list) => ListView.separated(
              physics: const AlwaysScrollableScrollPhysics(),
              padding: const EdgeInsets.all(AppSpacing.lg),
              itemCount: list.length,
              separatorBuilder: (_, _) => const SizedBox(height: AppSpacing.md),
              itemBuilder: (_, i) => _ExamCard(exam: list[i]),
            ),
          ),
        ),
      ),
    );
  }
}

class _ExamCard extends ConsumerWidget {
  const _ExamCard({required this.exam});
  final StudentExam exam;

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final s = context.examL10n;
    final text = Theme.of(context).textTheme;
    return AppSurfaceCard(
      onTap: () async {
        await context.push(RoutePaths.studentExam(exam.id));
        ref.invalidate(studentExamsProvider);
      },
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Expanded(
                child: Text(
                  exam.title,
                  style: text.titleMedium,
                  maxLines: 2,
                  overflow: TextOverflow.ellipsis,
                ),
              ),
              const SizedBox(width: AppSpacing.sm),
              StatusChip(
                label: s.statusLabel(exam),
                tone: s.statusTone(exam.status),
                dense: true,
              ),
            ],
          ),
          if (exam.courseName != null) ...[
            const SizedBox(height: 2),
            Text(
              exam.courseName!,
              maxLines: 1,
              overflow: TextOverflow.ellipsis,
              style: text.bodyMedium,
            ),
          ],
          const SizedBox(height: AppSpacing.sm),
          Text(
            exam.startsAt == null && exam.endsAt == null
                ? s.anytime
                : '${context.formatDateTime(exam.startsAt)} – ${context.formatDateTime(exam.endsAt)}',
            style: text.bodySmall,
          ),
          const SizedBox(height: AppSpacing.xs),
          Wrap(
            spacing: AppSpacing.md,
            runSpacing: 4,
            children: [
              Text(s.minutes(exam.durationMinutes), style: text.bodySmall),
              Text(
                '${s.attempts}: ${exam.attemptsUsed}/${exam.maxAttempts}',
                style: text.bodySmall,
              ),
              if (exam.resultVisible && exam.score != null)
                Text(
                  '${s.score}: ${exam.score}',
                  style: text.bodySmall?.copyWith(fontWeight: FontWeight.w600),
                ),
            ],
          ),
        ],
      ),
    );
  }
}
