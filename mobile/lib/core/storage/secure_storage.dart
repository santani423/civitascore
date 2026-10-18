import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:flutter_secure_storage/flutter_secure_storage.dart';

/// Token, university id and the cache encryption key live in the Android
/// Keystore-backed secure storage — never in SharedPreferences.
final secureStorageProvider = Provider<FlutterSecureStorage>((ref) {
  return const FlutterSecureStorage(aOptions: AndroidOptions());
});
