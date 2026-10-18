import '../../../core/network/api_envelope.dart';
import '../../../core/theme/app_palette.dart';

class TermRef {
  const TermRef({
    required this.id,
    required this.label,
    this.isCurrent = false,
  });
  factory TermRef.fromJson(Map<String, dynamic> j) => TermRef(
    id: asString(j['id'] ?? j['academic_term_id']) ?? '',
    label: asString(j['label']) ?? '',
    isCurrent: asBool(j['is_current']),
  );
  final String id;
  final String label;
  final bool isCurrent;
}

class NamedValue {
  const NamedValue({required this.value, required this.label});
  factory NamedValue.fromJson(Map<String, dynamic> j) => NamedValue(
    value: asString(j['value']) ?? '',
    label: asString(j['label']) ?? asString(j['value']) ?? '',
  );
  final String value;
  final String label;
}

class LastIps {
  const LastIps({required this.label, this.ips});
  factory LastIps.fromJson(Map<String, dynamic> j) =>
      LastIps(label: asString(j['label']) ?? '', ips: asDouble(j['ips']));
  final String label;
  final double? ips;
}

class AcademicSummary {
  const AcademicSummary({
    required this.nim,
    required this.name,
    required this.studyProgram,
    this.degreeLevel,
    this.faculty,
    this.admissionYear,
    this.semester,
    this.status,
    this.advisorName,
    this.curriculumName,
    this.currentTerm,
    this.ipk = 0,
    this.lastIps,
    this.totalCredits = 0,
    this.passedCredits = 0,
    this.remainingCredits,
    this.currentTermCredits = 0,
    this.maxCredits,
    this.enrolledAt,
    this.graduatedAt,
  });

  factory AcademicSummary.fromJson(Map<String, dynamic> j) {
    final program = asMap(j['study_program']);
    final faculty = asMap(j['faculty']);
    final advisor = asMap(j['academic_advisor']);
    final curriculum = asMap(j['curriculum']);
    final term = asMap(j['current_term']);
    final lastIps = asMap(j['last_ips']);
    return AcademicSummary(
      nim: asString(j['nim']) ?? '',
      name: asString(j['name']) ?? '',
      studyProgram: asString(program['name']) ?? '',
      degreeLevel: asString(program['degree_level']),
      faculty: asString(faculty['name']),
      admissionYear: asInt(j['admission_year']),
      semester: asInt(j['semester']),
      status: j['status'] is Map
          ? NamedValue.fromJson(asMap(j['status']))
          : null,
      advisorName: asString(advisor['name']),
      curriculumName: asString(curriculum['name']),
      currentTerm: term.isEmpty ? null : TermRef.fromJson(term),
      ipk: asDouble(j['ipk']) ?? 0,
      lastIps: lastIps.isEmpty ? null : LastIps.fromJson(lastIps),
      totalCredits: asInt(j['total_credits']) ?? 0,
      passedCredits: asInt(j['passed_credits']) ?? 0,
      remainingCredits: asInt(j['remaining_credits']),
      currentTermCredits: asInt(j['current_term_credits']) ?? 0,
      maxCredits: asInt(j['max_credits']),
      enrolledAt: asDateTime(j['enrolled_at']),
      graduatedAt: asDateTime(j['graduated_at']),
    );
  }

  final String nim;
  final String name;
  final String studyProgram;
  final String? degreeLevel;
  final String? faculty;
  final int? admissionYear;
  final int? semester;
  final NamedValue? status;
  final String? advisorName;
  final String? curriculumName;
  final TermRef? currentTerm;
  final double ipk;
  final LastIps? lastIps;
  final int totalCredits;
  final int passedCredits;
  final int? remainingCredits;
  final int currentTermCredits;
  final int? maxCredits;
  final DateTime? enrolledAt;
  final DateTime? graduatedAt;
}

class HistoryEntry {
  const HistoryEntry({
    required this.type,
    required this.title,
    this.date,
    this.description,
  });
  factory HistoryEntry.fromJson(Map<String, dynamic> j) => HistoryEntry(
    type: asString(j['type']) ?? '',
    title: asString(j['title']) ?? '',
    date: asDateTime(j['date']),
    description: asString(j['description']),
  );
  final String type;
  final String title;
  final DateTime? date;
  final String? description;
}

/// Tone for a letter grade (A/AB green, B/BC blue, C amber, D/E red).
StatusTone letterTone(String? letter) => switch (letter) {
  'A' || 'AB' => StatusTone.success,
  'B' || 'BC' => StatusTone.info,
  'C' => StatusTone.warning,
  'D' || 'E' => StatusTone.danger,
  _ => StatusTone.neutral,
};

