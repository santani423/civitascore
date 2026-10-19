import 'dart:async';

import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import '../../../../core/network/api_failure.dart';
import '../../../../core/l10n/core_strings.dart';
import '../../../../core/router/route_paths.dart';
import '../../../../core/security/secure_screen.dart';
import '../../../../core/theme/app_dimensions.dart';
import '../../../../core/widgets/app_feedback.dart';
import '../../../../core/widgets/state_views.dart';
import '../../../../core/widgets/ui_kit.dart';
import '../../data/exam_student_api.dart';
import '../../domain/exam_models.dart';
import '../../l10n/exam_student_strings.dart';
import '../providers.dart';
import '../widgets/exam_layout.dart';

/// Exam-taking screen (FLAG_SECURE). Loads the attempt and its exam, then
/// hands over to [_AttemptBody].
class ExamAttemptScreen extends ConsumerWidget {
  const ExamAttemptScreen({super.key, required this.attemptId});
  final String attemptId;

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final snap = ref.watch(examAttemptSnapshotProvider(attemptId));
    return SecureScreen(
      child: snap.when(
        loading: () => Scaffold(
          appBar: AppBar(title: Text(context.examL10n.taking)),
          body: const SkeletonList(),
        ),
        error: (e, _) => Scaffold(
          appBar: AppBar(title: Text(context.examL10n.taking)),
          body: ErrorView(
            error: e,
            onRetry: () =>
                ref.invalidate(examAttemptSnapshotProvider(attemptId)),
          ),
        ),
        data: (s) {
          if (s.attempt.submitted) {
            return _RedirectToResult(attemptId: attemptId);
          }
          final exam = ref.watch(studentExamProvider(s.attempt.examId));
          return exam.when(
            loading: () => Scaffold(
              appBar: AppBar(title: Text(context.examL10n.taking)),
              body: const SkeletonList(),
            ),
            error: (e, _) => Scaffold(
              appBar: AppBar(title: Text(context.examL10n.taking)),
              body: ErrorView(
                error: e,
                onRetry: () =>
                    ref.invalidate(studentExamProvider(s.attempt.examId)),
              ),
            ),
            data: (ex) => _AttemptBody(snapshot: s, exam: ex),
          );
        },
      ),
    );
  }
}

class _RedirectToResult extends StatefulWidget {
  const _RedirectToResult({required this.attemptId});
  final String attemptId;
  @override
  State<_RedirectToResult> createState() => _RedirectToResultState();
}

class _RedirectToResultState extends State<_RedirectToResult> {
  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) {
      if (mounted) {
        context.pushReplacement(RoutePaths.studentExamResult(widget.attemptId));
      }
    });
  }

  @override
  Widget build(BuildContext context) =>
      Scaffold(body: Center(child: Text(context.examL10n.alreadySubmitted)));
}

enum _Save { saved, saving, failed }

class _AttemptBody extends ConsumerStatefulWidget {
  const _AttemptBody({required this.snapshot, required this.exam});
  final AttemptSnapshot snapshot;
  final StudentExam exam;

  @override
  ConsumerState<_AttemptBody> createState() => _AttemptBodyState();
}

