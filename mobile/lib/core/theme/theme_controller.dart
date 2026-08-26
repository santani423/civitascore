import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../storage/local_prefs.dart';
import '../storage/storage_keys.dart';

class ThemeController extends Notifier<ThemeMode> {
  @override
  ThemeMode build() {
    final saved = ref
        .watch(sharedPreferencesProvider)
        .getString(StorageKeys.theme);
    return saved != null ? ThemeMode.values.byName(saved) : ThemeMode.system;
  }

  Future<void> setTheme(ThemeMode mode) async {
    state = mode;
    await ref
        .read(sharedPreferencesProvider)
        .setString(StorageKeys.theme, mode.name);
  }
}

final themeControllerProvider = NotifierProvider<ThemeController, ThemeMode>(
  ThemeController.new,
);
