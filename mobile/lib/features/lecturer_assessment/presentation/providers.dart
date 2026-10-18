import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../data/assessment_api.dart';
import '../domain/assessment_models.dart';

final attendanceSheetProvider = FutureProvider.autoDispose
    .family<AttendanceSheet, String>((ref, classSectionId) async {
      final api = ref.watch(assessmentApiProvider);
      final results = await Future.wait([
        api.roster(classSectionId),
        api.attendances(classSectionId),
      ]);
      return AttendanceSheet(
        roster: results[0] as List<RosterStudent>,
        records: results[1] as List<AttendanceRecord>,
      );
    });

final gradeSheetProvider = AsyncNotifierProvider.autoDispose
    .family<GradeSheetNotifier, List<GradeRow>, String>(GradeSheetNotifier.new);

class GradeSheetNotifier
    extends AutoDisposeFamilyAsyncNotifier<List<GradeRow>, String> {
  @override
  Future<List<GradeRow>> build(String arg) async {
    final api = ref.watch(assessmentApiProvider);
    final results = await Future.wait([api.roster(arg), api.grades(arg)]);
    return joinGrades(
      results[0] as List<RosterStudent>,
      results[1] as List<GradeEntry>,
    );
  }

  /// Saves one grade and updates the row with the backend result.
  Future<GradeEntry> save(
    String krsItemId, {
    required double score,
    String? letter,
  }) async {
    final entry = await ref
        .read(assessmentApiProvider)
        .saveGrade(krsItemId, score: score, letter: letter);
    final rows = state.valueOrNull;
    if (rows != null) {
      state = AsyncData([
        for (final r in rows)
          r.student.krsItemId == krsItemId ? r.withEntry(entry) : r,
      ]);
    }
    return entry;
  }

  Future<void> refresh() async {
    ref.invalidateSelf();
    await future;
  }
}
