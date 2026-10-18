import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../../core/network/api_client.dart';
import '../../../../core/network/api_failure.dart';
import '../../../../core/session/session_signals.dart';
import '../../../../core/storage/secure_storage.dart';
import '../../data/auth_api.dart';
import '../../data/auth_repository.dart';
import '../../domain/auth_user.dart';

final authApiProvider = Provider<AuthApi>(
  (ref) => AuthApi(ref.watch(apiClientProvider)),
);

final authRepositoryProvider = Provider<AuthRepository>((ref) {
  return AuthRepository(
    ref.watch(authApiProvider),
    ref.watch(secureStorageProvider),
    ref.watch(apiClientProvider),
  );
});

/// Session state. `null` = signed out (once resolved).
class AuthController extends AsyncNotifier<AuthSession?> {
  @override
  Future<AuthSession?> build() async {
    // Token revoked elsewhere (401 from any request) → sign out locally.
    ref.listen<int>(unauthorizedSignalProvider, (previous, next) {
      if (state.valueOrNull != null) clearSession();
    });

    final repo = ref.read(authRepositoryProvider);
    final token = await repo.readToken();
    if (token == null) return null;

    try {
      final user = await repo.fetchMe();
      return AuthSession(token: token, user: user);
    } on ApiFailure catch (failure) {
      if (failure.kind == ApiFailureKind.unauthorized ||
          failure.kind == ApiFailureKind.forbidden) {
        await repo.clearLocal();
        return null;
      }
      // Offline / server error at cold start: keep the user signed in with
      // the last known profile instead of logging them out.
      final cached = await repo.readCachedUser();
      if (cached != null) {
        return AuthSession(token: token, user: cached, restoredOffline: true);
      }
      rethrow;
    }
  }

  /// Throws [ApiFailure] on failure; state stays signed-out so the login
  /// form can show field errors.
  Future<void> login({
    required String identifier,
    required bool byNim,
    required String password,
  }) async {
    final session = await ref
        .read(authRepositoryProvider)
        .login(identifier: identifier, byNim: byNim, password: password);
    state = AsyncData(session);
  }

  /// Retries `/me` after an offline restore (e.g. when connectivity returns).
  Future<void> revalidate() async {
    final current = state.valueOrNull;
    if (current == null) return;
    try {
      final user = await ref.read(authRepositoryProvider).fetchMe();
      state = AsyncData(AuthSession(token: current.token, user: user));
    } on ApiFailure catch (f) {
      if (f.kind == ApiFailureKind.unauthorized) await clearSession();
    }
  }

  void markPasswordChanged() {
    final current = state.valueOrNull;
    if (current == null) return;
    final user = current.user.copyWith(mustChangePassword: false);
    ref.read(authRepositoryProvider).markPasswordChanged(user);
    state = AsyncData(AuthSession(token: current.token, user: user));
  }

  Future<void> logout({bool allDevices = false}) async {
    try {
      await ref.read(authRepositoryProvider).logout(allDevices: allDevices);
    } catch (_) {
      // Local session is cleared even if the server call fails.
    }
    state = const AsyncData(null);
  }

  Future<void> clearSession() async {
    await ref.read(authRepositoryProvider).clearLocal();
    state = const AsyncData(null);
  }
}

final authControllerProvider =
    AsyncNotifierProvider<AuthController, AuthSession?>(AuthController.new);

/// Convenience: current user or null.
final currentUserProvider = Provider<AuthUser?>(
  (ref) => ref.watch(authControllerProvider).valueOrNull?.user,
);