class GradeRow {
  const GradeRow({
    required this.courseCode,
    required this.courseName,
    required this.credits,
    this.krsItemId,
    this.classCode,
    this.score,
    this.letterGrade,
    this.weight,
    this.qualityPoints,
    this.isPassing,
    this.isGraded = false,
  });
  factory GradeRow.fromJson(Map<String, dynamic> j) => GradeRow(
    krsItemId: asString(j['krs_item_id']),
    courseCode: asString(j['course_code']) ?? '',
    courseName: asString(j['course_name']) ?? '',
    classCode: asString(j['class_code']),
    credits: asInt(j['credits']) ?? 0,
    score: asDouble(j['score']),
    letterGrade: asString(j['letter_grade']),
    weight: asDouble(j['weight']),
    qualityPoints: asDouble(j['quality_points']),
    isPassing: j['is_passing'] == null ? null : asBool(j['is_passing']),
    isGraded: asBool(j['is_graded']),
  );
  final String? krsItemId;
  final String courseCode;
  final String courseName;
  final String? classCode;
  final int credits;
  final double? score;
  final String? letterGrade;
  final double? weight;
  final double? qualityPoints;
  final bool? isPassing;
  final bool isGraded;
}

class GradeTerm {
  const GradeTerm({
    required this.term,
    required this.rows,
    this.semesterNumber,
    this.ips,
    this.gradedCredits = 0,
    this.totalCredits = 0,
  });
  factory GradeTerm.fromJson(Map<String, dynamic> j) => GradeTerm(
    term: TermRef.fromJson(asMap(j['term'])),
    semesterNumber: asInt(j['semester_number']),
    ips: asDouble(j['ips']),
    gradedCredits: asInt(j['graded_credits']) ?? 0,
    totalCredits: asInt(j['total_credits']) ?? 0,
    rows: asMapList(j['rows']).map(GradeRow.fromJson).toList(),
  );
  final TermRef term;
  final int? semesterNumber;
  final double? ips;
  final int gradedCredits;
  final int totalCredits;
  final List<GradeRow> rows;
}

class GradesOverview {
  const GradesOverview({
    required this.terms,
    this.ipk = 0,
    this.totalCredits = 0,
    this.passedCredits = 0,
  });
  factory GradesOverview.fromJson(Map<String, dynamic> j) => GradesOverview(
    terms: asMapList(j['terms']).map(GradeTerm.fromJson).toList(),
    ipk: asDouble(j['ipk']) ?? 0,
    totalCredits: asInt(j['total_credits']) ?? 0,
    passedCredits: asInt(j['passed_credits']) ?? 0,
  );
  final List<GradeTerm> terms;
  final double ipk;
  final int totalCredits;
  final int passedCredits;
}

class KhsSummary {
  const KhsSummary({
    this.termCredits = 0,
    this.takenCredits = 0,
    this.ips = 0,
    this.ipk = 0,
    this.cumulativeCredits = 0,
    this.allGraded = false,
  });
  factory KhsSummary.fromJson(Map<String, dynamic> j) => KhsSummary(
    termCredits: asInt(j['term_credits']) ?? 0,
    takenCredits: asInt(j['taken_credits']) ?? 0,
    ips: asDouble(j['ips']) ?? 0,
    ipk: asDouble(j['ipk']) ?? 0,
    cumulativeCredits: asInt(j['cumulative_credits']) ?? 0,
    allGraded: asBool(j['all_graded']),
  );
  final int termCredits;
  final int takenCredits;
  final double ips;
  final double ipk;
  final int cumulativeCredits;
  final bool allGraded;
}

class KhsData {
  const KhsData({
    required this.terms,
    required this.rows,
    this.term,
    this.semesterNumber,
    this.summary,
  });
  factory KhsData.fromJson(Map<String, dynamic> j) {
    final term = asMap(j['term']);
    final summary = asMap(j['summary']);
    return KhsData(
      terms: asMapList(j['terms']).map(TermRef.fromJson).toList(),
      term: term.isEmpty ? null : TermRef.fromJson(term),
      semesterNumber: asInt(j['semester_number']),
      rows: asMapList(j['rows']).map(GradeRow.fromJson).toList(),
      summary: summary.isEmpty ? null : KhsSummary.fromJson(summary),
    );
  }
  final List<TermRef> terms;
  final TermRef? term;
  final int? semesterNumber;
  final List<GradeRow> rows;
  final KhsSummary? summary;
}

class TranscriptRow {
  const TranscriptRow({
    required this.courseCode,
    required this.courseName,
    required this.credits,
    this.semesterLevel,
    this.termLabel,
    this.score,
    this.letterGrade,
    this.weight,
    this.qualityPoints,
    this.isPassing,
    this.attempts = 1,
  });
  factory TranscriptRow.fromJson(Map<String, dynamic> j) => TranscriptRow(
    courseCode: asString(j['course_code']) ?? '',
    courseName: asString(j['course_name']) ?? '',
    credits: asInt(j['credits']) ?? 0,
    semesterLevel: asInt(j['semester_level']),
    termLabel: asString(j['term_label']),
    score: asDouble(j['score']),
    letterGrade: asString(j['letter_grade']),
    weight: asDouble(j['weight']),
    qualityPoints: asDouble(j['quality_points']),
    isPassing: j['is_passing'] == null ? null : asBool(j['is_passing']),
    attempts: asInt(j['attempts']) ?? 1,
  );
  final String courseCode;
  final String courseName;
  final int credits;
  final int? semesterLevel;
  final String? termLabel;
  final double? score;
  final String? letterGrade;
  final double? weight;
  final double? qualityPoints;
  final bool? isPassing;
  final int attempts;
}

