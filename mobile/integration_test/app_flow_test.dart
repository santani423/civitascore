// End-to-end UI flow on a real Android device/emulator.
//
// Runs the real app (router, shells, secure storage, Hive, plugins) with the
// HTTP layer replaced by the test-only FakeApi, because no safe backend is
// available in CI/local (the local Laravel .env points at production).
// Responses follow the backend envelope documented in
// docs/mobile/03-integrasi-backend-dan-celah-api.md.
//
// Run: flutter test integration_test/app_flow_test.dart --flavor dev -d <emulator-id>
import 'package:civitasone/app.dart';
import 'package:civitasone/core/config/env.dart';
import 'package:civitasone/core/config/flavor.dart';
import 'package:civitasone/core/network/dio_client.dart';
import 'package:civitasone/core/storage/local_prefs.dart';
import 'package:civitasone/core/storage/secure_storage.dart';
import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:integration_test/integration_test.dart';
import 'package:intl/date_symbol_data_local.dart';
import 'package:shared_preferences/shared_preferences.dart';

import '../test/helpers/fake_api.dart';

Map<String, dynamic> _me(String role, List<String> permissions) => {
  'user': {
    'id': '01USER$role',
    'name': role == 'student' ? 'Andi Pratama' : 'Dr. Maria Wijaya',
    'email': '$role@campus.test',
    'nim': role == 'student' ? '2101001' : null,
    'must_change_password': false,
  },
  'roles': [role],
  'permissions': permissions,
};

const _studentPerms = [
  'student_portal.read',
  'student_portal.update',
  'krs_self_service.read',
  'krs_self_service.create',
  'krs_self_service.update',
  'student_requests.read',
  'student_requests.create',
  'student_requests.update',
  'exam_participation.read',
  'exam_participation.create',
  'exam_participation.update',
  'assignment_submissions.create',
  'student_finance.read',
  'student_finance.create',
];
const _lecturerPerms = [
  'students.read',
  'classes.read',
  'krs.read',
  'grades.read',
  'grades.create',
  'grades.update',
  'attendance.read',
  'attendance.create',
  'attendance.update',
  'exams.read',
  'exams.create',
  'exams.update',
  'exam_attempts.read',
  'question_bank.read',
  'lecturer_profile.read',
  'lecturer_profile.update',
  'course_materials.read',
  'assignments.read',
  'assignment_submissions.read',
  'krs_advising.read',
  'krs_advising.approve',
  'academic_calendar.read',
  'hr_self_service.read',
];

FakeApi _api({required bool student}) {
  final api = FakeApi()
    ..on(
      'POST',
      '/login',
      (_) => FakeResponse.ok({
        'token': '1|integration-token',
        'user': {
          'id': '01USER',
          'name': 'x',
          'email': 'x',
          'nim': null,
          'must_change_password': false,
        },
      }),
    )
    ..get(
      '/me',
      student ? _me('student', _studentPerms) : _me('lecturer', _lecturerPerms),
    )
    ..get('/tenant/profile', {
      'id': '01UNIVERSITY',
      'name': 'Universitas Katolik Indonesia Atma Jaya',
      'code': 'UAJ',
    })
    ..get('/notifications/unread-count', {'unread_count': 2})
    ..on('POST', '/logout', (_) => FakeResponse.ok(null))
    // Lists default to empty collections; screens must render empty states.
    ..get('/notifications', <dynamic>[])
    ..get('/student/finance/summary', {
      'outstanding_amount': '1500000.00',
      'overdue_amount': '0.00',
      'open_invoices': 1,
    })
    ..get('/student/courses', <dynamic>[])
    ..get('/student/assignments', <dynamic>[])
    ..get('/student/academic-summary', {'ipk': '3.45', 'total_sks': 96})
    ..get('/teaching/classes', <dynamic>[])
    ..get(
      '/krs-submissions',
      <dynamic>[],
      meta: {'current_page': 1, 'per_page': 15, 'total': 0, 'last_page': 1},
    );
  return api;
}

