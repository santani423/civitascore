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
import '../../../core/session/app_experience.dart';
import '../../../core/theme/app_dimensions.dart';
import '../../../core/theme/theme_controller.dart';
import '../../../core/widgets/app_feedback.dart';
import '../../../core/widgets/ui_kit.dart';
import '../../auth/presentation/controllers/auth_controller.dart';
import '../l10n/account_strings.dart';

final _packageInfoProvider = FutureProvider<PackageInfo>(
  (ref) => PackageInfo.fromPlatform(),
);

/// Pengaturan (mockup "Pengaturan"): kartu profil di atas, lalu grup akun &
/// keamanan, preferensi aplikasi, serta bantuan & informasi.
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
    final user = ref.watch(authControllerProvider).valueOrNull?.user;
    final isStudent =
        ref.watch(activeExperienceProvider) == AppExperience.student;
    final profileRoute = isStudent
        ? RoutePaths.studentProfile
        : RoutePaths.lecturerProfile;

    return Scaffold(
      appBar: AppBar(title: Text(s.settings)),
      body: ListView(
        padding: const EdgeInsets.only(bottom: AppSpacing.xl3),
        children: [
          Padding(
            padding: const EdgeInsets.all(AppSpacing.lg),
            child: _ProfileCard(
              name: user?.name ?? '-',
              detail: user?.nim ?? user?.email ?? '',
              onTap: () => context.push(profileRoute),
            ),
          ),
          SectionHeader(title: a.accountAndSecurity),
          _SettingsGroup(
            children: [
              _SettingsRow(
                icon: Icons.person_outline_rounded,
                title: a.editProfile,
                onTap: () => context.push(profileRoute),
              ),
              _SettingsRow(
                icon: Icons.password_rounded,
                title: a.changePassword,
                onTap: () => context.push(RoutePaths.changePassword),
              ),
              _SettingsRow(
                icon: Icons.devices_outlined,
                title: a.sessions,
                onTap: () => context.push(RoutePaths.securitySessions),
              ),
            ],
          ),
          SectionHeader(title: a.appPreferences),
          _SettingsGroup(
            children: [
              _ThemeSelector(
                title: a.displayMode,
                selected: themeMode,
                labels: (
                  system: a.themeSystem,
                  light: a.themeLight,
                  dark: a.themeDark,
                ),
                onChanged: (mode) =>
                    ref.read(themeControllerProvider.notifier).setTheme(mode),
              ),
              _SettingsRow(
                icon: Icons.language,
                title: a.language,
                subtitle: language.nativeName,
                onTap: () => _pickLanguage(context, ref, language),
              ),
              _SettingsRow(
                icon: Icons.fingerprint,
                title: a.biometric,
                subtitle: a.biometricSubtitle,
                trailing: Switch(
                  value: biometricOn,
                  onChanged: (value) => _setBiometric(context, ref, value, a),
                ),
              ),
              _SettingsRow(
                icon: Icons.notifications_none_rounded,
                title: a.notificationSettings,
                subtitle: a.notificationSettingsSubtitle,
                onTap: () => context.push(RoutePaths.notificationPreferences),
              ),
            ],
          ),
          SectionHeader(title: a.helpAndInfo),
          _SettingsGroup(
            children: [
              _SettingsRow(
                icon: Icons.info_outline_rounded,
                title: a.aboutApp,
                subtitle:
                    '${a.version} ${info?.version ?? '-'} (${info?.buildNumber ?? '-'})',
              ),
              _SettingsRow(
                icon: Icons.privacy_tip_outlined,
                title: a.privacyPolicy,
                subtitle: a.notAvailableYet,
                onTap: () =>
                    context.push(RoutePaths.pendingFeature('privacy_consent')),
              ),
            ],
          ),
          if (!Env.isProduction)
            _SettingsGroup(
              children: [
                _SettingsRow(
                  icon: Icons.dns_outlined,
                  title: a.server,
                  subtitle: '${Env.flavor.name} · ${Env.apiBaseUrl}',
                ),
              ],
            ),
          const SizedBox(height: AppSpacing.xl2),
          Center(child: StatusChip(label: s.appTagline, dense: true)),
        ],
      ),
    );
  }
}

void _pickLanguage(BuildContext context, WidgetRef ref, AppLanguage current) {
  showModalBottomSheet<void>(
    context: context,
    builder: (ctx) => SafeArea(
      child: RadioGroup<AppLanguage>(
        groupValue: current,
        onChanged: (l) {
          if (l != null) {
            ref.read(localeControllerProvider.notifier).setLanguage(l);
          }
          Navigator.pop(ctx);
        },
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            for (final l in AppLanguage.values)
              RadioListTile<AppLanguage>(value: l, title: Text(l.nativeName)),
          ],
        ),
      ),
    ),
  );
}

/// Biometrik hanya diaktifkan setelah perangkat mendukung dan pengguna
/// berhasil diautentikasi; mematikannya tidak memerlukan verifikasi.
Future<void> _setBiometric(
  BuildContext context,
  WidgetRef ref,
  bool value,
  AccountStrings a,
) async {
  final lock = ref.read(appLockProvider.notifier);
  if (value) {
    if (!await lock.isSupported()) {
      if (context.mounted) {
        showAppSnack(context, a.biometricUnsupported, isError: true);
      }
      return;
    }
    if (!context.mounted) return;
    final ok = await lock.authenticate(a.biometric);
    if (!ok) return;
  }
  await ref.read(biometricEnabledProvider.notifier).setEnabled(value);
}

