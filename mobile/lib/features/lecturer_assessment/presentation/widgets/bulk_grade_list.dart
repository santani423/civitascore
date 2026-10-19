import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../../core/l10n/core_strings.dart';
import '../../../../core/theme/app_dimensions.dart';
import '../../../../core/widgets/app_feedback.dart';
import '../../../../core/widgets/ui_kit.dart';
import '../../domain/assessment_models.dart';
import '../../l10n/assessment_strings.dart';
import '../providers.dart';
import 'grade_edit_sheet.dart';
import 'status_helpers.dart';

/// "Isi massal": editable score per row, saved sequentially (one PUT per
/// KRS item) with progress and per-row error reporting.
class BulkGradeList extends ConsumerStatefulWidget {
  const BulkGradeList({
    super.key,
    required this.classSectionId,
    required this.rows,
  });
  final String classSectionId;
  final List<GradeRow> rows;

  @override
  ConsumerState<BulkGradeList> createState() => _BulkGradeListState();
}

class _BulkGradeListState extends ConsumerState<BulkGradeList> {
  final _controllers = <String, TextEditingController>{};
  final _errors = <String, String>{};
  final _saved = <String>{};
  bool _running = false;
  int _done = 0;
  int _total = 0;

  String _fmt(double? v) =>
      v == null ? '' : (v == v.roundToDouble() ? '${v.toInt()}' : '$v');

  TextEditingController _ctrl(GradeRow r) => _controllers.putIfAbsent(
    r.student.krsItemId,
    () => TextEditingController(text: _fmt(r.score)),
  );

  @override
  void dispose() {
    for (final c in _controllers.values) {
      c.dispose();
    }
    super.dispose();
  }

  /// Rows whose input is filled and differs from the saved score.
  List<GradeRow> _pending() => [
    for (final r in widget.rows)
      if (_ctrl(r).text.trim().isNotEmpty &&
          parseScoreOrNaN(_ctrl(r).text) != r.score)
        r,
  ];

  double parseScoreOrNaN(String t) => parseScore(t) ?? double.nan;

  Future<void> _saveAll() async {
    final s = context.assess;
    final pending = _pending();
    if (pending.isEmpty) {
      showAppSnack(context, s.nothingToSave);
      return;
    }
    setState(() {
      _running = true;
      _done = 0;
      _total = pending.length;
      _errors.clear();
    });
    final notifier = ref.read(
      gradeSheetProvider(widget.classSectionId).notifier,
    );
    var ok = 0;
    var failed = 0;
    for (final r in pending) {
      final id = r.student.krsItemId;
      final v = parseScore(_ctrl(r).text);
      if (v == null || v < 0 || v > 100) {
        failed++;
        _errors[id] = context.l10n.validationRange(0, 100);
      } else {
        try {
          await notifier.save(id, score: v);
          ok++;
          _saved.add(id);
        } catch (e) {
          failed++;
          if (!mounted) return;
          _errors[id] = s.errorText(e);
        }
      }
      if (!mounted) return;
      setState(() => _done++);
    }
    if (!mounted) return;
    setState(() => _running = false);
    showAppSnack(context, s.bulkResult(ok, failed), isError: failed > 0);
  }

  @override
  Widget build(BuildContext context) {
    final s = context.assess;
    final pendingCount = _pending().length;
    return CenteredBody(
      child: Column(
        children: [
          Padding(
            padding: const EdgeInsets.all(AppSpacing.lg),
            child: AppBanner(message: s.bulkHint),
          ),
          if (_running)
            Padding(
              padding: const EdgeInsets.symmetric(horizontal: AppSpacing.lg),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  LinearProgressIndicator(
                    value: _total == 0 ? null : _done / _total,
                  ),
                  const SizedBox(height: 4),
                  Text(s.progress(_done, _total)),
                ],
              ),
            ),
          Expanded(
            child: ListView.separated(
              padding: const EdgeInsets.all(AppSpacing.lg),
              itemCount: widget.rows.length,
              separatorBuilder: (_, _) => const SizedBox(height: AppSpacing.sm),
              itemBuilder: (context, i) {
                final r = widget.rows[i];
                final id = r.student.krsItemId;
                final err = _errors[id];
                return AppSurfaceCard(
                  padding: const EdgeInsets.all(AppSpacing.md),
                  child: Row(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Expanded(
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Text(
                              r.student.name,
                              maxLines: 2,
                              overflow: TextOverflow.ellipsis,
                              style: Theme.of(context).textTheme.titleSmall,
                            ),
                            Text(
                              r.student.nim,
                              style: Theme.of(context).textTheme.bodySmall,
                            ),
                            if (err != null)
                              Text(
                                err,
                                style: TextStyle(
                                  color: Theme.of(context).colorScheme.error,
                                ),
                              ),
                          ],
                        ),
                      ),
                      const SizedBox(width: AppSpacing.sm),
                      if (r.letterGrade != null)
                        Padding(
                          padding: const EdgeInsets.only(top: 12, right: 8),
                          child: StatusChip(
                            dense: true,
                            tone: letterTone(r.letterGrade),
                            label: r.letterGrade!,
                            icon: _saved.contains(id) ? Icons.check : null,
                          ),
                        ),
                      SizedBox(
                        width: 84,
                        child: TextField(
                          key: ValueKey('bulk:$id'),
                          controller: _ctrl(r),
                          enabled: !_running,
                          keyboardType: const TextInputType.numberWithOptions(
                            decimal: true,
                          ),
                          onChanged: (_) => setState(() {}),
                          decoration: InputDecoration(
                            isDense: true,
                            errorText: err == null ? null : '',
                            errorStyle: const TextStyle(height: 0),
                          ),
                        ),
                      ),
                    ],
                  ),
                );
              },
            ),
          ),
          SafeArea(
            minimum: const EdgeInsets.all(AppSpacing.lg),
            child: SizedBox(
              width: double.infinity,
              child: FilledButton.icon(
                onPressed: _running ? null : _saveAll,
                icon: const Icon(Icons.save_outlined),
                label: Text(s.saveAll(pendingCount)),
              ),
            ),
          ),
        ],
      ),
    );
  }
}
