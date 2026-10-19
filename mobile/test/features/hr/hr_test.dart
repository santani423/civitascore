import 'package:civitasone/core/l10n/app_language.dart';
import 'package:civitasone/features/hr/data/hr_api.dart';
import 'package:civitasone/features/hr/presentation/screens/hr_profile_screen.dart';
import 'package:civitasone/features/hr/presentation/screens/leave_screens.dart';
import 'package:civitasone/features/hr/presentation/screens/lecturer_profile_screen.dart';
import 'package:civitasone/features/hr/presentation/screens/request_screens.dart';
import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';

import '../../helpers/fake_api.dart';
import '../../helpers/pump_app.dart';

const _lecturer = {
  'id': 'L1',
  'name': 'Dr. Maria Wijaya',
  'nidn': '0411118801',
  'nip': '198801',
  'email': 'maria@campus.test',
  'phone': '0812',
  'faculty_name': 'Fakultas Teknik',
  'employment_status': 'permanent',
  'functional_rank': 'lektor',
  'highest_education': 'S3',
  'hired_at': '2015-08-01',
  'is_active': true,
};

const _hr = {
  'employee': {
    'id': 'E1',
    'name': 'Dr. Maria Wijaya',
    'nip': '198801',
    'position': 'Dosen',
    'academic_rank_label': 'Lektor',
    'highest_education_label': 'Doktor (S3)',
    'rank': {'id': 'R', 'name': 'Penata', 'grade': 'III/c'},
    'lecturer': {
      'nidn': '0411118801',
      'serdos_number': 'SD-123',
      'expertise': 'Machine Learning',
      'lecturer_status_label': 'Aktif',
    },
  },
  'leave_quota': {'year': 2026, 'quota': 12, 'used': 3, 'remaining': 9},
};

const _leave = {
  'id': 'LV1',
  'leave_type': 'annual',
  'leave_type_label': 'Cuti Tahunan',
  'start_date': '2026-11-02',
  'end_date': '2026-11-04',
  'days': '3',
  'reason': 'Keperluan keluarga',
  'status': 'draft',
  'status_label': 'Draft',
};

FakeApi _api() => FakeApi()
  ..get('/lecturer-profile', _lecturer)
  ..get('/me/hr/profile', _hr)
  ..get('/me/hr/leave-requests', [_leave])
  ..get('/me/hr/requests', [
    {
      'id': 'RQ1',
      'type': 'data_change',
      'type_label': 'Perubahan Data',
      'title': 'Ubah alamat',
      'status': 'pending',
      'status_label': 'Menunggu',
    },
  ]);

