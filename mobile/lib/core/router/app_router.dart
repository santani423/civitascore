import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import '../../features/auth/presentation/controllers/auth_controller.dart';
import '../../features/auth/presentation/screens/forgot_password_screen.dart';
import '../../features/auth/presentation/screens/login_screen.dart';
import '../../features/auth/presentation/screens/reset_password_screen.dart';
import '../../features/auth/presentation/screens/security_sessions_screen.dart';
import '../../features/dashboard/presentation/screens/dashboard_screen.dart';
import '../widgets/app_shell.dart';
import 'route_guard.dart';
import 'route_paths.dart';

/// Menyalakan ulang `redirect` GoRouter tiap kali sesi auth berubah, tanpa
/// membuat instance GoRouter baru (menjaga back-stack) — padanan
/// `authStateChangesProvider.stream` di dokumen (BAB 7.1), diimplementasikan
/// lewat `ref.listen` karena `AsyncNotifierProvider` tidak mengekspos Stream.
class _AuthRouterRefreshNotifier extends ChangeNotifier {
  _AuthRouterRefreshNotifier(Ref ref) {
    ref.listen(authControllerProvider, (_, _) => notifyListeners());
  }
}

final appRouterProvider = Provider<GoRouter>((ref) {
  final refreshNotifier = _AuthRouterRefreshNotifier(ref);

  return GoRouter(
    initialLocation: RoutePaths.dashboard,
    refreshListenable: refreshNotifier,
    redirect: (context, state) => authRedirect(ref, state),
    routes: [
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
      ShellRoute(
        builder: (context, state, child) => AppShell(child: child),
        routes: [
          GoRoute(
            path: RoutePaths.dashboard,
            builder: (_, _) => const DashboardScreen(),
          ),
          GoRoute(
            path: RoutePaths.pengaturanKeamanan,
            builder: (_, _) => const SecuritySessionsScreen(),
          ),
        ],
      ),
    ],
    errorBuilder: (_, _) => const _NotFoundRedirect(),
  );
});

class _NotFoundRedirect extends StatelessWidget {
  const _NotFoundRedirect();

  @override
  Widget build(BuildContext context) {
    WidgetsBinding.instance.addPostFrameCallback((_) {
      if (context.mounted) context.go(RoutePaths.dashboard);
    });
    return const Scaffold(body: Center(child: CircularProgressIndicator()));
  }
}
