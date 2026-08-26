class UserDevice {
  const UserDevice({
    required this.id,
    required this.deviceName,
    required this.deviceType,
    required this.platform,
    required this.isTrusted,
    required this.lastUsedAt,
  });

  final String id;
  final String? deviceName;
  final String deviceType;
  final String? platform;
  final bool isTrusted;
  final DateTime? lastUsedAt;
}
