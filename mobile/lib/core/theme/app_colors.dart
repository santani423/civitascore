import 'package:flutter/material.dart';

abstract final class AppColorsLight {
  static const primary = Color(0xFF166534);
  static const primaryHover = Color(0xFF114F29);
  static const accent = Color(0xFFD9791A);
  static const accentHover = Color(0xFFB35F12);
  static const success = Color(0xFF166534);
  static const warning = Color(0xFFCA8A04);
  static const danger = Color(0xFFDC2626);
  static const info = Color(0xFF0284C7);
  static const background = Color(0xFFF8FAFC);
  static const surface = Color(0xFFFFFFFF);
  static const surfaceHover = Color(0xFFF1F5F9);
  static const border = Color(0xFFE2E8F0);
  static const borderStrong = Color(0xFFCBD5E1);
  static const textPrimary = Color(0xFF0F172A);
  static const textSecondary = Color(0xFF475569);
  static const textTertiary = Color(0xFF94A3B8);
  static const textInverted = Color(0xFFF8FAFC);
}

abstract final class AppColorsDark {
  static const primary = Color(0xFF3F9E68);
  static const primaryHover = Color(0xFF68BB88);
  static const accent = Color(0xFFF0AD53);
  static const accentHover = Color(0xFFF6C989);
  static const success = Color(0xFF3F9E68);
  static const warning = Color(0xFFFBBF24);
  static const danger = Color(0xFFF87171);
  static const info = Color(0xFF38BDF8);
  static const background = Color(0xFF0A0F1A);
  static const surface = Color(0xFF111827);
  static const surfaceHover = Color(0xFF1A2333);
  static const border = Color(0xFF26324A);
  static const borderStrong = Color(0xFF35435F);
  static const textPrimary = Color(0xFFF1F5F9);
  static const textSecondary = Color(0xFF94A3B8);
  static const textTertiary = Color(0xFF64748B);
  static const textInverted = Color(0xFF0F172A);
}

/// Skala statis `primary` (dipakai Badge/Avatar/hover — sama di kedua tema).
abstract final class AppColorsPrimaryScale {
  static const s50 = Color(0xFFEAF6EE);
  static const s100 = Color(0xFFCDEAD6);
  static const s200 = Color(0xFF9CD5AF);
  static const s300 = Color(0xFF68BB88);
  static const s400 = Color(0xFF3F9E68);
  static const s500 = Color(0xFF237F4C);
  static const s600 = Color(0xFF166534);
  static const s700 = Color(0xFF114F29);
  static const s800 = Color(0xFF0D3D20);
  static const s900 = Color(0xFF0A2F19);
  static const s950 = Color(0xFF051A0E);
}

abstract final class AppColorsAccentScale {
  static const s50 = Color(0xFFFDF3E7);
  static const s100 = Color(0xFFFBE4C4);
  static const s200 = Color(0xFFF6C989);
  static const s300 = Color(0xFFF0AD53);
  static const s400 = Color(0xFFE9932E);
  static const s500 = Color(0xFFD9791A);
  static const s600 = Color(0xFFB35F12);
  static const s700 = Color(0xFF8C4A0F);
  static const s800 = Color(0xFF66360B);
  static const s900 = Color(0xFF452507);
}
