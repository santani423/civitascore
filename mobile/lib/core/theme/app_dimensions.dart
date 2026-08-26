import 'package:flutter/material.dart';

/// Spacing scale, grid 4px — padanan `gap-*`/`p-*` Tailwind.
abstract final class AppSpacing {
  static const xs = 4.0;
  static const sm = 8.0;
  static const md = 12.0;
  static const lg = 16.0;
  static const xl = 20.0;
  static const xl2 = 24.0;
}

abstract final class AppRadius {
  static const sm = 8.0; // input/tombol, rounded-lg
  static const lg = 14.0; // Card, .xl
  static const xl = 18.0; // Modal, .2xl
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
