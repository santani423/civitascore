import '../../../core/network/api_envelope.dart';

/// Class slot (`today_schedule[]` / `next_class`).
class HomeClassSlot {
  const HomeClassSlot({
    required this.courseName,
    this.courseCode,
    this.classCode,
    this.startTime,
    this.endTime,
    this.room,
    this.lecturer,
    this.status,
    this.date,
    this.startsAt,
    this.dayLabel,
    this.isPendingApproval = false,
  });

  factory HomeClassSlot.fromJson(Map<String, dynamic> j) => HomeClassSlot(
    courseName: asString(j['course_name']) ?? '',
    courseCode: asString(j['course_code']),
    classCode: asString(j['class_code']),
    startTime: asString(j['start_time']),
    endTime: asString(j['end_time']),
    room: asString(j['room']),
    lecturer: asString(asMap(j['lecturer'])['name']),
    status: asString(j['status']),
    date: asDateTime(j['date']),
    startsAt: asDateTime(j['starts_at']),
    dayLabel: asString(j['day_label']),
    isPendingApproval: asBool(j['is_pending_approval']),
  );

  final String courseName;
  final String? courseCode;
  final String? classCode;
  final String? startTime;
  final String? endTime;
  final String? room;
  final String? lecturer;

  /// upcoming | ongoing | finished (today's list only).
  final String? status;
  final DateTime? date;
  final DateTime? startsAt;
  final String? dayLabel;
  final bool isPendingApproval;
}

class HomeProfile {
  const HomeProfile({
    required this.name,
    required this.nim,
    this.studyProgram,
    this.faculty,
    this.semester,
    this.admissionYear,
    this.statusValue,
    this.statusLabel,
  });

  factory HomeProfile.fromJson(Map<String, dynamic> j) {
    final status = asMap(j['status']);
    return HomeProfile(
      name: asString(j['name']) ?? '',
      nim: asString(j['nim']) ?? '',
      studyProgram: asString(j['study_program']),
      faculty: asString(j['faculty']),
      semester: asInt(j['semester']),
      admissionYear: asInt(j['admission_year']),
      statusValue: asString(status['value']),
      statusLabel: asString(status['label']),
    );
  }

  final String name;
  final String nim;
  final String? studyProgram;
  final String? faculty;
  final int? semester;
  final int? admissionYear;
  final String? statusValue;
  final String? statusLabel;
}

class HomeAcademic {
  const HomeAcademic({
    this.ipk,
    this.ips,
    this.ipsTermLabel,
    this.totalCredits,
    this.passedCredits,
    this.currentTermCredits,
    this.maxCredits,
    this.remainingCredits,
    this.termLabel,
    this.advisorName,
  });

  factory HomeAcademic.fromJson(Map<String, dynamic> j) {
    final ips = asMap(j['last_ips']);
    return HomeAcademic(
      ipk: asDouble(j['ipk']),
      ips: asDouble(ips['ips']),
      ipsTermLabel: asString(ips['label']),
      totalCredits: asInt(j['total_credits']),
      passedCredits: asInt(j['passed_credits']),
      currentTermCredits: asInt(j['current_term_credits']),
      maxCredits: asInt(j['max_credits']),
      remainingCredits: asInt(j['remaining_credits']),
      termLabel: asString(asMap(j['current_term'])['label']),
      advisorName: asString(asMap(j['academic_advisor'])['name']),
    );
  }

  final double? ipk;
  final double? ips;
  final String? ipsTermLabel;
  final int? totalCredits;
  final int? passedCredits;
  final int? currentTermCredits;
  final int? maxCredits;

  /// SKS yang masih harus ditempuh untuk kurikulum (null bila belum diketahui).
  final int? remainingCredits;
  final String? termLabel;
  final String? advisorName;
}

class HomeKrs {
  const HomeKrs({
    required this.status,
    this.statusLabel,
    this.totalCredits,
    this.maxCredits,
    this.courseCount,
    this.isOpen = false,
  });

  factory HomeKrs.fromJson(Map<String, dynamic> j) => HomeKrs(
    status: asString(j['status']) ?? '',
    statusLabel: asString(j['status_label']),
    totalCredits: asInt(j['total_credits']),
    maxCredits: asInt(j['max_credits']),
    courseCount: asInt(j['course_count']),
    isOpen: asBool(j['is_open']),
  );

  final String status;
  final String? statusLabel;
  final int? totalCredits;
  final int? maxCredits;
  final int? courseCount;
  final bool isOpen;
}

class HomeAttendanceCourse {
  const HomeAttendanceCourse({
    required this.courseName,
    this.percentage,
    this.krsItemId,
  });

  factory HomeAttendanceCourse.fromJson(Map<String, dynamic> j) =>
      HomeAttendanceCourse(
        courseName: asString(j['course_name']) ?? '',
        percentage: asDouble(j['percentage']),
        krsItemId: asString(j['krs_item_id']),
      );

  final String courseName;
  final double? percentage;
  final String? krsItemId;
}

class HomeAttendance {
  const HomeAttendance({
    this.overallPercentage,
    this.totalMeetings = 0,
    this.minimumPercent,
    this.belowMinimum = const [],
  });

  factory HomeAttendance.fromJson(Map<String, dynamic> j) {
    final overall = asMap(j['overall']);
    return HomeAttendance(
      overallPercentage: asDouble(overall['percentage']),
      totalMeetings: asInt(overall['total_meetings']) ?? 0,
      minimumPercent: asDouble(j['minimum_percent']),
      belowMinimum: asMapList(
        j['below_minimum'],
      ).map(HomeAttendanceCourse.fromJson).toList(),
    );
  }

