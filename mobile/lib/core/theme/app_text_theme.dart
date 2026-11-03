import 'package:flutter/material.dart';

/// Type scale on Plus Jakarta Sans (dibundel di `assets/fonts/`, lihat
/// `pubspec.yaml`). Glyph Mandarin tetap jatuh ke font sistem (Noto CJK).
///
/// Aturan: caption minimal 12sp, heading tidak lebih besar dari 24sp, dan
/// bobot konsisten — 400 untuk isi, 600 untuk judul, 700 hanya untuk heading.
abstract final class AppTextTheme {
  static const fontFamily = 'PlusJakartaSans';

  static TextTheme build(Color onSurface, Color muted) {
    TextStyle s(
      double size,
      FontWeight weight, {
      double ls = 0,
      Color? color,
      double? h,
    }) => TextStyle(
      fontFamily: fontFamily,
      fontSize: size,
      fontWeight: weight,
      letterSpacing: ls,
      height: h,
      color: color ?? onSurface,
    );
    return TextTheme(
      displaySmall: s(30, FontWeight.w700, ls: -0.5, h: 1.2),
      headlineMedium: s(24, FontWeight.w700, ls: -0.4, h: 1.25),
      headlineSmall: s(22, FontWeight.w700, ls: -0.3, h: 1.25),
      titleLarge: s(18, FontWeight.w600, ls: -0.2, h: 1.3),
      titleMedium: s(16, FontWeight.w600, h: 1.35),
      titleSmall: s(14, FontWeight.w600, h: 1.35),
      bodyLarge: s(16, FontWeight.w400, h: 1.5),
      bodyMedium: s(14, FontWeight.w400, h: 1.45),
      bodySmall: s(13, FontWeight.w400, color: muted, h: 1.4),
      labelLarge: s(14, FontWeight.w600),
      labelMedium: s(13, FontWeight.w600),
      labelSmall: s(12, FontWeight.w600, ls: 0.1, color: muted),
    );
  }
}
