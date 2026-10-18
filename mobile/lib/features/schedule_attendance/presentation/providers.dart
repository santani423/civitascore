import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../data/schedule_attendance_api.dart';
import '../domain/models.dart';

final studentScheduleProvider = FutureProvider.autoDispose<ScheduleOverview>(
  (ref) => ref.watch(scheduleAttendanceApiProvider).schedule(),
);

/// Key: first day of the visible month.
final studentCalendarProvider = FutureProvider.autoDispose
    .family<List<CalendarEvent>, DateTime>((ref, month) {
      final last = DateTime(month.year, month.month + 1, 0);
      return ref
          .watch(scheduleAttendanceApiProvider)
          .calendar(from: DateTime(month.year, month.month, 1), to: last);
    });

/// Key: selected term id (null = backend default).
final studentAttendanceProvider = FutureProvider.autoDispose
    .family<AttendanceSummary, String?>(
      (ref, termId) => ref
          .watch(scheduleAttendanceApiProvider)
          .attendance(termId: termId),
    );

final studentAttendanceDetailProvider = FutureProvider.autoDispose
    .family<AttendanceDetail, String>(
      (ref, id) =>
          ref.watch(scheduleAttendanceApiProvider).attendanceDetail(id),
    );
