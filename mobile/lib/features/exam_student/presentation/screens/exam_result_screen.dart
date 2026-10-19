import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import '../../../../core/network/api_failure.dart';
import '../../../../core/router/route_paths.dart';
import '../../../../core/theme/app_dimensions.dart';
import '../../../../core/utils/file_actions.dart';
import '../../../../core/utils/formatters.dart';
import '../../../../core/widgets/state_views.dart';
import '../../../../core/widgets/ui_kit.dart';
import '../../data/exam_student_api.dart';
import '../../domain/exam_models.dart';
import '../../l10n/exam_student_strings.dart';
import '../providers.dart';
import '../widgets/exam_layout.dart';

class ExamResultScreen extends ConsumerWidget {
  const ExamResultScreen({super.key, required this.attemptId});
  final String attemptId;

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final s = context.examL10n;
    final result = ref.watch(examResultProvider(attemptId));
    return Scaffold(
      appBar: AppBar(
        title: Text(s.resultTitle),
        actions: [
          if (result.hasValue)
            IconButton(
              tooltip: s.downloadPdf,
              icon: const Icon(Icons.picture_as_pdf_outlined),
              onPressed: () => downloadAndOpen(
                context,
                ref,
                path: ExamStudentApi.resultPdfPath(attemptId),
                filename: 'hasil-ujian-$attemptId.pdf',
              ),
            ),
        ],
      ),
      body: ExamConstrained(
        child: result.when(
          skipLoadingOnRefresh: true,
          loading: () => const SkeletonList(),
          error: (e, _) => e is ApiFailure && e.kind == ApiFailureKind.conflict
              ? MessageView(
                  icon: Icons.hourglass_top_rounded,
                  tone: StatusTone.info,
                  title: s.resultPending,
                  message: e.message,
                  action: OutlinedButton(
                    onPressed: () => context.go(RoutePaths.studentExams),
                    child: Text(s.backToExams),
                  ),
                )
              : ErrorView(
                  error: e,
                  onRetry: () => ref.invalidate(examResultProvider(attemptId)),
                ),
          data: (r) => _ResultBody(result: r),
        ),
      ),
    );
  }
}

class _ResultBody extends StatelessWidget {
  const _ResultBody({required this.result});
  final ExamResult result;

  @override
  Widget build(BuildContext context) {
    final s = context.examL10n;
    final text = Theme.of(context).textTheme;
    return ListView(
      padding: const EdgeInsets.all(AppSpacing.lg),
      children: [
        Text(result.examTitle, style: text.titleLarge),
        if (result.courseName != null) Text(result.courseName!),
        const SizedBox(height: AppSpacing.md),
        Row(
          children: [
            Expanded(
              child: StatTile(label: s.finalScore, value: result.score ?? '—'),
            ),
            const SizedBox(width: AppSpacing.md),
            Expanded(
              child: StatTile(label: s.grade, value: result.grade ?? '—'),
            ),
          ],
        ),
        const SizedBox(height: AppSpacing.md),
        AppSurfaceCard(
          child: Column(
            children: [
              InfoRow(label: s.correct, value: '${result.correctAnswers}'),
              InfoRow(label: s.wrong, value: '${result.wrongAnswers}'),
              InfoRow(label: s.rawScore, value: result.rawScore ?? '—'),
              InfoRow(
                label: s.penalty,
                value:
                    '${result.penaltyScore ?? '0'} (${result.violationCount}×)',
              ),
              InfoRow(label: s.attemptNo, value: '${result.attemptNumber}'),
              if (result.durationSeconds != null)
                InfoRow(
                  label: s.timeSpent,
                  value: s.durationLabel(result.durationSeconds!),
                ),
              InfoRow(
                label: s.submittedAt,
                value: context.formatDateTime(result.submittedAt),
              ),
            ],
          ),
        ),
        if (result.questions.isNotEmpty) ...[
          SectionHeader(
            title: s.review,
            padding: const EdgeInsets.only(top: AppSpacing.lg),
          ),
          for (final q in result.questions) _ReviewCard(q: q),
        ],
      ],
    );
  }
}

class _ReviewCard extends StatelessWidget {
  const _ReviewCard({required this.q});
  final ResultQuestion q;

  @override
  Widget build(BuildContext context) {
    final s = context.examL10n;
    final text = Theme.of(context).textTheme;
    return Padding(
      padding: const EdgeInsets.only(bottom: AppSpacing.md),
      child: AppSurfaceCard(
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              children: [
                Expanded(child: Text('${q.number}.', style: text.titleSmall)),
                StatusChip(
                  label: q.selectedOptionId == null
                      ? s.skipped
                      : (q.isCorrect ? s.correct : s.wrong),
                  tone: q.selectedOptionId == null
                      ? StatusTone.neutral
                      : (q.isCorrect ? StatusTone.success : StatusTone.danger),
                  dense: true,
                ),
              ],
            ),
            const SizedBox(height: AppSpacing.xs),
            Text(q.text ?? s.noQuestionText),
            const SizedBox(height: AppSpacing.sm),
            for (final o in q.options) _optionRow(context, o),
            if (q.explanation != null) ...[
              const SizedBox(height: AppSpacing.sm),
              Text('${s.explanation}: ${q.explanation}', style: text.bodySmall),
            ],
          ],
        ),
      ),
    );
  }

  Widget _optionRow(BuildContext context, AttemptOption o) {
    final s = context.examL10n;
    final isSelected = o.id == q.selectedOptionId;
    final isCorrect = o.id == q.correctOptionId;
    final tone = isCorrect
        ? StatusTone.success
        : (isSelected ? StatusTone.danger : null);
    final (fg, bg) = tone == null
        ? (Theme.of(context).colorScheme.onSurface, Colors.transparent)
        : context.toneColors(tone);
    return Container(
      margin: const EdgeInsets.only(bottom: 6),
      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 8),
      decoration: BoxDecoration(
        color: bg,
        borderRadius: BorderRadius.circular(AppRadius.sm),
      ),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Icon(
            isCorrect
                ? Icons.check_circle
                : (isSelected ? Icons.cancel : Icons.radio_button_unchecked),
            size: 18,
            color: fg,
          ),
          const SizedBox(width: 8),
          Expanded(
            child: Text(
              [
                o.text ?? '',
                if (isSelected) '(${s.yourAnswer})',
                if (isCorrect && !isSelected) '(${s.correctAnswer})',
              ].join(' '),
              style: TextStyle(color: fg),
            ),
          ),
        ],
      ),
    );
  }
}