Future<void> _launch(WidgetTester tester, FakeApi api) async {
  SharedPreferences.setMockInitialValues({});
  final prefs = await SharedPreferences.getInstance();
  final storage = ProviderContainer().read(secureStorageProvider);
  await storage.deleteAll();
  await tester.pumpWidget(
    ProviderScope(
      overrides: [
        sharedPreferencesProvider.overrideWithValue(prefs),
        // Real Dio with the app's interceptors (token, tenant, language);
        // only the transport is replaced by the test-only FakeApi.
        dioProvider.overrideWith(
          (ref) => buildDio(ref)..httpClientAdapter = api,
        ),
      ],
      child: const CivitasOneApp(),
    ),
  );
  await tester.pumpAndSettle(const Duration(seconds: 2));
}

Future<void> _login(
  WidgetTester tester, {
  required bool nim,
  required String id,
}) async {
  expect(find.byKey(const Key('login-submit')), findsOneWidget);
  if (!nim) {
    await tester.tap(find.text('Dosen'));
    await tester.pumpAndSettle();
  }
  await tester.enterText(find.byKey(const Key('login-identifier')), id);
  await tester.enterText(find.byKey(const Key('login-password')), 'rahasia123');
  await tester.tap(find.byKey(const Key('login-submit')));
  await tester.pumpAndSettle(const Duration(seconds: 3));
}

Future<void> _visitTabs(WidgetTester tester, List<String> labels) async {
  for (final label in labels) {
    await tester.tap(
      find.descendant(
        of: find.byType(NavigationBar),
        matching: find.text(label),
      ),
    );
    await tester.pumpAndSettle(const Duration(seconds: 2));
    expect(tester.takeException(), isNull, reason: 'tab $label threw');
  }
}

void main() {
  IntegrationTestWidgetsFlutterBinding.ensureInitialized();

  setUpAll(() async {
    Env.init(Flavor.dev);
    await initializeDateFormatting('id_ID');
    await initializeDateFormatting('en_US');
    await initializeDateFormatting('zh_CN');
  });

  testWidgets('student: login with NIM, visit every tab, sign out', (
    tester,
  ) async {
    final api = _api(student: true);
    await _launch(tester, api);
    await _login(tester, nim: true, id: '2101001');

    final login = api.requests.firstWhere((r) => r.path == '/login');
    expect((login.body as Map)['nim'], '2101001');
    expect(find.byType(NavigationBar), findsOneWidget);

    await _visitTabs(tester, [
      'Akademik',
      'Kuliah',
      'Keuangan',
      'Akun',
      'Beranda',
    ]);

    // Requests after login carry the token and learned tenant id.
    final afterLogin = api.requests
        .where((r) => r.path == '/student/finance/summary')
        .first;
    expect(afterLogin.headers['Authorization'], 'Bearer 1|integration-token');
    expect(afterLogin.headers['X-University-ID'], '01UNIVERSITY');

    await _visitTabs(tester, ['Akun']);
    await tester.scrollUntilVisible(
      find.byKey(const Key('account-logout')),
      200,
    );
    await tester.tap(find.byKey(const Key('account-logout')));
    await tester.pumpAndSettle();
    await tester.tap(find.widgetWithText(FilledButton, 'Keluar'));
    await tester.pumpAndSettle(const Duration(seconds: 2));
    expect(find.byKey(const Key('login-submit')), findsOneWidget);
  });

  testWidgets('lecturer/advisor: login with email and visit every tab', (
    tester,
  ) async {
    final api = _api(student: false);
    await _launch(tester, api);
    await _login(tester, nim: false, id: 'lecturer@campus.test');

    final login = api.requests.firstWhere((r) => r.path == '/login');
    expect((login.body as Map)['email'], 'lecturer@campus.test');
    await _visitTabs(tester, [
      'Mengajar',
      'Jadwal',
      'Perwalian',
      'Akun',
      'Beranda',
    ]);
  });
}
