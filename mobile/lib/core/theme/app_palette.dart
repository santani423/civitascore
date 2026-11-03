import 'package:flutter/material.dart';

import 'app_colors.dart';

/// Semantic colours not covered by [ColorScheme] (status tones, subtle
/// surfaces). Always read through `context.palette` — never hardcode colours
/// in widgets, so light/dark themes stay consistent.
@immutable
class AppPalette extends ThemeExtension<AppPalette> {
  const AppPalette({
    required this.success,
    required this.successContainer,
    required this.warning,
    required this.warningContainer,
    required this.danger,
    required this.dangerContainer,
    required this.info,
    required this.infoContainer,
    required this.neutral,
    required this.neutralContainer,
    required this.accent,
    required this.accentContainer,
    required this.border,
    required this.mutedText,
    required this.subtleSurface,
  });

  final Color success;
  final Color successContainer;
  final Color warning;
  final Color warningContainer;
  final Color danger;
  final Color dangerContainer;
  final Color info;
  final Color infoContainer;
  final Color neutral;
  final Color neutralContainer;
  final Color accent;
  final Color accentContainer;
  final Color border;
  final Color mutedText;
  final Color subtleSurface;

  static const light = AppPalette(
    success: AppColorsLight.success,
    successContainer: Color(0xFFD1FAE5),
    warning: Color(0xFFB45309),
    warningContainer: Color(0xFFFEF3C7),
    danger: AppColorsLight.danger,
    dangerContainer: Color(0xFFFEE2E2),
    info: AppColorsLight.info,
    infoContainer: Color(0xFFDBEAFE),
    neutral: AppColorsLight.textSecondary,
    neutralContainer: Color(0xFFF1F5F9),
    accent: AppColorsLight.accentHover,
    accentContainer: Color(0xFFFFF0E5),
    border: AppColorsLight.border,
    mutedText: AppColorsLight.textSecondary,
    subtleSurface: AppColorsLight.surfaceHover,
  );

  static const dark = AppPalette(
    success: AppColorsDark.success,
    successContainer: Color(0xFF064E3B),
    warning: AppColorsDark.warning,
    warningContainer: Color(0xFF451A03),
    danger: AppColorsDark.danger,
    dangerContainer: Color(0xFF450A0A),
    info: AppColorsDark.info,
    infoContainer: Color(0xFF1E3A8A),
    neutral: AppColorsDark.textSecondary,
    neutralContainer: Color(0xFF252C38),
    accent: AppColorsDark.accent,
    accentContainer: Color(0xFF3A1D0C),
    border: AppColorsDark.border,
    mutedText: AppColorsDark.textSecondary,
    subtleSurface: AppColorsDark.surfaceHover,
  );

  @override
  AppPalette copyWith() => this;

  @override
  AppPalette lerp(ThemeExtension<AppPalette>? other, double t) {
    if (other is! AppPalette) return this;
    Color l(Color a, Color b) => Color.lerp(a, b, t)!;
    return AppPalette(
      success: l(success, other.success),
      successContainer: l(successContainer, other.successContainer),
      warning: l(warning, other.warning),
      warningContainer: l(warningContainer, other.warningContainer),
      danger: l(danger, other.danger),
      dangerContainer: l(dangerContainer, other.dangerContainer),
      info: l(info, other.info),
      infoContainer: l(infoContainer, other.infoContainer),
      neutral: l(neutral, other.neutral),
      neutralContainer: l(neutralContainer, other.neutralContainer),
      accent: l(accent, other.accent),
      accentContainer: l(accentContainer, other.accentContainer),
      border: l(border, other.border),
      mutedText: l(mutedText, other.mutedText),
      subtleSurface: l(subtleSurface, other.subtleSurface),
    );
  }
}

/// Status tone used by chips/badges/banners across features.
enum StatusTone { success, warning, danger, info, neutral, accent, primary }

extension AppPaletteX on BuildContext {
  AppPalette get palette =>
      Theme.of(this).extension<AppPalette>() ?? AppPalette.light;

  /// (foreground, background) colours for a [StatusTone].
  (Color, Color) toneColors(StatusTone tone) {
    final p = palette;
    final scheme = Theme.of(this).colorScheme;
    return switch (tone) {
      StatusTone.success => (p.success, p.successContainer),
      StatusTone.warning => (p.warning, p.warningContainer),
      StatusTone.danger => (p.danger, p.dangerContainer),
      StatusTone.info => (p.info, p.infoContainer),
      StatusTone.neutral => (p.neutral, p.neutralContainer),
      StatusTone.accent => (p.accent, p.accentContainer),
      StatusTone.primary => (scheme.primary, scheme.primaryContainer),
    };
  }
}
