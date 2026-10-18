import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../core/network/api_client.dart';
import '../../../core/network/api_envelope.dart';
import '../domain/assessment_models.dart';

final assessmentApiProvider = Provider<AssessmentApi>(
  (ref) => AssessmentApi(ref.watch(apiClientProvider)),
);

class AssessmentApi {
  AssessmentApi(this._api);
  final ApiClient _api;

  static const _perPage = 100;
  static const _maxPages = 50;

  Future<List<T>> _all<T>(
    String path,
    Map<String, dynamic> query,
    T Function(Map<String, dynamic>) item, {
    bool cache = true,
  }) async {
    final out = <T>[];
    for (var page = 1; page <= _maxPages; page++) {
      final res = await _api.getPage<T>(
        path,
        query: {...query, 'per_page': _perPage, 'page': page},
        item: item,
        cache: cache,
      );
      out.addAll(res.items);
      if (!res.hasNextPage) break;
    }
    return out;
  }

  /// Enrolled students of a class (`GET /krs-items`), sorted by NIM.
  Future<List<RosterStudent>> roster(String classSectionId) async {
    final list = await _all(
      '/krs-items',
      {'filter[class_section_id]': classSectionId, 'filter[status]': 'enrolled'},
      RosterStudent.fromJson,
    );
    list.sort((a, b) => a.nim.compareTo(b.nim));
    return list;
  }

  Future<List<AttendanceRecord>> attendances(String classSectionId) => _all(
    '/attendances',
    {'filter[class_section_id]': classSectionId, 'sort': 'meeting_number'},
    AttendanceRecord.fromJson,
  );

  Future<List<GradeEntry>> grades(String classSectionId) => _all(
    '/grades',
    {'filter[class_section_id]': classSectionId},
    GradeEntry.fromJson,
  );

  /// Upserts one meeting (re-posting the same number corrects it). Returns
  /// the backend message.
  Future<String?> recordMeeting(
    String classSectionId, {
    required int meetingNumber,
    required DateTime meetingDate,
    required Map<String, AttendanceStatus> entries,
  }) => _api.postForMessage(
    '/class-sections/$classSectionId/attendances',
    data: {
      'meeting_number': meetingNumber,
      'meeting_date': formatApiDate(meetingDate),
      'entries': [
        for (final e in entries.entries)
          {'krs_item_id': e.key, 'status': e.value.value},
      ],
    },
  );

  /// Saves a score; the backend computes the letter unless [letter] is set.
  Future<GradeEntry> saveGrade(
    String krsItemId, {
    required double score,
    String? letter,
  }) => _api.put<GradeEntry>(
    '/krs-items/$krsItemId/grade',
    data: {'score': score, 'letter_grade': ?letter},
    decode: (d) => GradeEntry.fromJson(asMap(d)),
  );
}
