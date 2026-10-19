import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../data/teaching_api.dart';
import '../domain/teaching_models.dart';

final teachingClassesProvider = FutureProvider.autoDispose<List<TeachingClass>>(
  (ref) => ref.watch(teachingApiProvider).classes(),
);

final classDetailProvider = FutureProvider.autoDispose
    .family<ClassDetail, String>(
      (ref, id) => ref.watch(teachingApiProvider).classDetail(id),
    );

final rosterProvider = FutureProvider.autoDispose
    .family<List<RosterEntry>, String>(
      (ref, id) => ref.watch(teachingApiProvider).roster(id),
    );

final materialsProvider = FutureProvider.autoDispose
    .family<List<CourseMaterial>, String>(
      (ref, id) => ref.watch(teachingApiProvider).materials(id),
    );

final assignmentsProvider = FutureProvider.autoDispose
    .family<List<TeachingAssignment>, String>(
      (ref, id) => ref.watch(teachingApiProvider).assignments(id),
    );

final submissionsProvider = FutureProvider.autoDispose
    .family<SubmissionsResult, String>(
      (ref, id) => ref.watch(teachingApiProvider).submissions(id),
    );

/// Month start (first day) -> events of that month.
final calendarProvider = FutureProvider.autoDispose
    .family<List<CalendarEvent>, DateTime>((ref, month) {
      final last = DateTime(month.year, month.month + 1, 0);
      return ref.watch(teachingApiProvider).calendar(from: month, to: last);
    });

final studentRecordProvider = FutureProvider.autoDispose
    .family<StudentRecord, String>(
      (ref, id) => ref.watch(teachingApiProvider).student(id),
    );

final transcriptProvider = FutureProvider.autoDispose
    .family<Transcript, String>(
      (ref, id) => ref.watch(teachingApiProvider).transcript(id),
    );
