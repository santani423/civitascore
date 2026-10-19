import 'package:flutter/material.dart';

import '../../../../core/l10n/core_strings.dart';
import '../../../../core/network/api_failure.dart';
import '../../../../core/theme/app_dimensions.dart';
import '../../../../core/widgets/app_feedback.dart';
import '../../domain/exam_question.dart';
import '../../domain/own_class.dart';
import '../../l10n/exam_strings.dart';

typedef QuestionSaveCallback =
    Future<void> Function({
      required String text,
      required double points,
      required List<OptionDraft> options,
      String? courseId,
    });

/// Bottom sheet penyunting soal pilihan ganda (min 2 opsi, tepat 1 benar) —
/// dipakai untuk soal ujian dan butir bank soal. `courses` non-null
/// menampilkan pemilih mata kuliah (bank soal).
class QuestionEditorSheet extends StatefulWidget {
  const QuestionEditorSheet({
    super.key,
    required this.title,
    required this.onSave,
    this.initialText = '',
    this.initialPoints = 1,
    this.initialOptions,
    this.courses,
    this.initialCourseId,
  });

  final String title;
  final QuestionSaveCallback onSave;
  final String initialText;
  final double initialPoints;
  final List<ExamOption>? initialOptions;
  final List<OwnClass>? courses;
  final String? initialCourseId;

  @override
  State<QuestionEditorSheet> createState() => _QuestionEditorSheetState();
}

class _QuestionEditorSheetState extends State<QuestionEditorSheet> {
  late final TextEditingController _text;
  late final TextEditingController _points;
  late final List<TextEditingController> _options;
  int _correct = 0;
  String? _courseId;
  String? _error;
  String? _questionError;
  bool _saving = false;

  @override
  void initState() {
    super.initState();
    _text = TextEditingController(text: widget.initialText);
    _points = TextEditingController(
      text: widget.initialPoints % 1 == 0
          ? '${widget.initialPoints.toInt()}'
          : '${widget.initialPoints}',
    );
    final initial = widget.initialOptions;
    if (initial != null && initial.isNotEmpty) {
      _options = [
        for (final o in initial) TextEditingController(text: o.optionText),
      ];
      final idx = initial.indexWhere((o) => o.isCorrect);
      _correct = idx < 0 ? 0 : idx;
    } else {
      _options = [TextEditingController(), TextEditingController()];
    }
    _courseId = widget.initialCourseId;
  }

  @override
  void dispose() {
    _text.dispose();
    _points.dispose();
    for (final c in _options) {
      c.dispose();
    }
    super.dispose();
  }

