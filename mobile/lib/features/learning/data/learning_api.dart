import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../core/network/api_client.dart';
import '../domain/learning_models.dart';

class LearningApi {
  LearningApi(this._api);
  final ApiClient _api;

  Future<CoursesOverview> courses() => _api.get(
    '/student/courses',
    cache: true,
    decode: CoursesOverview.fromJson,
  );

  Future<CourseDetail> course(String classSectionId) => _api.get(
    '/student/courses/$classSectionId',
    cache: true,
    decode: CourseDetail.fromJson,
  );

  Future<MaterialsOverview> materials() => _api.get(
    '/student/materials',
    cache: true,
    decode: MaterialsOverview.fromJson,
  );

  /// [status]: all|not_submitted|submitted|late|graded|missed.
  Future<AssignmentsOverview> assignments({String? status}) => _api.get(
    '/student/assignments',
    query: status == null || status == 'all' ? null : {'status': status},
    cache: true,
    decode: AssignmentsOverview.fromJson,
  );

  Future<AssignmentItem> assignment(String id) => _api.get(
    '/student/assignments/$id',
    cache: true,
    decode: (d) => AssignmentItem.fromJson(Map<String, dynamic>.from(d as Map)),
  );

  /// Two-step upload: [fileUploadId] comes from `ApiClient.uploadFile`.
  /// Returns the updated assignment row.
  Future<AssignmentItem> submit(
    String assignmentId, {
    String? fileUploadId,
    String? notes,
  }) => _api.post(
    '/student/assignments/$assignmentId/submission',
    data: {
      'file_upload_id': ?fileUploadId,
      if (notes != null && notes.trim().isNotEmpty) 'notes': notes.trim(),
    },
    decode: (d) => AssignmentItem.fromJson(Map<String, dynamic>.from(d as Map)),
  );
}

final learningApiProvider = Provider<LearningApi>(
  (ref) => LearningApi(ref.watch(apiClientProvider)),
);
