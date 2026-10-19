import '../../../core/network/api_envelope.dart';

class ClassSlot {
  const ClassSlot({
    required this.id,
    required this.dayOfWeek,
    required this.startTime,
    required this.endTime,
    this.room,
  });
  factory ClassSlot.fromJson(Map<String, dynamic> j) => ClassSlot(
    id: asString(j['id']) ?? '',
    dayOfWeek: asInt(j['day_of_week']) ?? 1,
    startTime: asString(j['start_time']) ?? '',
    endTime: asString(j['end_time']) ?? '',
    room: asString(j['room']),
  );
  final String id;
  final int dayOfWeek;
  final String startTime;
  final String endTime;
  final String? room;
}

/// One entry of `GET /teaching/classes`.
class TeachingClass {
  const TeachingClass({
    required this.id,
    required this.classCode,
    required this.courseCode,
    required this.courseName,
    required this.credits,
    required this.schedules,
    required this.enrolledCount,
    required this.materialsCount,
    required this.assignmentsCount,
    this.capacity,
    this.termLabel,
  });
  factory TeachingClass.fromJson(Map<String, dynamic> j) {
    final course = asMap(j['course']);
    return TeachingClass(
      id: asString(j['id']) ?? '',
      classCode: asString(j['class_code']) ?? '',
      courseCode: asString(course['code']) ?? '',
      courseName: asString(course['name']) ?? '',
      credits: asInt(course['credits']) ?? 0,
      schedules: asMapList(j['schedules']).map(ClassSlot.fromJson).toList(),
      enrolledCount: asInt(j['enrolled_count']) ?? 0,
      materialsCount: asInt(j['materials_count']) ?? 0,
      assignmentsCount: asInt(j['assignments_count']) ?? 0,
      capacity: asInt(j['capacity']),
      termLabel: asString(asMap(j['term'])['label']),
    );
  }
  final String id;
  final String classCode;
  final String courseCode;
  final String courseName;
  final int credits;
  final List<ClassSlot> schedules;
  final int enrolledCount;
  final int materialsCount;
  final int assignmentsCount;
  final int? capacity;
  final String? termLabel;
}

/// `GET /class-sections/{id}` (ClassSectionResource).
class ClassDetail {
  const ClassDetail({
    required this.id,
    required this.classCode,
    required this.courseCode,
    required this.courseName,
    required this.credits,
    required this.schedules,
    this.studyProgram,
    this.termLabel,
    this.capacity,
    this.enrolledCount,
    this.lecturerName,
  });
  factory ClassDetail.fromJson(Map<String, dynamic> j) => ClassDetail(
    id: asString(j['id']) ?? '',
    classCode: asString(j['class_code']) ?? '',
    courseCode: asString(j['course_code']) ?? '',
    courseName: asString(j['course_name']) ?? '',
    credits: asInt(j['credits']) ?? 0,
    schedules: asMapList(j['schedules']).map(ClassSlot.fromJson).toList(),
    studyProgram: asString(j['study_program_name']),
    termLabel: asString(j['academic_term_label']),
    capacity: asInt(j['capacity']),
    enrolledCount: asInt(j['enrolled_count']),
    lecturerName: asString(asMap(j['lecturer'])['name']),
  );
  final String id;
  final String classCode;
  final String courseCode;
  final String courseName;
  final int credits;
  final List<ClassSlot> schedules;
  final String? studyProgram;
  final String? termLabel;
  final int? capacity;
  final int? enrolledCount;
  final String? lecturerName;
}

/// KRS item row of the roster.
class RosterEntry {
  const RosterEntry({
    required this.krsItemId,
    required this.studentId,
    required this.name,
    required this.nim,
    this.letterGrade,
  });
  factory RosterEntry.fromJson(Map<String, dynamic> j) => RosterEntry(
    krsItemId: asString(j['id']) ?? '',
    studentId: asString(j['student_id']) ?? '',
    name: asString(j['student_name']) ?? '',
    nim: asString(j['student_nim']) ?? '',
    letterGrade: asString(j['letter_grade']),
  );
  final String krsItemId;
  final String studentId;
  final String name;
  final String nim;
  final String? letterGrade;
}

