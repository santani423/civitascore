import 'package:civitasone/core/network/api_failure.dart';
import 'package:civitasone/features/schedule_attendance/data/schedule_attendance_api.dart';
import 'package:civitasone/features/schedule_attendance/domain/check_in_payload.dart';
import 'package:civitasone/features/schedule_attendance/domain/models.dart';
import 'package:civitasone/features/schedule_attendance/presentation/screens/attendance_check_in_screen.dart';
import 'package:flutter_test/flutter_test.dart';

import '../../helpers/fake_api.dart';
import '../../helpers/pump_app.dart';

const _payload = '01m6kd5sqvh71s63dpsmvxqapa.0123456789abcdef0123456789abcdef';

Map<String, dynamic> _checkInJson({bool alreadyRecorded = false}) => {
  'course_name': 'Basis Data',
  'course_code': 'IF201',
  'class_code': 'A',
  'meeting_number': 3,
  'meeting_date': '2026-11-04',
  'status': 'present',
  'status_label': 'Hadir',
  'recorded_at': '2026-11-04T08:05:00+07:00',
  'already_recorded': alreadyRecorded,
};

void main() {
  test('parseCheckInPayload keeps only the session.token format', () {
    expect(parseCheckInPayload('  $_payload \n'), _payload);
    expect(parseCheckInPayload('https://example.test/absen'), isNull);
    expect(parseCheckInPayload('$_payload-extra'), isNull);
    expect(parseCheckInPayload('01m6kd5sqvh71s63dpsmvxqapa.xyz'), isNull);
    expect(parseCheckInPayload(''), isNull);
  });

  test('checkInByQr posts only the payload and decodes the result', () async {
    final api = FakeApi()
      ..on(
        'POST',
        '/student/attendance/qr-scan',
        (_) => FakeResponse.ok(_checkInJson()),
      );

    final result = await ScheduleAttendanceApi(
      api.client(),
    ).checkInByQr(_payload);

    expect(result.courseName, 'Basis Data');
    expect(result.meetingNumber, 3);
    expect(result.status, 'present');
    expect(result.alreadyRecorded, isFalse);
    expect(api.requests.single.body, {'payload': _payload});
  });

  test('a repeated scan is decoded as already recorded', () async {
    final api = FakeApi()
      ..on(
        'POST',
        '/student/attendance/qr-scan',
        (_) => FakeResponse.ok(_checkInJson(alreadyRecorded: true)),
      );

    final result = await ScheduleAttendanceApi(
      api.client(),
    ).checkInByQr(_payload);

    expect(result.alreadyRecorded, isTrue);
  });

  test('a refused check-in surfaces the server failure', () async {
    final api = FakeApi()
      ..on(
        'POST',
        '/student/attendance/qr-scan',
        (_) => FakeResponse.error(409, 'Sesi presensi sudah ditutup.'),
      );

    await expectLater(
      ScheduleAttendanceApi(api.client()).checkInByQr(_payload),
      throwsA(isA<ApiFailure>()),
    );
  });

  testWidgets(
    'the result view shows the course and the already-recorded title',
    (tester) async {
      var doneCount = 0;

      await pumpApp(
        tester,
        CheckInResultView(
          result: CheckInResult.fromJson(_checkInJson(alreadyRecorded: true)),
          onDone: () => doneCount++,
        ),
        api: FakeApi(),
      );

      expect(find.text('Sudah tercatat'), findsOneWidget);
      expect(find.text('Basis Data'), findsOneWidget);
      expect(find.text('Pertemuan 3'), findsOneWidget);

      await tester.tap(find.text('Selesai'));
      expect(doneCount, 1);
    },
  );
}
