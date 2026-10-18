import 'package:flutter/widgets.dart';

/// Supported UI languages. Mandarin uses Simplified Chinese (zh-Hans),
/// matching the Civitas One web prototype (`frontend/src/one/i18n`).
enum AppLanguage {
  id('id', 'Bahasa Indonesia', Locale('id')),
  en('en', 'English', Locale('en')),
  zh('zh', '简体中文', Locale('zh'));

  const AppLanguage(this.code, this.nativeName, this.locale);

  final String code;
  final String nativeName;
  final Locale locale;

  /// Locale tag for `intl` formatters.
  String get intlTag => switch (this) {
    AppLanguage.id => 'id_ID',
    AppLanguage.en => 'en_US',
    AppLanguage.zh => 'zh_CN',
  };

  static AppLanguage fromCode(String? code) => AppLanguage.values.firstWhere(
    (l) => l.code == code,
    orElse: () => AppLanguage.id,
  );

  /// Language of the nearest [Localizations] (falls back to Indonesian).
  static AppLanguage of(BuildContext context) =>
      fromCode(Localizations.maybeLocaleOf(context)?.languageCode);

  static List<Locale> get supportedLocales =>
      AppLanguage.values.map((l) => l.locale).toList();
}

/// Picks the string for the current language. Used by every feature's
/// typed strings class: `pick(context, id: '…', en: '…', zh: '…')`.
String pick(
  BuildContext context, {
  required String id,
  required String en,
  required String zh,
}) => switch (AppLanguage.of(context)) {
  AppLanguage.id => id,
  AppLanguage.en => en,
  AppLanguage.zh => zh,
};
