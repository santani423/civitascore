import '../../domain/entities/user_session.dart';

class UserSessionModel extends UserSession {
  const UserSessionModel({
    required super.id,
    required super.device,
    required super.ipAddress,
    required super.userAgent,
    required super.lastActivityAt,
    required super.isActive,
    required super.revokedAt,
  });

  factory UserSessionModel.fromJson(Map<String, dynamic> json) {
    final deviceJson = json['device'] as Map<String, dynamic>?;

    return UserSessionModel(
      id: json['id'] as String,
      device: deviceJson == null
          ? null
          : UserSessionDevice(
              id: deviceJson['id'] as String,
              deviceName: deviceJson['device_name'] as String?,
              platform: deviceJson['platform'] as String?,
            ),
      ipAddress: json['ip_address'] as String?,
      userAgent: json['user_agent'] as String?,
      lastActivityAt: json['last_activity_at'] != null
          ? DateTime.parse(json['last_activity_at'] as String)
          : null,
      isActive: json['is_active'] as bool,
      revokedAt: json['revoked_at'] != null
          ? DateTime.parse(json['revoked_at'] as String)
          : null,
    );
  }
}
