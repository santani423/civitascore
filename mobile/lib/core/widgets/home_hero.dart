import 'package:flutter/material.dart';

import '../theme/app_colors.dart';
import '../theme/app_dimensions.dart';

/// Kartu sapaan di bagian atas beranda: avatar inisial, judul sapaan, dan
/// chip peran. Light mode memakai navy solid (warna sekunder brand) agar
/// orange tetap menjadi aksen; dark mode memakai surface elevated.
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
        : Colors.white.withValues(alpha: 0.14);

    return Container(
      padding: const EdgeInsets.all(AppSpacing.lg),
      decoration: BoxDecoration(
        color: isDark ? AppColorsDark.surfaceElevated : AppColorsLight.navy,
        borderRadius: BorderRadius.circular(AppRadius.lg),
        border: isDark
            ? Border.all(color: theme.colorScheme.outlineVariant)
            : null,
      ),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.center,
        children: [
          CircleAvatar(
            radius: 24,
            backgroundColor: theme.colorScheme.primary,
            child: Text(
              initial.isEmpty ? '?' : initial.characters.first.toUpperCase(),
              style: text.titleLarge?.copyWith(color: Colors.white),
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
                      color: fg.withValues(alpha: 0.8),
                    ),
                  ),
                Text(
                  title,
                  style: text.titleLarge?.copyWith(color: fg),
                  maxLines: 2,
                  overflow: TextOverflow.ellipsis,
                ),
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
