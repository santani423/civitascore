import 'package:flutter/material.dart';

/// Brand: Universitas Atma Jaya (One Atma). Orange dipakai sebagai accent —
/// CTA, ikon aktif, indikator — bukan warna dominan layar.
abstract final class AppColorsLight {
  static const primary = Color(0xFFEA5B0C); // Atma Orange
  static const primaryHover = Color(0xFFC74705); // Primary Dark
  static const primaryLight = Color(0xFFFFF0E5);
  static const accent = Color(0xFFEA5B0C);
  static const accentHover = Color(0xFFC74705);
  static const navy = Color(0xFF1E293B);
  static const cream = Color(0xFFFFF7ED);
  static const success = Color(0xFF10B981);
  static const warning = Color(0xFFF59E0B);
  static const danger = Color(0xFFEF4444);
  static const info = Color(0xFF3B82F6);
  static const background = Color(0xFFFFF9F4);
  static const surface = Color(0xFFFFFFFF);
  static const surfaceHover = Color(0xFFF7F3EF);
  static const border = Color(0xFFEBE5DE);
  static const borderStrong = Color(0xFFD4CBC0);
  static const textPrimary = Color(0xFF1E293B);
  static const textSecondary = Color(0xFF5B6678);
  static const textTertiary = Color(0xFF8A94A6);
  static const textInverted = Color(0xFFFFFFFF);
}

/// Dark mode dirancang khusus (bukan inversi filter). Orange sedikit lebih
/// terang agar kontras terhadap surface gelap.
abstract final class AppColorsDark {
  static const primary = Color(0xFFFF791F);
  static const primaryHover = Color(0xFFFF9A4D);
  static const primaryLight = Color(0xFF3A1D0C);
  static const accent = Color(0xFFFF791F);
  static const accentHover = Color(0xFFFF9A4D);
  static const navy = Color(0xFFF8FAFC);
  static const cream = Color(0xFF1C222C);
  static const success = Color(0xFF34D399);
  static const warning = Color(0xFFFBBF24);
  static const danger = Color(0xFFF87171);
  static const info = Color(0xFF60A5FA);
  static const background = Color(0xFF101318);
  static const surface = Color(0xFF1C222C);
  static const surfaceElevated = Color(0xFF252C38);
  static const surfaceHover = Color(0xFF252C38);
  static const border = Color(0xFF343B47);
  static const borderStrong = Color(0xFF465062);
  static const textPrimary = Color(0xFFF8FAFC);
  static const textSecondary = Color(0xFFA1AAB8);
  static const textTertiary = Color(0xFF6E7889);
  static const textInverted = Color(0xFF101318);
}

/// Skala statis `primary` (Badge/Avatar/hover — sama di kedua tema).
abstract final class AppColorsPrimaryScale {
  static const s50 = Color(0xFFFFF7F0);
  static const s100 = Color(0xFFFFF0E5);
  static const s200 = Color(0xFFFFD9BF);
  static const s300 = Color(0xFFFFB88A);
  static const s400 = Color(0xFFFF9A4D);
  static const s500 = Color(0xFFFF791F);
  static const s600 = Color(0xFFEA5B0C);
  static const s700 = Color(0xFFC74705);
  static const s800 = Color(0xFF9A3A05);
  static const s900 = Color(0xFF6B2803);
  static const s950 = Color(0xFF3D1702);
}

abstract final class AppColorsAccentScale {
  static const s50 = Color(0xFFFFF7F0);
  static const s100 = Color(0xFFFFF0E5);
  static const s200 = Color(0xFFFFD9BF);
  static const s300 = Color(0xFFFFB88A);
  static const s400 = Color(0xFFFF9A4D);
  static const s500 = Color(0xFFFF791F);
  static const s600 = Color(0xFFEA5B0C);
  static const s700 = Color(0xFFC74705);
  static const s800 = Color(0xFF9A3A05);
  static const s900 = Color(0xFF6B2803);
}
