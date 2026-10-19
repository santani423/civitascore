import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import '../../../../core/l10n/core_strings.dart';
import '../../../../core/network/api_failure.dart';
import '../../../../core/permission/permission_gate.dart';
import '../../../../core/router/route_paths.dart';
import '../../../../core/theme/app_dimensions.dart';
import '../../../../core/utils/formatters.dart';
import '../../../../core/utils/validators.dart';
import '../../../../core/widgets/app_feedback.dart';
import '../../../../core/widgets/state_views.dart';
import '../../../../core/widgets/ui_kit.dart';
import '../../data/exam_api.dart';
import '../../domain/exam.dart';
import '../../domain/exam_validation.dart';
import '../../domain/question_selection_mode.dart';
import '../../l10n/exam_strings.dart';
import '../providers.dart';

/// Wizard 3 langkah pembuatan/ubah ujian (Informasi, Soal, Preview & Publish).
/// `examId == null` berarti ujian baru.
class ExamFormScreen extends ConsumerStatefulWidget {
  const ExamFormScreen({super.key, this.examId});

  final String? examId;

  @override
  ConsumerState<ExamFormScreen> createState() => _ExamFormScreenState();
}

class _ExamFormScreenState extends ConsumerState<ExamFormScreen> {
  final _infoKey = GlobalKey<FormState>();
  final _title = TextEditingController();
  final _duration = TextEditingController(text: '60');
  final _attempts = TextEditingController(text: '1');
  final _weight = TextEditingController();
  final _qpp = TextEditingController(text: '1');

  String? _examId;
  String? _classId;
  Exam? _loaded;
  DateTime? _startsAt;
  DateTime? _endsAt;
  QuestionSelectionMode _mode = QuestionSelectionMode.all;
  bool _shuffleQ = false;
  bool _shuffleO = false;
  bool _allowBack = true;
  bool _showResult = true;
  bool _published = false;
  int _step = 0;
  bool _busy = false;
  bool _initialized = false;
  String? _endError;
  String? _publishError;
  Map<String, String> _serverErrors = const {};

  @override
  void initState() {
    super.initState();
    _examId = widget.examId;
    _initialized = widget.examId == null;
  }

  @override
  void dispose() {
    for (final c in [_title, _duration, _attempts, _weight, _qpp]) {
      c.dispose();
    }
    super.dispose();
  }

  void _initFrom(Exam e) {
    _initialized = true;
    _loaded = e;
    _classId = e.classSectionId;
    _title.text = e.title;
    _duration.text = '${e.durationMinutes}';
    _attempts.text = '${e.maxAttempts}';
    _weight.text = e.weightPercentage == null
        ? ''
        : '${e.weightPercentage}'.replaceAll(RegExp(r'\.0+$'), '');
    _qpp.text = '${e.questionsPerParticipant}';
    _startsAt = e.startsAt;
    _endsAt = e.endsAt;
    _mode = e.questionSelectionMode;
    _shuffleQ = e.randomizeQuestions;
    _shuffleO = e.randomizeOptions;
    _allowBack = e.allowBackNavigation;
    _showResult = e.showResultAfterSubmission;
    _published = e.isPublished;
  }

  @override
  Widget build(BuildContext context) {
    final s = context.examL10n;
    final examAsync = widget.examId == null
        ? null
        : ref.watch(examProvider(widget.examId!));
    if (examAsync != null && !_initialized && examAsync.hasValue) {
      _initFrom(examAsync.requireValue);
    }

    return Scaffold(
      appBar: AppBar(
        title: Text(widget.examId == null ? s.newExam : s.editExam),
      ),
      body: examAsync != null && !_initialized
          ? AsyncValueView<Exam>(
              value: examAsync,
              data: (_) => const SizedBox.shrink(),
              onRetry: () => ref.invalidate(examProvider(widget.examId!)),
            )
          : Center(
              child: ConstrainedBox(
                constraints: const BoxConstraints(maxWidth: 720),
                child: _buildStepper(context),
              ),
            ),
    );
  }

  int get _perParticipant => int.tryParse(_qpp.text.trim()) ?? 0;

