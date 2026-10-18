import 'package:civitasone/core/l10n/app_language.dart';
import 'package:civitasone/core/network/api_client.dart';
import 'package:civitasone/core/storage/local_prefs.dart';
import 'package:civitasone/core/theme/app_theme.dart';
import 'package:civitasone/features/auth/domain/auth_user.dart';
import 'package:civitasone/features/auth/presentation/controllers/auth_controller.dart';
import 'package:flutter/material.dart';
import 'package:flutter_localizations/flutter_localizations.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:go_router/go_router.dart';
import 'package:intl/date_symbol_data_local.dart';
import 'package:shared_preferences/shared_preferences.dart';

import 'fake_api.dart';

/// Common Android screen sizes (logical px) for responsive tests.
abstract final class TestScreens {
  static const smallPhone = Size(360, 640);
  static const phone = Size(411, 891);
  static const tablet = Size(800, 1280);
  static const all = [smallPhone, phone, tablet];
}

const testStudent = AuthUser(
  id: '01STUDENT',
  name: 'Andi Pratama',
  email: 'andi@student.test',
  nim: '2101001',
  roles: ['student'],
  permissions: [
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
  ],
);

const testLecturer = AuthUser(
  id: '01LECTURER',
  name: 'Dr. Maria Wijaya',
  email: 'maria@campus.test',
  roles: ['lecturer', 'academic_advisor'],
  permissions: [
    'students.read',
    'classes.read',
    'courses.read',
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
    'exams.delete',
    'exams.publish',
    'exam_attempts.read',
    'question_bank.read',
    'question_bank.create',
    'question_bank.update',
    'question_bank.delete',
    'lecturer_profile.read',
    'lecturer_profile.update',
    'course_materials.read',
    'course_materials.create',
    'course_materials.update',
    'course_materials.delete',
    'assignments.read',
    'assignments.create',
    'assignments.update',
    'assignments.delete',
    'assignment_submissions.read',
    'assignment_submissions.update',
    'krs_advising.read',
    'krs_advising.approve',
    'academic_calendar.read',
    'hr_self_service.read',
    'hr_self_service.create',
    'hr_self_service.update',
  ],
);

/// Signed-in session for widget tests (no secure storage / network).
class FakeAuthController extends AuthController {
  FakeAuthController(this.user);
  final AuthUser? user;

  @override
  Future<AuthSession?> build() async =>
      user == null ? null : AuthSession(token: 'test-token', user: user!);

  @override
  Future<void> logout({bool allDevices = false}) async =>
      state = const AsyncData(null);
}

bool _datesReady = false;

/// Pumps [child] inside ProviderScope + MaterialApp.router with the app
/// themes and ID/EN/ZH localizations. Navigation to unknown routes renders
/// `Text('route:<location>')`, so tests can assert `context.push` targets.
Future<void> pumpApp(
  WidgetTester tester,
  Widget child, {
  FakeApi? api,
  AuthUser? user = testStudent,
  AppLanguage language = AppLanguage.id,
  ThemeMode themeMode = ThemeMode.light,
  Size size = TestScreens.phone,
  List<Override> overrides = const [],
}) async {
  if (!_datesReady) {
    await initializeDateFormatting('id_ID');
    await initializeDateFormatting('en_US');
    await initializeDateFormatting('zh_CN');
    _datesReady = true;
  }
  tester.view.physicalSize = size * tester.view.devicePixelRatio;
  addTearDown(tester.view.resetPhysicalSize);

  final router = GoRouter(
    routes: [GoRoute(path: '/', builder: (_, _) => child)],
    errorBuilder: (_, state) => Scaffold(body: Text('route:${state.uri}')),
  );
  SharedPreferences.setMockInitialValues({});
  final prefs = await SharedPreferences.getInstance();

  await tester.pumpWidget(
    ProviderScope(
      overrides: [
        sharedPreferencesProvider.overrideWithValue(prefs),
        authControllerProvider.overrideWith(() => FakeAuthController(user)),
        if (api != null) apiClientProvider.overrideWithValue(api.client()),
        ...overrides,
      ],
      child: MaterialApp.router(
        routerConfig: router,
        theme: AppTheme.light(),
        darkTheme: AppTheme.dark(),
        themeMode: themeMode,
        locale: language.locale,
        supportedLocales: AppLanguage.supportedLocales,
        localizationsDelegates: const [
          GlobalMaterialLocalizations.delegate,
          GlobalWidgetsLocalizations.delegate,
          GlobalCupertinoLocalizations.delegate,
        ],
      ),
    ),
  );
  await tester.pumpAndSettle();
}
