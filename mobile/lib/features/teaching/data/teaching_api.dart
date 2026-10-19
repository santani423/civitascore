import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../core/network/api_client.dart';
import '../domain/teaching_models.dart';

final teachingApiProvider = Provider<TeachingApi>(
  (ref) => TeachingApi(ref.watch(apiClientProvider)),
);

class TeachingApi {
  TeachingApi(this._api);
  final ApiClient _api;

  /// Lecturer's own classes of the current term (all pages).
  Future<List<TeachingClass>> classes() async {
    final all = <TeachingClass>[];
    for (var page = 1; page <= 10; page++) {
      final res = await _api.getPage(
        '/teaching/classes',
        query: {'per_page': 100, 'page': page},
        item: TeachingClass.fromJson,
        cache: true,
      );
      all.addAll(res.items);
      if (!res.hasNextPage) break;
    }
    return all;
  }

  Future<ClassDetail> classDetail(String id) => _api.get(
    '/class-sections/$id',
    decode: (d) => ClassDetail.fromJson(d as Map<String, dynamic>),
    cache: true,
  );

  /// Enrolled participants (KRS items) of a class.
  Future<List<RosterEntry>> roster(String classSectionId) async {
    final all = <RosterEntry>[];
    for (var page = 1; page <= 20; page++) {
      final res = await _api.getPage(
        '/krs-items',
        query: {
          'filter[class_section_id]': classSectionId,
          'filter[status]': 'enrolled',
          'per_page': 100,
          'page': page,
        },
        item: RosterEntry.fromJson,
        cache: true,
      );
      all.addAll(res.items);
      if (!res.hasNextPage) break;
    }
    all.sort((a, b) => a.nim.compareTo(b.nim));
    return all;
  }

  // Materials ---------------------------------------------------------------
  Future<List<CourseMaterial>> materials(String classSectionId) => _api.getList(
    '/class-sections/$classSectionId/materials',
    item: CourseMaterial.fromJson,
  );

  Future<CourseMaterial> createMaterial(
    String classSectionId,
    Map<String, dynamic> body,
  ) => _api.post(
    '/class-sections/$classSectionId/materials',
    data: body,
    decode: (d) => CourseMaterial.fromJson(d as Map<String, dynamic>),
  );

  Future<CourseMaterial> updateMaterial(String id, Map<String, dynamic> body) =>
      _api.put(
        '/course-materials/$id',
        data: body,
        decode: (d) => CourseMaterial.fromJson(d as Map<String, dynamic>),
      );

  Future<void> deleteMaterial(String id) =>
      _api.delete<void>('/course-materials/$id', decode: (_) {});

  // Assignments -------------------------------------------------------------
  Future<List<TeachingAssignment>> assignments(String classSectionId) =>
      _api.getList(
        '/class-sections/$classSectionId/assignments',
        item: TeachingAssignment.fromJson,
      );

  Future<TeachingAssignment> createAssignment(
    String classSectionId,
    Map<String, dynamic> body,
  ) => _api.post(
    '/class-sections/$classSectionId/assignments',
    data: body,
    decode: (d) => TeachingAssignment.fromJson(d as Map<String, dynamic>),
  );

  Future<TeachingAssignment> updateAssignment(
    String id,
    Map<String, dynamic> body,
  ) => _api.put(
    '/assignments/$id',
    data: body,
    decode: (d) => TeachingAssignment.fromJson(d as Map<String, dynamic>),
  );

  Future<void> deleteAssignment(String id) =>
      _api.delete<void>('/assignments/$id', decode: (_) {});

  Future<SubmissionsResult> submissions(String assignmentId) => _api.get(
    '/assignments/$assignmentId/submissions',
    decode: SubmissionsResult.fromJson,
  );

  Future<SubmissionDetail> gradeSubmission(
    String submissionId, {
    required double score,
    String? feedback,
  }) => _api.put(
    '/assignment-submissions/$submissionId/grade',
    data: {'score': score, 'feedback': feedback},
    decode: (d) => SubmissionDetail.fromJson(d as Map<String, dynamic>),
  );

  Future<String> uploadFile(String path) => _api.uploadFile(path);

  // Calendar & student ------------------------------------------------------
  Future<List<CalendarEvent>> calendar({
    required DateTime from,
    required DateTime to,
  }) => _api.getList(
    '/academic-calendar-events',
    query: {'from': _ymd(from), 'to': _ymd(to)},
    item: CalendarEvent.fromJson,
    cache: true,
  );

  Future<StudentRecord> student(String id) => _api.get(
    '/students/$id',
    decode: (d) => StudentRecord.fromJson(d as Map<String, dynamic>),
    cache: true,
  );

  Future<Transcript> transcript(String id) => _api.get(
    '/students/$id/transcript',
    decode: Transcript.fromJson,
    cache: true,
  );

  static String _ymd(DateTime d) =>
      '${d.year.toString().padLeft(4, '0')}-${d.month.toString().padLeft(2, '0')}-${d.day.toString().padLeft(2, '0')}';
}
