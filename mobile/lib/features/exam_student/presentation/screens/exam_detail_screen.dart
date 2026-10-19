import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import '../../../../core/network/api_envelope.dart';
import '../../../../core/network/api_failure.dart';
import '../../../../core/router/route_paths.dart';
import '../../../../core/theme/app_dimensions.dart';
import '../../../../core/utils/formatters.dart';
import '../../../../core/widgets/app_feedback.dart';
import '../../../../core/widgets/state_views.dart';
import '../../../../core/widgets/ui_kit.dart';
import '../../data/exam_student_api.dart';
import '../../domain/exam_models.dart';
import '../../l10n/exam_student_strings.dart';
import '../providers.dart';
import '../widgets/exam_layout.dart';

class ExamDetailScreen extends ConsumerStatefulWidget {
  const ExamDetailScreen({super.key, required this.examId});
  final String examId;

  @override
  ConsumerState<ExamDetailScreen> createState() => _ExamDetailScreenState();
}

class _ExamDetailScreenState extends ConsumerState<ExamDetailScreen> {
  bool _busy = false;
  ApiFailure? _blocked;
  final _scroll = ScrollController();

  @override
  void dispose() {
    _scroll.dispose();
    super.dispose();
  }

  Future<void> _start(StudentExam exam) async {
    final s = context.examL10n;
    final ok = await confirmAction(
      context,
      title: exam.isResuming ? s.confirmResumeTitle : s.confirmStartTitle,
      message: exam.isResuming
          ? s.confirmResumeBody
          : s.confirmStartBody(exam.durationMinutes),
      confirmLabel: exam.isResuming ? s.resume : s.start,
    );
    if (!ok || !mounted) return;
    setState(() {
      _busy = true;
      _blocked = null;
    });
    try {
      final attempt = await ref
          .read(examStudentApiProvider)
          .start(widget.examId);
      if (!mounted) return;
      setState(() => _busy = false);
      await context.push(RoutePaths.studentExamAttempt(attempt.id));
      ref.invalidate(studentExamProvider(widget.examId));
      ref.invalidate(studentExamsProvider);
    } catch (e) {
      if (!mounted) return;
      final blocked = e is ApiFailure && e.code == 'ACADEMIC_BLOCKED';
      setState(() {
        _busy = false;
        if (blocked) _blocked = e;
      });
      if (blocked && _scroll.hasClients) {
        _scroll.animateTo(
          0,
          duration: const Duration(milliseconds: 250),
          curve: Curves.easeOut,
        );
      }
      if (!blocked) showErrorSnack(context, e);
    }
  }

  @override
  Widget build(BuildContext context) {
    final s = context.examL10n;
    final exam = ref.watch(studentExamProvider(widget.examId));
    return Scaffold(
      appBar: AppBar(title: Text(s.detailTitle)),
      body: ExamConstrained(
        child: AsyncValueView<StudentExam>(
          value: exam,
          onRetry: () => ref.invalidate(studentExamProvider(widget.examId)),
          data: (e) => _content(context, e),
        ),
      ),
    );
  }

  Widget _content(BuildContext context, StudentExam e) {
    final s = context.examL10n;
    final text = Theme.of(context).textTheme;
    final blocks = asMapList(_blocked?.details?['blocks']);
    return ListView(
      controller: _scroll,
      padding: const EdgeInsets.all(AppSpacing.lg),
      children: [
        Row(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Expanded(child: Text(e.title, style: text.titleLarge)),
            const SizedBox(width: AppSpacing.sm),
            StatusChip(label: s.statusLabel(e), tone: s.statusTone(e.status)),
          ],
        ),
        const SizedBox(height: AppSpacing.md),
        if (_blocked != null) ...[
          AppBanner(
            tone: StatusTone.danger,
            title: s.blockedTitle,
            message: [
              _blocked!.message ?? s.blockedBody,
              for (final b in blocks)
                if (asString(b['reason']) != null) '• ${asString(b['reason'])}',
            ].join('\n'),
            action: TextButton(
              onPressed: () => context.push(RoutePaths.studentClearance),
              child: Text(s.openClearance),
            ),
          ),
          const SizedBox(height: AppSpacing.md),
        ],
        AppSurfaceCard(
          child: Column(
            children: [
              if (e.courseName != null)
                InfoRow(label: s.course, value: e.courseName!),
              if (e.lecturerName != null)
                InfoRow(label: s.lecturer, value: e.lecturerName!),
              InfoRow(label: s.duration, value: s.minutes(e.durationMinutes)),
              InfoRow(
                label: s.opens,
                value: e.startsAt == null
                    ? s.anytime
                    : context.formatDateTime(e.startsAt),
              ),
              InfoRow(
                label: s.closes,
                value: e.endsAt == null
                    ? s.anytime
                    : context.formatDateTime(e.endsAt),
              ),
              if (e.questionsPerParticipant != null)
                InfoRow(
                  label: s.questions,
                  value: '${e.questionsPerParticipant}',
                ),
              InfoRow(
                label: s.attempts,
                value: s.attemptsOf(e.attemptsUsed, e.maxAttempts),
              ),
              InfoRow(
                label: s.backNavigation,
                value: e.allowBackNavigation ? s.allowed : s.notAllowed,
              ),
              if (e.resultVisible && e.score != null)
                InfoRow(label: s.score, value: e.score!),
            ],
          ),
        ),
        SectionHeader(
          title: s.rulesTitle,
          padding: const EdgeInsets.only(top: AppSpacing.lg),
        ),
        for (final rule in [
          s.ruleSecure,
          s.ruleAutosave,
          s.ruleAutoSubmit,
          s.ruleViolation,
          e.allowBackNavigation ? s.ruleBackOk : s.ruleNoBack,
        ])
          Padding(
            padding: const EdgeInsets.symmetric(vertical: 4),
            child: Row(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                const Icon(Icons.check_circle_outline, size: 18),
                const SizedBox(width: AppSpacing.sm),
                Expanded(child: Text(rule, style: text.bodyMedium)),
              ],
            ),
          ),
        const SizedBox(height: AppSpacing.lg),
        ..._actions(context, e),
      ],
    );
  }

  List<Widget> _actions(BuildContext context, StudentExam e) {
    final s = context.examL10n;
    final text = Theme.of(context).textTheme;
    final widgets = <Widget>[];
    if (e.canStart) {
      widgets.add(
        FilledButton.icon(
          onPressed: _busy ? null : () => _start(e),
          icon: _busy
              ? const SizedBox(
                  width: 18,
                  height: 18,
                  child: CircularProgressIndicator(strokeWidth: 2),
                )
              : const Icon(Icons.play_arrow_rounded),
          label: Text(e.isResuming ? s.resume : s.start),
        ),
      );
    } else {
      final msg = switch (e.status) {
        StudentExamStatus.upcoming => s.notYetOpen,
        StudentExamStatus.expired => s.expiredMsg,
        StudentExamStatus.completed => s.completedMsg,
        _ => null,
      };
      if (msg != null) widgets.add(Text(msg, style: text.bodyMedium));
    }
    if (e.hasFinishedAttempt) {
      widgets.add(const SizedBox(height: AppSpacing.md));
      if (e.resultVisible) {
        widgets.add(
          OutlinedButton.icon(
            onPressed: () =>
                context.push(RoutePaths.studentExamResult(e.latestAttemptId!)),
            icon: const Icon(Icons.assessment_outlined),
            label: Text(s.viewResult),
          ),
        );
      } else {
        widgets.add(AppBanner(message: s.waitingResult, tone: StatusTone.info));
      }
    }
    return widgets;
  }
}
