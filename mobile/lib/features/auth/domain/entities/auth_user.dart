class AuthUser {
  const AuthUser({
    required this.id,
    required this.name,
    required this.email,
    required this.roles,
    required this.permissions,
    this.isSuperAdmin = false,
    this.isStudent = false,
  });

  final String id;
  final String name;
  final String email;
  final List<String> roles;
  final List<String> permissions;
  final bool isSuperAdmin;
  final bool isStudent;

  bool hasPermission(String permission) => permissions.contains(permission);
}
