import 'package:flutter_secure_storage/flutter_secure_storage.dart';

import '../../../core/storage/storage_keys.dart';
import '../domain/auth_repository.dart';
import '../domain/entities/auth_session.dart';
import '../domain/entities/user_device.dart';
import '../domain/entities/user_session.dart';
import 'auth_remote_data_source.dart';

class AuthRepositoryImpl implements AuthRepository {
  AuthRepositoryImpl(this._remote, this._secureStorage);

  final AuthRemoteDataSource _remote;
  final FlutterSecureStorage _secureStorage;

  @override
  Future<AuthSession> login({
    required String email,
    required String password,
  }) async {
    final token = await _remote.login(email: email, password: password);
    await _secureStorage.write(key: StorageKeys.authToken, value: token);
    final user = await _remote.me();
    return AuthSession(token: token, user: user);
  }

  @override
  Future<void> logout() async {
    await _remote.logout();
    await clearStoredToken();
  }

  @override
  Future<void> logoutAll() async {
    await _remote.logoutAll();
    await clearStoredToken();
  }

  @override
  Future<void> forgotPassword({required String email}) =>
      _remote.forgotPassword(email: email);

  @override
  Future<void> resetPassword({
    required String token,
    required String email,
    required String password,
    required String passwordConfirmation,
  }) {
    return _remote.resetPassword(
      token: token,
      email: email,
      password: password,
      passwordConfirmation: passwordConfirmation,
    );
  }

  @override
  Future<String?> readStoredToken() =>
      _secureStorage.read(key: StorageKeys.authToken);

  @override
  Future<AuthSession> fetchCurrentSession(String token) async {
    final user = await _remote.me();
    return AuthSession(token: token, user: user);
  }

  @override
  Future<void> clearStoredToken() =>
      _secureStorage.delete(key: StorageKeys.authToken);

  @override
  Future<List<UserSession>> listSessions() => _remote.sessions();

  @override
  Future<void> revokeSession(String id) => _remote.revokeSession(id);

  @override
  Future<List<UserDevice>> listDevices() => _remote.devices();
}