void main() {
  test('api parses and builds payloads', () async {
    final fake = _api()
      ..on(
        'POST',
        '/me/hr/leave-requests',
        (_) => FakeResponse.ok(_leave, message: 'ok'),
      )
      ..on('PUT', '/lecturer-profile', (_) => FakeResponse.ok(_lecturer))
      ..on('PATCH', '/me/hr/requests/{id}/cancel', (_) => FakeResponse.ok(null))
      ..on('POST', '/me/hr/requests', (_) => FakeResponse.ok({'id': 'x'}));
    final api = HrApi(fake.client());
    final hr = await api.hrProfile();
    expect(hr.serdosNumber, 'SD-123');
    expect(hr.quota!.remaining, 9);
    expect(hr.rankGrade, 'III/c');
    final leaves = await api.leaveRequests();
    expect(leaves.single.days, 3);
    expect(leaves.single.canSubmit, isTrue);

    await api.createLeave(
      leaveType: 'sick',
      startDate: DateTime(2026, 11, 2),
      endDate: DateTime(2026, 11, 3),
      reason: 'x',
      attachmentFileId: 'F1',
      submit: true,
    );
    var req = fake.requests.last;
    expect(req.path, '/me/hr/leave-requests');
    expect(req.body, {
      'leave_type': 'sick',
      'start_date': '2026-11-02',
      'end_date': '2026-11-03',
      'reason': 'x',
      'attachment_file_id': 'F1',
      'submit': true,
    });
    await api.updatePhone(null);
    expect(fake.requests.last.body, {'phone': null});
    await api.createRequest(
      type: 'data_change',
      title: 't',
      changes: {'phone': '08'},
    );
    req = fake.requests.last;
    expect((req.body as Map)['payload'], {
      'changes': {'phone': '08'},
    });
    await api.cancelRequest('RQ1');
    expect(fake.requests.last.method, 'PATCH');
  });

  testWidgets('lecturer profile renders and saves phone', (tester) async {
    final api = _api()
      ..on('PUT', '/lecturer-profile', (_) => FakeResponse.ok(_lecturer));
    await pumpApp(
      tester,
      const LecturerProfileScreen(),
      api: api,
      user: testLecturer,
    );
    await tester.pumpAndSettle();
    expect(find.text('0411118801'), findsOneWidget);
    expect(find.text('Lektor'), findsOneWidget);
    await tester.tap(find.text('Simpan'));
    await tester.pumpAndSettle();
    expect(api.requests.any((r) => r.method == 'PUT'), isTrue);
  });

  testWidgets('hr profile shows data, quota and pending attendance', (
    tester,
  ) async {
    await pumpApp(
      tester,
      const HrProfileScreen(),
      api: _api(),
      user: testLecturer,
    );
    await tester.pumpAndSettle();
    expect(find.text('SD-123'), findsOneWidget);
    expect(find.text('Machine Learning'), findsOneWidget);
    await tester.scrollUntilVisible(find.text('Kehadiran pegawai'), 200);
    expect(find.text('Kehadiran pegawai'), findsOneWidget);
    await tester.scrollUntilVisible(find.text('Cuti & izin'), -200);
    await tester.tap(find.text('Cuti & izin'));
    await tester.pumpAndSettle();
    expect(find.text('route:/l/hr/leave'), findsOneWidget);
  });

  testWidgets('hr profile 404 shows explanatory state (EN, dark)', (
    tester,
  ) async {
    final api = FakeApi()
      ..on(
        'GET',
        '/me/hr/profile',
        (_) => FakeResponse.error(404, 'Akun Anda belum tertaut'),
      );
    await pumpApp(
      tester,
      const HrProfileScreen(),
      api: api,
      user: testLecturer,
      language: AppLanguage.en,
      themeMode: ThemeMode.dark,
    );
    await tester.pumpAndSettle();
    expect(
      find.text('Account not linked to an employee record'),
      findsOneWidget,
    );
  });

  testWidgets('leave list shows status and submits draft', (tester) async {
    final api = _api()
      ..on(
        'POST',
        '/me/hr/leave-requests/{id}/submit',
        (_) => FakeResponse.ok(_leave),
      );
    await pumpApp(
      tester,
      const LeaveRequestsScreen(),
      api: api,
      user: testLecturer,
      size: TestScreens.smallPhone,
    );
    await tester.pumpAndSettle();
    expect(tester.takeException(), isNull);
    expect(find.text('Cuti tahunan'), findsOneWidget);
    expect(find.text('Draf'), findsOneWidget);
    await tester.scrollUntilVisible(find.text('Ajukan draf'), 200);
    await tester.drag(find.byType(Scrollable).first, const Offset(0, -150));
    await tester.pumpAndSettle();
    await tester.tap(find.text('Ajukan draf'));
    await tester.pumpAndSettle();
    await tester.tap(find.text('Konfirmasi'));
    await tester.pumpAndSettle();
    expect(
      api.requests.any((r) => r.path == '/me/hr/leave-requests/LV1/submit'),
      isTrue,
    );
  });

  testWidgets('leave list error state and empty state', (tester) async {
    final api = FakeApi()
      ..on('GET', '/me/hr/leave-requests', (_) => FakeResponse.error(500, 'x'))
      ..get('/me/hr/profile', _hr);
    await pumpApp(
      tester,
      const LeaveRequestsScreen(),
      api: api,
      user: testLecturer,
    );
    await tester.pumpAndSettle();
    expect(find.text('Coba lagi'), findsWidgets);

    final empty = FakeApi()
      ..get('/me/hr/leave-requests', [])
      ..get('/me/hr/profile', _hr);
    await pumpApp(
      tester,
      const LeaveRequestsScreen(),
      api: empty,
      user: testLecturer,
    );
    await tester.pumpAndSettle();
    expect(find.text('Belum ada pengajuan cuti'), findsOneWidget);
  });

  testWidgets('leave form validates required fields', (tester) async {
    final api = _api();
    await pumpApp(
      tester,
      const LeaveRequestFormScreen(),
      api: api,
      user: testLecturer,
      size: TestScreens.smallPhone,
    );
    final submit = find.widgetWithText(FilledButton, 'Ajukan');
    await tester.scrollUntilVisible(
      submit,
      200,
      scrollable: find.byType(Scrollable).first,
    );
    await tester.tap(submit);
    await tester.pumpAndSettle();
    await tester.drag(find.byType(Scrollable).first, const Offset(0, 600));
    await tester.pumpAndSettle();
    expect(find.text('Pilih tanggal'), findsOneWidget);
    expect(api.requests.where((r) => r.method == 'POST'), isEmpty);
  });

  testWidgets('requests list cancels pending', (tester) async {
    final api = _api()
      ..on(
        'PATCH',
        '/me/hr/requests/{id}/cancel',
        (_) => FakeResponse.ok(null),
      );
    await pumpApp(
      tester,
      const HrRequestsScreen(),
      api: api,
      user: testLecturer,
    );
    await tester.pumpAndSettle();
    expect(find.text('Ubah alamat'), findsOneWidget);
    await tester.tap(find.text('Batalkan pengajuan'));
    await tester.pumpAndSettle();
    await tester.tap(find.text('Ya'));
    await tester.pumpAndSettle();
    expect(api.requests.any((r) => r.method == 'PATCH'), isTrue);
  });

  testWidgets('request form submits', (tester) async {
    final api = _api()
      ..on('POST', '/me/hr/requests', (_) => FakeResponse.ok({'id': 'n'}));
    await pumpApp(
      tester,
      const HrRequestFormScreen(),
      api: api,
      user: testLecturer,
    );
    await tester.enterText(find.byType(TextFormField).first, 'Judul');
    await tester.tap(find.text('Kirim pengajuan'));
    await tester.pumpAndSettle();
    final post = api.requests.firstWhere((r) => r.method == 'POST');
    expect((post.body as Map)['title'], 'Judul');
    expect((post.body as Map)['type'], 'transfer');
  });
}
