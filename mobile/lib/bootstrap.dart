import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:intl/date_symbol_data_local.dart';
import 'package:shared_preferences/shared_preferences.dart';

import 'app.dart';
import 'core/config/env.dart';
import 'core/config/flavor.dart';
import 'core/network/api_client.dart';
import 'core/storage/hive_boxes.dart';
import 'core/storage/local_prefs.dart';
import 'core/storage/secure_storage.dart';

Future<void> bootstrap(Flavor flavor) async {
  WidgetsFlutterBinding.ensureInitialized();
  Env.init(flavor);
  await Future.wait([
    initializeDateFormatting('id_ID'),
    initializeDateFormatting('en_US'),
    initializeDateFormatting('zh_CN'),
    initHive(),
  ]);
  final prefs = await SharedPreferences.getInstance();

  // Encrypted offline cache; if it cannot be opened (corrupted key/box) the
  // app still works online-only.
  final container = ProviderContainer(
    overrides: [sharedPreferencesProvider.overrideWithValue(prefs)],
  );
  final cache = await openResponseCache(
    container.read(secureStorageProvider),
  ).then<dynamic>((b) => b, onError: (_) => null);
  container.dispose();

  runApp(
    ProviderScope(
      overrides: [
        sharedPreferencesProvider.overrideWithValue(prefs),
        if (cache != null) responseCacheProvider.overrideWithValue(cache),
      ],
      child: const CivitasOneApp(),
    ),
  );
}
