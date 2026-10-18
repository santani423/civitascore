import 'package:flutter/material.dart';

/// Type scale on the platform font (Roboto on Android) — no runtime font
/// download, works offline and falls back to Noto CJK for Mandarin.
abstract final class AppTextTheme {
  static TextTheme build(Color onSurface, Color muted) {
    TextStyle s(
      double size,
      FontWeight weight, {
      double ls = 0,
      Color? color,
      double? h,
    }) => TextStyle(
      fontSize: size,
      fontWeight: weight,
      letterSpacing: ls,
      height: h,
      color: color ?? onSurface,
    );
    return TextTheme(
      displaySmall: s(32, FontWeight.w700, ls: -0.5),
      headlineMedium: s(26, FontWeight.w700, ls: -0.4),
      headlineSmall: s(22, FontWeight.w700, ls: -0.3),
      titleLarge: s(19, FontWeight.w600, ls: -0.2),
      titleMedium: s(16, FontWeight.w600),
      titleSmall: s(14, FontWeight.w600),
      bodyLarge: s(16, FontWeight.w400, h: 1.45),
      bodyMedium: s(14, FontWeight.w400, h: 1.4),
      bodySmall: s(12.5, FontWeight.w400, color: muted, h: 1.35),
      labelLarge: s(14, FontWeight.w600),
      labelMedium: s(12.5, FontWeight.w600),
      labelSmall: s(11, FontWeight.w600, ls: 0.2, color: muted),
    );
  }
}
