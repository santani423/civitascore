import '../../../core/network/api_envelope.dart';
import '../../../core/theme/app_palette.dart';

class KrsTerm {
  const KrsTerm({
    required this.id,
    required this.label,
    this.startDate,
    this.endDate,
    this.krsStartDate,
    this.krsEndDate,
    this.isKrsOpen = false,
  });
  final String id;
  final String label;
  final DateTime? startDate;
  final DateTime? endDate;
  final DateTime? krsStartDate;
  final DateTime? krsEndDate;
  final bool isKrsOpen;

  factory KrsTerm.fromJson(Map<String, dynamic> j) => KrsTerm(
    id: asString(j['id']) ?? '',
    label: asString(j['label']) ?? asString(j['academic_year']) ?? '',
    startDate: asDateTime(j['start_date']),
    endDate: asDateTime(j['end_date']),
    krsStartDate: asDateTime(j['krs_start_date']),
    krsEndDate: asDateTime(j['krs_end_date']),
    isKrsOpen: asBool(j['is_krs_open']),
  );

  static KrsTerm? maybe(Object? v) =>
      v is Map ? KrsTerm.fromJson(asMap(v)) : null;
}

class KrsLecturer {
  const KrsLecturer({required this.id, required this.name, this.email});
  final String id;
  final String name;
  final String? email;
  static KrsLecturer? maybe(Object? v) {
    if (v is! Map) return null;
    final m = asMap(v);
    return KrsLecturer(
      id: asString(m['id']) ?? '',
      name: asString(m['name']) ?? '',
      email: asString(m['email']),
    );
  }
}

class KrsSchedule {
  const KrsSchedule({
    required this.dayOfWeek,
    required this.dayLabel,
    required this.startTime,
    required this.endTime,
    this.room,
  });
  final int dayOfWeek;
  final String dayLabel;
  final String startTime;
  final String endTime;
  final String? room;
  factory KrsSchedule.fromJson(Map<String, dynamic> j) => KrsSchedule(
    dayOfWeek: asInt(j['day_of_week']) ?? 1,
    dayLabel: asString(j['day_label']) ?? '',
    startTime: asString(j['start_time']) ?? '',
    endTime: asString(j['end_time']) ?? '',
    room: asString(j['room']),
  );
}

class KrsCourse {
  const KrsCourse({
    required this.id,
    required this.code,
    required this.name,
    required this.credits,
    this.semesterLevel,
  });
  final String id;
  final String code;
  final String name;
  final int credits;
  final int? semesterLevel;
  factory KrsCourse.fromJson(Map<String, dynamic> j) => KrsCourse(
    id: asString(j['id']) ?? '',
    code: asString(j['code']) ?? '',
    name: asString(j['name']) ?? '',
    credits: asInt(j['credits']) ?? 0,
    semesterLevel: asInt(j['semester_level']),
  );
}

class KrsClassSection {
  const KrsClassSection({
    required this.id,
    required this.classCode,
    required this.course,
    this.lecturer,
    this.schedules = const [],
  });
  final String id;
  final String classCode;
  final KrsCourse course;
  final KrsLecturer? lecturer;
  final List<KrsSchedule> schedules;
  factory KrsClassSection.fromJson(Map<String, dynamic> j) => KrsClassSection(
    id: asString(j['id']) ?? '',
    classCode: asString(j['class_code']) ?? '',
    course: KrsCourse.fromJson(asMap(j['course'])),
    lecturer: KrsLecturer.maybe(j['lecturer']),
    schedules: asMapList(j['schedules']).map(KrsSchedule.fromJson).toList(),
  );
}

class KrsSubmission {
  const KrsSubmission({
    required this.status,
    this.statusLabel,
    this.totalCredits = 0,
    this.maxCredits,
    this.submittedAt,
    this.decidedAt,
    this.decidedBy,
    this.decisionNote,
  });
  final String status;
  final String? statusLabel;
  final int totalCredits;
  final int? maxCredits;
  final DateTime? submittedAt;
  final DateTime? decidedAt;
  final String? decidedBy;
  final String? decisionNote;
  factory KrsSubmission.fromJson(Map<String, dynamic> j) => KrsSubmission(
    status: asString(j['status']) ?? 'draft',
    statusLabel: asString(j['status_label']),
    totalCredits: asInt(j['total_credits']) ?? 0,
    maxCredits: asInt(j['max_credits']),
    submittedAt: asDateTime(j['submitted_at']),
    decidedAt: asDateTime(j['decided_at']),
    decidedBy: asString(j['decided_by']),
    decisionNote: asString(j['decision_note']),
  );
  static KrsSubmission? maybe(Object? v) =>
      v is Map ? KrsSubmission.fromJson(asMap(v)) : null;
}