class _AttemptBodyState extends ConsumerState<_AttemptBody>
    with WidgetsBindingObserver {
  late final ExamAttempt _attempt = widget.snapshot.attempt;
  late final DateTime? _deadline = computeAttemptDeadline(
    _attempt,
    widget.exam,
  );

  final Map<String, String?> _answers = {};
  final Map<String, _Save> _save = {};
  final Map<String, Timer> _debounce = {};
  final Map<String, Timer> _retry = {};
  Timer? _ticker;
  Duration? _remaining;
  bool _autoTriggered = false;
  bool _submitting = false;
  bool _finished = false;
  int _current = 0;

  int _violations = 0;
  String? _penalty;
  String? _warning;
  DateTime? _lastViolationAt;
  DateTime? _pausedAt;
  DateTime? _inactiveAt;

  ExamStudentApi get _api => ref.read(examStudentApiProvider);
  List<AttemptQuestion> get _questions => _attempt.questions;
  bool get _allowBack => widget.exam.allowBackNavigation;

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addObserver(this);
    for (final q in _questions) {
      _answers[q.id] = q.selectedOptionId;
    }
    _violations = _attempt.violationCount;
    _penalty = _attempt.penaltyScore;
    if (!_allowBack) {
      final first = _questions.indexWhere((q) => _answers[q.id] == null);
      _current = first < 0 ? 0 : first;
    }
    _tick();
    _ticker = Timer.periodic(const Duration(seconds: 1), (_) => _tick());
  }

  @override
  void dispose() {
    WidgetsBinding.instance.removeObserver(this);
    _ticker?.cancel();
    for (final t in [..._debounce.values, ..._retry.values]) {
      t.cancel();
    }
    super.dispose();
  }

  DateTime get _serverNow => DateTime.now().add(widget.snapshot.serverOffset);

  void _tick() {
    final d = _deadline;
    if (d == null || _finished) return;
    final left = d.difference(_serverNow);
    if (mounted) setState(() => _remaining = left);
    if (left <= Duration.zero && !_autoTriggered) {
      _autoTriggered = true;
      _submit(auto: true);
    }
  }

  // Violations ------------------------------------------------------------
  @override
  void didChangeAppLifecycleState(AppLifecycleState state) {
    if (_finished) return;
    switch (state) {
      case AppLifecycleState.paused:
      case AppLifecycleState.hidden:
        _pausedAt ??= DateTime.now();
      case AppLifecycleState.inactive:
        _inactiveAt ??= DateTime.now();
      case AppLifecycleState.resumed:
        final paused = _pausedAt;
        final inactive = _inactiveAt;
        _pausedAt = null;
        _inactiveAt = null;
        if (paused != null) {
          _report(
            ExamViolationType.tabSwitch,
            DateTime.now().difference(paused),
          );
        } else if (inactive != null &&
            DateTime.now().difference(inactive) >= const Duration(seconds: 2)) {
          _report(
            ExamViolationType.windowBlur,
            DateTime.now().difference(inactive),
          );
        }
      case AppLifecycleState.detached:
        break;
    }
  }

  Future<void> _report(ExamViolationType type, Duration away) async {
    final last = _lastViolationAt;
    if (last != null &&
        DateTime.now().difference(last) < const Duration(seconds: 1)) {
      return;
    }
    _lastViolationAt = DateTime.now();
    try {
      final res = await _api.violation(
        _attempt.id,
        type,
        metadata: {
          'platform': 'mobile',
          'page': RoutePaths.studentExamAttempt(_attempt.id),
          'away_seconds': away.inSeconds,
        },
      );
      if (!mounted) return;
      if (res.violationCount > _violations) {
        final s = context.examL10n;
        setState(() {
          _violations = res.violationCount;
          _penalty = res.penaltyScore;
          _warning = [
            s.violationWarning(res.violationCount),
            if (res.penaltyScore != null) s.totalPenalty(res.penaltyScore!),
          ].join(' ');
        });
      }
    } on ApiFailure catch (e) {
      if (e.kind == ApiFailureKind.conflict) _onClosedByServer();
    }
  }

  // Answers ---------------------------------------------------------------
  bool _canNavigateTo(int i) => _allowBack || i >= _current;

  void _select(AttemptQuestion q, String optionId) {
    if (_submitting || _finished) return;
    setState(() {
      _answers[q.id] = optionId;
      _save[q.id] = _Save.saving;
    });
    _retry.remove(q.id)?.cancel();
    _debounce[q.id]?.cancel();
    _debounce[q.id] = Timer(
      const Duration(milliseconds: 350),
      () => _send(q.id),
    );
  }

  Future<bool> _send(String qid) async {
    _debounce.remove(qid)?.cancel();
    final optionId = _answers[qid];
    try {
      await _api.answer(_attempt.id, questionId: qid, optionId: optionId);
      if (mounted && _answers[qid] == optionId && !_debounce.containsKey(qid)) {
        setState(() => _save[qid] = _Save.saved);
      }
      return true;
    } on ApiFailure catch (e) {
      if (e.kind == ApiFailureKind.conflict) {
        _onClosedByServer();
        return false;
      }
      if (mounted) {
        setState(() => _save[qid] = _Save.failed);
        _retry[qid]?.cancel();
        _retry[qid] = Timer(const Duration(seconds: 4), () {
          if (mounted && _save[qid] == _Save.failed) {
            setState(() => _save[qid] = _Save.saving);
            _send(qid);
          }
        });
      }
      return false;
    }
  }

  Future<bool> _flushAll() async {
    for (final t in _debounce.values) {
      t.cancel();
    }
    _debounce.clear();
    var ok = true;
    for (final entry in _save.entries.toList()) {
      if (entry.value != _Save.saved) {
        ok = await _send(entry.key) && ok;
      }
    }
    return ok;
  }

  // Submit ----------------------------------------------------------------
  void _goResult() {
    if (_finished || !mounted) return;
    _finished = true;
    ref.invalidate(studentExamsProvider);
    ref.invalidate(studentExamProvider(widget.exam.id));
    context.pushReplacement(RoutePaths.studentExamResult(_attempt.id));
  }

  /// The server rejected a write because the attempt is already closed
  /// (deadline passed / submitted). Go to the result screen.
  void _onClosedByServer() {
    if (_finished || !mounted) return;
    showAppSnack(context, context.examL10n.alreadySubmitted);
    _goResult();
  }

  Future<void> _submit({bool auto = false}) async {
    if (_submitting || _finished) return;
    final s = context.examL10n;
    if (!auto) {
      final ok = await _confirmSubmit();
      if (!ok || !mounted) return;
    }
    setState(() => _submitting = true);
    final saved = await _flushAll();
    if (!mounted || _finished) return;
    if (!saved && !auto) {
      setState(() => _submitting = false);
      showAppSnack(context, s.answerSaveFailed, isError: true);
      return;
    }
    try {
      await _api.submit(_attempt.id);
      _goResult();
    } on ApiFailure catch (e) {
      if (!mounted) return;
      if (e.kind == ApiFailureKind.conflict) {
        _goResult();
        return;
      }
      setState(() => _submitting = false);
      showAppSnack(
        context,
        '${s.submitFailed} ${e.serverDetail ?? ''}',
        isError: true,
      );
      if (auto) {
        Future<void>.delayed(const Duration(seconds: 3), () {
          if (mounted && !_finished) {
            _autoTriggered = false;
          }
        });
      }
    }
  }

  Future<bool> _confirmSubmit() async {
    final s = context.examL10n;
    final unanswered = <int>[
      for (var i = 0; i < _questions.length; i++)
        if (_answers[_questions[i].id] == null) i + 1,
    ];
    final result = await showDialog<bool>(
      context: context,
      builder: (ctx) => AlertDialog(
        title: Text(s.submitTitle),
        content: SingleChildScrollView(
          child: Column(
            mainAxisSize: MainAxisSize.min,
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text(
                unanswered.isEmpty
                    ? s.submitAllAnswered
                    : s.submitUnanswered(unanswered.length),
              ),
              if (unanswered.isNotEmpty) ...[
                const SizedBox(height: AppSpacing.md),
                Text(s.unansweredNumbers),
                const SizedBox(height: AppSpacing.xs),
                Wrap(
                  spacing: 6,
                  runSpacing: 6,
                  children: [
                    for (final n in unanswered)
                      StatusChip(
                        label: '$n',
                        tone: StatusTone.warning,
                        dense: true,
                      ),
                  ],
                ),
              ],
            ],
          ),
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(ctx, false),
            child: Text(context.l10n.cancel),
          ),
          FilledButton(
            onPressed: () => Navigator.pop(ctx, true),
            child: Text(s.submitExam),
          ),
        ],
      ),
    );
    return result ?? false;
  }

  Future<void> _confirmLeave() async {
    final s = context.examL10n;
    final ok = await confirmAction(
      context,
      title: s.leaveTitle,
      message: s.leaveBody,
      confirmLabel: s.leave,
      destructive: true,
    );
    if (!ok || !mounted) return;
    await _flushAll();
    if (!mounted) return;
    _finished = true;
    if (context.canPop()) {
      context.pop();
    } else {
      context.go(RoutePaths.studentExams);
    }
  }

  // UI --------------------------------------------------------------------
  String _clock(Duration d) {
    final t = d.isNegative ? Duration.zero : d;
    String two(int n) => n.toString().padLeft(2, '0');
    final h = t.inHours;
    final m = t.inMinutes.remainder(60);
    final sec = t.inSeconds.remainder(60);
    return h > 0 ? '${two(h)}:${two(m)}:${two(sec)}' : '${two(m)}:${two(sec)}';
  }

  void _openGrid() {
    final s = context.examL10n;
    showModalBottomSheet<void>(
      context: context,
      showDragHandle: true,
      isScrollControlled: true,
      builder: (ctx) => SafeArea(
        child: Padding(
          padding: const EdgeInsets.fromLTRB(
            AppSpacing.lg,
            0,
            AppSpacing.lg,
            AppSpacing.lg,
          ),
          child: Column(
            mainAxisSize: MainAxisSize.min,
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text(s.questionList, style: Theme.of(ctx).textTheme.titleMedium),
              const SizedBox(height: 4),
              Text(s.answeredOf(_answeredCount, _questions.length)),
              const SizedBox(height: AppSpacing.md),
              Flexible(
                child: SingleChildScrollView(
                  child: Wrap(
                    spacing: 8,
                    runSpacing: 8,
                    children: [
                      for (var i = 0; i < _questions.length; i++)
                        _GridCell(
                          number: i + 1,
                          answered: _answers[_questions[i].id] != null,
                          current: i == _current,
                          enabled: _canNavigateTo(i),
                          onTap: () {
                            Navigator.pop(ctx);
                            setState(() => _current = i);
                          },
                        ),
                    ],
                  ),
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }

  int get _answeredCount =>
      _questions.where((q) => _answers[q.id] != null).length;

  @override
  Widget build(BuildContext context) {
    final s = context.examL10n;
    final scheme = Theme.of(context).colorScheme;
    final rem = _remaining;
    final critical = rem != null && rem <= const Duration(minutes: 1);
    final low = rem != null && rem <= const Duration(minutes: 5);
    return PopScope(
      canPop: false,
      onPopInvokedWithResult: (didPop, _) {
        if (!didPop) _confirmLeave();
      },
      child: Scaffold(
        appBar: AppBar(
          automaticallyImplyLeading: false,
          leading: IconButton(
            tooltip: s.leave,
            icon: const Icon(Icons.close),
            onPressed: _submitting ? null : _confirmLeave,
          ),
          title: rem == null
              ? Text(s.taking)
              : Text(
                  _clock(rem),
                  style: TextStyle(
                    fontFeatures: const [FontFeature.tabularFigures()],
                    fontWeight: FontWeight.w700,
                    color: critical
                        ? scheme.error
                        : (low
                              ? context.toneColors(StatusTone.warning).$1
                              : null),
                  ),
                ),
          actions: [
            IconButton(
              tooltip: s.questionList,
              icon: const Icon(Icons.grid_view_rounded),
              onPressed: _openGrid,
            ),
            TextButton(
              onPressed: _submitting ? null : () => _submit(),
              child: Text(s.submitExam),
            ),
          ],
        ),
        body: ExamConstrained(
          child: Column(
            children: [
              if (rem != null && rem <= Duration.zero)
                Padding(
                  padding: const EdgeInsets.all(AppSpacing.md),
                  child: AppBanner(message: s.timeUp, tone: StatusTone.danger),
                )
              else if (critical)
                Padding(
                  padding: const EdgeInsets.all(AppSpacing.md),
                  child: AppBanner(
                    message: s.timeCritical,
                    tone: StatusTone.danger,
                  ),
                )
              else if (low)
                Padding(
                  padding: const EdgeInsets.all(AppSpacing.md),
                  child: AppBanner(
                    message: s.timeLow,
                    tone: StatusTone.warning,
                  ),
                ),
              if (_warning != null)
                Padding(
                  padding: const EdgeInsets.fromLTRB(
                    AppSpacing.md,
                    AppSpacing.md,
                    AppSpacing.md,
                    0,
                  ),
                  child: AppBanner(
                    message: _warning!,
                    tone: StatusTone.danger,
                    action: TextButton(
                      onPressed: () => setState(() => _warning = null),
                      child: Text(context.l10n.ok),
                    ),
                  ),
                ),
              Padding(
                padding: const EdgeInsets.fromLTRB(
                  AppSpacing.lg,
                  AppSpacing.md,
                  AppSpacing.lg,
                  0,
                ),
                child: Row(
                  children: [
                    Expanded(
                      child: Text(
                        s.answeredOf(_answeredCount, _questions.length),
                        style: Theme.of(context).textTheme.bodySmall,
                      ),
                    ),
                    if (_violations > 0)
                      StatusChip(
                        label:
                            '${s.violations}: $_violations'
                            '${_penalty == null ? '' : ' (-$_penalty)'}',
                        tone: StatusTone.danger,
                        dense: true,
                      ),
                  ],
                ),
              ),
              Expanded(
                child: _questions.isEmpty
                    ? const SizedBox.shrink()
                    : _questionView(context, _questions[_current]),
              ),
              _bottomBar(context),
            ],
          ),
        ),
      ),
    );
  }

  Widget _questionView(BuildContext context, AttemptQuestion q) {
    final s = context.examL10n;
    final text = Theme.of(context).textTheme;
    final scheme = Theme.of(context).colorScheme;
    return ListView(
      padding: const EdgeInsets.all(AppSpacing.lg),
      children: [
        Text(
          [
            s.questionOf(_current + 1, _questions.length),
            if (q.points != null) s.points(q.points!),
          ].join(' · '),
          style: text.labelLarge?.copyWith(color: context.palette.mutedText),
        ),
        const SizedBox(height: AppSpacing.sm),
        Text(q.text ?? s.noQuestionText, style: text.titleMedium),
        const SizedBox(height: AppSpacing.lg),
        for (final o in q.options)
          Padding(
            padding: const EdgeInsets.only(bottom: AppSpacing.sm),
            child: Material(
              color: _answers[q.id] == o.id
                  ? scheme.primaryContainer
                  : scheme.surfaceContainerLow,
              borderRadius: BorderRadius.circular(AppRadius.sm),
              child: InkWell(
                borderRadius: BorderRadius.circular(AppRadius.sm),
                onTap: _submitting ? null : () => _select(q, o.id),
                child: Padding(
                  padding: const EdgeInsets.symmetric(
                    horizontal: AppSpacing.md,
                    vertical: AppSpacing.md,
                  ),
                  child: Row(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Icon(
                        _answers[q.id] == o.id
                            ? Icons.radio_button_checked
                            : Icons.radio_button_unchecked,
                        color: _answers[q.id] == o.id
                            ? scheme.primary
                            : scheme.outline,
                      ),
                      const SizedBox(width: AppSpacing.md),
                      Expanded(
                        child: Text(o.text ?? '', style: text.bodyLarge),
                      ),
                    ],
                  ),
                ),
              ),
            ),
          ),
        const SizedBox(height: AppSpacing.sm),
        _saveIndicator(context, q.id),
        if (!_allowBack)
          Padding(
            padding: const EdgeInsets.only(top: AppSpacing.sm),
            child: Text(s.ruleNoBack, style: text.bodySmall),
          ),
      ],
    );
  }

  Widget _saveIndicator(BuildContext context, String qid) {
    final s = context.examL10n;
    final answered = _answers[qid] != null;
    final st = _save[qid] ?? (answered ? _Save.saved : null);
    if (st == null) {
      return Text(s.notAnswered, style: Theme.of(context).textTheme.bodySmall);
    }
    final (icon, label, tone) = switch (st) {
      _Save.saved => (Icons.cloud_done_outlined, s.saved, StatusTone.success),
      _Save.saving => (Icons.cloud_upload_outlined, s.saving, StatusTone.info),
      _Save.failed => (Icons.cloud_off_outlined, s.unsaved, StatusTone.danger),
    };
    final fg = context.toneColors(tone).$1;
    return Row(
      children: [
        Icon(icon, size: 18, color: fg),
        const SizedBox(width: 6),
        Flexible(
          child: Text(
            label,
            style: Theme.of(context).textTheme.bodySmall?.copyWith(color: fg),
          ),
        ),
        if (st == _Save.failed)
          TextButton(
            onPressed: () {
              setState(() => _save[qid] = _Save.saving);
              _send(qid);
            },
            child: Text(s.retryNow),
          ),
      ],
    );
  }

  Widget _bottomBar(BuildContext context) {
    final s = context.examL10n;
    final last = _current >= _questions.length - 1;
    return SafeArea(
      top: false,
      child: Padding(
        padding: const EdgeInsets.all(AppSpacing.md),
        child: Row(
          children: [
            Expanded(
              child: OutlinedButton.icon(
                onPressed: _allowBack && _current > 0
                    ? () => setState(() => _current--)
                    : null,
                icon: const Icon(Icons.chevron_left),
                label: Text(context.l10n.previous),
              ),
            ),
            const SizedBox(width: AppSpacing.md),
            Expanded(
              child: last
                  ? FilledButton.icon(
                      onPressed: _submitting ? null : () => _submit(),
                      icon: _submitting
                          ? const SizedBox(
                              width: 18,
                              height: 18,
                              child: CircularProgressIndicator(strokeWidth: 2),
                            )
                          : const Icon(Icons.send_rounded),
                      label: Text(s.submitExam),
                    )
                  : FilledButton.icon(
                      onPressed: _questions.isEmpty
                          ? null
                          : () => setState(() => _current++),
                      icon: const Icon(Icons.chevron_right),
                      iconAlignment: IconAlignment.end,
                      label: Text(context.l10n.next),
                    ),
            ),
          ],
        ),
      ),
    );
  }
}

class _GridCell extends StatelessWidget {
  const _GridCell({
    required this.number,
    required this.answered,
    required this.current,
    required this.enabled,
    required this.onTap,
  });
  final int number;
  final bool answered;
  final bool current;
  final bool enabled;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    final scheme = Theme.of(context).colorScheme;
    final (fg, bg) = answered
        ? context.toneColors(StatusTone.success)
        : (scheme.onSurface, scheme.surfaceContainerHighest);
    return Semantics(
      button: true,
      enabled: enabled,
      label: '$number',
      child: Opacity(
        opacity: enabled ? 1 : 0.4,
        child: InkWell(
          onTap: enabled ? onTap : null,
          customBorder: const CircleBorder(),
          child: Container(
            width: 48,
            height: 48,
            alignment: Alignment.center,
            decoration: BoxDecoration(
              color: bg,
              shape: BoxShape.circle,
              border: current
                  ? Border.all(color: scheme.primary, width: 2.5)
                  : null,
            ),
            child: Text(
              '$number',
              style: TextStyle(color: fg, fontWeight: FontWeight.w600),
            ),
          ),
        ),
      ),
    );
  }
}