  Widget _buildStepper(BuildContext context) {
    final s = context.examL10n;
    final core = context.l10n;
    final questions = _examId == null
        ? null
        : ref.watch(examQuestionsProvider(_examId!)).valueOrNull;
    final poolSize = questions?.length ?? _loaded?.questionPoolSize ?? 0;
    final selected = questions?.where((q) => q.isSelected).length ?? 0;
    final issues = ExamValidation.issuesFor(
      questionPoolSize: poolSize,
      questionsPerParticipant: _perParticipant,
      selectionMode: _mode,
      manuallySelectedCount: selected,
    );
    final canPublish = PermissionGate.allows(ref, 'exams.publish');

    return Stepper(
      currentStep: _step,
      onStepTapped: (i) {
        if (i > 0 && _examId == null) return;
        setState(() => _step = i);
      },
      controlsBuilder: (context, details) => details.stepIndex != _step
          ? const SizedBox.shrink()
          : Padding(
              padding: const EdgeInsets.only(top: AppSpacing.md),
              child: Wrap(
                spacing: AppSpacing.sm,
                runSpacing: AppSpacing.sm,
                children: [
                  if (_step > 0)
                    OutlinedButton(
                      onPressed: _busy
                          ? null
                          : () => setState(() => _step -= 1),
                      child: Text(core.back),
                    ),
                  if (_step < 2)
                    FilledButton(
                      onPressed: _busy ? null : _saveAndContinue,
                      child: _busy
                          ? const SizedBox(
                              width: 18,
                              height: 18,
                              child: CircularProgressIndicator(strokeWidth: 2),
                            )
                          : Text(core.next),
                    )
                  else
                    FilledButton(
                      onPressed:
                          _busy ||
                              _published ||
                              issues.isNotEmpty ||
                              !canPublish
                          ? null
                          : _publish,
                      child: Text(
                        _published ? s.alreadyPublished : s.publishExam,
                      ),
                    ),
                ],
              ),
            ),
      steps: [
        Step(
          title: Text(s.stepInfo),
          isActive: _step >= 0,
          state: _step > 0 ? StepState.complete : StepState.indexed,
          content: _infoStep(context),
        ),
        Step(
          title: Text(s.stepQuestions),
          isActive: _step >= 1,
          state: _step > 1 ? StepState.complete : StepState.indexed,
          content: _questionStep(context, poolSize),
        ),
        Step(
          title: Text(s.stepPublish),
          isActive: _step >= 2,
          content: _publishStep(context, poolSize, issues, canPublish),
        ),
      ],
    );
  }

  // Step 1 ---------------------------------------------------------------