class KrsItem {
  const KrsItem({
    required this.id,
    required this.status,
    this.statusLabel,
    required this.classSection,
  });
  final String id;
  final String status;
  final String? statusLabel;
  final KrsClassSection classSection;
  factory KrsItem.fromJson(Map<String, dynamic> j) => KrsItem(
    id: asString(j['id']) ?? '',
    status: asString(j['status']) ?? 'draft',
    statusLabel: asString(j['status_label']),
    classSection: KrsClassSection.fromJson(asMap(j['class_section'])),
  );
}

class KrsOverview {
  const KrsOverview({
    this.term,
    required this.status,
    this.statusLabel,
    this.submission,
    this.items = const [],
    this.totalCredits = 0,
    this.maxCredits,
    this.remainingCredits,
    this.canEdit = false,
    this.canSubmit = false,
    this.canCancelSubmission = false,
    this.notices = const [],
    this.academicAdvisor,
  });
  final KrsTerm? term;
  final String status;
  final String? statusLabel;
  final KrsSubmission? submission;
  final List<KrsItem> items;
  final int totalCredits;
  final int? maxCredits;
  final int? remainingCredits;
  final bool canEdit;
  final bool canSubmit;
  final bool canCancelSubmission;
  final List<String> notices;
  final KrsLecturer? academicAdvisor;

  bool get isOverLimit => maxCredits != null && totalCredits > maxCredits!;

  factory KrsOverview.fromJson(Map<String, dynamic> j) => KrsOverview(
    term: KrsTerm.maybe(j['term']),
    status: asString(j['status']) ?? 'no_active_term',
    statusLabel: asString(j['status_label']),
    submission: KrsSubmission.maybe(j['submission']),
    items: asMapList(j['items']).map(KrsItem.fromJson).toList(),
    totalCredits: asInt(j['total_credits']) ?? 0,
    maxCredits: asInt(j['max_credits']),
    remainingCredits: asInt(j['remaining_credits']),
    canEdit: asBool(j['can_edit']),
    canSubmit: asBool(j['can_submit']),
    canCancelSubmission: asBool(j['can_cancel_submission']),
    notices: asStringList(j['notices']),
    academicAdvisor: KrsLecturer.maybe(j['academic_advisor']),
  );
}

class KrsPrerequisite {
  const KrsPrerequisite({
    this.code,
    this.name,
    this.minGrade = '',
    this.satisfied = false,
  });
  final String? code;
  final String? name;
  final String minGrade;
  final bool satisfied;
  String get display => name ?? code ?? '';
  factory KrsPrerequisite.fromJson(Map<String, dynamic> j) => KrsPrerequisite(
    code: asString(j['code']),
    name: asString(j['name']),
    minGrade: asString(j['min_grade']) ?? '',
    satisfied: asBool(j['satisfied']),
  );
}

class KrsOfferingClass {
  const KrsOfferingClass({
    required this.section,
    this.capacity = 0,
    this.seatsTaken = 0,
    this.seatsLeft = 0,
    this.isFull = false,
    this.isSelected = false,
    this.conflicts = const [],
  });
  final KrsClassSection section;
  final int capacity;
  final int seatsTaken;
  final int seatsLeft;
  final bool isFull;
  final bool isSelected;
  final List<String> conflicts;
  factory KrsOfferingClass.fromJson(Map<String, dynamic> j) => KrsOfferingClass(
    section: KrsClassSection.fromJson(j),
    capacity: asInt(j['capacity']) ?? 0,
    seatsTaken: asInt(j['seats_taken']) ?? 0,
    seatsLeft: asInt(j['seats_left']) ?? 0,
    isFull: asBool(j['is_full']),
    isSelected: asBool(j['is_selected']),
    conflicts: asStringList(j['conflicts']),
  );
}