class _ProfileCard extends StatelessWidget {
  const _ProfileCard({
    required this.name,
    required this.detail,
    required this.onTap,
  });
  final String name;
  final String detail;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    final text = Theme.of(context).textTheme;
    final scheme = Theme.of(context).colorScheme;
    return AppSurfaceCard(
      onTap: onTap,
      padding: const EdgeInsets.all(AppSpacing.lg),
      child: Row(
        children: [
          CircleAvatar(
            radius: 26,
            backgroundColor: scheme.primaryContainer,
            child: Text(
              name.isEmpty ? '?' : name.characters.first.toUpperCase(),
              style: text.titleLarge?.copyWith(
                color: scheme.onPrimaryContainer,
              ),
            ),
          ),
          const SizedBox(width: AppSpacing.lg),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  name,
                  style: text.titleMedium,
                  maxLines: 2,
                  overflow: TextOverflow.ellipsis,
                ),
                if (detail.isNotEmpty)
                  Text(
                    detail,
                    style: text.bodySmall,
                    maxLines: 1,
                    overflow: TextOverflow.ellipsis,
                  ),
              ],
            ),
          ),
          Icon(Icons.chevron_right_rounded, color: scheme.onSurfaceVariant),
        ],
      ),
    );
  }
}

/// Kartu berisi beberapa baris yang dipisah garis tipis.
class _SettingsGroup extends StatelessWidget {
  const _SettingsGroup({required this.children});
  final List<Widget> children;

  @override
  Widget build(BuildContext context) {
    final divider = Divider(
      height: 1,
      // Garis mulai di bawah judul, melewati ikon dan jarak.
      indent: AppSpacing.md + 40 + AppSpacing.md,
      color: Theme.of(context).colorScheme.outlineVariant,
    );
    return Padding(
      padding: const EdgeInsets.symmetric(horizontal: AppSpacing.lg),
      child: AppSurfaceCard(
        padding: EdgeInsets.zero,
        child: Column(
          children: [
            for (var i = 0; i < children.length; i++) ...[
              if (i > 0) divider,
              children[i],
            ],
          ],
        ),
      ),
    );
  }
}

class _SettingsRow extends StatelessWidget {
  const _SettingsRow({
    required this.icon,
    required this.title,
    this.subtitle,
    this.trailing,
    this.onTap,
  });
  final IconData icon;
  final String title;
  final String? subtitle;
  final Widget? trailing;
  final VoidCallback? onTap;

  @override
  Widget build(BuildContext context) {
    final text = Theme.of(context).textTheme;
    final scheme = Theme.of(context).colorScheme;
    return InkWell(
      onTap: onTap,
      child: Padding(
        padding: const EdgeInsets.symmetric(
          horizontal: AppSpacing.md,
          vertical: AppSpacing.sm,
        ),
        child: Row(
          children: [
            _IconBox(icon: icon),
            const SizedBox(width: AppSpacing.md),
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(title, style: text.titleSmall),
                  if (subtitle != null)
                    Text(
                      subtitle!,
                      style: text.bodySmall?.copyWith(
                        color: scheme.onSurfaceVariant,
                      ),
                    ),
                ],
              ),
            ),
            trailing ??
                (onTap == null
                    ? const SizedBox.shrink()
                    : Icon(
                        Icons.chevron_right_rounded,
                        color: scheme.onSurfaceVariant,
                      )),
          ],
        ),
      ),
    );
  }
}

/// Pemilih tema: tiga pilihan dalam satu segmented control di bawah judul.
class _ThemeSelector extends StatelessWidget {
  const _ThemeSelector({
    required this.title,
    required this.selected,
    required this.labels,
    required this.onChanged,
  });
  final String title;
  final ThemeMode selected;
  final ({String system, String light, String dark}) labels;
  final ValueChanged<ThemeMode> onChanged;

  @override
  Widget build(BuildContext context) {
    final text = Theme.of(context).textTheme;
    return Padding(
      padding: const EdgeInsets.all(AppSpacing.md),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              _IconBox(icon: Icons.dark_mode_outlined),
              const SizedBox(width: AppSpacing.md),
              Text(title, style: text.titleSmall),
            ],
          ),
          const SizedBox(height: AppSpacing.sm),
          SizedBox(
            width: double.infinity,
            child: SegmentedButton<ThemeMode>(
              // Tanpa ikon: tiga segmen dengan label dan ikon tidak muat di
              // lebar 360 dp (kartu grup menyisakan ±300 dp).
              segments: [
                ButtonSegment(
                  value: ThemeMode.system,
                  label: Text(labels.system),
                ),
                ButtonSegment(
                  value: ThemeMode.light,
                  label: Text(labels.light),
                ),
                ButtonSegment(value: ThemeMode.dark, label: Text(labels.dark)),
              ],
              selected: {selected},
              showSelectedIcon: false,
              onSelectionChanged: (v) => onChanged(v.first),
            ),
          ),
        ],
      ),
    );
  }
}

class _IconBox extends StatelessWidget {
  const _IconBox({required this.icon});
  final IconData icon;

  @override
  Widget build(BuildContext context) {
    final scheme = Theme.of(context).colorScheme;
    return Container(
      width: 40,
      height: 40,
      decoration: BoxDecoration(
        color: scheme.primaryContainer,
        borderRadius: BorderRadius.circular(AppRadius.md),
      ),
      child: Icon(icon, size: 20, color: scheme.onPrimaryContainer),
    );
  }
}
