import 'package:flutter/material.dart';
import 'package:flutter_localizations/flutter_localizations.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import 'core/config/env.dart';
import 'core/l10n/app_language.dart';
import 'core/l10n/locale_controller.dart';
import 'core/router/app_router.dart';
import 'core/security/app_lock.dart';
import 'core/theme/app_theme.dart';
import 'core/theme/theme_controller.dart';

class CivitasOneApp extends ConsumerWidget {
  const CivitasOneApp({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final themeMode = ref.watch(themeControllerProvider);
    final language = ref.watch(localeControllerProvider);
    final router = ref.watch(appRouterProvider);

    return AppLockLifecycle(
      child: MaterialApp.router(
        title: Env.appName,
        debugShowCheckedModeBanner: false,
        themeMode: themeMode,
        theme: AppTheme.light(),
        darkTheme: AppTheme.dark(),
        routerConfig: router,
        locale: language.locale,
        supportedLocales: AppLanguage.supportedLocales,
        localizationsDelegates: const [
          GlobalMaterialLocalizations.delegate,
          GlobalWidgetsLocalizations.delegate,
          GlobalCupertinoLocalizations.delegate,
        ],
      ),
    );
  }
}
