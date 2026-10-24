import 'dart:async';

import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../../core/l10n/core_strings.dart';
import '../../../../core/theme/app_dimensions.dart';
import '../../../../core/utils/file_actions.dart';
import '../../../../core/utils/formatters.dart';
import '../../../../core/widgets/state_views.dart';
import '../../../../core/widgets/ui_kit.dart';
import '../../data/exam_api.dart';
import '../../domain/attempt_report.dart';
import '../../l10n/exam_strings.dart';
import '../providers.dart';

/// Interval refresh laporan selama peserta masih mengerjakan (sama dengan web).
const kReportRefreshInterval = Duration(seconds: 10);

/// 3725 → "01:02:05" (jam:menit:detik sejak mulai mengerjakan).
String formatHms(int? seconds) {
  if (seconds == null) return '—';
  String pad(int v) => v.toString().padLeft(2, '0');
  return '${pad(seconds ~/ 3600)}:${pad(seconds % 3600 ~/ 60)}:${pad(seconds % 60)}';
}

StatusTone questionTone(ReportQuestionStatus s) => switch (s) {
  ReportQuestionStatus.correct => StatusTone.success,
  ReportQuestionStatus.wrong => StatusTone.danger,
  ReportQuestionStatus.unanswered => StatusTone.neutral,
};

IconData _questionIcon(ReportQuestionStatus s) => switch (s) {
  ReportQuestionStatus.correct => Icons.check_circle_outline,
  ReportQuestionStatus.wrong => Icons.cancel_outlined,
  ReportQuestionStatus.unanswered => Icons.remove_circle_outline,
};

/// "Detail Report" satu percobaan ujian untuk dosen (dibuka dari kartu
/// peserta di tab Pantau/Rekap) — identitas, waktu pengerjaan, ringkasan
/// nilai, lembar jawaban, pelanggaran, linimasa, dan jawaban per soal. Selama
/// peserta masih mengerjakan, laporan diperbarui tiap 10 detik (berhenti saat
/// aplikasi di latar belakang). PDF dirender backend
/// (ExamAttemptController::reportPdf).
class ExamAttemptReportScreen extends ConsumerStatefulWidget {
  const ExamAttemptReportScreen({super.key, required this.attemptId});

  final String attemptId;

  @override
  ConsumerState<ExamAttemptReportScreen> createState() =>
      _ExamAttemptReportScreenState();
}

class _ExamAttemptReportScreenState
    extends ConsumerState<ExamAttemptReportScreen>
    with WidgetsBindingObserver {
  late String _attemptId = widget.attemptId;
  Timer? _timer;
  bool _inProgress = false;
  bool _foreground = true;
  ReportQuestionStatus? _filter;

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addObserver(this);
  }

  @override
  void dispose() {
    WidgetsBinding.instance.removeObserver(this);
    _timer?.cancel();
    super.dispose();
  }

  @override
  void didChangeAppLifecycleState(AppLifecycleState state) {
    _foreground = state == AppLifecycleState.resumed;
    if (_foreground && _inProgress) _refresh();
    _syncTimer();
  }

  void _refresh() => ref.invalidate(attemptReportProvider(_attemptId));

  void _syncTimer() {
    final shouldRun = _inProgress && _foreground;
    if (!shouldRun) {
      _timer?.cancel();
      _timer = null;
    } else {
      _timer ??= Timer.periodic(kReportRefreshInterval, (_) => _refresh());
    }
  }

  void _selectAttempt(String id) {
    if (id == _attemptId) return;
    setState(() {
      _attemptId = id;
      _filter = null;
    });
  }

  @override
  Widget build(BuildContext context) {
    final s = context.examL10n;
    final provider = attemptReportProvider(_attemptId);
    final async = ref.watch(provider);
    ref.listen(provider, (_, next) {
      final report = next.valueOrNull;
      if (report == null) return;
      _inProgress = !report.isSubmitted;
      _syncTimer();
    });

    final report = async.valueOrNull;
    return Scaffold(
      appBar: AppBar(
        title: Text(s.reportTitle),
        actions: [
          if (report != null)
            IconButton(
              tooltip: s.downloadReportPdf,
              icon: const Icon(Icons.picture_as_pdf_outlined),
              onPressed: () => downloadAndOpen(
                context,
                ref,
                path: ExamApi.attemptReportPdfPath(report.attemptId),
                filename:
                    'laporan-ujian-${report.studentNim}-percobaan-${report.attemptNumber}.pdf',
              ),
            ),
        ],
      ),
      body: Center(
        child: ConstrainedBox(
          constraints: const BoxConstraints(maxWidth: 720),
          child: async.when(
            skipLoadingOnRefresh: true,
            skipLoadingOnReload: true,
            loading: () => const SkeletonList(),
            error: (e, _) => ErrorView(error: e, onRetry: _refresh),
            data: (r) => RefreshIndicator(
              onRefresh: () async {
                _refresh();
                await ref.read(provider.future);
              },
              child: _ReportBody(
                report: r,
                filter: _filter,
                onFilter: (f) => setState(() => _filter = f),
                onSelectAttempt: _selectAttempt,
              ),
            ),
          ),
        ),
      ),
    );
  }
}

