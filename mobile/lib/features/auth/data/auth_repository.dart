import 'dart:convert';

import 'package:device_info_plus/device_info_plus.dart';
import 'package:flutter_secure_storage/flutter_secure_storage.dart';
import 'package:uuid/uuid.dart';

import '../../../core/config/env.dart';
import '../../../core/network/api_client.dart';
import '../../../core/network/api_envelope.dart';
import '../../../core/storage/storage_keys.dart';
import '../domain/auth_user.dart';
import 'auth_api.dart';

const _cachedUserKey = 'civitasone.cached_me';

/// Orchestrates the session lifecycle: token + last `/me` snapshot in secure
/// storage, tenant id discovery, device identity and cache cleanup.
class AuthRepository {
  AuthRepository(this._api, this._storage, this._client);

  final AuthApi _api;
  final FlutterSecureStorage _storage;
  final ApiClient _client;

  Future<String?> readToken() => _storage.read(key: StorageKeys.authToken);

  Future<AuthSession> login({
    required String identifier,
    required bool byNim,
    required String password,
  }) async {
    final result = await _api.login(
      identifier: identifier,
      byNim: byNim,
      password: password,
      universityCode: Env.universityCode,
      deviceIdentifier: await _deviceIdentifier(),
      deviceName: await _deviceName(),
    );
    await _client.clearCache();
    await _storage.write(key: StorageKeys.authToken, value: result.token);
    try {
      final user = await _api.me();
      await _storeUser(user);
      await _rememberUniversity();
      return AuthSession(token: result.token, user: user);
    } catch (_) {
      await clearLocal();
      rethrow;
    }
  }

  /// Revalidates the stored token with `/me`; refreshes the local snapshot.
  Future<AuthUser> fetchMe() async {
    final user = await _api.me();
    await _storeUser(user);
    return user;
  }

  Future<AuthUser?> readCachedUser() async {
    final raw = await _storage.read(key: _cachedUserKey);
    if (raw == null) return null;
    try {
      return AuthUser.fromMeJson(asMap(jsonDecode(raw)));
    } catch (_) {
      return null;
    }
  }

  Future<void> markPasswordChanged(AuthUser user) =>
      _storeUser(user.copyWith(mustChangePassword: false));

  Future<void> logout({bool allDevices = false}) async {
    try {
      allDevices ? await _api.logoutAll() : await _api.logout();
    } finally {
      await clearLocal();
    }
  }

  /// Removes token, user snapshot, tenant id and cached responses.
  Future<void> clearLocal() async {
    await _storage.delete(key: StorageKeys.authToken);
    await _storage.delete(key: _cachedUserKey);
    await _storage.delete(key: StorageKeys.selectedUniversityId);
    await _client.clearCache();
  }

  Future<void> _storeUser(AuthUser user) =>
      _storage.write(key: _cachedUserKey, value: jsonEncode(user.toMeJson()));

  /// Learns the university ULID for `X-University-ID` (the backend resolved
  /// the tenant from the user's default membership for this call).
  Future<void> _rememberUniversity() async {
    if (Env.universityId.isNotEmpty) return;
    try {
      final profile = await _api.tenantProfile();
      final id = asString(profile['id']);
      if (id != null && id.isNotEmpty) {
        await _storage.write(key: StorageKeys.selectedUniversityId, value: id);
      }
    } catch (_) {
      // Non-fatal: requests fall back to the default membership.
    }
  }

  Future<String> _deviceIdentifier() async {
    final existing = await _storage.read(key: StorageKeys.deviceIdentifier);
    if (existing != null) return existing;
    final id = const Uuid().v4();
    await _storage.write(key: StorageKeys.deviceIdentifier, value: id);
    return id;
  }

  Future<String?> _deviceName() async {
    try {
      final info = await DeviceInfoPlugin().androidInfo;
      return '${info.manufacturer} ${info.model}'.trim();
    } catch (_) {
      return null;
    }
  }
}
