import '../../../core/network/api_envelope.dart';

class LearningTerm {
  const LearningTerm({required this.id, required this.label});
  factory LearningTerm.fromJson(Map<String, dynamic> j) => LearningTerm(
    id: asString(j['id']) ?? '',
    label: asString(j['label']) ?? '',
  );
  final String id;
  final String label;
}

class ClassSchedule {
  const ClassSchedule({
    required this.dayLabel,
    required this.startTime,
    required this.endTime,
    this.room,
  });
  factory ClassSchedule.fromJson(Map<String, dynamic> j) => ClassSchedule(
    dayLabel: asString(j['day_label']) ?? '',
    startTime: asString(j['start_time']) ?? '',
    endTime: asString(j['end_time']) ?? '',
    room: asString(j['room']),
  );
  final String dayLabel;
  final String startTime;
  final String endTime;
  final String? room;

  /// "Senin 08:00–09:40 (R.201)"
  String get text {
    final t = '$dayLabel $startTime–$endTime';
    return room == null || room!.isEmpty ? t : '$t ($room)';
  }
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
  factory AttendanceTotals.fromJson(Object? raw) {
    final j = asMap(raw);
    return AttendanceTotals(
      totalMeetings: asInt(j['total_meetings']) ?? 0,
      present: asInt(j['present']) ?? 0,
      permitted: asInt(j['permitted']) ?? 0,
      sick: asInt(j['sick']) ?? 0,
      absent: asInt(j['absent']) ?? 0,
      percentage: asDouble(j['percentage']),
    );
  }
  final int totalMeetings;
  final int present;
  final int permitted;
  final int sick;
  final int absent;
  final double? percentage;
}

/// Class section fields shared by every LMS payload.
class ClassInfo {
  const ClassInfo({
    required this.id,
    required this.classCode,
    required this.courseCode,
    required this.courseName,
    this.credits,
    this.lecturerName,
    this.lecturerEmail,
    this.schedules = const [],
  });
  factory ClassInfo.fromJson(Map<String, dynamic> j) {
    final course = asMap(j['course']);
    final lecturer = asMap(j['lecturer']);
    return ClassInfo(
      id: asString(j['id']) ?? '',
      classCode: asString(j['class_code']) ?? '',
      courseCode: asString(course['code']) ?? '',
      courseName: asString(course['name']) ?? '',
      credits: asInt(course['credits']),
      lecturerName: asString(lecturer['name']),
      lecturerEmail: asString(lecturer['email']),
      schedules: asMapList(j['schedules']).map(ClassSchedule.fromJson).toList(),
    );
  }
  final String id;
  final String classCode;
  final String courseCode;
  final String courseName;
  final int? credits;
  final String? lecturerName;
  final String? lecturerEmail;
  final List<ClassSchedule> schedules;
}

class CourseSummary {
  const CourseSummary({
    required this.info,
    required this.materialsCount,
    required this.assignmentsCount,
    required this.pendingAssignmentsCount,
    required this.attendance,
  });
  factory CourseSummary.fromJson(Map<String, dynamic> j) => CourseSummary(
    info: ClassInfo.fromJson(j),
    materialsCount: asInt(j['materials_count']) ?? 0,
    assignmentsCount: asInt(j['assignments_count']) ?? 0,
    pendingAssignmentsCount: asInt(j['pending_assignments_count']) ?? 0,
    attendance: AttendanceTotals.fromJson(j['attendance']),
  );
  final ClassInfo info;
  final int materialsCount;
  final int assignmentsCount;
  final int pendingAssignmentsCount;
  final AttendanceTotals attendance;
}

class CoursesOverview {
  const CoursesOverview({this.term, this.courses = const []});
  factory CoursesOverview.fromJson(Object? raw) {
    final j = asMap(raw);
    return CoursesOverview(
      term: j['term'] is Map ? LearningTerm.fromJson(asMap(j['term'])) : null,
      courses: asMapList(j['courses']).map(CourseSummary.fromJson).toList(),
    );
  }
  final LearningTerm? term;
  final List<CourseSummary> courses;
}

enum LmsMaterialType { file, link, video, text }

LmsMaterialType lmsMaterialTypeOf(String? v) => switch (v) {
  'link' => LmsMaterialType.link,
  'video' => LmsMaterialType.video,
  'text' => LmsMaterialType.text,
  _ => LmsMaterialType.file,
};

class FileRef {
  const FileRef({required this.id, required this.name, this.sizeBytes});
  static FileRef? tryParse(Object? raw) {
    if (raw is! Map) return null;
    final j = asMap(raw);
    final id = asString(j['id']);
    if (id == null) return null;
    return FileRef(
      id: id,
      name: asString(j['name']) ?? 'file',
      sizeBytes: asInt(j['size_bytes']),
    );
  }