class TranscriptTerm {
  const TranscriptTerm({required this.label, this.sks, this.ip});
  factory TranscriptTerm.fromJson(Map<String, dynamic> j) => TranscriptTerm(
    label: asString(j['label']) ?? '',
    sks: asInt(j['sks']),
    ip: asDouble(j['ip']),
  );
  final String label;
  final int? sks;
  final double? ip;
}

class TranscriptData {
  const TranscriptData({
    required this.name,
    required this.nim,
    required this.studyProgram,
    required this.rows,
    required this.terms,
    this.degreeLevel,
    this.faculty,
    this.admissionYear,
    this.status,
    this.university,
    this.totalCredits = 0,
    this.passedCredits = 0,
    this.ipk = 0,
    this.courseCount = 0,
  });
  factory TranscriptData.fromJson(Map<String, dynamic> j) {
    final st = asMap(j['student']);
    final sm = asMap(j['summary']);
    return TranscriptData(
      name: asString(st['name']) ?? '',
      nim: asString(st['nim']) ?? '',
      studyProgram: asString(st['study_program']) ?? '',
      degreeLevel: asString(st['degree_level']),
      faculty: asString(st['faculty']),
      admissionYear: asInt(st['admission_year']),
      status: asString(st['status']),
      university: asString(st['university']),
      rows: asMapList(j['rows']).map(TranscriptRow.fromJson).toList(),
      terms: asMapList(j['terms']).map(TranscriptTerm.fromJson).toList(),
      totalCredits: asInt(sm['total_credits']) ?? 0,
      passedCredits: asInt(sm['passed_credits']) ?? 0,
      ipk: asDouble(sm['ipk']) ?? 0,
      courseCount: asInt(sm['course_count']) ?? 0,
    );
  }
  final String name;
  final String nim;
  final String studyProgram;
  final String? degreeLevel;
  final String? faculty;
  final int? admissionYear;
  final String? status;
  final String? university;
  final List<TranscriptRow> rows;
  final List<TranscriptTerm> terms;
  final int totalCredits;
  final int passedCredits;
  final double ipk;
  final int courseCount;
}

class StudentDocument {
  const StudentDocument({
    required this.type,
    required this.title,
    required this.url,
    required this.filename,
    this.description,
  });
  factory StudentDocument.fromJson(Map<String, dynamic> j) => StudentDocument(
    type: asString(j['type']) ?? '',
    title: asString(j['title']) ?? '',
    description: asString(j['description']),
    url: asString(j['url']) ?? '',
    filename: asString(j['filename']) ?? 'dokumen.pdf',
  );
  final String type;
  final String title;
  final String? description;
  final String url;
  final String filename;

  /// Path (relative to `/api/v1`) without the query string.
  String get path {
    var p = Uri.tryParse(url)?.path ?? url;
    if (p.startsWith('/api/v1')) p = p.substring(7);
    return p;
  }

  Map<String, dynamic>? get query {
    final q = Uri.tryParse(url)?.queryParameters;
    return (q == null || q.isEmpty) ? null : Map<String, dynamic>.from(q);
  }
}

class StudentProfile {
  const StudentProfile({
    required this.name,
    required this.nim,
    required this.academic,
    this.birthPlace,
    this.birthDate,
    this.gender,
    this.genderLabel,
    this.email,
    this.phone,
    this.address,
    this.editableFields = const [],
    this.hasPendingChange = false,
  });
  factory StudentProfile.fromJson(Map<String, dynamic> j) {
    final p = asMap(j['personal']);
    final c = asMap(j['contact']);
    final pending = asMap(j['pending_data_change']);
    return StudentProfile(
      name: asString(p['name']) ?? '',
      nim: asString(p['nim']) ?? '',
      birthPlace: asString(p['birth_place']),
      birthDate: asDateTime(p['birth_date']),
      gender: asString(p['gender']),
      genderLabel: asString(p['gender_label']),
      email: asString(c['email']),
      phone: asString(c['phone']),
      address: asString(j['address']),
      academic: AcademicSummary.fromJson(asMap(j['academic'])),
      editableFields: asStringList(j['editable_fields']),
      hasPendingChange: pending.isNotEmpty,
    );
  }
  final String name;
  final String nim;
  final String? birthPlace;
  final DateTime? birthDate;
  final String? gender;
  final String? genderLabel;
  final String? email;
  final String? phone;
  final String? address;
  final AcademicSummary academic;
  final List<String> editableFields;
  final bool hasPendingChange;

  bool canEdit(String field) =>
      editableFields.isEmpty || editableFields.contains(field);
}
