import '../../../core/network/api_envelope.dart';

/// Kelas ajar milik dosen (`GET /class-sections`, ClassSectionResource) —
/// dipakai sebagai pemilih kelas di form ujian dan filter kepemilikan klien.
class OwnClass {
  const OwnClass({
    required this.id,
    required this.classCode,
    this.courseId,
    this.courseName,
    this.courseCode,
    this.termLabel,
  });

  factory OwnClass.fromJson(Map<String, dynamic> json) => OwnClass(
    id: asString(json['id']) ?? '',
    classCode: asString(json['class_code']) ?? '',
    courseId: asString(json['course_id']),
    courseName: asString(json['course_name']),
    courseCode: asString(json['course_code']),
    termLabel: asString(json['academic_term_label']),
  );

  final String id;
  final String classCode;
  final String? courseId;
  final String? courseName;
  final String? courseCode;
  final String? termLabel;

  String get label =>
      [?courseName, classCode, if (termLabel != null) '($termLabel)'].join(' ');
}
