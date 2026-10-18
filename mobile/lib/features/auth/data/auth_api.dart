import '../../../core/network/api_client.dart';
import '../../../core/network/api_envelope.dart';
import '../domain/auth_user.dart';

/// Auth endpoints (`app/Modules/Auth`) and `GET /tenant/profile`.
class AuthApi {
  AuthApi(this._api);
  final ApiClient _api;

  /// `POST /login` with either `nim` (students) or `email` (lecturers/staff).
  /// Returns the Sanctum token.
  Future<LoginResult> login({
    required String identifier,
    required bool byNim,
    required String password,
    String? universityCode,
    String? deviceIdentifier,
    String? deviceName,
  }) async {
    final data = await _api.post<Map<String, dynamic>>(
      '/login',
      data: {
        byNim ? 'nim' : 'email': identifier.trim(),
        'password': password,
        if (byNim && universityCode != null && universityCode.isNotEmpty)
          'university_code': universityCode,
        'device_identifier': ?deviceIdentifier,
      },
      headers: {'X-Device-Name': ?deviceName},
      decode: asMap,
    );
    final user = asMap(data['user']);
    return LoginResult(
      token: asString(data['token']) ?? '',
      mustChangePassword: asBool(user['must_change_password']),
    );
  }

  Future<AuthUser> me() =>
      _api.get('/me', decode: (d) => AuthUser.fromMeJson(asMap(d)));

  /// University of the current tenant (id, name, timezone, colours).
  Future<Map<String, dynamic>> tenantProfile() =>
      _api.get('/tenant/profile', decode: asMap);

  Future<void> logout() => _api.post('/logout');
  Future<void> logoutAll() => _api.post('/logout-all');

  Future<String?> forgotPassword(String email) =>
      _api.postForMessage('/forgot-password', data: {'email': email.trim()});

  Future<String?> resetPassword({
    required String token,
    required String email,
    required String password,
    required String confirmation,
  }) => _api.postForMessage(
    '/reset-password',
    data: {
      'token': token,
      'email': email.trim(),
      'password': password,
      'password_confirmation': confirmation,
    },
  );

  Future<String?> changePassword({
    required String current,
    required String password,
    required String confirmation,
  }) => _api.postForMessage(
    '/change-password',
    data: {
      'current_password': current,
      'password': password,
      'password_confirmation': confirmation,
    },
  );

  Future<List<UserSession>> sessions() =>
      _api.getList('/sessions', item: UserSession.fromJson);
  Future<void> revokeSession(String id) => _api.delete('/sessions/$id');
  Future<List<UserDevice>> devices() =>
      _api.getList('/devices', item: UserDevice.fromJson);
}

class LoginResult {
  const LoginResult({required this.token, required this.mustChangePassword});
  final String token;
  final bool mustChangePassword;
}