  Widget _infoStep(BuildContext context) {
    final s = context.examL10n;
    final core = context.l10n;
    final classes = ref.watch(ownClassesProvider);
    final req = Validators.required(core);
    String? positive(String? v) {
      final r = req(v);
      if (r != null) return r;
      final n = int.tryParse(v!.trim());
      return (n == null || n < 1) ? s.validationPositive : null;
    }

    return Form(
      key: _infoKey,
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          if (_examId == null)
            classes.when(
              loading: () => const LinearProgressIndicator(),
              error: (e, _) => ErrorView(
                error: e,
                onRetry: () => ref.invalidate(ownClassesProvider),
              ),
              data: (list) => list.isEmpty
                  ? AppBanner(message: s.noClasses, tone: StatusTone.warning)
                  : DropdownButtonFormField<String>(
                      isExpanded: true,
                      initialValue: _classId,
                      decoration: InputDecoration(
                        labelText: s.classLabel,
                        errorText: _serverErrors['class_section_id'],
                      ),
                      items: [
                        for (final c in list)
                          DropdownMenuItem(
                            value: c.id,
                            child: Text(
                              c.label,
                              overflow: TextOverflow.ellipsis,
                            ),
                          ),
                      ],
                      validator: (v) =>
                          v == null ? core.validationRequired : null,
                      onChanged: (v) => setState(() => _classId = v),
                    ),
            )
          else
            InfoRow(
              label: s.classLabel,
              value: [?_loaded?.courseName, ?_loaded?.classCode].join(' • '),
            ),
          if (_examId != null)
            Padding(
              padding: const EdgeInsets.only(bottom: AppSpacing.sm),
              child: Text(
                s.classLockedHelp,
                style: Theme.of(context).textTheme.bodySmall,
              ),
            ),
          const SizedBox(height: AppSpacing.md),
          TextFormField(
            controller: _title,
            decoration: InputDecoration(
              labelText: s.titleLabel,
              errorText: _serverErrors['title'],
            ),
            validator: req,
          ),
          const SizedBox(height: AppSpacing.md),
          TextFormField(
            controller: _duration,
            keyboardType: TextInputType.number,
            inputFormatters: [FilteringTextInputFormatter.digitsOnly],
            decoration: InputDecoration(
              labelText: s.durationLabel,
              errorText: _serverErrors['duration_minutes'],
            ),
            validator: positive,
          ),
          const SizedBox(height: AppSpacing.md),
          TextFormField(
            controller: _attempts,
            keyboardType: TextInputType.number,
            inputFormatters: [FilteringTextInputFormatter.digitsOnly],
            decoration: InputDecoration(
              labelText: s.attemptsLabel,
              errorText: _serverErrors['max_attempts'],
            ),
            validator: positive,
          ),
          const SizedBox(height: AppSpacing.md),
          _DateField(
            label: s.startsAt,
            value: _startsAt,
            onChanged: (v) => setState(() {
              _startsAt = v;
              _endError = null;
            }),
          ),
          const SizedBox(height: AppSpacing.md),
          _DateField(
            label: s.endsAt,
            value: _endsAt,
            errorText: _endError ?? _serverErrors['ends_at'],
            onChanged: (v) => setState(() {
              _endsAt = v;
              _endError = null;
            }),
          ),
          const SizedBox(height: AppSpacing.md),
          TextFormField(
            controller: _weight,
            keyboardType: const TextInputType.numberWithOptions(decimal: true),
            decoration: InputDecoration(
              labelText: s.weightLabel,
              errorText: _serverErrors['weight_percentage'],
            ),
            validator: Validators.numberInRange(core, 0, 100),
          ),
        ],
      ),
    );
  }

  // Step 2 ---------------------------------------------------------------

  Widget _questionStep(BuildContext context, int poolSize) {
    final s = context.examL10n;
    final text = Theme.of(context).textTheme;
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Card(
          child: Padding(
            padding: const EdgeInsets.all(AppSpacing.md),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(s.questionSettings, style: text.titleSmall),
                const SizedBox(height: AppSpacing.sm),
                Text(s.poolInfo(poolSize), style: text.bodyMedium),
                const SizedBox(height: AppSpacing.sm),
                if (_examId != null)
                  OutlinedButton.icon(
                    icon: const Icon(Icons.quiz_outlined),
                    label: Text(s.manageQuestions),
                    onPressed: () async {
                      await context.push(
                        RoutePaths.lecturerExamQuestions(_examId!),
                      );
                      if (mounted) setState(() {});
                    },
                  )
                else
                  Text(s.saveFirst, style: text.bodySmall),
                const SizedBox(height: AppSpacing.md),
                TextFormField(
                  controller: _qpp,
                  keyboardType: TextInputType.number,
                  inputFormatters: [FilteringTextInputFormatter.digitsOnly],
                  onChanged: (_) => setState(() {}),
                  decoration: InputDecoration(
                    labelText: s.qppLabel,
                    helperText: s.qppHelp,
                    helperMaxLines: 3,
                    errorText: _serverErrors['questions_per_participant'],
                  ),
                ),
              ],
            ),
          ),
        ),
        const SizedBox(height: AppSpacing.md),
        Card(
          child: Padding(
            padding: const EdgeInsets.all(AppSpacing.md),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(s.selectionTitle, style: text.titleSmall),
                Text(s.selectionHelp, style: text.bodySmall),
                RadioGroup<QuestionSelectionMode>(
                  groupValue: _mode,
                  onChanged: (v) {
                    if (v != null) setState(() => _mode = v);
                  },
                  child: Column(
                    children: [
                      for (final m in QuestionSelectionMode.values)
                        RadioListTile<QuestionSelectionMode>(
                          contentPadding: EdgeInsets.zero,
                          title: Text(s.selectionModeLabel(m)),
                          value: m,
                        ),
                    ],
                  ),
                ),
              ],
            ),
          ),
        ),
        const SizedBox(height: AppSpacing.md),
        Card(
          child: Padding(
            padding: const EdgeInsets.all(AppSpacing.md),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(s.shuffleTitle, style: text.titleSmall),
                SwitchListTile(
                  contentPadding: EdgeInsets.zero,
                  title: Text(s.shuffleQuestions),
                  subtitle: Text(s.shuffleQuestionsHelp),
                  value: _shuffleQ,
                  onChanged: (v) => setState(() => _shuffleQ = v),
                ),
                SwitchListTile(
                  contentPadding: EdgeInsets.zero,
                  title: Text(s.shuffleOptions),
                  subtitle: Text(s.shuffleOptionsHelp),
                  value: _shuffleO,
                  onChanged: (v) => setState(() => _shuffleO = v),
                ),
              ],
            ),
          ),
        ),
        ExpansionTile(
          tilePadding: EdgeInsets.zero,
          title: Text(s.advanced),
          children: [
            SwitchListTile(
              contentPadding: EdgeInsets.zero,
              title: Text(s.allowBack),
              value: _allowBack,
              onChanged: (v) => setState(() => _allowBack = v),
            ),
            SwitchListTile(
              contentPadding: EdgeInsets.zero,
              title: Text(s.showResult),
              value: _showResult,
              onChanged: (v) => setState(() => _showResult = v),
            ),
          ],
        ),
      ],
    );
  }

  // Step 3 ---------------------------------------------------------------

  Widget _publishStep(
    BuildContext context,
    int poolSize,
    List<ExamIssue> issues,
    bool canPublish,
  ) {
    final s = context.examL10n;
    final summary = s.distributionSummary(
      pool: poolSize,
      perParticipant: _perParticipant,
      mode: _mode,
      shuffleQ: _shuffleQ,
      shuffleO: _shuffleO,
    );
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Card(
          child: Padding(
            padding: const EdgeInsets.all(AppSpacing.md),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  s.distributionTitle,
                  style: Theme.of(context).textTheme.titleSmall,
                ),
                InfoRow(label: s.poolRow, value: '$poolSize'),
                InfoRow(label: s.perParticipantRow, value: '$_perParticipant'),
                InfoRow(
                  label: s.selectionRow,
                  value: s.selectionModeLabel(_mode),
                ),
                InfoRow(
                  label: s.shuffleQuestions,
                  value: _shuffleQ ? s.shuffled : s.inOrder,
                ),
                InfoRow(
                  label: s.shuffleOptions,
                  value: _shuffleO ? s.shuffled : s.inOrder,
                ),
                const SizedBox(height: AppSpacing.sm),
                AppBanner(message: summary),
              ],
            ),
          ),
        ),
        for (final issue in issues) ...[
          const SizedBox(height: AppSpacing.sm),
          AppBanner(message: s.issueText(issue), tone: StatusTone.danger),
        ],
        if (_publishError != null) ...[
          const SizedBox(height: AppSpacing.sm),
          AppBanner(
            title: s.publishErrorsTitle,
            message: _publishError!,
            tone: StatusTone.danger,
          ),
        ],
        if (!canPublish) ...[
          const SizedBox(height: AppSpacing.sm),
          AppBanner(message: s.publishNoPermission, tone: StatusTone.warning),
        ],
        const SizedBox(height: AppSpacing.md),
        if (_examId != null)
          OutlinedButton.icon(
            icon: const Icon(Icons.visibility_outlined),
            label: Text(s.previewAsStudent),
            onPressed: () =>
                context.push(RoutePaths.lecturerExamPreview(_examId!)),
          ),
      ],
    );
  }

  // Actions --------------------------------------------------------------

  Map<String, dynamic> _payload({required bool create}) {
    final weight = _weight.text.trim().replaceAll(',', '.');
    return {
      if (create) 'class_section_id': _classId,
      'title': _title.text.trim(),
      'duration_minutes': int.tryParse(_duration.text.trim()) ?? 60,
      'max_attempts': int.tryParse(_attempts.text.trim()) ?? 1,
      'starts_at': _startsAt?.toUtc().toIso8601String(),
      'ends_at': _endsAt?.toUtc().toIso8601String(),
      'weight_percentage': weight.isEmpty ? null : double.tryParse(weight),
      'questions_per_participant': _perParticipant,
      'question_selection_mode': _mode.apiValue,
      'randomize_questions': _shuffleQ,
      'randomize_options': _shuffleO,
      'allow_back_navigation': _allowBack,
      'show_result_after_submission': _showResult,
    };
  }

  Future<void> _saveAndContinue() async {
    final s = context.examL10n;
    if (_step == 0) {
      if (!_infoKey.currentState!.validate()) return;
      if (_startsAt != null &&
          _endsAt != null &&
          !_endsAt!.isAfter(_startsAt!)) {
        setState(() => _endError = s.endBeforeStart);
        return;
      }
    }
    if (_step == 1 && _perParticipant < 1) {
      showAppSnack(
        context,
        s.issueText(const ExamIssue(ExamIssueType.participantCount)),
        isError: true,
      );
      return;
    }
    setState(() {
      _busy = true;
      _serverErrors = const {};
    });
    try {
      final api = ref.read(examApiProvider);
      final Exam saved;
      if (_examId == null) {
        saved = await api.createExam(_payload(create: true));
        _examId = saved.id;
      } else {
        saved = await api.updateExam(_examId!, _payload(create: false));
      }
      _loaded = saved;
      ref.invalidate(examProvider(saved.id));
      ref.invalidate(examListProvider);
      if (mounted) setState(() => _step = (_step + 1).clamp(0, 2));
    } on ApiFailure catch (e) {
      if (!mounted) return;
      setState(() {
        _serverErrors = {
          for (final entry in e.fieldErrors.entries)
            if (entry.value.isNotEmpty) entry.key: entry.value.first,
        };
        if (_serverErrors.keys.any(
          (k) => const {
            'questions_per_participant',
            'question_selection_mode',
          }.contains(k),
        )) {
          _step = 1;
        } else if (_serverErrors.isNotEmpty) {
          _step = 0;
        }
      });
      showErrorSnack(context, e);
    } finally {
      if (mounted) setState(() => _busy = false);
    }
  }

  Future<void> _publish() async {
    final s = context.examL10n;
    setState(() {
      _busy = true;
      _publishError = null;
    });
    try {
      final published = await ref.read(examApiProvider).publish(_examId!);
      _loaded = published;
      _published = published.isPublished;
      ref.invalidate(examProvider(_examId!));
      ref.invalidate(examListProvider);
      if (mounted) {
        showAppSnack(context, s.publishedOk);
        context.pushReplacement(RoutePaths.lecturerExam(_examId!));
      }
    } on ApiFailure catch (e) {
      if (mounted) {
        setState(() => _publishError = context.l10n.errorFor(e));
      }
    } finally {
      if (mounted) setState(() => _busy = false);
    }
  }
}

