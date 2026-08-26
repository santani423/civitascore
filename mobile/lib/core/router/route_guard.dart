import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import '../../features/auth/presentation/controllers/auth_controller.dart';
import 'route_paths.dart';

const _authRoutes = [
  RoutePaths.login,
  RoutePaths.forgotPassword,
  RoutePaths.resetPassword,
];

/// Padanan `ProtectedRoute`/`PublicOnlyRoute` React (BAB 7.4) — lapis pertama
/// dari dua lapis guard, memutuskan grup publik vs terproteksi. Lapis kedua
/// (per-permission) ada di [PermissionGate].
String? authRedirect(Ref ref, GoRouterState state) {
  final authState = ref.read(authControllerProvider);
  if (authState.isLoading) return null;

  final isAuthenticated = authState.valueOrNull != null;
  final isAuthRoute = _authRoutes.contains(state.matchedLocation);

  if (!isAuthenticated && !isAuthRoute) {
    return Uri(
      path: RoutePaths.login,
      queryParameters: {'from': state.matchedLocation},
    ).toString();
  }
  if (isAuthenticated && isAuthRoute) return RoutePaths.dashboard;
  return null;
}
