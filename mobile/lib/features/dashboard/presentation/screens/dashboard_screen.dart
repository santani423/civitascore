import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import '../../../../core/router/route_paths.dart';
import '../../../../core/theme/app_dimensions.dart';
import '../../../auth/presentation/controllers/auth_controller.dart';

/// Placeholder Fase 0 — dipilih otomatis oleh `DashboardRouteBuilder` (varian
/// tenant/Platform/Portal, BAB 6.2) begitu Fase 1 selesai. Untuk sekarang
/// cukup menunjukkan sesi berhasil dan menyediakan aksi keluar untuk
/// menguji alur logout end-to-end.
class DashboardScreen extends ConsumerWidget {
  const DashboardScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final session = ref.watch(authControllerProvider).valueOrNull;

    return Scaffold(
      appBar: AppBar(
        title: const Text('Dashboard'),
        actions: [
          IconButton(
            icon: const Icon(Icons.security_outlined),
            tooltip: 'Keamanan',
            onPressed: () => context.push(RoutePaths.pengaturanKeamanan),
          ),
          IconButton(
            icon: const Icon(Icons.logout),
            tooltip: 'Keluar',
            onPressed: () => ref.read(authControllerProvider.notifier).logout(),
          ),
        ],
      ),
      body: Center(
        child: Padding(
          padding: const EdgeInsets.all(AppSpacing.xl2),
          child: Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              const Icon(Icons.check_circle_outline, size: 48),
              const SizedBox(height: AppSpacing.md),
              Text(
                'Selamat datang, ${session?.user.name ?? ''}',
                textAlign: TextAlign.center,
              ),
              const SizedBox(height: AppSpacing.xs),
              Text(
                'Dashboard penuh (BAB 6.2) menyusul di Fase 1.',
                style: Theme.of(context).textTheme.bodySmall,
              ),
            ],
          ),
        ),
      ),
    );
  }
}