class FileRef {
  const FileRef({required this.id, required this.name, this.sizeBytes});
  static FileRef? maybe(Object? v) {
    if (v is! Map) return null;
    final m = asMap(v);
    final id = asString(m['id']);
    if (id == null) return null;
    return FileRef(
      id: id,
      name: asString(m['name']) ?? id,
      sizeBytes: asInt(m['size_bytes']),
    );
  }

  final String id;
  final String name;
  final int? sizeBytes;
}

class CourseMaterial {
  const CourseMaterial({
    required this.id,
    required this.title,
    required this.type,
    required this.isPublished,
    this.typeLabel,
    this.meetingNumber,
    this.description,
    this.url,
    this.file,
  });
  factory CourseMaterial.fromJson(Map<String, dynamic> j) => CourseMaterial(
    id: asString(j['id']) ?? '',
    title: asString(j['title']) ?? '',
    type: asString(j['type']) ?? 'file',
    typeLabel: asString(j['type_label']),
    meetingNumber: asInt(j['meeting_number']),
    description: asString(j['description']),
    url: asString(j['url']),
    file: FileRef.maybe(j['file']),
    isPublished: asBool(j['is_published']),
  );
  final String id;
  final String title;
  final String type;
  final String? typeLabel;
  final int? meetingNumber;
  final String? description;
  final String? url;
  final FileRef? file;
  final bool isPublished;
}

class TeachingAssignment {
  const TeachingAssignment({
    required this.id,
    required this.title,
    required this.allowLate,
    required this.allowResubmission,
    required this.maxFileSizeMb,
    required this.allowedExtensions,
    required this.maxScore,
    required this.isPublished,
    required this.submissionsCount,
    required this.gradedCount,
    this.description,
    this.dueAt,
    this.attachment,
  });
  factory TeachingAssignment.fromJson(Map<String, dynamic> j) =>
      TeachingAssignment(
        id: asString(j['id']) ?? '',
        title: asString(j['title']) ?? '',
        description: asString(j['description']),
        dueAt: asDateTime(j['due_at']),
        allowLate: asBool(j['allow_late_submission']),
        allowResubmission: asBool(j['allow_resubmission']),
        maxFileSizeMb: asInt(j['max_file_size_mb']) ?? 10,
        allowedExtensions: asStringList(j['allowed_extensions']),
        maxScore: asDouble(j['max_score']) ?? 100,
        isPublished: asBool(j['is_published']),
        attachment: FileRef.maybe(j['attachment']),
        submissionsCount: asInt(j['submissions_count']) ?? 0,
        gradedCount: asInt(j['graded_count']) ?? 0,
      );
  final String id;
  final String title;
  final String? description;
  final DateTime? dueAt;
  final bool allowLate;
  final bool allowResubmission;
  final int maxFileSizeMb;
  final List<String> allowedExtensions;
  final double maxScore;
  final bool isPublished;
  final FileRef? attachment;
  final int submissionsCount;
  final int gradedCount;
}

class SubmissionDetail {
  const SubmissionDetail({
    required this.id,
    required this.isLate,
    required this.submissionCount,
    this.notes,
    this.submittedAt,
    this.score,
    this.feedback,
    this.gradedAt,
    this.gradedBy,
    this.file,
  });
  factory SubmissionDetail.fromJson(Map<String, dynamic> j) => SubmissionDetail(
    id: asString(j['id']) ?? '',
    notes: asString(j['notes']),
    submittedAt: asDateTime(j['submitted_at']),
    isLate: asBool(j['is_late']),
    submissionCount: asInt(j['submission_count']) ?? 1,
    score: asDouble(j['score']),
    feedback: asString(j['feedback']),
    gradedAt: asDateTime(j['graded_at']),
    gradedBy: asString(j['graded_by']),
    file: FileRef.maybe(j['file']),
  );
  final String id;
  final String? notes;
  final DateTime? submittedAt;
  final bool isLate;
  final int submissionCount;
  final double? score;
  final String? feedback;
  final DateTime? gradedAt;
  final String? gradedBy;
  final FileRef? file;
}

