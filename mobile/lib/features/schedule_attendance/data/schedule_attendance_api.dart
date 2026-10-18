import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../core/network/api_client.dart';
import '../domain/models.dart';

final scheduleAttendanceApiProvider = Provider<ScheduleAttendanceApi>(
  (ref) => ScheduleAttendanceApi(ref.watch(apiClientProvider)),
);

class ScheduleAttendanceApi {
  ScheduleAttendanceApi(this._api);
  final ApiClient _api;

  Future<ScheduleOverview> schedule() => _api.get(
    '/student/schedule',
    cache: true,
    decode: ScheduleOverview.fromJson,
  );

  static String _ymd(DateTime d) =>
      '${d.year.toString().padLeft(4, '0')}-${d.month.toString().padLeft(2, '0')}-${d.day.toString().padLeft(2, '0')}';

  /// Backend requires both bounds and rejects ranges over 62 days.
  Future<List<CalendarEvent>> calendar({
    required DateTime from,
    required DateTime to,
  }) => _api.getList(
    '/student/calendar',
    query: {'from': _ymd(from), 'to': _ymd(to)},
    cache: true,
    item: CalendarEvent.fromJson,
  );

  Future<AttendanceSummary> attendance({String? termId}) => _api.get(
    '/student/attendance',
    query: termId == null ? null : {'academic_term_id': termId},
    cache: true,
    decode: AttendanceSummary.fromJson,
  );

  Future<AttendanceDetail> attendanceDetail(String krsItemId) => _api.get(
    '/student/attendance/$krsItemId',
    cache: true,
    decode: AttendanceDetail.fromJson,
  );
}