class _ReportBody extends StatelessWidget {
  const _ReportBody({
    required this.report,
    required this.filter,
    required this.onFilter,
    required this.onSelectAttempt,
  });

  final AttemptReport report;
  final ReportQuestionStatus? filter;
  final ValueChanged<ReportQuestionStatus?> onFilter;
  final ValueChanged<String> onSelectAttempt;

  @override
  Widget build(BuildContext context) {
    final s = context.examL10n;
    final text = Theme.of(context).textTheme;
    final r = report;
    final questions = r.questions
        .where((q) => filter == null || q.status == filter)
        .toList();

    return ListView(
      physics: const AlwaysScrollableScrollPhysics(),
      padding: const EdgeInsets.all(AppSpacing.lg),
      children: [
        Text(r.studentName, style: text.titleLarge),
        Text(
          '${r.studentNim} — ${r.examTitle}',
          style: text.bodySmall?.copyWith(color: context.palette.mutedText),
        ),
        if (r.attempts.length > 1) ...[
          const SizedBox(height: AppSpacing.md),
          DropdownButtonFormField<String>(
            key: ValueKey(r.attemptId),
            isExpanded: true,
            initialValue: r.attemptId,
            decoration: InputDecoration(labelText: s.attemptPicker),
            items: [
              for (final a in r.attempts)
                DropdownMenuItem(
                  value: a.id,
                  child: Text(
                    s.attemptOption(
                      a.attemptNumber,
                      a.isSubmitted,
                      context.formatNumber(a.score),
                    ),
                    overflow: TextOverflow.ellipsis,
                  ),
                ),
            ],
            onChanged: (id) {
              if (id != null) onSelectAttempt(id);
            },
          ),
        ],
        if (!r.isSubmitted) ...[
          const SizedBox(height: AppSpacing.md),
          AppBanner(message: s.inProgressBanner, tone: StatusTone.warning),
        ],
        _section(context, s.studentSection),
        AppSurfaceCard(
          child: Column(
            children: [
              InfoRow(label: s.nameRow, value: r.studentName),
              InfoRow(label: s.nimRow, value: r.studentNim),
              InfoRow(label: s.studyProgramRow, value: r.studyProgram ?? '—'),
              InfoRow(
                label: s.attemptPicker,
                value: s.attemptOf(r.attemptNumber, r.maxAttempts),
              ),
            ],
          ),
        ),
        _section(context, s.examSection),
        AppSurfaceCard(
          child: Column(
            children: [
              InfoRow(label: s.titleLabel, value: r.examTitle),
              InfoRow(
                label: s.courseRow,
                value: [?r.courseCode, r.courseName ?? '—'].join(' — '),
              ),
              InfoRow(label: s.classRow, value: r.classCode ?? '—'),
              InfoRow(label: s.termRow, value: r.academicTerm ?? '—'),
              InfoRow(label: s.lecturerRow, value: r.lecturerName ?? '—'),
            ],
          ),
        ),
        _section(context, s.timeSection),
        _TimingCard(report: r),
        _section(context, s.scoreSection),
        _ScoreGrid(report: r),
        _section(context, s.answerSheet),
        _AnswerSheet(report: r),
        _section(
          context,
          r.violationCount > 0
              ? '${s.violationsSection} (${r.violationCount}×)'
              : s.violationsSection,
        ),
        _ViolationsCard(report: r),
        _section(context, s.activityTimeline),
        _TimelineCard(report: r),
        _section(context, s.answersSection),
        Wrap(
          spacing: AppSpacing.sm,
          runSpacing: AppSpacing.xs,
          children: [
            ChoiceChip(
              label: Text(context.l10n.all),
              selected: filter == null,
              onSelected: (_) => onFilter(null),
            ),
            for (final st in ReportQuestionStatus.values)
              ChoiceChip(
                label: Text(s.questionStatus(st)),
                selected: filter == st,
                onSelected: (_) => onFilter(st),
              ),
          ],
        ),
        const SizedBox(height: AppSpacing.md),
        if (questions.isEmpty)
          Padding(
            padding: const EdgeInsets.all(AppSpacing.xl),
            child: Center(child: Text(s.noQuestionsInFilter)),
          )
        else
          for (final q in questions)
            Padding(
              padding: const EdgeInsets.only(bottom: AppSpacing.md),
              child: QuestionReportCard(question: q),
            ),
      ],
    );
  }

