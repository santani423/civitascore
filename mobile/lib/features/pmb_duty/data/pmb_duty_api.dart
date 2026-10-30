import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../core/network/api_client.dart';
import '../../../core/network/api_envelope.dart';
import '../domain/pmb_duty_models.dart';

final pmbDutyApiProvider = Provider<PmbDutyApi>(
  (ref) => PmbDutyApi(ref.watch(apiClientProvider)),
);

/// PMB exam duties of a lecturer (proctor/examiner/interviewer) on
/// `/pmb/admin/*`. Responses carry `Cache-Control: no-store` and applicant
/// names, so nothing here is written to the offline cache.
class PmbDutyApi {
  PmbDutyApi(this._api);
  final ApiClient _api;

  static const _base = '/pmb/admin';

  /// Sessions assigned to me (max 200, newest first).
  Future<List<DutySession>> mySessions() =>
      _api.getList('$_base/examiner/sessions', item: DutySession.fromJson);

  Future<SessionDetail> session(String sessionId) => _api.get(
    '$_base/exam-sessions/$sessionId',
    decode: (d) => SessionDetail.fromJson(asMap(d)),
  );

  /// Records attendance from a scanned card [token] or a picked
  /// [participantId] (the backend requires exactly one of them).
  Future<SessionParticipant> checkIn(
    String sessionId, {
    String? token,
    String? participantId,
    PmbAttendance status = PmbAttendance.present,
  }) => _api.post(
    '$_base/exam-sessions/$sessionId/check-in',
    data: {
      'token': ?token,
      'participant_id': ?participantId,
      'status': status.value,
    },
    decode: (d) => SessionParticipant.fromJson(asMap(d)),
  );

  /// Short-lived signed URL of the attendance sheet PDF.
  Future<Uri?> attendanceSheetUrl(String sessionId) => _api.get(
    '$_base/exam-sessions/$sessionId/attendance-sheet',
    decode: (d) => Uri.tryParse(asString(asMap(d)['url']) ?? ''),
  );

  /// [userId] selects the caller's own score among the returned rows.
  Future<ScoreSheet> scores(String sessionId, {required String userId}) =>
      _api.get(
        '$_base/exam-sessions/$sessionId/scores',
        decode: (d) => ScoreSheet.fromJson(asMap(d), userId: userId),
      );

  /// Saves rows atomically (one invalid row rejects the batch). Returns the
  /// number of saved rows; skipped absent/excused rows are not counted.
  Future<int> saveScores(String sessionId, List<ScoreInput> rows) => _api.put(
    '$_base/exam-sessions/$sessionId/scores',
    data: {'rows': rows.map((r) => r.toJson()).toList()},
    decode: (d) => asInt(asMap(d)['saved']) ?? 0,
  );
}
