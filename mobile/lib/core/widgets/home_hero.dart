import 'package:flutter/material.dart';

import '../theme/app_colors.dart';
import '../theme/app_dimensions.dart';

/// Kartu sapaan di bagian atas beranda: avatar inisial, judul sapaan, dan
/// chip peran. Light mode memakai gradien orange brand; dark mode memakai
/// surface elevated agar tidak menyilaukan.
class HomeHeroCard extends StatelessWidget {
  const HomeHeroCard({
    super.key,
    required this.title,
    required this.initial,
    this.kicker,
    this.roles = const [],
    this.trailing,
  });

  /// Baris kecil di atas [title], mis. "Selamat pagi".
  final String? kicker;
  final String title;
  final String initial;
  final List<String> roles;
  final Widget? trailing;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final isDark = theme.brightness == Brightness.dark;
    final text = theme.textTheme;
    final fg = isDark ? theme.colorScheme.onSurface : Colors.white;
    final chipBg = isDark
        ? theme.colorScheme.primary.withValues(alpha: 0.18)
        : Colors.white.withValues(alpha: 0.2);

    return Container(
      clipBehavior: Clip.antiAlias,
      padding: const EdgeInsets.all(AppSpacing.lg),
      decoration: BoxDecoration(
        borderRadius: BorderRadius.circular(AppRadius.lg),
        gradient: isDark
            ? const LinearGradient(
                begin: Alignment.topLeft,
                end: Alignment.bottomRight,
                colors: [AppColorsDark.surfaceElevated, AppColorsDark.surface],
              )
            : const LinearGradient(
                begin: Alignment.topLeft,
                end: Alignment.bottomRight,
                colors: [AppColorsLight.primary, AppColorsLight.primaryHover],
              ),
        border: isDark
            ? Border.all(color: theme.colorScheme.outlineVariant)
            : null,
      ),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          CircleAvatar(
            radius: 26,
            backgroundColor: isDark
                ? theme.colorScheme.primary
                : Colors.white.withValues(alpha: 0.22),
            child: Text(
              initial.isEmpty ? '?' : initial.characters.first.toUpperCase(),
              style: text.titleLarge?.copyWith(color: fg),
            ),
          ),
          const SizedBox(width: AppSpacing.md),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                if (kicker != null)
                  Text(
                    kicker!,
                    style: text.bodySmall?.copyWith(
                      color: fg.withValues(alpha: 0.85),
                    ),
                  ),
                Text(title, style: text.titleLarge?.copyWith(color: fg)),
                if (roles.isNotEmpty)
                  Padding(
                    padding: const EdgeInsets.only(top: AppSpacing.sm),
                    child: Wrap(
                      spacing: AppSpacing.sm,
                      runSpacing: AppSpacing.sm,
                      children: [
                        for (final role in roles)
                          DecoratedBox(
                            decoration: BoxDecoration(
                              color: chipBg,
                              borderRadius: BorderRadius.circular(
                                AppRadius.full,
                              ),
                            ),
                            child: Padding(
                              padding: const EdgeInsets.symmetric(
                                horizontal: AppSpacing.md,
                                vertical: AppSpacing.xs,
                              ),
                              child: Text(
                                role,
                                style: text.labelMedium?.copyWith(color: fg),
                              ),
                            ),
                          ),
                      ],
                    ),
                  ),
              ],
            ),
          ),
          ?trailing,
        ],
      ),
    );
  }
}
