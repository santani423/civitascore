import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import '../../features/academic_records/academic_records_routes.dart';
import '../../features/account/presentation/account_screen.dart';
import '../../features/account/presentation/settings_screen.dart';
import '../../features/advising/advising_routes.dart';
import '../../features/announcements/announcements_routes.dart';
import '../../features/auth/presentation/controllers/auth_controller.dart';
import '../../features/auth/presentation/screens/change_password_screen.dart';
import '../../features/auth/presentation/screens/forgot_password_screen.dart';
import '../../features/auth/presentation/screens/lock_screen.dart';
import '../../features/auth/presentation/screens/login_screen.dart';
import '../../features/auth/presentation/screens/reset_password_screen.dart';
import '../../features/auth/presentation/screens/security_sessions_screen.dart';
import '../../features/exam/exam_routes.dart';
import '../../features/exam_student/exam_student_routes.dart';
import '../../features/finance/finance_routes.dart';
import '../../features/hr/hr_routes.dart';
import '../../features/krs/krs_routes.dart';
import '../../features/learning/learning_routes.dart';
import '../../features/lecturer_assessment/lecturer_assessment_routes.dart';
import '../../features/lecturer_home/lecturer_home_routes.dart';
import '../../features/notifications/notifications_routes.dart';
import '../../features/schedule_attendance/schedule_attendance_routes.dart';
import '../../features/student_home/student_home_routes.dart';
import '../../features/student_services/student_services_routes.dart';
import '../../features/teaching/teaching_routes.dart';
import '../pending/pending_feature_screen.dart';
import '../pending/pending_features.dart';
import '../security/app_lock.dart';
import '../session/app_experience.dart';
import 'route_paths.dart';
import 'session_screens.dart';
import 'shells.dart';

final rootNavigatorKey = GlobalKey<NavigatorState>(debugLabel: 'root');

/// Re-evaluates `redirect` whenever session, lock or experience changes,
/// without rebuilding the GoRouter (keeps the back stack).
class _RouterRefresh extends ChangeNotifier {
  _RouterRefresh(Ref ref) {
    ref.listen(authControllerProvider, (_, _) => notifyListeners());
    ref.listen(appLockProvider, (_, _) => notifyListeners());
    ref.listen(activeExperienceProvider, (_, _) => notifyListeners());
  }
}

const _publicPaths = {
  RoutePaths.login,
  RoutePaths.forgotPassword,
  RoutePaths.resetPassword,
};

/// Pure redirect decision, unit-tested in `test/core/router/redirect_test.dart`.
String? resolveRedirect({
  required String location,
  required bool authLoading,
  required bool signedIn,
  required bool locked,
  required bool mustChangePassword,
  required AppExperience? experience,
}) {
  if (authLoading) {
    return location == RoutePaths.splash ? null : RoutePaths.splash;
  }

  final isPublic = _publicPaths.contains(location);
  if (!signedIn) return isPublic ? null : RoutePaths.login;

  if (locked) return location == RoutePaths.lock ? null : RoutePaths.lock;
  if (mustChangePassword) {
    return location == RoutePaths.changePassword
        ? null
        : RoutePaths.changePassword;
  }
  if (experience == null) {
    return location == RoutePaths.noAccess ? null : RoutePaths.noAccess;
  }

  final home = experience == AppExperience.student
      ? RoutePaths.studentHome
      : RoutePaths.lecturerHome;
  const entryPaths = {
    RoutePaths.splash,
    RoutePaths.lock,
    RoutePaths.noAccess,
    '/',
  };
  if (isPublic || entryPaths.contains(location)) return home;

  // Keep each experience inside its own route space.
  if (experience == AppExperience.student && location.startsWith('/l/')) {
    return home;
  }
  if (experience == AppExperience.lecturer && location.startsWith('/s/')) {
    return home;
  }
  return null;
}