  final String id;
  final String name;
  final int? sizeBytes;

  /// Authenticated download endpoint (Bearer header).
  String get downloadPath => '/file-uploads/$id/download';
}

class CourseMaterial {
  const CourseMaterial({
    required this.id,
    required this.title,
    required this.type,
    required this.typeLabel,
    this.meetingNumber,
    this.description,
    this.url,
    this.file,
    this.publishedAt,
  });
  factory CourseMaterial.fromJson(Map<String, dynamic> j) => CourseMaterial(
    id: asString(j['id']) ?? '',
    title: asString(j['title']) ?? '',
    type: lmsMaterialTypeOf(asString(j['type'])),
    typeLabel: asString(j['type_label']) ?? '',
    meetingNumber: asInt(j['meeting_number']),
    description: asString(j['description']),
    url: asString(j['url']),
    file: FileRef.tryParse(j['file']),
    publishedAt: asDateTime(j['published_at']),
  );
  final String id;
  final String title;
  final LmsMaterialType type;
  final String typeLabel;
  final int? meetingNumber;
  final String? description;
  final String? url;
  final FileRef? file;
  final DateTime? publishedAt;
}

class CourseMaterials {
  const CourseMaterials({required this.info, required this.materials});
  factory CourseMaterials.fromJson(Map<String, dynamic> j) => CourseMaterials(
    info: ClassInfo.fromJson(j),
    materials: asMapList(j['materials']).map(CourseMaterial.fromJson).toList(),
  );
  final ClassInfo info;
  final List<CourseMaterial> materials;
}

class MaterialsOverview {
  const MaterialsOverview({this.term, this.courses = const []});
  factory MaterialsOverview.fromJson(Object? raw) {
    final j = asMap(raw);
    return MaterialsOverview(
      term: j['term'] is Map ? LearningTerm.fromJson(asMap(j['term'])) : null,
      courses: asMapList(j['courses']).map(CourseMaterials.fromJson).toList(),
    );
  }
  final LearningTerm? term;
  final List<CourseMaterials> courses;
}

enum AssignmentStatus {
  notSubmitted('not_submitted'),
  submitted('submitted'),
  late('late'),
  graded('graded'),
  missed('missed');

  const AssignmentStatus(this.value);
  final String value;

  static AssignmentStatus? tryParse(String? v) {
    for (final s in values) {
      if (s.value == v) return s;
    }
    return null;
  }
}

class AssignmentSubmission {
  const AssignmentSubmission({
    required this.id,
    this.notes,
    this.submittedAt,
    this.isLate = false,
    this.submissionCount = 1,
    this.score,
    this.feedback,
    this.gradedAt,
    this.gradedBy,
    this.file,
  });
  factory AssignmentSubmission.fromJson(Map<String, dynamic> j) =>
      AssignmentSubmission(
        id: asString(j['id']) ?? '',
        notes: asString(j['notes']),
        submittedAt: asDateTime(j['submitted_at']),
        isLate: asBool(j['is_late']),
        submissionCount: asInt(j['submission_count']) ?? 1,
        score: asString(j['score']),
        feedback: asString(j['feedback']),
        gradedAt: asDateTime(j['graded_at']),
        gradedBy: asString(j['graded_by']),
        file: FileRef.tryParse(j['file']),
      );
  final String id;
  final String? notes;
  final DateTime? submittedAt;
  final bool isLate;
  final int submissionCount;

  /// Decimal string from the backend ("90.00").
  final String? score;
  final String? feedback;
  final DateTime? gradedAt;
  final String? gradedBy;
  final FileRef? file;
}

class AssignmentItem {
  const AssignmentItem({
    required this.id,
    required this.classSectionId,
    required this.title,
    required this.statusValue,
    required this.statusLabel,
    this.description,
    this.dueAt,
    this.allowLate = false,
    this.allowResubmission = false,
    this.maxFileSizeMb = 10,
    this.allowedExtensions = const [],
    this.maxScore,
    this.attachment,
    this.course,
    this.isPastDue = false,
    this.canSubmit = false,
    this.submission,
  });
  factory AssignmentItem.fromJson(Map<String, dynamic> j) {
    final course = j['course'];
    return AssignmentItem(
      id: asString(j['id']) ?? '',
      classSectionId: asString(j['class_section_id']) ?? '',
      title: asString(j['title']) ?? '',
      description: asString(j['description']),
      dueAt: asDateTime(j['due_at']),
      allowLate: asBool(j['allow_late_submission']),
      allowResubmission: asBool(j['allow_resubmission']),
      maxFileSizeMb: asInt(j['max_file_size_mb']) ?? 10,
      allowedExtensions: asStringList(j['allowed_extensions']),
      maxScore: asString(j['max_score']),
      attachment: FileRef.tryParse(j['attachment']),
      course: course is Map ? AssignmentCourse.fromJson(asMap(course)) : null,
      statusValue: asString(j['status']) ?? 'not_submitted',
      statusLabel: asString(j['status_label']) ?? '',
      isPastDue: asBool(j['is_past_due']),
      canSubmit: asBool(j['can_submit']),
      submission: j['submission'] is Map
          ? AssignmentSubmission.fromJson(asMap(j['submission']))
          : null,
    );
  }
  final String id;
  final String classSectionId;
  final String title;
  final String? description;
  final DateTime? dueAt;
  final bool allowLate;
  final bool allowResubmission;
  final int maxFileSizeMb;
  final List<String> allowedExtensions;
  final String? maxScore;
  final FileRef? attachment;
  final AssignmentCourse? course;
  final String statusValue;
  final String statusLabel;
  final bool isPastDue;
  final bool canSubmit;
  final AssignmentSubmission? submission;