  Widget _section(BuildContext context, String title) => SectionHeader(
    title: title,
    padding: const EdgeInsets.only(top: AppSpacing.lg, bottom: AppSpacing.sm),
  );
}

class _TimingCard extends StatelessWidget {
  const _TimingCard({required this.report});
  final AttemptReport report;

  @override
  Widget build(BuildContext context) {
    final s = context.examL10n;
    final text = Theme.of(context).textTheme;
    final r = report;
    final usage = r.timeUsage;
    return AppSurfaceCard(
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Align(
            alignment: Alignment.centerLeft,
            child: r.isSubmitted
                ? StatusChip(
                    label: r.autoSubmitted
                        ? s.submittedAuto
                        : s.submittedManual,
                    tone: StatusTone.success,
                    icon: Icons.flag_outlined,
                  )
                : StatusChip(
                    label: s.attemptInProgress,
                    tone: StatusTone.warning,
                    icon: Icons.timelapse,
                  ),
          ),
          const SizedBox(height: AppSpacing.sm),
          InfoRow(
            label: s.startedRow,
            value: context.formatDateTime(r.startedAt),
          ),
          InfoRow(
            label: s.submittedRow,
            value: context.formatDateTime(r.submittedAt),
          ),
          InfoRow(
            label: s.deadlineRow,
            value: context.formatDateTime(r.deadlineAt),
          ),
          InfoRow(
            label: s.examScheduleRow,
            value:
                '${context.formatDateTime(r.examStartsAt)} – ${context.formatDateTime(r.examEndsAt)}',
          ),
          InfoRow(
            label: s.durationRow,
            value: s.durationMinutes(r.durationMinutes),
          ),
          InfoRow(label: s.timeSpentRow, value: formatHms(r.durationSeconds)),
          const SizedBox(height: AppSpacing.sm),
          Wrap(
            alignment: WrapAlignment.spaceBetween,
            spacing: AppSpacing.sm,
            children: [
              Text(
                s.timeUsage,
                style: text.bodySmall?.copyWith(
                  color: context.palette.mutedText,
                ),
              ),
              Text(
                '${formatHms(r.durationSeconds)} / ${formatHms(r.allowedSeconds)} (${(usage * 100).round()}%)',
                style: text.bodySmall,
              ),
            ],
          ),
          const SizedBox(height: AppSpacing.xs),
          ClipRRect(
            borderRadius: BorderRadius.circular(AppRadius.full),
            child: LinearProgressIndicator(value: usage, minHeight: 8),
          ),
        ],
      ),
    );
  }
}