class SubmissionRow {
  const SubmissionRow({
    required this.krsItemId,
    required this.studentId,
    required this.nim,
    required this.name,
    required this.status,
    this.submission,
  });
  factory SubmissionRow.fromJson(Map<String, dynamic> j) {
    final s = asMap(j['student']);
    return SubmissionRow(
      krsItemId: asString(j['krs_item_id']) ?? '',
      studentId: asString(s['id']) ?? '',
      nim: asString(s['nim']) ?? '',
      name: asString(s['name']) ?? '',
      status: asString(j['status']) ?? 'not_submitted',
      submission: j['submission'] is Map
          ? SubmissionDetail.fromJson(asMap(j['submission']))
          : null,
    );
  }
  final String krsItemId;
  final String studentId;
  final String nim;
  final String name;
  final String status;
  final SubmissionDetail? submission;
}

class SubmissionsResult {
  const SubmissionsResult({required this.assignment, required this.rows});
  factory SubmissionsResult.fromJson(dynamic data) {
    final m = asMap(data);
    return SubmissionsResult(
      assignment: TeachingAssignment.fromJson(asMap(m['assignment'])),
      rows: asMapList(m['submissions']).map(SubmissionRow.fromJson).toList(),
    );
  }
  final TeachingAssignment assignment;
  final List<SubmissionRow> rows;
}

class CalendarEvent {
  const CalendarEvent({
    required this.id,
    required this.title,
    this.description,
    this.category,
    this.categoryLabel,
    this.startDate,
    this.endDate,
  });
  factory CalendarEvent.fromJson(Map<String, dynamic> j) => CalendarEvent(
    id: asString(j['id']) ?? '',
    title: asString(j['title']) ?? '',
    description: asString(j['description']),
    category: asString(j['category']),
    categoryLabel: asString(j['category_label']),
    startDate: asDateTime(asString(j['start_date'])),
    endDate: asDateTime(asString(j['end_date'])),
  );
  final String id;
  final String title;
  final String? description;
  final String? category;
  final String? categoryLabel;
  final DateTime? startDate;
  final DateTime? endDate;
}

class StudentRecord {
  const StudentRecord({
    required this.id,
    required this.nim,
    required this.name,
    required this.status,
    this.email,
    this.studyProgram,
    this.admissionYear,
  });
  factory StudentRecord.fromJson(Map<String, dynamic> j) => StudentRecord(
    id: asString(j['id']) ?? '',
    nim: asString(j['nim']) ?? '',
    name: asString(j['name']) ?? '',
    status: asString(j['status']) ?? '',
    email: asString(j['email']),
    studyProgram: asString(j['study_program_name']),
    admissionYear: asInt(j['admission_year']),
  );
  final String id;
  final String nim;
  final String name;
  final String status;
  final String? email;
  final String? studyProgram;
  final int? admissionYear;
}

class TranscriptTerm {
  const TranscriptTerm({required this.label, required this.sks, this.ip});
  factory TranscriptTerm.fromJson(Map<String, dynamic> j) => TranscriptTerm(
    label: asString(j['label']) ?? '',
    sks: asInt(j['sks']) ?? 0,
    ip: asDouble(j['ip']),
  );
  final String label;
  final int sks;
  final double? ip;
}

class Transcript {
  const Transcript({
    required this.terms,
    required this.ipk,
    required this.totalSks,
    required this.passedSks,
  });
  factory Transcript.fromJson(dynamic data) {
    final m = asMap(data);
    return Transcript(
      terms: asMapList(m['terms']).map(TranscriptTerm.fromJson).toList(),
      ipk: asDouble(m['ipk']) ?? 0,
      totalSks: asInt(m['total_sks']) ?? 0,
      passedSks: asInt(m['passed_sks']) ?? 0,
    );
  }
  final List<TranscriptTerm> terms;
  final double ipk;
  final int totalSks;
  final int passedSks;
}

/// Flattened weekly slot for the timetable.
class WeeklySlot {
  const WeeklySlot({required this.cls, required this.slot});
  final TeachingClass cls;
  final ClassSlot slot;
}

List<WeeklySlot> slotsForDay(List<TeachingClass> classes, int isoDay) {
  final list = <WeeklySlot>[
    for (final c in classes)
      for (final s in c.schedules)
        if (s.dayOfWeek == isoDay) WeeklySlot(cls: c, slot: s),
  ]..sort((a, b) => a.slot.startTime.compareTo(b.slot.startTime));
  return list;
}
