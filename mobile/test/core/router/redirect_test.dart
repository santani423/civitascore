import 'package:civitasone/core/router/app_router.dart';
import 'package:civitasone/core/router/route_paths.dart';
import 'package:civitasone/core/session/app_experience.dart';
import 'package:flutter_test/flutter_test.dart';

String? go(
  String location, {
  bool loading = false,
  bool signedIn = true,
  bool locked = false,
  bool mustChange = false,
  AppExperience? experience = AppExperience.student,
}) => resolveRedirect(
  location: location,
  authLoading: loading,
  signedIn: signedIn,
  locked: locked,
  mustChangePassword: mustChange,
  experience: experience,
);

void main() {
  group('resolveRedirect', () {
    test('shows splash while the session is restoring', () {
      expect(go(RoutePaths.studentHome, loading: true), RoutePaths.splash);
      expect(go(RoutePaths.splash, loading: true), isNull);
    });

    test('signed-out users only reach public auth pages', () {
      expect(go(RoutePaths.studentHome, signedIn: false), RoutePaths.login);
      expect(go(RoutePaths.login, signedIn: false), isNull);
      expect(go(RoutePaths.forgotPassword, signedIn: false), isNull);
      expect(go(RoutePaths.resetPassword, signedIn: false), isNull);
    });

    test('biometric lock gates everything when signed in', () {
      expect(go(RoutePaths.studentFinance, locked: true), RoutePaths.lock);
      expect(go(RoutePaths.lock, locked: true), isNull);
    });

    test('forced password change comes before the shell', () {
      expect(
        go(RoutePaths.studentHome, mustChange: true),
        RoutePaths.changePassword,
      );
      expect(go(RoutePaths.changePassword, mustChange: true), isNull);
    });

    test('accounts without student/lecturer role see no-access', () {
      expect(go(RoutePaths.studentHome, experience: null), RoutePaths.noAccess);
    });

    test('entry and public pages forward to the experience home', () {
      expect(go(RoutePaths.login), RoutePaths.studentHome);
      expect(
        go(RoutePaths.splash, experience: AppExperience.lecturer),
        RoutePaths.lecturerHome,
      );
      expect(go(RoutePaths.lock), RoutePaths.studentHome);
    });

    test('experiences cannot open each other\'s routes', () {
      expect(go('/l/classes/1'), RoutePaths.studentHome);
      expect(
        go('/s/krs', experience: AppExperience.lecturer),
        RoutePaths.lecturerHome,
      );
      expect(go('/s/krs'), isNull);
      expect(
        go(RoutePaths.notifications, experience: AppExperience.lecturer),
        isNull,
      );
    });
  });
}