class _ScoreGrid extends StatelessWidget {
  const _ScoreGrid({required this.report});
  final AttemptReport report;

  @override
  Widget build(BuildContext context) {
    final s = context.examL10n;
    final r = report;
    final hasPenalty = (r.penaltyScore ?? 0) > 0;
    final tiles = <Widget>[
      StatTile(
        label: s.statQuestions,
        value: '${r.totalQuestions}',
        tone: StatusTone.neutral,
      ),
      StatTile(
        label: s.statAnswered,
        value: '${r.answered}',
        tone: StatusTone.info,
      ),
      StatTile(
        label: s.statCorrect,
        value: '${r.correctAnswers}',
        tone: StatusTone.success,
      ),
      StatTile(
        label: s.statWrong,
        value: '${r.wrongAnswers}',
        tone: StatusTone.danger,
      ),
      StatTile(
        label: s.statUnanswered,
        value: '${r.unanswered}',
        tone: StatusTone.neutral,
      ),
      StatTile(
        label: s.statPoints,
        value:
            '${context.formatNumber(r.earnedPoints)} / ${context.formatNumber(r.totalPoints)}',
      ),
      StatTile(label: s.rawScore, value: context.formatNumber(r.rawScore)),
      StatTile(
        label: s.penaltyTimes(r.violationCount),
        value: hasPenalty ? '-${context.formatNumber(r.penaltyScore)}' : '0',
        tone: hasPenalty ? StatusTone.danger : StatusTone.neutral,
      ),
      StatTile(label: s.finalScore, value: context.formatNumber(r.score)),
      StatTile(label: s.gradeLabel, value: r.grade ?? '—'),
      StatTile(
        label: r.weightPercentage != null
            ? '${s.weightedScore} (${context.formatNumber(r.weightPercentage)}%)'
            : s.weightedScore,
        value: context.formatNumber(r.weightedScore),
      ),
    ];
    return LayoutBuilder(
      builder: (context, c) {
        final w = (c.maxWidth - 2 * AppSpacing.sm) / 3;
        return Wrap(
          spacing: AppSpacing.sm,
          runSpacing: AppSpacing.sm,
          children: [for (final t in tiles) SizedBox(width: w, child: t)],
        );
      },
    );
  }
}

/// Ringkasan jawaban per nomor; ketuk nomor untuk melihat detail soal.
class _AnswerSheet extends StatelessWidget {
  const _AnswerSheet({required this.report});
  final AttemptReport report;

  @override
  Widget build(BuildContext context) {
    final s = context.examL10n;
    final text = Theme.of(context).textTheme;
    return AppSurfaceCard(
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(
            s.answerSheetHelp,
            style: text.bodySmall?.copyWith(color: context.palette.mutedText),
          ),
          const SizedBox(height: AppSpacing.sm),
          Wrap(
            spacing: AppSpacing.xs,
            runSpacing: AppSpacing.xs,
            children: [for (final q in report.questions) _cell(context, q)],
          ),
        ],
      ),
    );
  }

  Widget _cell(BuildContext context, ReportQuestion q) {
    final s = context.examL10n;
    final text = Theme.of(context).textTheme;
    final (fg, bg) = context.toneColors(questionTone(q.status));
    return Semantics(
      button: true,
      label: '${s.questionNo(q.number)}: ${s.questionStatus(q.status)}',
      excludeSemantics: true,
      child: Material(
        color: bg,
        borderRadius: BorderRadius.circular(AppRadius.sm),
        child: InkWell(
          key: ValueKey('answer-cell-${q.number}'),
          borderRadius: BorderRadius.circular(AppRadius.sm),
          onTap: () => showQuestionReport(context, q),
          child: SizedBox(
            width: 48,
            height: 48,
            child: Column(
              mainAxisAlignment: MainAxisAlignment.center,
              children: [
                Text(
                  '${q.number}',
                  style: text.labelSmall?.copyWith(
                    color: context.palette.mutedText,
                  ),
                ),
                Text(
                  q.selectedOptionLabel ?? '–',
                  style: text.titleSmall?.copyWith(color: fg),
                ),
              ],
            ),
          ),
        ),
      ),
    );
  }
}

