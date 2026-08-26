import 'entities/auth_session.dart';
import 'entities/user_device.dart';
import 'entities/user_session.dart';

abstract interface class AuthRepository {
  Future<AuthSession> login({required String email, required String password});
  Future<void> logout();
  Future<void> logoutAll();
  Future<void> forgotPassword({required String email});
  Future<void> resetPassword({
    required String token,
    required String email,
    required String password,
    required String passwordConfirmation,
  });
  Future<String?> readStoredToken();
  Future<AuthSession> fetchCurrentSession(String token);
  Future<void> clearStoredToken();
  Future<List<UserSession>> listSessions();
  Future<void> revokeSession(String id);
  Future<List<UserDevice>> listDevices();
}
