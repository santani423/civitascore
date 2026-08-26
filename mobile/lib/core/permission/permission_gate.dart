import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../features/auth/presentation/controllers/auth_controller.dart';

/// Padanan `RequirePermission` React (BAB 3.4/7.4) — OR-match terhadap
/// permission user login. Merender [child] di tempat kalau lolos, atau
/// pesan akses ditolak inline kalau tidak (bukan redirect, agar URL/back
/// stack tetap konsisten dengan yang ditap pengguna).
class PermissionGate extends ConsumerWidget {
  const PermissionGate({
    super.key,
    required this.permission,
    required this.child,
  });

  final String permission;
  final Widget child;

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final session = ref.watch(authControllerProvider).valueOrNull;
    final permissions = permission.contains(',')
        ? permission.split(',')
        : [permission];
    final allowed =
        session != null && permissions.any(session.user.hasPermission);

    if (allowed) return child;

    return const Scaffold(
      body: Center(
        child: Padding(
          padding: EdgeInsets.all(24),
          child: Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              Icon(Icons.lock_outline, size: 48),
              SizedBox(height: 12),
              Text(
                'Akses Ditolak',
                style: TextStyle(fontSize: 18, fontWeight: FontWeight.w600),
              ),
              SizedBox(height: 8),
              Text(
                'Anda tidak memiliki izin untuk mengakses halaman ini.',
                textAlign: TextAlign.center,
              ),
            ],
          ),
        ),
      ),
    );
  }
}
