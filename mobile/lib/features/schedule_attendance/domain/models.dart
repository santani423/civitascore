import '../../../core/network/api_envelope.dart';

class TermInfo {
  const TermInfo({required this.id, required this.label, this.isCurrent});
  final String id;
  final String label;
  final bool? isCurrent;

  factory TermInfo.fromJson(Map<String, dynamic> j) => TermInfo(
    id: asString(j['id']) ?? '',
    label: asString(j['label']) ?? '',
    isCurrent: j['is_current'] == null ? null : asBool(j['is_current']),
  );

  static TermInfo? maybe(Object? v) {
    final m = asMap(v);
    return m.isEmpty ? null : TermInfo.fromJson(m);
  }
}

class ScheduleSlot {
  const ScheduleSlot({
    required this.dayOfWeek,
    this.startTime,
    this.endTime,
    this.room,
    this.krsItemId,
    this.isPendingApproval = false,
    this.classCode,
    this.courseCode,
    required this.courseName,
    this.credits,
    this.lecturer,
    this.date,
    this.status,
    this.startsAt,
  });

  final int dayOfWeek;
  final String? startTime;
  final String? endTime;
  final String? room;
  final String? krsItemId;
  final bool isPendingApproval;
  final String? classCode;
  final String? courseCode;
  final String courseName;
  final int? credits;
  final String? lecturer;
  final DateTime? date;

  /// upcoming | ongoing | finished (only on `today` slots).
  final String? status;
  final DateTime? startsAt;

  factory ScheduleSlot.fromJson(Map<String, dynamic> j) => ScheduleSlot(
    dayOfWeek: asInt(j['day_of_week']) ?? 1,
    startTime: asString(j['start_time']),
    endTime: asString(j['end_time']),
    room: asString(j['room']),
    krsItemId: asString(j['krs_item_id']),
    isPendingApproval: asBool(j['is_pending_approval']),
    classCode: asString(j['class_code']),
    courseCode: asString(j['course_code']),
    courseName: asString(j['course_name']) ?? '',
    credits: asInt(j['credits']),
    lecturer: asString(j['lecturer']),
    date: asDateTime(j['date']),
    status: asString(j['status']),
    startsAt: asDateTime(j['starts_at']),
  );
}

class ScheduleOverview {
  const ScheduleOverview({
    this.term,
    this.now,
    this.isInTerm = false,
    this.slots = const [],
    this.today = const [],
    this.nextClass,
  });

  final TermInfo? term;
  final DateTime? now;
  final bool isInTerm;
  final List<ScheduleSlot> slots;
  final List<ScheduleSlot> today;
  final ScheduleSlot? nextClass;

  factory ScheduleOverview.fromJson(Object? data) {
    final j = asMap(data);
    final next = asMap(j['next_class']);
    return ScheduleOverview(
      term: TermInfo.maybe(j['term']),
      now: asDateTime(j['now']),
      isInTerm: asBool(j['is_in_term']),
      slots: asMapList(j['slots']).map(ScheduleSlot.fromJson).toList(),
      today: asMapList(j['today']).map(ScheduleSlot.fromJson).toList(),
      nextClass: next.isEmpty ? null : ScheduleSlot.fromJson(next),
    );
  }

  List<ScheduleSlot> slotsForDay(int isoWeekday) =>
      slots.where((s) => s.dayOfWeek == isoWeekday).toList();
}

class CalendarEvent {
  const CalendarEvent({
    required this.id,
    required this.type,
    required this.category,
    this.categoryLabel,
    required this.title,
    this.description,
    this.start,
    this.end,
    this.allDay = false,
    this.location,
  });

  final String id;
  final String type;
  final String category;
  final String? categoryLabel;
  final String title;
  final String? description;
  final DateTime? start;
  final DateTime? end;
  final bool allDay;
  final String? location;

  factory CalendarEvent.fromJson(Map<String, dynamic> j) => CalendarEvent(
    id: asString(j['id']) ?? '',
    type: asString(j['type']) ?? '',
    category: asString(j['category']) ?? 'other',
    categoryLabel: asString(j['category_label']),
    title: asString(j['title']) ?? '',
    description: asString(j['description']),
    start: asDateTime(j['start']),
    end: asDateTime(j['end']),
    allDay: asBool(j['all_day']),
    location: asString(j['location']),
  );
}

class AttendanceTotals {
  const AttendanceTotals({
    this.totalMeetings = 0,
    this.present = 0,
    this.permitted = 0,
    this.sick = 0,
    this.absent = 0,
    this.percentage,
  });