  Future<void> _save() async {
    final s = context.examL10n;
    final core = context.l10n;
    if (_text.text.trim().isEmpty) {
      setState(() {
        _questionError = core.validationRequired;
        _error = null;
      });
      return;
    }
    if (_options.length < 2) {
      setState(() => _error = s.minTwoOptions);
      return;
    }
    if (_options.any((c) => c.text.trim().isEmpty)) {
      setState(() => _error = s.optionsFilled);
      return;
    }
    setState(() {
      _saving = true;
      _error = null;
      _questionError = null;
    });
    try {
      await widget.onSave(
        text: _text.text.trim(),
        points: double.tryParse(_points.text.trim().replaceAll(',', '.')) ?? 1,
        options: [
          for (var i = 0; i < _options.length; i++)
            (optionText: _options[i].text.trim(), isCorrect: i == _correct),
        ],
        courseId: _courseId,
      );
      if (mounted) Navigator.of(context).pop(true);
    } on ApiFailure catch (e) {
      if (mounted) {
        setState(() => _error = core.errorFor(e));
      }
    } finally {
      if (mounted) setState(() => _saving = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    final s = context.examL10n;
    final core = context.l10n;
    final text = Theme.of(context).textTheme;
    return Padding(
      padding: EdgeInsets.only(
        left: AppSpacing.lg,
        right: AppSpacing.lg,
        top: AppSpacing.lg,
        bottom: MediaQuery.of(context).viewInsets.bottom + AppSpacing.lg,
      ),
      child: SingleChildScrollView(
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          mainAxisSize: MainAxisSize.min,
          children: [
            Text(widget.title, style: text.titleLarge),
            const SizedBox(height: AppSpacing.md),
            if (widget.courses != null) ...[
              DropdownButtonFormField<String?>(
                isExpanded: true,
                initialValue: _courseId,
                decoration: InputDecoration(labelText: s.courseLabel),
                items: [
                  DropdownMenuItem<String?>(
                    value: null,
                    child: Text(s.noCourse),
                  ),
                  for (final c in widget.courses!)
                    DropdownMenuItem<String?>(
                      value: c.courseId,
                      child: Text(
                        c.courseName ?? c.classCode,
                        overflow: TextOverflow.ellipsis,
                      ),
                    ),
                ],
                onChanged: (v) => setState(() => _courseId = v),
              ),
              const SizedBox(height: AppSpacing.md),
            ],
            TextField(
              controller: _text,
              minLines: 2,
              maxLines: 6,
              decoration: InputDecoration(
                labelText: s.questionLabel,
                errorText: _questionError,
              ),
            ),
            const SizedBox(height: AppSpacing.md),
            TextField(
              controller: _points,
              keyboardType: const TextInputType.numberWithOptions(
                decimal: true,
              ),
              decoration: InputDecoration(labelText: s.pointsLabel),
            ),
            const SizedBox(height: AppSpacing.md),
            Text(s.optionsLabel, style: text.labelLarge),
            Text(s.markCorrect, style: text.bodySmall),
            const SizedBox(height: AppSpacing.sm),
            RadioGroup<int>(
              groupValue: _correct,
              onChanged: (v) => setState(() => _correct = v ?? _correct),
              child: Column(
                children: [
                  for (var i = 0; i < _options.length; i++)
                    Row(
                      children: [
                        Radio<int>(value: i),
                        Expanded(
                          child: Padding(
                            padding: const EdgeInsets.symmetric(vertical: 4),
                            child: TextField(
                              controller: _options[i],
                              decoration: InputDecoration(
                                labelText: s.optionN(i + 1),
                              ),
                            ),
                          ),
                        ),
                        if (_options.length > 2)
                          IconButton(
                            tooltip: s.removeOption,
                            icon: const Icon(Icons.close),
                            onPressed: () => setState(() {
                              _options.removeAt(i).dispose();
                              if (_correct >= _options.length) {
                                _correct = _options.length - 1;
                              } else if (_correct > i) {
                                _correct -= 1;
                              }
                            }),
                          ),
                      ],
                    ),
                ],
              ),
            ),
            TextButton.icon(
              icon: const Icon(Icons.add),
              label: Text(s.addOption),
              onPressed: () =>
                  setState(() => _options.add(TextEditingController())),
            ),
            if (_error != null) ...[
              const SizedBox(height: AppSpacing.sm),
              Text(
                _error!,
                style: text.bodySmall?.copyWith(
                  color: Theme.of(context).colorScheme.error,
                ),
              ),
            ],
            const SizedBox(height: AppSpacing.md),
            SizedBox(
              width: double.infinity,
              child: FilledButton(
                onPressed: _saving ? null : _save,
                child: _saving
                    ? const SizedBox(
                        width: 18,
                        height: 18,
                        child: CircularProgressIndicator(strokeWidth: 2),
                      )
                    : Text(core.save),
              ),
            ),
          ],
        ),
      ),
    );
  }
}

Future<bool?> showQuestionEditor(
  BuildContext context,
  QuestionEditorSheet sheet,
) => showModalBottomSheet<bool>(
  context: context,
  isScrollControlled: true,
  showDragHandle: true,
  builder: (_) => sheet,
);

/// Dipakai juga untuk menyimpan snackbar sukses bersama.
void showSavedSnack(BuildContext context, String message) =>
    showAppSnack(context, message);