  AssignmentStatus? get status => AssignmentStatus.tryParse(statusValue);
  bool get isGraded => status == AssignmentStatus.graded;
  bool get hasSubmission => submission != null;
}

class AssignmentCourse {
  const AssignmentCourse({
    required this.code,
    required this.name,
    this.classCode,
    this.lecturer,
  });
  factory AssignmentCourse.fromJson(Map<String, dynamic> j) {
    final lec = j['lecturer'];
    return AssignmentCourse(
      code: asString(j['code']) ?? '',
      name: asString(j['name']) ?? '',
      classCode: asString(j['class_code']),
      lecturer: lec is Map ? asString(lec['name']) : asString(lec),
    );
  }
  final String code;
  final String name;
  final String? classCode;
  final String? lecturer;
}

class AssignmentsOverview {
  const AssignmentsOverview({
    this.term,
    this.assignments = const [],
    this.counts = const {},
  });
  factory AssignmentsOverview.fromJson(Object? raw) {
    final j = asMap(raw);
    final counts = asMap(j['counts']);
    return AssignmentsOverview(
      term: j['term'] is Map ? LearningTerm.fromJson(asMap(j['term'])) : null,
      assignments: asMapList(
        j['assignments'],
      ).map(AssignmentItem.fromJson).toList(),
      counts: {
        for (final e in counts.entries) e.key.toString(): asInt(e.value) ?? 0,
      },
    );
  }
  final LearningTerm? term;
  final List<AssignmentItem> assignments;
  final Map<String, int> counts;
  int count(String key) => counts[key] ?? 0;
}

class MeetingGroup {
  const MeetingGroup({
    this.meetingNumber,
    required this.label,
    required this.materials,
  });
  factory MeetingGroup.fromJson(Map<String, dynamic> j) => MeetingGroup(
    meetingNumber: asInt(j['meeting_number']),
    label: asString(j['label']) ?? '',
    materials: asMapList(j['materials']).map(CourseMaterial.fromJson).toList(),
  );
  final int? meetingNumber;
  final String label;
  final List<CourseMaterial> materials;
}

class CourseExam {
  const CourseExam({
    required this.id,
    required this.title,
    this.startsAt,
    this.endsAt,
    this.durationMinutes,
  });
  factory CourseExam.fromJson(Map<String, dynamic> j) => CourseExam(
    id: asString(j['id']) ?? '',
    title: asString(j['title']) ?? '',
    startsAt: asDateTime(j['starts_at']),
    endsAt: asDateTime(j['ends_at']),
    durationMinutes: asInt(j['duration_minutes']),
  );
  final String id;
  final String title;
  final DateTime? startsAt;
  final DateTime? endsAt;
  final int? durationMinutes;
}

class CourseDetail {
  const CourseDetail({
    required this.info,
    this.term,
    this.meetings = const [],
    this.assignments = const [],
    this.exams = const [],
    this.attendance = const AttendanceTotals(),
  });
  factory CourseDetail.fromJson(Object? raw) {
    final j = asMap(raw);
    return CourseDetail(
      info: ClassInfo.fromJson(j),
      term: j['term'] is Map ? LearningTerm.fromJson(asMap(j['term'])) : null,
      meetings: asMapList(j['meetings']).map(MeetingGroup.fromJson).toList(),
      assignments: asMapList(
        j['assignments'],
      ).map(AssignmentItem.fromJson).toList(),
      exams: asMapList(j['exams']).map(CourseExam.fromJson).toList(),
      attendance: AttendanceTotals.fromJson(j['attendance']),
    );
  }
  final ClassInfo info;
  final LearningTerm? term;
  final List<MeetingGroup> meetings;
  final List<AssignmentItem> assignments;
  final List<CourseExam> exams;
  final AttendanceTotals attendance;
}
