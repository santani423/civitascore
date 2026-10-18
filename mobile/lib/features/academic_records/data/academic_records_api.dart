import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../core/network/api_client.dart';
import '../../../core/network/api_envelope.dart';
import '../domain/academic_models.dart';

class AcademicRecordsApi {
  AcademicRecordsApi(this._api);
  final ApiClient _api;

  Future<AcademicSummary> summary() => _api.get(
    '/student/academic-summary',
    cache: true,
    decode: (d) => AcademicSummary.fromJson(asMap(d)),
  );

  Future<List<HistoryEntry>> history() => _api.getList(
    '/student/academic-history',
    cache: true,
    item: HistoryEntry.fromJson,
  );

  Future<GradesOverview> grades() => _api.get(
    '/student/grades',
    cache: true,
    decode: (d) => GradesOverview.fromJson(asMap(d)),
  );

  Future<KhsData> khs({String? termId}) => _api.get(
    '/student/khs',
    cache: true,
    query: termId == null ? null : {'academic_term_id': termId},
    decode: (d) => KhsData.fromJson(asMap(d)),
  );

  Future<TranscriptData> transcript() => _api.get(
    '/student/transcript',
    cache: true,
    decode: (d) => TranscriptData.fromJson(asMap(d)),
  );

  Future<List<StudentDocument>> documents() => _api.getList(
    '/student/documents',
    cache: true,
    item: StudentDocument.fromJson,
  );

  Future<StudentProfile> profile() => _api.get(
    '/student/profile',
    cache: true,
    decode: (d) => StudentProfile.fromJson(asMap(d)),
  );

  /// PATCH sends only the fields the backend accepts (phone, address).
  Future<StudentProfile> updateProfile({String? phone, String? address}) =>
      _api.patch(
        '/student/profile',
        data: {'phone': phone, 'address': address},
        decode: (d) => StudentProfile.fromJson(asMap(d)),
      );
}

final academicRecordsApiProvider = Provider<AcademicRecordsApi>(
  (ref) => AcademicRecordsApi(ref.watch(apiClientProvider)),
);