final appRouterProvider = Provider<GoRouter>((ref) {
  final refresh = _RouterRefresh(ref);
  ref.onDispose(refresh.dispose);

  return GoRouter(
    navigatorKey: rootNavigatorKey,
    initialLocation: RoutePaths.splash,
    refreshListenable: refresh,
    redirect: (context, state) {
      final auth = ref.read(authControllerProvider);
      final session = auth.valueOrNull;
      return resolveRedirect(
        location: state.matchedLocation,
        authLoading: auth.isLoading && !auth.hasValue,
        signedIn: session != null,
        locked: session != null && ref.read(appLockProvider),
        mustChangePassword: session?.user.mustChangePassword ?? false,
        experience: ref.read(activeExperienceProvider),
      );
    },
    routes: [
      GoRoute(path: RoutePaths.splash, builder: (_, _) => const SplashScreen()),
      GoRoute(path: RoutePaths.login, builder: (_, _) => const LoginScreen()),
      GoRoute(
        path: RoutePaths.forgotPassword,
        builder: (_, _) => const ForgotPasswordScreen(),
      ),
      GoRoute(
        path: RoutePaths.resetPassword,
        builder: (_, state) => ResetPasswordScreen(
          token: state.uri.queryParameters['token'],
          email: state.uri.queryParameters['email'],
        ),
      ),
      GoRoute(
        path: RoutePaths.changePassword,
        builder: (_, _) => const ChangePasswordScreen(),
      ),
      GoRoute(path: RoutePaths.lock, builder: (_, _) => const LockScreen()),
      GoRoute(
        path: RoutePaths.noAccess,
        builder: (_, _) => const NoAccessScreen(),
      ),

      // Student experience ---------------------------------------------------
      StatefulShellRoute.indexedStack(
        builder: (_, _, shell) => StudentShell(shell: shell),
        branches: [
          StatefulShellBranch(
            routes: [
              GoRoute(
                path: RoutePaths.studentHome,
                builder: (_, _) => const StudentHomeScreen(),
              ),
            ],
          ),
          StatefulShellBranch(
            routes: [
              GoRoute(
                path: RoutePaths.studentAcademic,
                builder: (_, _) => const AcademicHubScreen(),
              ),
            ],
          ),
          StatefulShellBranch(
            routes: [
              GoRoute(
                path: RoutePaths.studentLearning,
                builder: (_, _) => const LearningHubScreen(),
              ),
            ],
          ),
          StatefulShellBranch(
            routes: [
              GoRoute(
                path: RoutePaths.studentFinance,
                builder: (_, _) => const FinanceHomeScreen(),
              ),
            ],
          ),
          StatefulShellBranch(
            routes: [
              GoRoute(
                path: RoutePaths.studentAccount,
                builder: (_, _) => const AccountScreen(),
              ),
            ],
          ),
        ],
      ),

      // Lecturer / advisor experience ----------------------------------------
      StatefulShellRoute.indexedStack(
        builder: (_, _, shell) => LecturerShell(shell: shell),
        branches: [
          StatefulShellBranch(
            routes: [
              GoRoute(
                path: RoutePaths.lecturerHome,
                builder: (_, _) => const LecturerHomeScreen(),
              ),
            ],
          ),
          StatefulShellBranch(
            routes: [
              GoRoute(
                path: RoutePaths.lecturerTeaching,
                builder: (_, _) => const TeachingHomeScreen(),
              ),
            ],
          ),
          StatefulShellBranch(
            routes: [
              GoRoute(
                path: RoutePaths.lecturerSchedule,
                builder: (_, _) => const LecturerScheduleScreen(),
              ),
            ],
          ),
          StatefulShellBranch(
            routes: [
              GoRoute(
                path: RoutePaths.lecturerAdvising,
                builder: (_, _) => const AdvisingScreen(),
              ),
            ],
          ),
          StatefulShellBranch(
            routes: [
              GoRoute(
                path: RoutePaths.lecturerAccount,
                builder: (_, _) => const AccountScreen(),
              ),
            ],
          ),
        ],
      ),

      // Shared -----------------------------------------------------------------
      GoRoute(
        path: RoutePaths.settings,
        builder: (_, _) => const SettingsScreen(),
      ),
      GoRoute(
        path: RoutePaths.securitySessions,
        builder: (_, _) => const SecuritySessionsScreen(),
      ),
      GoRoute(
        path: RoutePaths.pendingFeaturesStudent,
        builder: (_, _) =>
            const PendingFeaturesScreen(audience: PendingAudience.student),
      ),
      GoRoute(
        path: RoutePaths.pendingFeaturesLecturer,
        builder: (_, _) =>
            const PendingFeaturesScreen(audience: PendingAudience.lecturer),
      ),
      GoRoute(
        path: RoutePaths.pendingFeaturesAdvisor,
        builder: (_, _) =>
            const PendingFeaturesScreen(audience: PendingAudience.advisor),
      ),
      GoRoute(
        path: '/pending/feature/:key',
        builder: (_, state) =>
            PendingFeatureScreen(featureKey: state.pathParameters['key']!),
      ),

      // Feature modules ----------------------------------------------------------
      ...notificationsRoutes,
      ...studentHomeRoutes,
      ...academicRecordsRoutes,
      ...scheduleAttendanceRoutes,
      ...krsRoutes,
      ...learningRoutes,
      ...examStudentRoutes,
      ...financeRoutes,
      ...studentServicesRoutes,
      ...announcementsRoutes,
      ...lecturerHomeRoutes,
      ...teachingRoutes,
      ...lecturerAssessmentRoutes,
      ...examRoutes,
      ...advisingRoutes,
      ...hrRoutes,
    ],
    errorBuilder: (context, state) => const _NotFoundRedirect(),
  );
});

class _NotFoundRedirect extends StatelessWidget {
  const _NotFoundRedirect();

  @override
  Widget build(BuildContext context) {
    WidgetsBinding.instance.addPostFrameCallback((_) {
      if (context.mounted) context.go(RoutePaths.splash);
    });
    return const Scaffold(body: Center(child: CircularProgressIndicator()));
  }
}
