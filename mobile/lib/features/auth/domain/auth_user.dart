import '../../../core/network/api_envelope.dart';

/// Logged-in user built from `GET /me` (`{user, roles, permissions}`).
/// `POST /login` returns no roles, so the app always follows login with /me.
class AuthUser {
  const AuthUser({
    required this.id,
    required this.name,
    required this.email,
    required this.roles,
    required this.permissions,
    this.nim,
    this.mustChangePassword = false,
  });

  factory AuthUser.fromMeJson(Map<String, dynamic> json) {
    final user = asMap(json['user']);
    return AuthUser(
      id: asString(user['id']) ?? '',
      name: asString(user['name']) ?? '',
      email: asString(user['email']) ?? '',
      nim: asString(user['nim']),
      mustChangePassword: asBool(user['must_change_password']),
      roles: asStringList(json['roles']),
      permissions: asStringList(json['permissions']),
    );
  }

  final String id;
  final String name;
  final String email;
  final String? nim;
  final bool mustChangePassword;
  final List<String> roles;
  final List<String> permissions;

  bool hasPermission(String permission) => permissions.contains(permission);
  bool hasAnyPermission(Iterable<String> list) =>
      list.any(permissions.contains);

  /// Same rules as the web portal: student by role, lecturer by
  /// `lecturer_profile.read`, advisor by `krs_advising.read`.
  bool get isStudent => roles.contains('student');
  bool get isLecturer =>
      hasPermission('lecturer_profile.read') || roles.contains('lecturer');
  bool get isAdvisor => hasPermission('krs_advising.read');

  AuthUser copyWith({bool? mustChangePassword}) => AuthUser(
    id: id,
    name: name,
    email: email,
    nim: nim,
    roles: roles,
    permissions: permissions,
    mustChangePassword: mustChangePassword ?? this.mustChangePassword,
  );

  Map<String, dynamic> toMeJson() => {
    'user': {
      'id': id,
      'name': name,
      'email': email,
      'nim': nim,
      'must_change_password': mustChangePassword,
    },
    'roles': roles,
    'permissions': permissions,
  };
}

class AuthSession {
  const AuthSession({
    required this.token,
    required this.user,
    this.restoredOffline = false,
  });

  final String token;
  final AuthUser user;

  /// True when the session was restored from the local copy because the
  /// server could not be reached at app start.
  final bool restoredOffline;
}

class UserSession {
  const UserSession({
    required this.id,
    this.deviceName,
    this.platform,
    this.ipAddress,
    this.userAgent,
    this.lastActivityAt,
    this.isActive = false,
    this.revokedAt,
  });

  factory UserSession.fromJson(Map<String, dynamic> json) {
    final device = asMap(json['device']);
    return UserSession(
      id: asString(json['id']) ?? '',
      deviceName: asString(device['device_name']),
      platform: asString(device['platform']),
      ipAddress: asString(json['ip_address']),
      userAgent: asString(json['user_agent']),
      lastActivityAt: asDateTime(json['last_activity_at']),
      isActive: asBool(json['is_active']),
      revokedAt: asDateTime(json['revoked_at']),
    );
  }

  final String id;
  final String? deviceName;
  final String? platform;
  final String? ipAddress;
  final String? userAgent;
  final DateTime? lastActivityAt;
  final bool isActive;
  final DateTime? revokedAt;
}

class UserDevice {
  const UserDevice({
    required this.id,
    this.deviceName,
    this.deviceType,
    this.platform,
    this.isTrusted = false,
    this.lastUsedAt,
  });

  factory UserDevice.fromJson(Map<String, dynamic> json) => UserDevice(
    id: asString(json['id']) ?? '',
    deviceName: asString(json['device_name']),
    deviceType: asString(json['device_type']),
    platform: asString(json['platform']),
    isTrusted: asBool(json['is_trusted']),
    lastUsedAt: asDateTime(json['last_used_at']),
  );

  final String id;
  final String? deviceName;
  final String? deviceType;
  final String? platform;
  final bool isTrusted;
  final DateTime? lastUsedAt;
}
