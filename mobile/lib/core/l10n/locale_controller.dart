import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../storage/local_prefs.dart';
import '../storage/storage_keys.dart';
import 'app_language.dart';

class LocaleController extends Notifier<AppLanguage> {
  @override
  AppLanguage build() {
    final saved = ref
        .watch(sharedPreferencesProvider)
        .getString(StorageKeys.locale);
    return AppLanguage.fromCode(saved);
  }

  Future<void> setLanguage(AppLanguage language) async {
    state = language;
    await ref
        .read(sharedPreferencesProvider)
        .setString(StorageKeys.locale, language.code);
  }
}

final localeControllerProvider =
    NotifierProvider<LocaleController, AppLanguage>(LocaleController.new);
