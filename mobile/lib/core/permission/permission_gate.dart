import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../features/auth/presentation/controllers/auth_controller.dart';
import '../l10n/core_strings.dart';
import '../widgets/state_views.dart';
import '../widgets/ui_kit.dart';

/// Renders [child] when the user holds ANY of the comma-separated
/// [permission]s (same OR semantics as the backend `permission:a,b`
/// middleware); otherwise an in-place "access denied" page.
class PermissionGate extends ConsumerWidget {
  const PermissionGate({
    super.key,
    required this.permission,
    required this.child,
  });

  final String permission;
  final Widget child;

  static bool allows(WidgetRef ref, String permission) {
    final user = ref.read(currentUserProvider);
    return user != null && user.hasAnyPermission(permission.split(','));
  }

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final user = ref.watch(currentUserProvider);
    final allowed =
        user != null && user.hasAnyPermission(permission.split(','));
    if (allowed) return child;
    final s = context.l10n;
    return Scaffold(
      appBar: AppBar(),
      body: MessageView(
        icon: Icons.lock_outline,
        tone: StatusTone.warning,
        title: s.accessDeniedTitle,
        message: s.accessDeniedBody,
      ),
    );
  }
}
