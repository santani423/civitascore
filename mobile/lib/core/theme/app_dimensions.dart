import 'package:flutter/material.dart';

/// Spacing scale, grid 4px — padanan `gap-*`/`p-*` Tailwind.
abstract final class AppSpacing {
  static const xs = 4.0;
  static const sm = 8.0;
  static const md = 12.0;
  static const lg = 16.0;
  static const xl = 20.0;
  static const xl2 = 24.0;
  static const xl3 = 32.0;
  static const xl4 = 40.0;
  static const xl5 = 48.0;
}

/// Radius design system: 8 / 12 / 16 / 24.
abstract final class AppRadius {
  static const sm = 8.0; // chip kecil, thumbnail
  static const md = 12.0; // input & tombol
  static const lg = 16.0; // Card
  static const xl = 24.0; // Modal, bottom sheet
  static const full = 999.0; // Badge/Avatar, rounded-full
}

abstract final class AppShadows {
  static const card = [
    BoxShadow(color: Color(0x0A000000), blurRadius: 3, offset: Offset(0, 1)),
  ];
  static const popover = [
    BoxShadow(color: Color(0x14000000), blurRadius: 15, offset: Offset(0, 10)),
  ];
}
