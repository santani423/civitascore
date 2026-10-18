import 'dart:convert';
import 'dart:math';

import 'package:flutter_secure_storage/flutter_secure_storage.dart';
import 'package:hive_ce_flutter/hive_ce_flutter.dart';

import 'storage_keys.dart';

abstract final class HiveBoxes {
  /// Encrypted cache of GET responses (per user), cleared on logout.
  static const responseCache = 'cache_responses_v1';
}

Future<void> initHive() async {
  await Hive.initFlutter();
}

/// Opens the AES-encrypted response cache. The 256-bit key is generated once
/// and kept in secure storage, so cached personal data (grades, bills) is not
/// readable from the app's files directory.
Future<Box<String>> openResponseCache(FlutterSecureStorage storage) async {
  if (Hive.isBoxOpen(HiveBoxes.responseCache)) {
    return Hive.box<String>(HiveBoxes.responseCache);
  }
  var encoded = await storage.read(key: StorageKeys.cacheEncryptionKey);
  if (encoded == null) {
    final random = Random.secure();
    final key = List<int>.generate(32, (_) => random.nextInt(256));
    encoded = base64UrlEncode(key);
    await storage.write(key: StorageKeys.cacheEncryptionKey, value: encoded);
  }
  return Hive.openBox<String>(
    HiveBoxes.responseCache,
    encryptionCipher: HiveAesCipher(base64Url.decode(encoded)),
  );
}
