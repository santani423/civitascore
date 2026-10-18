import '../../../core/network/api_envelope.dart';

class KrsSchedule {
  const KrsSchedule({
    required this.dayOfWeek,
    this.dayLabel,
    this.startTime,
    this.endTime,
    this.room,
  });

  factory KrsSchedule.fromJson(Map<String, dynamic> j) => KrsSchedule(
    dayOfWeek: asInt(j['day_of_week']) ?? 0,
    dayLabel: asString(j['day_label']),
    startTime: asString(j['start_time']),
    endTime: asString(j['end_time']),
    room: asString(j['room']),
  );

  final int dayOfWeek;
  final String? dayLabel;
  final String? startTime;
  final String? endTime;
  final String? room;
}

class KrsItem {
  const KrsItem({
    required this.id,
    this.status,
    this.statusLabel,
    this.classCode,
    this.courseCode,
    this.courseName,
    this.credits,
    this.lecturerName,
    this.schedules = const [],
  });

  factory KrsItem.fromJson(Map<String, dynamic> j) {
    final cs = asMap(j['class_section']);
    final course = asMap(cs['course']);
    final lecturer = asMap(cs['lecturer']);
    return KrsItem(
      id: asString(j['id']) ?? '',
      status: asString(j['status']),
      statusLabel: asString(j['status_label']),
      classCode: asString(cs['class_code']),
      courseCode: asString(course['code']),
      courseName: asString(course['name']),
      credits: asInt(course['credits']) ?? 0,
      lecturerName: asString(lecturer['name']),
      schedules: asMapList(cs['schedules']).map(KrsSchedule.fromJson).toList(),
    );
  }

  final String id;
  final String? status;
  final String? statusLabel;
  final String? classCode;
  final String? courseCode;
  final String? courseName;
  final int? credits;
  final String? lecturerName;
  final List<KrsSchedule> schedules;
}

class KrsStudent {
  const KrsStudent({
    required this.id,
    this.nim,
    this.name,
    this.admissionYear,
    this.studyProgram,
    this.advisorName,
  });

  factory KrsStudent.fromJson(Map<String, dynamic> j) {
    final sp = j['study_program'];
    return KrsStudent(
      id: asString(j['id']) ?? '',
      nim: asString(j['nim']),
      name: asString(j['name']),
      admissionYear: asInt(j['admission_year']),
      studyProgram: sp is Map ? asString(sp['name']) : asString(sp),
      advisorName: asString(asMap(j['academic_advisor'])['name']),
    );
  }

  final String id;
  final String? nim;
  final String? name;
  final int? admissionYear;
  final String? studyProgram;
  final String? advisorName;
}

class KrsSubmission {
  const KrsSubmission({
    required this.id,
    required this.status,
    this.statusLabel,
    this.totalCredits = 0,
    this.maxCredits,
    this.submittedAt,
    this.decidedAt,
    this.decidedBy,
    this.decisionNote,
    this.termLabel,
    this.student,
    this.items = const [],
    this.ips,
    this.ipk,
  });

  factory KrsSubmission.fromJson(Map<String, dynamic> j) {
    final decidedBy = j['decided_by'];
    final student = asMap(j['student']);
    return KrsSubmission(
      id: asString(j['id']) ?? '',
      status: asString(j['status']) ?? '',
      statusLabel: asString(j['status_label']),
      totalCredits: asInt(j['total_credits']) ?? 0,
      maxCredits: asInt(j['max_credits']),
      submittedAt: asDateTime(j['submitted_at']),
      decidedAt: asDateTime(j['decided_at']),
      decidedBy: decidedBy is Map
          ? asString(decidedBy['name'])
          : asString(decidedBy),
      decisionNote: asString(j['decision_note']),
      termLabel: asString(asMap(j['term'])['label']),
      student: student.isEmpty ? null : KrsStudent.fromJson(student),
      items: asMapList(j['items']).map(KrsItem.fromJson).toList(),
      ips: asDouble(j['ips'] ?? student['ips']),
      ipk: asDouble(j['ipk'] ?? student['ipk']),
    );
  }

  final String id;
  final String status;
  final String? statusLabel;
  final int totalCredits;
  final int? maxCredits;
  final DateTime? submittedAt;
  final DateTime? decidedAt;
  final String? decidedBy;
  final String? decisionNote;
  final String? termLabel;
  final KrsStudent? student;
  final List<KrsItem> items;
  final double? ips;
  final double? ipk;

  bool get isSubmitted => status == 'submitted';
  bool get overLimit => maxCredits != null && totalCredits > maxCredits!;
}
