import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../../core/l10n/core_strings.dart';
import '../../../../core/network/api_failure.dart';
import '../../../../core/theme/app_dimensions.dart';
import '../../../../core/utils/validators.dart';
import '../../../../core/widgets/app_feedback.dart';
import '../../domain/assessment_models.dart';
import '../../l10n/assessment_strings.dart';
import '../providers.dart';

double? parseScore(String? v) =>
    double.tryParse((v ?? '').trim().replaceAll(',', '.'));

/// Bottom sheet to enter / edit one grade (score + optional letter override).
class GradeEditSheet extends ConsumerStatefulWidget {
  const GradeEditSheet({
    super.key,
    required this.classSectionId,
    required this.row,
  });
  final String classSectionId;
  final GradeRow row;

  @override
  ConsumerState<GradeEditSheet> createState() => _GradeEditSheetState();
}

class _GradeEditSheetState extends ConsumerState<GradeEditSheet> {
  final _formKey = GlobalKey<FormState>();
  late final TextEditingController _score;
  String? _letter;
  bool _busy = false;
  String? _scoreError;

  @override
  void initState() {
    super.initState();
    final sc = widget.row.score;
    _score = TextEditingController(
      text: sc == null
          ? ''
          : (sc == sc.roundToDouble() ? '${sc.toInt()}' : '$sc'),
    );
  }

  @override
  void dispose() {
    _score.dispose();
    super.dispose();
  }

  Future<void> _save() async {
    if (!_formKey.currentState!.validate()) return;
    setState(() {
      _busy = true;
      _scoreError = null;
    });
    final s = context.assess;
    try {
      final entry = await ref
          .read(gradeSheetProvider(widget.classSectionId).notifier)
          .save(
            widget.row.student.krsItemId,
            score: parseScore(_score.text)!,
            letter: _letter,
          );
      if (!mounted) return;
      showAppSnack(
        context,
        s.savedLetter(widget.row.student.name, entry.letterGrade ?? '—'),
      );
      Navigator.of(context).pop(true);
    } catch (e) {
      if (!mounted) return;
      setState(() {
        _busy = false;
        if (e is ApiFailure) _scoreError = e.fieldError('score');
      });
      showAppSnack(context, s.errorText(e), isError: true);
    }
  }

  @override
  Widget build(BuildContext context) {
    final s = context.assess;
    final l = context.l10n;
    return Padding(
      padding: EdgeInsets.fromLTRB(
        AppSpacing.lg,
        AppSpacing.lg,
        AppSpacing.lg,
        MediaQuery.of(context).viewInsets.bottom + AppSpacing.lg,
      ),
      child: Form(
        key: _formKey,
        child: SingleChildScrollView(
          child: Column(
            mainAxisSize: MainAxisSize.min,
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text(
                widget.row.student.name,
                style: Theme.of(context).textTheme.titleMedium,
                maxLines: 2,
                overflow: TextOverflow.ellipsis,
              ),
              Text(widget.row.student.nim),
              const SizedBox(height: AppSpacing.lg),
              TextFormField(
                controller: _score,
                autofocus: true,
                keyboardType: const TextInputType.numberWithOptions(
                  decimal: true,
                ),
                decoration: InputDecoration(
                  labelText: s.score,
                  errorText: _scoreError,
                ),
                validator: Validators.compose([
                  Validators.required(l),
                  Validators.numberInRange(l, 0, 100),
                ]),
              ),
              const SizedBox(height: AppSpacing.md),
              DropdownButtonFormField<String?>(
                initialValue: _letter,
                decoration: InputDecoration(
                  labelText: s.letter,
                  helperText: s.letterHint,
                ),
                items: [
                  DropdownMenuItem<String?>(
                    value: null,
                    child: Text(s.letterAuto),
                  ),
                  for (final g in kLetterGrades)
                    DropdownMenuItem<String?>(value: g, child: Text(g)),
                ],
                onChanged: (v) => setState(() => _letter = v),
              ),
              const SizedBox(height: AppSpacing.lg),
              FilledButton(
                onPressed: _busy ? null : _save,
                child: _busy
                    ? const SizedBox(
                        width: 18,
                        height: 18,
                        child: CircularProgressIndicator(strokeWidth: 2),
                      )
                    : Text(l.save),
              ),
            ],
          ),
        ),
      ),
    );
  }
}
