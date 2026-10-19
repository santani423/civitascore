import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../core/network/api_client.dart';
import '../../../core/network/api_envelope.dart';
import '../domain/student_request.dart';

final studentServicesApiProvider = Provider<StudentServicesApi>(
  (ref) => StudentServicesApi(ref.watch(apiClientProvider)),
);

/// Student administrative requests (`StudentRequestController`).
class StudentServicesApi {
  StudentServicesApi(this._api);
  final ApiClient _api;

  Future<Paginated<StudentRequest>> list(int page) => _api.getPage(
    '/student/requests',
    query: {'page': page, 'per_page': 20},
    item: StudentRequest.fromJson,
    cache: true,
  );

  Future<RequestOptions> options() => _api.get(
    '/student/requests/options',
    decode: RequestOptions.fromJson,
    cache: true,
  );

  Future<StudentRequest> detail(String id) => _api.get(
    '/student/requests/$id',
    decode: (d) => StudentRequest.fromJson(asMap(d)),
    cache: true,
  );

  /// Creates a draft (never sends `submit`; submitting is a second call).
  Future<StudentRequest> create(Map<String, dynamic> body) => _api.post(
    '/student/requests',
    data: body,
    decode: (d) => StudentRequest.fromJson(asMap(d)),
  );

  /// Updates a draft; `type` is prohibited by the backend on update.
  Future<StudentRequest> update(String id, Map<String, dynamic> body) =>
      _api.put(
        '/student/requests/$id',
        data: body,
        decode: (d) => StudentRequest.fromJson(asMap(d)),
      );

  Future<StudentRequest> submit(String id) => _api.post(
    '/student/requests/$id/submit',
    decode: (d) => StudentRequest.fromJson(asMap(d)),
  );

  Future<StudentRequest> cancel(String id) => _api.post(
    '/student/requests/$id/cancel',
    decode: (d) => StudentRequest.fromJson(asMap(d)),
  );

  /// Two-step upload: returns the file id for `attachment_file_id`.
  Future<String> uploadAttachment(String path, {String? filename}) =>
      _api.uploadFile(path, filename: filename);
}
