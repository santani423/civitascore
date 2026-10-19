import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../core/network/api_client.dart';
import '../../../core/network/api_envelope.dart';
import '../domain/hr_models.dart';

class HrApi {
  HrApi(this._api);
  final ApiClient _api;

  Future<LecturerProfile> lecturerProfile() => _api.get(
    '/lecturer-profile',
    cache: true,
    decode: (d) => LecturerProfile.fromJson(asMap(d)),
  );

  Future<LecturerProfile> updatePhone(String? phone) => _api.put(
    '/lecturer-profile',
    data: {'phone': phone},
    decode: (d) => LecturerProfile.fromJson(asMap(d)),
  );

  Future<HrProfile> hrProfile() => _api.get(
    '/me/hr/profile',
    cache: true,
    decode: (d) => HrProfile.fromJson(asMap(d)),
  );

  Future<List<LeaveRequest>> leaveRequests() => _api.getList(
    '/me/hr/leave-requests',
    cache: true,
    item: LeaveRequest.fromJson,
  );

  Future<LeaveRequest> createLeave({
    required String leaveType,
    required DateTime startDate,
    required DateTime endDate,
    required String reason,
    String? attachmentFileId,
    required bool submit,
  }) => _api.post(
    '/me/hr/leave-requests',
    data: {
      'leave_type': leaveType,
      'start_date': _ymd(startDate),
      'end_date': _ymd(endDate),
      'reason': reason,
      'attachment_file_id': ?attachmentFileId,
      'submit': submit,
    },
    decode: (d) => LeaveRequest.fromJson(asMap(d)),
  );

  Future<void> submitLeave(String id) =>
      _api.post<void>('/me/hr/leave-requests/$id/submit', decode: (_) {});

  Future<void> cancelLeave(String id) =>
      _api.patch<void>('/me/hr/leave-requests/$id/cancel', decode: (_) {});

  Future<List<HrRequest>> requests() =>
      _api.getList('/me/hr/requests', cache: true, item: HrRequest.fromJson);

  Future<HrRequest> createRequest({
    required String type,
    required String title,
    String? description,
    String? attachmentFileId,
    Map<String, String>? changes,
  }) => _api.post(
    '/me/hr/requests',
    data: {
      'type': type,
      'title': title,
      if (description != null && description.isNotEmpty)
        'description': description,
      'attachment_file_id': ?attachmentFileId,
      if (changes != null) 'payload': {'changes': changes},
    },
    decode: (d) => HrRequest.fromJson(asMap(d)),
  );

  Future<void> cancelRequest(String id) =>
      _api.patch<void>('/me/hr/requests/$id/cancel', decode: (_) {});

  Future<String> upload(String path) => _api.uploadFile(path);

  static String _ymd(DateTime d) =>
      '${d.year.toString().padLeft(4, '0')}-${d.month.toString().padLeft(2, '0')}-${d.day.toString().padLeft(2, '0')}';
}

final hrApiProvider = Provider<HrApi>(
  (ref) => HrApi(ref.watch(apiClientProvider)),
);
