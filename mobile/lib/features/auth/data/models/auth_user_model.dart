import '../../domain/entities/auth_user.dart';

/// Built from the `GET /me` envelope `{user: {...}, roles: [...], permissions: [...]}`
/// (BAB 2.4) — `POST /login` only returns `{id, name, email}` without roles/permissions,
/// so the app always follows login with `/me` before an [AuthUser] can be constructed.
class AuthUserModel extends AuthUser {
  const AuthUserModel({
    required super.id,
    required super.name,
    required super.email,
    required super.roles,
    required super.permissions,
    required super.isSuperAdmin,
    required super.isStudent,
  });

  factory AuthUserModel.fromMeJson(Map<String, dynamic> json) {
    final user = json['user'] as Map<String, dynamic>;
    final roles = (json['roles'] as List? ?? const []).cast<String>();

    return AuthUserModel(
      id: user['id'] as String,
      name: user['name'] as String,
      email: user['email'] as String,
      roles: roles,
      permissions: (json['permissions'] as List? ?? const []).cast<String>(),
      isSuperAdmin: roles.contains('super_admin'),
      isStudent: roles.contains('student'),
    );
  }
}
