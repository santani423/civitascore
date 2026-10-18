import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import 'package:package_info_plus/package_info_plus.dart';

import '../../../core/config/env.dart';
import '../../../core/l10n/app_language.dart';
import '../../../core/l10n/core_strings.dart';
import '../../../core/l10n/locale_controller.dart';
import '../../../core/router/route_paths.dart';
import '../../../core/security/app_lock.dart';
import '../../../core/theme/theme_controller.dart';
import '../../../core/widgets/app_feedback.dart';
import '../../../core/widgets/ui_kit.dart';
import '../l10n/account_strings.dart';

final _packageInfoProvider = FutureProvider<PackageInfo>(
  (ref) => PackageInfo.fromPlatform(),
);

class SettingsScreen extends ConsumerWidget {
  const SettingsScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final s = context.l10n;
    final a = context.accountL10n;
    final language = ref.watch(localeControllerProvider);
    final themeMode = ref.watch(themeControllerProvider);
    final biometricOn = ref.watch(biometricEnabledProvider);
    final info = ref.watch(_packageInfoProvider).valueOrNull;

    return Scaffold(
      appBar: AppBar(title: Text(s.settings)),
      body: ListView(
        children: [
          SectionHeader(title: a.preferences),
          ListTile(
            leading: const Icon(Icons.language),
            title: Text(a.language),
            subtitle: Text(language.nativeName),
            onTap: () => showModalBottomSheet<void>(
              context: context,
              builder: (ctx) => SafeArea(
                child: RadioGroup<AppLanguage>(
                  groupValue: language,
                  onChanged: (l) {
                    if (l != null) {
                      ref
                          .read(localeControllerProvider.notifier)
                          .setLanguage(l);
                    }
                    Navigator.pop(ctx);
                  },
                  child: Column(
                    mainAxisSize: MainAxisSize.min,
                    children: [
                      for (final l in AppLanguage.values)
                        RadioListTile<AppLanguage>(
                          value: l,
                          title: Text(l.nativeName),
                        ),
                    ],
                  ),
                ),
              ),
            ),
          ),
          Padding(
            padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(a.theme, style: Theme.of(context).textTheme.titleSmall),
                const SizedBox(height: 8),
                SizedBox(
                  width: double.infinity,
                  child: SegmentedButton<ThemeMode>(
                    segments: [
                      ButtonSegment(
                        value: ThemeMode.system,
                        label: Text(a.themeSystem),
                        icon: const Icon(Icons.brightness_auto_outlined),
                      ),
                      ButtonSegment(
                        value: ThemeMode.light,
                        label: Text(a.themeLight),
                        icon: const Icon(Icons.light_mode_outlined),
                      ),
                      ButtonSegment(
                        value: ThemeMode.dark,
                        label: Text(a.themeDark),
                        icon: const Icon(Icons.dark_mode_outlined),
                      ),
                    ],
                    selected: {themeMode},
                    showSelectedIcon: false,
                    onSelectionChanged: (v) => ref
                        .read(themeControllerProvider.notifier)
                        .setTheme(v.first),
                  ),
                ),
              ],
            ),
          ),
          SectionHeader(title: a.security),
          SwitchListTile(
            secondary: const Icon(Icons.fingerprint),
            title: Text(a.biometric),
            subtitle: Text(a.biometricSubtitle),
            value: biometricOn,
            onChanged: (value) async {
              final lock = ref.read(appLockProvider.notifier);
              if (value) {
                if (!await lock.isSupported()) {
                  if (context.mounted) {
                    showAppSnack(
                      context,
                      a.biometricUnsupported,
                      isError: true,
                    );
                  }
                  return;
                }
                if (!context.mounted) return;
                final ok = await lock.authenticate(a.biometric);
                if (!ok) return;
              }
              await ref
                  .read(biometricEnabledProvider.notifier)
                  .setEnabled(value);
            },
          ),
          ListTile(
            leading: const Icon(Icons.password_rounded),
            title: Text(a.changePassword),
            trailing: const Icon(Icons.chevron_right),
            onTap: () => context.push(RoutePaths.changePassword),
          ),
          ListTile(
            leading: const Icon(Icons.devices_outlined),
            title: Text(a.sessions),
            trailing: const Icon(Icons.chevron_right),
            onTap: () => context.push(RoutePaths.securitySessions),
          ),
          SectionHeader(title: a.about),
          ListTile(
            leading: const Icon(Icons.info_outline),
            title: Text(s.appName),
            subtitle: Text(
              '${a.version} ${info?.version ?? '-'} (${info?.buildNumber ?? '-'})',
            ),
          ),
          if (!Env.isProduction)
            ListTile(
              leading: const Icon(Icons.dns_outlined),
              title: Text(a.server),
              subtitle: Text('${Env.flavor.name} · ${Env.apiBaseUrl}'),
            ),
          const SizedBox(height: 24),
          Center(child: StatusChip(label: s.appTagline, dense: true)),
          const SizedBox(height: 24),
        ],
      ),
    );
  }
}
