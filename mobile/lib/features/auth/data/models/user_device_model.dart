import '../../domain/entities/user_device.dart';

class UserDeviceModel extends UserDevice {
  const UserDeviceModel({
    required super.id,
    required super.deviceName,
    required super.deviceType,
    required super.platform,
    required super.isTrusted,
    required super.lastUsedAt,
  });

  factory UserDeviceModel.fromJson(Map<String, dynamic> json) {
    return UserDeviceModel(
      id: json['id'] as String,
      deviceName: json['device_name'] as String?,
      deviceType: json['device_type'] as String,
      platform: json['platform'] as String?,
      isTrusted: json['is_trusted'] as bool,
      lastUsedAt: json['last_used_at'] != null
          ? DateTime.parse(json['last_used_at'] as String)
          : null,
    );
  }
}