  final int totalMeetings;
  final int present;
  final int permitted;
  final int sick;
  final int absent;
  final double? percentage;

  factory AttendanceTotals.fromJson(Object? data) {
    final j = asMap(data);
    return AttendanceTotals(
      totalMeetings: asInt(j['total_meetings']) ?? 0,
      present: asInt(j['present']) ?? 0,
      permitted: asInt(j['permitted']) ?? 0,
      sick: asInt(j['sick']) ?? 0,
      absent: asInt(j['absent']) ?? 0,
      percentage: asDouble(j['percentage']),
    );
  }
}

class AttendanceCourse {
  const AttendanceCourse({
    required this.krsItemId,
    this.courseCode,
    required this.courseName,
    this.classCode,
    this.credits,
    this.lecturer,
    required this.totals,
    this.isBelowMinimum = false,
  });

  final String krsItemId;
  final String? courseCode;
  final String courseName;
  final String? classCode;
  final int? credits;
  final String? lecturer;
  final AttendanceTotals totals;
  final bool isBelowMinimum;

  factory AttendanceCourse.fromJson(Map<String, dynamic> j) => AttendanceCourse(
    krsItemId: asString(j['krs_item_id']) ?? '',
    courseCode: asString(j['course_code']),
    courseName: asString(j['course_name']) ?? '',
    classCode: asString(j['class_code']),
    credits: asInt(j['credits']),
    lecturer: asString(j['lecturer']),
    totals: AttendanceTotals.fromJson(j),
    isBelowMinimum: asBool(j['is_below_minimum']),
  );
}

class AttendanceSummary {
  const AttendanceSummary({
    this.term,
    this.terms = const [],
    this.minimumPercent,
    this.courses = const [],
    this.overall = const AttendanceTotals(),
  });

  final TermInfo? term;
  final List<TermInfo> terms;
  final double? minimumPercent;
  final List<AttendanceCourse> courses;
  final AttendanceTotals overall;

  factory AttendanceSummary.fromJson(Object? data) {
    final j = asMap(data);
    return AttendanceSummary(
      term: TermInfo.maybe(j['term']),
      terms: asMapList(j['terms']).map(TermInfo.fromJson).toList(),
      minimumPercent: asDouble(j['minimum_percent']),
      courses: asMapList(j['courses']).map(AttendanceCourse.fromJson).toList(),
      overall: AttendanceTotals.fromJson(j['overall']),
    );
  }

  List<AttendanceCourse> get belowMinimum =>
      courses.where((c) => c.isBelowMinimum).toList();
}

class AttendanceMeeting {
  const AttendanceMeeting({
    this.number,
    this.date,
    required this.status,
    this.statusLabel,
    this.notes,
  });

  final int? number;
  final DateTime? date;
  final String status;
  final String? statusLabel;
  final String? notes;

  factory AttendanceMeeting.fromJson(Map<String, dynamic> j) =>
      AttendanceMeeting(
        number: asInt(j['meeting_number']),
        date: asDateTime(j['meeting_date']),
        status: asString(j['status']) ?? '',
        statusLabel: asString(j['status_label']),
        notes: asString(j['notes']),
      );
}

class AttendanceDetail {
  const AttendanceDetail({
    required this.krsItemId,
    this.term,
    this.courseCode,
    required this.courseName,
    this.classCode,
    this.lecturer,
    this.minimumPercent,
    required this.totals,
    this.meetings = const [],
  });

  final String krsItemId;
  final TermInfo? term;
  final String? courseCode;
  final String courseName;
  final String? classCode;
  final String? lecturer;
  final double? minimumPercent;
  final AttendanceTotals totals;
  final List<AttendanceMeeting> meetings;

  bool get isBelowMinimum =>
      minimumPercent != null &&
      totals.percentage != null &&
      totals.percentage! < minimumPercent!;

  factory AttendanceDetail.fromJson(Object? data) {
    final j = asMap(data);
    return AttendanceDetail(
      krsItemId: asString(j['krs_item_id']) ?? '',
      term: TermInfo.maybe(j['term']),
      courseCode: asString(j['course_code']),
      courseName: asString(j['course_name']) ?? '',
      classCode: asString(j['class_code']),
      lecturer: asString(j['lecturer']),
      minimumPercent: asDouble(j['minimum_percent']),
      totals: AttendanceTotals.fromJson(j),
      meetings: asMapList(
        j['meetings'],
      ).map(AttendanceMeeting.fromJson).toList(),
    );
  }
}
