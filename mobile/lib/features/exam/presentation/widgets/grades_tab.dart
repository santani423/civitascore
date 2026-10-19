import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../../core/l10n/core_strings.dart';
import '../../../../core/network/api_failure.dart';
import '../../../../core/permission/permission_gate.dart';
import '../../../../core/theme/app_dimensions.dart';
import '../../../../core/widgets/app_feedback.dart';
import '../../../../core/widgets/state_views.dart';
import '../../../../core/widgets/ui_kit.dart';
import '../../data/exam_api.dart';
import '../../domain/grade_range.dart';
import '../../l10n/exam_strings.dart';
import '../providers.dart';

/// Rentang nilai huruf per ujian (GET/PUT /exams/{id}/grade-ranges).
class GradesTab extends ConsumerWidget {
  const GradesTab({super.key, required this.examId});

  final String examId;

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final async = ref.watch(gradeRangesProvider(examId));
    return AsyncValueView<List<GradeRange>>(
      value: async,
      onRetry: () => ref.invalidate(gradeRangesProvider(examId)),
      data: (ranges) => _GradeEditor(
        key: ValueKey(ranges.length),
        examId: examId,
        initial: ranges,
      ),
    );
  }
}

class _RangeRow {
  _RangeRow(this.grade, double min, double max)
    : min = TextEditingController(text: _fmt(min)),
      max = TextEditingController(text: _fmt(max));

  String? grade;
  final TextEditingController min;
  final TextEditingController max;

  static String _fmt(double v) => v % 1 == 0 ? '${v.toInt()}' : v.toString();

  GradeRange? toRange() {
    final lo = double.tryParse(min.text.trim().replaceAll(',', '.'));
    final hi = double.tryParse(max.text.trim().replaceAll(',', '.'));
    if (grade == null || lo == null || hi == null) return null;
    return GradeRange(grade: grade!, minScore: lo, maxScore: hi);
  }

  void dispose() {
    min.dispose();
    max.dispose();
  }
}

class _GradeEditor extends ConsumerStatefulWidget {
  const _GradeEditor({super.key, required this.examId, required this.initial});

  final String examId;
  final List<GradeRange> initial;

  @override
  ConsumerState<_GradeEditor> createState() => _GradeEditorState();
}

class _GradeEditorState extends ConsumerState<_GradeEditor> {
  late List<_RangeRow> _rows;
  String? _error;
  bool _saving = false;

  @override
  void initState() {
    super.initState();
    _rows = _fromRanges(widget.initial);
  }

  List<_RangeRow> _fromRanges(List<GradeRange> ranges) {
    if (ranges.isEmpty) {
      return [
        for (final d in kDefaultGradeRanges.reversed)
          _RangeRow(d.$1, d.$2, d.$3),
      ];
    }
    final sorted = [...ranges]
      ..sort((a, b) => b.minScore.compareTo(a.minScore));
    return [for (final r in sorted) _RangeRow(r.grade, r.minScore, r.maxScore)];
  }

  @override
  void dispose() {
    for (final r in _rows) {
      r.dispose();
    }
    super.dispose();
  }

  Future<void> _save() async {
    final s = context.examL10n;
    final ranges = <GradeRange>[];
    for (final row in _rows) {
      final r = row.toRange();
      if (r == null) {
        setState(() => _error = s.rangeIssue(GradeRangeIssue.invalidBounds));
        return;
      }
      ranges.add(r);
    }
    final issue = GradeRangeValidation.check(ranges);
    if (issue != null) {
      setState(() => _error = s.rangeIssue(issue));
      return;
    }
    setState(() {
      _error = null;
      _saving = true;
    });
    try {
      await ref.read(examApiProvider).saveGradeRanges(widget.examId, ranges);
      ref.invalidate(gradeRangesProvider(widget.examId));
      if (mounted) showAppSnack(context, s.rangesSaved);
    } on ApiFailure catch (e) {
      if (mounted) setState(() => _error = context.l10n.errorFor(e));
    } finally {
      if (mounted) setState(() => _saving = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    final s = context.examL10n;
    final text = Theme.of(context).textTheme;
    final canEdit = PermissionGate.allows(ref, 'exams.update');
    final used = _rows.map((r) => r.grade).toSet();

    return ListView(
      padding: const EdgeInsets.all(AppSpacing.lg),
      children: [
        Text(s.gradeRangesTitle, style: text.titleMedium),
        const SizedBox(height: AppSpacing.xs),
        Text(s.gradeRangesHelp, style: text.bodySmall),
        const SizedBox(height: AppSpacing.md),
        for (var i = 0; i < _rows.length; i++)
          Padding(
            padding: const EdgeInsets.only(bottom: AppSpacing.sm),
            child: Row(
              children: [
                SizedBox(
                  width: 84,
                  child: DropdownButtonFormField<String>(
                    isExpanded: true,
                    initialValue: _rows[i].grade,
                    decoration: InputDecoration(labelText: s.gradeLabel),
                    items: [
                      for (final g in kLetterGrades)
                        if (g == _rows[i].grade || !used.contains(g))
                          DropdownMenuItem(value: g, child: Text(g)),
                    ],
                    onChanged: canEdit
                        ? (v) => setState(() => _rows[i].grade = v)
                        : null,
                  ),
                ),
                const SizedBox(width: AppSpacing.sm),
                Expanded(
                  child: TextField(
                    controller: _rows[i].min,
                    enabled: canEdit,
                    keyboardType: const TextInputType.numberWithOptions(
                      decimal: true,
                    ),
                    decoration: InputDecoration(labelText: s.minScore),
                  ),
                ),
                const SizedBox(width: AppSpacing.sm),
                Expanded(
                  child: TextField(
                    controller: _rows[i].max,
                    enabled: canEdit,
                    keyboardType: const TextInputType.numberWithOptions(
                      decimal: true,
                    ),
                    decoration: InputDecoration(labelText: s.maxScore),
                  ),
                ),
                if (canEdit)
                  IconButton(
                    tooltip: context.l10n.delete,
                    icon: const Icon(Icons.close),
                    onPressed: () =>
                        setState(() => _rows.removeAt(i).dispose()),
                  ),
              ],
            ),
          ),
        if (canEdit) ...[
          Wrap(
            spacing: AppSpacing.sm,
            children: [
              TextButton.icon(
                icon: const Icon(Icons.add),
                label: Text(s.addRange),
                onPressed: _rows.length >= kLetterGrades.length
                    ? null
                    : () => setState(() => _rows.add(_RangeRow(null, 0, 0))),
              ),
              TextButton(
                onPressed: () => setState(() {
                  for (final r in _rows) {
                    r.dispose();
                  }
                  _rows = _fromRanges(const []);
                }),
                child: Text(s.useDefaults),
              ),
            ],
          ),
          if (_error != null) ...[
            const SizedBox(height: AppSpacing.sm),
            AppBanner(message: _error!, tone: StatusTone.danger),
          ],
          const SizedBox(height: AppSpacing.md),
          FilledButton(
            onPressed: _saving ? null : _save,
            child: _saving
                ? const SizedBox(
                    width: 18,
                    height: 18,
                    child: CircularProgressIndicator(strokeWidth: 2),
                  )
                : Text(s.saveRanges),
          ),
        ],
        const SizedBox(height: AppSpacing.lg),
        AppBanner(message: s.weightFormulaHelp),
      ],
    );
  }
}
