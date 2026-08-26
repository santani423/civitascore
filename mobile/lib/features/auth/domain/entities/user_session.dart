class UserSessionDevice {
  const UserSessionDevice({
    required this.id,
    required this.deviceName,
    required this.platform,
  });

  final String id;
  final String? deviceName;
  final String? platform;
}

class UserSession {
  const UserSession({
    required this.id,
    required this.device,
    required this.ipAddress,
    required this.userAgent,
    required this.lastActivityAt,
    required this.isActive,
    required this.revokedAt,
  });

  final String id;
  final UserSessionDevice? device;
  final String? ipAddress;
  final String? userAgent;
  final DateTime? lastActivityAt;
  final bool isActive;
  final DateTime? revokedAt;
}
