import 'dart:math' as math;

import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../../core/l10n/core_strings.dart';
import '../../../../core/network/api_failure.dart';
import '../../../../core/theme/app_dimensions.dart';
import '../../../../core/utils/formatters.dart';
import '../../../../core/widgets/app_feedback.dart';
import '../../../../core/widgets/ui_kit.dart';
import '../../data/pmb_duty_api.dart';
import '../../domain/pmb_duty_models.dart';
import '../../l10n/pmb_duty_strings.dart';
import '../providers.dart';

enum ScoreEditResult { saved, savedNext }

/// Digits with at most 2 decimals, '.' or ',' as decimal separator. Three
/// digits after a separator ("1.000", "1,000") are rejected instead of being
/// read as 1, since that is how thousands are grouped in ID/EN.
final _scorePattern = RegExp(r'^-?\d+([.,]\d{1,2})?$');

double? _parse(String? v) {
  final t = (v ?? '').trim();
  if (!_scorePattern.hasMatch(t)) return null;
  return double.tryParse(t.replaceAll(',', '.'));
}

String _fmt(double? v) =>
    v == null ? '' : (v == v.roundToDouble() ? '${v.toInt()}' : '$v');

/// Score of one participant: numeric (range), pass/fail, or rubric
/// criteria (the backend derives the raw score from the rubric). Saved
/// immediately as a single-row `PUT …/scores`. Errors are shown inside the
/// sheet: a SnackBar would be hidden behind it.
class ScoreEditSheet extends ConsumerStatefulWidget {
  const ScoreEditSheet({
    super.key,
    required this.sessionId,
    required this.component,
    required this.row,
    this.hasNext = false,
  });
  final String sessionId;
  final ScoreComponent component;
  final ScoreRow row;
  final bool hasNext;

  @override
  ConsumerState<ScoreEditSheet> createState() => _ScoreEditSheetState();
}

class _ScoreEditSheetState extends ConsumerState<ScoreEditSheet> {
  final _formKey = GlobalKey<FormState>();
  late final TextEditingController _raw;
  late final TextEditingController _note;
  late final Map<String, TextEditingController> _criteria;
  bool? _passed;
  bool _busy = false;
  String? _rawError;
  String? _error;

  ScoreComponent get _c => widget.component;

  @override
  void initState() {
    super.initState();
    final score = widget.row.score;
    _raw = TextEditingController(text: _fmt(score?.rawScore));
    _note = TextEditingController(text: score?.note ?? '');
    _criteria = {
      for (final k in _c.criteria)
        k.key: TextEditingController(text: _fmt(score?.rubricScores[k.key])),
    };
    _passed = score?.passed;
  }

  @override
  void dispose() {
    _raw.dispose();
    _note.dispose();
    for (final c in _criteria.values) {
      c.dispose();
    }
    super.dispose();
  }

  Map<String, double> get _rubric => {
    for (final e in _criteria.entries)
      if (_parse(e.value.text) != null) e.key: _parse(e.value.text)!,
  };

  String _rangeError(double min, double max) => context.pmbDuty.outOfRange(
    context.formatNumber(min),
    context.formatNumber(max),
  );

  /// Required number in `min..max`, messages formatted like the helper text.
  FormFieldValidator<String> _scoreValidator(double min, double max) => (v) {
    if (v == null || v.trim().isEmpty) return context.l10n.validationRequired;
    final n = _parse(v);
    if (n == null) return context.pmbDuty.scoreFormat;
    return (n < min || n > max) ? _rangeError(min, max) : null;
  };

  Future<void> _save({required bool next}) async {
    final s = context.pmbDuty;
    setState(() {
      _rawError = null;
      _error = null;
    });
    if (!_formKey.currentState!.validate()) return;
    if (_c.isPassFail && _passed == null) {
      setState(() => _error = s.chooseResult);
      return;
    }
    // The whole batch is rejected on a range error, so check the
    // rubric-derived score locally as well.
    if (_c.hasRubric) {
      final raw = _c.rubricRaw(_rubric);
      if (raw != null && !_c.inRange(raw)) {
        setState(() => _error = _rangeError(_c.minScore, _c.maxScore));
        return;
      }
    }
    final input = ScoreInput(
      participantId: widget.row.participantId,
      rawScore: _c.isPassFail || _c.hasRubric ? null : _parse(_raw.text),
      rubricScores: _c.hasRubric ? _rubric : null,
      passed: _c.isPassFail ? _passed : null,
      note: _note.text,
    );
    setState(() => _busy = true);
    try {
      final saved = await ref.read(pmbDutyApiProvider).saveScores(
        widget.sessionId,
        [input],
      );
      if (!mounted) return;
      if (saved == 0) {
        // Skipped by the backend (attendance changed meanwhile).
        ref.invalidate(dutyScoreSheetProvider(widget.sessionId));
        setState(() {
          _busy = false;
          _error = s.notSaved;
        });
        return;
      }
      showAppSnack(context, s.savedScore(widget.row.displayName));
      Navigator.of(
        context,
      ).pop(next ? ScoreEditResult.savedNext : ScoreEditResult.saved);
    } catch (e) {
      if (!mounted) return;
      if (e is ApiFailure && (e.status == 423 || e.code == 'SCORES_LOCKED')) {
        // Locked meanwhile: refresh the list behind the sheet.
        ref.invalidate(dutyScoreSheetProvider(widget.sessionId));
      }
      setState(() {
        _busy = false;
        if (e is ApiFailure) _rawError = e.fieldError('raw_score');
        _error = context.l10n.errorFor(e);
      });
    }
  }