  final double? overallPercentage;
  final int totalMeetings;
  final double? minimumPercent;
  final List<HomeAttendanceCourse> belowMinimum;
}

class HomeExam {
  const HomeExam({
    required this.id,
    required this.title,
    this.courseName,
    this.startsAt,
    this.durationMinutes,
    this.status,
  });

  factory HomeExam.fromJson(Map<String, dynamic> j) => HomeExam(
    id: asString(j['id']) ?? '',
    title: asString(j['title']) ?? '',
    courseName: asString(j['course_name']),
    startsAt: asDateTime(j['starts_at']),
    durationMinutes: asInt(j['duration_minutes']),
    status: asString(j['status']),
  );

  final String id;
  final String title;
  final String? courseName;
  final DateTime? startsAt;
  final int? durationMinutes;

  /// upcoming | available | in_progress
  final String? status;
}

class HomeAssignment {
  const HomeAssignment({
    required this.id,
    required this.title,
    this.courseName,
    this.dueAt,
  });

  factory HomeAssignment.fromJson(Map<String, dynamic> j) => HomeAssignment(
    id: asString(j['id']) ?? '',
    title: asString(j['title']) ?? '',
    courseName: asString(asMap(j['course'])['name']),
    dueAt: asDateTime(j['due_at']),
  );

  final String id;
  final String title;
  final String? courseName;
  final DateTime? dueAt;
}

class HomeGrade {
  const HomeGrade({
    required this.courseName,
    required this.letterGrade,
    this.courseCode,
  });

  factory HomeGrade.fromJson(Map<String, dynamic> j) => HomeGrade(
    courseName: asString(j['course_name']) ?? '',
    courseCode: asString(j['course_code']),
    letterGrade: asString(j['letter_grade']) ?? '',
  );

  final String courseName;
  final String? courseCode;
  final String letterGrade;
}

class HomeAnnouncement {
  const HomeAnnouncement({
    required this.id,
    required this.title,
    this.publishedAt,
    this.isPinned = false,
    this.isRead = false,
  });

  factory HomeAnnouncement.fromJson(Map<String, dynamic> j) => HomeAnnouncement(
    id: asString(j['id']) ?? '',
    title: asString(j['title']) ?? '',
    publishedAt: asDateTime(j['published_at']),
    isPinned: asBool(j['is_pinned']),
    isRead: asBool(j['is_read']),
  );

  final String id;
  final String title;
  final DateTime? publishedAt;
  final bool isPinned;
  final bool isRead;
}

class HomeAlert {
  const HomeAlert({
    required this.type,
    required this.severity,
    required this.message,
    this.link,
  });

  factory HomeAlert.fromJson(Map<String, dynamic> j) => HomeAlert(
    type: asString(j['type']) ?? '',
    severity: asString(j['severity']) ?? 'info',
    message: asString(j['message']) ?? '',
    link: asString(j['link']),
  );

  final String type;

  /// info | success | warning | danger
  final String severity;
  final String message;

  /// Web path (`/portal/...`); map with `routeForPortalLink`.
  final String? link;
}

/// `GET /student/dashboard` → `data`. Every section is optional so a
/// missing/odd section never breaks the rest of the screen.
class StudentDashboard {
  const StudentDashboard({
    this.profile,
    this.academic,
    this.krs,
    this.todaySchedule = const [],
    this.nextClass,
    this.attendance,
    this.upcomingExams = const [],
    this.assignmentsDue = const [],
    this.recentGrades = const [],
    this.latestAnnouncements = const [],
    this.unreadAnnouncements = 0,
    this.notificationsUnread = 0,
    this.alerts = const [],
  });

  factory StudentDashboard.fromJson(Object? data) {
    final j = asMap(data);
    final ann = asMap(j['announcements']);
    return StudentDashboard(
      profile: j['profile'] is Map
          ? HomeProfile.fromJson(asMap(j['profile']))
          : null,
      academic: j['academic'] is Map
          ? HomeAcademic.fromJson(asMap(j['academic']))
          : null,
      krs: j['krs'] is Map ? HomeKrs.fromJson(asMap(j['krs'])) : null,
      todaySchedule: asMapList(
        j['today_schedule'],
      ).map(HomeClassSlot.fromJson).toList(),
      nextClass: j['next_class'] is Map
          ? HomeClassSlot.fromJson(asMap(j['next_class']))
          : null,
      attendance: j['attendance'] is Map
          ? HomeAttendance.fromJson(asMap(j['attendance']))
          : null,
      upcomingExams: asMapList(
        j['upcoming_exams'],
      ).map(HomeExam.fromJson).toList(),
      assignmentsDue: asMapList(
        j['assignments_due'],
      ).map(HomeAssignment.fromJson).toList(),
      recentGrades: asMapList(
        j['recent_grades'],
      ).map(HomeGrade.fromJson).toList(),
      latestAnnouncements: asMapList(
        ann['latest'],
      ).map(HomeAnnouncement.fromJson).toList(),
      unreadAnnouncements: asInt(ann['unread_count']) ?? 0,
      notificationsUnread: asInt(j['notifications_unread']) ?? 0,
      alerts: asMapList(j['alerts']).map(HomeAlert.fromJson).toList(),
    );
  }

  final HomeProfile? profile;
  final HomeAcademic? academic;
  final HomeKrs? krs;
  final List<HomeClassSlot> todaySchedule;
  final HomeClassSlot? nextClass;
  final HomeAttendance? attendance;
  final List<HomeExam> upcomingExams;
  final List<HomeAssignment> assignmentsDue;
  final List<HomeGrade> recentGrades;
  final List<HomeAnnouncement> latestAnnouncements;
  final int unreadAnnouncements;
  final int notificationsUnread;
  final List<HomeAlert> alerts;
}