class _DateField extends StatelessWidget {
  const _DateField({
    required this.label,
    required this.value,
    required this.onChanged,
    this.errorText,
  });

  final String label;
  final DateTime? value;
  final ValueChanged<DateTime?> onChanged;
  final String? errorText;

  Future<void> _pick(BuildContext context) async {
    final now = DateTime.now();
    final date = await showDatePicker(
      context: context,
      initialDate: value ?? now,
      firstDate: DateTime(now.year - 1),
      lastDate: DateTime(now.year + 3),
    );
    if (date == null || !context.mounted) return;
    final time = await showTimePicker(
      context: context,
      initialTime: TimeOfDay.fromDateTime(value ?? now),
    );
    if (time == null) return;
    onChanged(
      DateTime(date.year, date.month, date.day, time.hour, time.minute),
    );
  }

  @override
  Widget build(BuildContext context) {
    final s = context.examL10n;
    return InkWell(
      onTap: () => _pick(context),
      child: InputDecorator(
        decoration: InputDecoration(
          labelText: label,
          errorText: errorText,
          suffixIcon: value == null
              ? const Icon(Icons.event_outlined)
              : IconButton(
                  tooltip: s.clear,
                  icon: const Icon(Icons.close),
                  onPressed: () => onChanged(null),
                ),
        ),
        child: Text(
          value == null ? s.notSet : context.formatDateTime(value),
          maxLines: 1,
          overflow: TextOverflow.ellipsis,
        ),
      ),
    );
  }
}
