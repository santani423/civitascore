import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../../core/network/api_failure.dart';
import '../../../../core/network/dio_client.dart';
import '../../../../core/storage/secure_storage.dart';
import '../../data/auth_remote_data_source.dart';
import '../../data/auth_repository_impl.dart';
import '../../domain/auth_repository.dart';
import '../../domain/entities/auth_session.dart';

final authRepositoryProvider = Provider<AuthRepository>((ref) {
  return AuthRepositoryImpl(
    AuthRemoteDataSource(ref.watch(dioProvider)),
    ref.watch(secureStorageProvider),
  );
});

/// `null` state means "no active session" (not "loading") once resolved —
/// callers should check `isLoading`/`hasValue` for the initial restore.
class AuthController extends AsyncNotifier<AuthSession?> {
  @override
  Future<AuthSession?> build() async {
    final repository = ref.watch(authRepositoryProvider);
    final token = await repository.readStoredToken();
    if (token == null) return null;

    try {
      return await repository.fetchCurrentSession(token);
    } on ApiFailure {
      await repository.clearStoredToken();
      return null;
    }
  }

  Future<void> login({required String email, required String password}) async {
    state = const AsyncLoading();
    final repository = ref.read(authRepositoryProvider);
    state = await AsyncValue.guard(
      () => repository.login(email: email, password: password),
    );
  }

  Future<void> logout() async {
    final repository = ref.read(authRepositoryProvider);
    try {
      await repository.logout();
    } on ApiFailure {
      // Sesi lokal tetap dihapus meski request logout gagal (mis. token sudah kedaluwarsa).
    }
    state = const AsyncData(null);
  }

  Future<void> logoutAll() async {
    final repository = ref.read(authRepositoryProvider);
    try {
      await repository.logoutAll();
    } on ApiFailure {
      // Sesi lokal tetap dihapus meski request logout gagal.
    }
    state = const AsyncData(null);
  }

  /// Dipanggil interceptor Dio saat 401 global (BAB 3.4) — memaksa keluar
  /// tanpa request /logout tambahan karena sesi di server sudah tidak valid.
  Future<void> clearSession() async {
    await ref.read(authRepositoryProvider).clearStoredToken();
    state = const AsyncData(null);
  }
}

final authControllerProvider =
    AsyncNotifierProvider<AuthController, AuthSession?>(AuthController.new);
