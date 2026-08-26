import 'package:flutter/material.dart';

/// Chrome bersama untuk seluruh route terproteksi (Drawer, AppBar,
/// TenantModeBanner) — dibungkus `ShellRoute` di [core/router/app_router.dart].
///
/// Fase 0: kerangka kosong (cuma `Scaffold(body: child)`). Drawer dinamis
/// per role, AppBar, dan TenantModeBanner dilengkapi di Fase 1 (BAB 12)
/// setelah struktur menu (BAB 7.2/7.3) tersedia.
class AppShell extends StatelessWidget {
  const AppShell({super.key, required this.child});

  final Widget child;

  @override
  Widget build(BuildContext context) => Scaffold(body: SafeArea(child: child));
}
