import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../auth/presentation/controllers/auth_controller.dart';
import '../data/pmb_duty_api.dart';
import '../domain/pmb_duty_models.dart';

/// Permissions of `GET /pmb/admin/examiner/sessions` (OR).
const kPmbDutyPermissions = ['pmb_scores.create', 'pmb_exams.update'];
const kPmbAttendancePermission = 'pmb_exam_attendance.update';

final dutySessionsProvider = FutureProvider.autoDispose<List<DutySession>>(
  (ref) => ref.watch(pmbDutyApiProvider).mySessions(),
);

/// Home entry: empty (hidden) when nothing is assigned or the request
/// fails — the module is conditional. Watch it only for users holding
/// [kPmbDutyPermissions].
final homeDutySessionsProvider = FutureProvider.autoDispose<List<DutySession>>((
  ref,
) async {
  try {
    return await ref.watch(pmbDutyApiProvider).mySessions();
  } catch (_) {
    return const [];
  }
});

final dutySessionProvider = AsyncNotifierProvider.autoDispose
    .family<DutySessionNotifier, SessionDetail, String>(
      DutySessionNotifier.new,
    );

class DutySessionNotifier
    extends AutoDisposeFamilyAsyncNotifier<SessionDetail, String> {
  @override
  Future<SessionDetail> build(String arg) =>
      ref.watch(pmbDutyApiProvider).session(arg);

  /// Records attendance and patches the participant in place.
  Future<SessionParticipant> checkIn({
    String? token,
    String? participantId,
    PmbAttendance status = PmbAttendance.present,
  }) async {
    final updated = await ref
        .read(pmbDutyApiProvider)
        .checkIn(
          arg,
          token: token,
          participantId: participantId,
          status: status,
        );
    final current = state.valueOrNull;
    if (current != null) state = AsyncData(current.withParticipant(updated));
    return updated;
  }

  Future<void> refresh() async {
    ref.invalidateSelf();
    await future;
  }
}

final dutyScoreSheetProvider = FutureProvider.autoDispose
    .family<ScoreSheet, String>(
      (ref, sessionId) => ref
          .watch(pmbDutyApiProvider)
          .scores(sessionId, userId: ref.watch(currentUserProvider)?.id ?? ''),
    );