class KrsOffering {
  const KrsOffering({
    required this.course,
    this.prerequisites = const [],
    this.bestGrade,
    this.isRetake = false,
    this.isSelected = false,
    this.selectedClassSectionId,
    this.blockers = const [],
    this.classes = const [],
  });
  final KrsCourse course;
  final List<KrsPrerequisite> prerequisites;
  final String? bestGrade;
  final bool isRetake;
  final bool isSelected;
  final String? selectedClassSectionId;
  final List<String> blockers;
  final List<KrsOfferingClass> classes;
  factory KrsOffering.fromJson(Map<String, dynamic> j) => KrsOffering(
    course: KrsCourse.fromJson(asMap(j['course'])),
    prerequisites: asMapList(
      j['prerequisites'],
    ).map(KrsPrerequisite.fromJson).toList(),
    bestGrade: asString(j['best_grade']),
    isRetake: asBool(j['is_retake']),
    isSelected: asBool(j['is_selected']),
    selectedClassSectionId: asString(j['selected_class_section_id']),
    blockers: asStringList(j['blockers']),
    classes: asMapList(j['classes']).map(KrsOfferingClass.fromJson).toList(),
  );
}

class KrsOfferings {
  const KrsOfferings({
    this.term,
    this.courses = const [],
    this.totalCredits = 0,
    this.maxCredits,
  });
  final KrsTerm? term;
  final List<KrsOffering> courses;
  final int totalCredits;
  final int? maxCredits;
  factory KrsOfferings.fromJson(Map<String, dynamic> j) => KrsOfferings(
    term: KrsTerm.maybe(j['term']),
    courses: asMapList(j['courses']).map(KrsOffering.fromJson).toList(),
    totalCredits: asInt(j['total_credits']) ?? 0,
    maxCredits: asInt(j['max_credits']),
  );
}

class KrsHistoryItem {
  const KrsHistoryItem({
    required this.id,
    required this.status,
    this.statusLabel,
    required this.courseCode,
    required this.courseName,
    this.credits = 0,
    this.classCode,
    this.letterGrade,
  });
  final String id;
  final String status;
  final String? statusLabel;
  final String courseCode;
  final String courseName;
  final int credits;
  final String? classCode;
  final String? letterGrade;
  factory KrsHistoryItem.fromJson(Map<String, dynamic> j) => KrsHistoryItem(
    id: asString(j['id']) ?? '',
    status: asString(j['status']) ?? '',
    statusLabel: asString(j['status_label']),
    courseCode: asString(j['course_code']) ?? '',
    courseName: asString(j['course_name']) ?? '',
    credits: asInt(j['credits']) ?? 0,
    classCode: asString(j['class_code']),
    letterGrade: asString(j['letter_grade']),
  );
}

class KrsHistoryEntry {
  const KrsHistoryEntry({
    this.term,
    required this.status,
    this.statusLabel,
    this.totalCredits = 0,
    this.courseCount = 0,
    this.submission,
    this.items = const [],
  });
  final KrsTerm? term;
  final String status;
  final String? statusLabel;
  final int totalCredits;
  final int courseCount;
  final KrsSubmission? submission;
  final List<KrsHistoryItem> items;
  factory KrsHistoryEntry.fromJson(Map<String, dynamic> j) => KrsHistoryEntry(
    term: KrsTerm.maybe(j['term']),
    status: asString(j['status']) ?? '',
    statusLabel: asString(j['status_label']),
    totalCredits: asInt(j['total_credits']) ?? 0,
    courseCount: asInt(j['course_count']) ?? 0,
    submission: KrsSubmission.maybe(j['submission']),
    items: asMapList(j['items']).map(KrsHistoryItem.fromJson).toList(),
  );
}

/// Tone for a KRS / KRS item status value.
StatusTone krsStatusTone(String status) => switch (status) {
  'approved' || 'enrolled' => StatusTone.success,
  'submitted' || 'pending' => StatusTone.info,
  'rejected' => StatusTone.danger,
  'not_started' => StatusTone.warning,
  'draft' => StatusTone.accent,
  _ => StatusTone.neutral,
};