class _ViolationsCard extends StatelessWidget {
  const _ViolationsCard({required this.report});
  final AttemptReport report;

  @override
  Widget build(BuildContext context) {
    final s = context.examL10n;
    final text = Theme.of(context).textTheme;
    final danger = context.palette.danger;
    if (report.violations.isEmpty) {
      return AppSurfaceCard(
        child: Text(s.noViolations, style: text.bodyMedium),
      );
    }
    return AppSurfaceCard(
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Wrap(
            spacing: AppSpacing.xs,
            runSpacing: AppSpacing.xs,
            children: [
              for (final b in report.violationBreakdown)
                StatusChip(
                  label:
                      '${b.label ?? b.type} · ${b.count}× · -${context.formatNumber(b.penaltyPoints)}',
                  tone: StatusTone.danger,
                  icon: Icons.shield_outlined,
                  dense: true,
                ),
            ],
          ),
          const SizedBox(height: AppSpacing.sm),
          for (final v in report.violations)
            ListTile(
              contentPadding: EdgeInsets.zero,
              leading: CircleAvatar(child: Text('${v.sequenceNumber}')),
              title: Text(v.label ?? v.type),
              subtitle: Text(
                '${context.formatDate(v.occurredAt)} ${context.formatTimeSeconds(v.occurredAt)} · ${s.elapsedAt(formatHms(v.occurredAfterSeconds))}',
              ),
              trailing: Text(
                '-${context.formatNumber(v.penaltyPoints)}',
                style: text.titleSmall?.copyWith(color: danger),
              ),
            ),
        ],
      ),
    );
  }
}

class _TimelineCard extends StatelessWidget {
  const _TimelineCard({required this.report});
  final AttemptReport report;

  @override
  Widget build(BuildContext context) {
    final s = context.examL10n;
    final text = Theme.of(context).textTheme;
    final scheme = Theme.of(context).colorScheme;
    final danger = context.palette.danger;
    return AppSurfaceCard(
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(
            s.activityTimelineHelp,
            style: text.bodySmall?.copyWith(color: context.palette.mutedText),
          ),
          const SizedBox(height: AppSpacing.sm),
          for (final e in report.timeline)
            Padding(
              padding: const EdgeInsets.symmetric(vertical: AppSpacing.xs),
              child: Row(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Icon(
                    switch (e.type) {
                      TimelineEventType.started => Icons.play_circle_outline,
                      TimelineEventType.answered => Icons.edit_outlined,
                      TimelineEventType.violation => Icons.shield_outlined,
                      TimelineEventType.submitted => Icons.flag_outlined,
                    },
                    size: 18,
                    color: e.type == TimelineEventType.violation
                        ? danger
                        : scheme.primary,
                  ),
                  const SizedBox(width: AppSpacing.sm),
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(
                          e.description,
                          style: e.type == TimelineEventType.violation
                              ? text.bodyMedium?.copyWith(
                                  color: danger,
                                  fontWeight: FontWeight.w600,
                                )
                              : text.bodyMedium,
                        ),
                        Text(
                          '${context.formatTimeSeconds(e.occurredAt)} · ${s.elapsedAt(formatHms(e.elapsedSeconds))}',
                          style: text.bodySmall?.copyWith(
                            color: context.palette.mutedText,
                          ),
                        ),
                      ],
                    ),
                  ),
                ],
              ),
            ),
        ],
      ),
    );
  }
}

