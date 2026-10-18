abstract final class StorageKeys {
  // Secure storage (Keystore-backed).
  static const authToken = 'civitasone.auth_token';
  static const selectedUniversityId = 'civitasone.selected_university_id';
  static const deviceIdentifier = 'civitasone.device_identifier';
  static const cacheEncryptionKey = 'civitasone.cache_key';

  // SharedPreferences (non-sensitive UI preferences).
  static const theme = 'civitasone.theme';
  static const locale = 'civitasone.locale';
  static const biometricEnabled = 'civitasone.biometric_enabled';
  static const activeExperience = 'civitasone.active_experience';
  static const lastLoginIdentifier = 'civitasone.last_login_identifier';
}