  @override
  Widget build(BuildContext context) {
    final s = context.pmbDuty;
    final l = context.l10n;
    final text = Theme.of(context).textTheme;
    final media = MediaQuery.of(context);
    final rubricRaw = _c.hasRubric ? _c.rubricRaw(_rubric) : null;
    return PopScope(
      // Keep the sheet while saving so the result is not lost.
      canPop: !_busy,
      child: Padding(
        padding: EdgeInsets.fromLTRB(
          AppSpacing.lg,
          0,
          AppSpacing.lg,
          math.max(media.viewInsets.bottom, media.viewPadding.bottom) +
              AppSpacing.lg,
        ),
        child: Form(
          key: _formKey,
          child: SingleChildScrollView(
            child: Column(
              mainAxisSize: MainAxisSize.min,
              crossAxisAlignment: CrossAxisAlignment.stretch,
              children: [
                Text(
                  widget.row.displayName,
                  style: text.titleMedium,
                  maxLines: 2,
                  overflow: TextOverflow.ellipsis,
                ),
                Text(
                  '${widget.row.number} · ${_c.name}',
                  style: text.bodySmall,
                ),
                const SizedBox(height: AppSpacing.lg),
                if (_c.isPassFail)
                  SegmentedButton<bool>(
                    emptySelectionAllowed: true,
                    segments: [
                      ButtonSegment(
                        value: true,
                        icon: const Icon(Icons.check),
                        label: Text(s.passed),
                      ),
                      ButtonSegment(
                        value: false,
                        icon: const Icon(Icons.close),
                        label: Text(s.failed),
                      ),
                    ],
                    selected: {?_passed},
                    onSelectionChanged: _busy
                        ? null
                        : (v) => setState(
                            () => _passed = v.isEmpty ? null : v.first,
                          ),
                  )
                else if (_c.hasRubric) ...[
                  for (final k in _c.criteria)
                    Padding(
                      padding: const EdgeInsets.only(bottom: AppSpacing.md),
                      child: TextFormField(
                        key: ValueKey('criterion:${k.key}'),
                        controller: _criteria[k.key],
                        enabled: !_busy,
                        autofocus: k == _c.criteria.first,
                        keyboardType: const TextInputType.numberWithOptions(
                          decimal: true,
                        ),
                        textInputAction: TextInputAction.next,
                        decoration: InputDecoration(
                          labelText:
                              '${k.title} (${context.formatNumber(k.weight)}%)',
                          helperText: '0–${context.formatNumber(k.max)}',
                          errorMaxLines: 2,
                        ),
                        onChanged: (_) => setState(() {}),
                        validator: _scoreValidator(0, k.max),
                      ),
                    ),
                  Text(
                    s.computedRaw(
                      rubricRaw == null ? '—' : context.formatNumber(rubricRaw),
                    ),
                    style: text.titleSmall,
                  ),
                ] else
                  TextFormField(
                    key: const ValueKey('raw_score'),
                    controller: _raw,
                    enabled: !_busy,
                    autofocus: true,
                    keyboardType: const TextInputType.numberWithOptions(
                      decimal: true,
                    ),
                    decoration: InputDecoration(
                      labelText: s.score,
                      helperText: s.rangeLabel(
                        context.formatNumber(_c.minScore),
                        context.formatNumber(_c.maxScore),
                      ),
                      errorText: _rawError,
                      errorMaxLines: 2,
                    ),
                    validator: _scoreValidator(_c.minScore, _c.maxScore),
                  ),
                const SizedBox(height: AppSpacing.md),
                TextFormField(
                  key: const ValueKey('note'),
                  controller: _note,
                  enabled: !_busy,
                  maxLength: 255,
                  decoration: InputDecoration(
                    labelText: '${s.note} (${l.optional})',
                    errorMaxLines: 2,
                  ),
                  // The backend keeps the previous note when none is sent, so
                  // an emptied note would be reported saved but not removed.
                  validator: (v) =>
                      (widget.row.score?.note?.trim().isNotEmpty ?? false) &&
                          (v == null || v.trim().isEmpty)
                      ? s.noteCannotBeCleared
                      : null,
                ),
                if (_error != null)
                  Padding(
                    padding: const EdgeInsets.only(bottom: AppSpacing.md),
                    child: AppBanner(
                      key: const ValueKey('score-error'),
                      message: _error!,
                      tone: StatusTone.danger,
                      icon: Icons.error_outline,
                    ),
                  )
                else
                  const SizedBox(height: AppSpacing.md),
                Row(
                  children: [
                    if (widget.hasNext)
                      Expanded(
                        child: OutlinedButton(
                          onPressed: _busy ? null : () => _save(next: true),
                          child: Text(
                            s.saveAndNext,
                            textAlign: TextAlign.center,
                          ),
                        ),
                      ),
                    if (widget.hasNext) const SizedBox(width: AppSpacing.sm),
                    Expanded(
                      child: FilledButton(
                        onPressed: _busy ? null : () => _save(next: false),
                        child: _busy
                            ? const SizedBox(
                                width: 18,
                                height: 18,
                                child: CircularProgressIndicator(
                                  strokeWidth: 2,
                                ),
                              )
                            : Text(l.save),
                      ),
                    ),
                  ],
                ),
              ],
            ),
          ),
        ),
      ),
    );
  }
}