Future<void> showQuestionReport(BuildContext context, ReportQuestion q) =>
    showModalBottomSheet<void>(
      context: context,
      isScrollControlled: true,
      showDragHandle: true,
      builder: (_) => DraggableScrollableSheet(
        expand: false,
        initialChildSize: 0.6,
        maxChildSize: 0.95,
        builder: (context, controller) => ListView(
          controller: controller,
          padding: const EdgeInsets.fromLTRB(
            AppSpacing.lg,
            0,
            AppSpacing.lg,
            AppSpacing.lg,
          ),
          children: [QuestionReportCard(question: q)],
        ),
      ),
    );

/// Satu soal: status, poin, waktu jawaban disimpan, dan opsi dengan
/// penanda jawaban mahasiswa / kunci jawaban.
class QuestionReportCard extends StatelessWidget {
  const QuestionReportCard({super.key, required this.question});
  final ReportQuestion question;

  @override
  Widget build(BuildContext context) {
    final s = context.examL10n;
    final text = Theme.of(context).textTheme;
    final q = question;
    final muted = text.bodySmall?.copyWith(color: context.palette.mutedText);
    return AppSurfaceCard(
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              Expanded(
                child: Text(s.questionNo(q.number), style: text.titleSmall),
              ),
              StatusChip(
                label: s.questionStatus(q.status),
                tone: questionTone(q.status),
                icon: _questionIcon(q.status),
                dense: true,
              ),
            ],
          ),
          const SizedBox(height: AppSpacing.xs),
          Wrap(
            spacing: AppSpacing.md,
            runSpacing: 2,
            children: [
              Text(
                s.pointsOf(
                  context.formatNumber(q.earnedPoints),
                  context.formatNumber(q.points),
                ),
                style: muted,
              ),
              if (q.answeredAt != null)
                Text(
                  s.savedAt(
                    context.formatTimeSeconds(q.answeredAt),
                    formatHms(q.answeredAfterSeconds),
                  ),
                  style: muted,
                ),
              if (q.fromQuestionBank) Text(s.fromBank, style: muted),
            ],
          ),
          const SizedBox(height: AppSpacing.sm),
          Text(
            q.text ?? s.questionUnavailable,
            style: text.bodyMedium?.copyWith(fontWeight: FontWeight.w500),
          ),
          const SizedBox(height: AppSpacing.sm),
          for (final o in q.options) _optionRow(context, o),
          if (q.selectedOptionId == null)
            Text(
              s.notAnswered,
              style: muted?.copyWith(fontStyle: FontStyle.italic),
            ),
        ],
      ),
    );
  }

  Widget _optionRow(BuildContext context, ReportOption o) {
    final s = context.examL10n;
    final isSelected = o.id == question.selectedOptionId;
    final tone = o.isCorrect
        ? StatusTone.success
        : (isSelected ? StatusTone.danger : null);
    final (fg, bg) = tone == null
        ? (Theme.of(context).colorScheme.onSurface, Colors.transparent)
        : context.toneColors(tone);
    final tags = [
      if (isSelected) s.studentAnswer,
      if (o.isCorrect) s.answerKey,
    ];
    return Container(
      margin: const EdgeInsets.only(bottom: 6),
      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 8),
      decoration: BoxDecoration(
        color: bg,
        borderRadius: BorderRadius.circular(AppRadius.sm),
        border: tone == null ? Border.all(color: context.palette.border) : null,
      ),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(
            '${o.label}.',
            style: TextStyle(color: fg, fontWeight: FontWeight.w600),
          ),
          const SizedBox(width: 8),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(o.text ?? '', style: TextStyle(color: fg)),
                if (tags.isNotEmpty)
                  Text(
                    tags.join(' · '),
                    style: Theme.of(context).textTheme.labelSmall?.copyWith(
                      color: fg,
                      fontWeight: FontWeight.w600,
                    ),
                  ),
              ],
            ),
          ),
        ],
      ),
    );
  }
}
