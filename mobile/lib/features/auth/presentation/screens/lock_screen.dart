import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../../core/security/app_lock.dart';
import '../../../../core/theme/app_dimensions.dart';
import '../../l10n/auth_strings.dart';
import '../controllers/auth_controller.dart';

class LockScreen extends ConsumerStatefulWidget {
  const LockScreen({super.key});

  @override
  ConsumerState<LockScreen> createState() => _LockScreenState();
}

class _LockScreenState extends ConsumerState<LockScreen> {
  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) => _unlock());
  }

  Future<void> _unlock() async {
    if (!mounted) return;
    await ref
        .read(appLockProvider.notifier)
        .authenticate(context.authL10n.unlockReason);
  }

  @override
  Widget build(BuildContext context) {
    final a = context.authL10n;
    final scheme = Theme.of(context).colorScheme;
    return PopScope(
      canPop: false,
      child: Scaffold(
        body: SafeArea(
          child: Center(
            child: Padding(
              padding: const EdgeInsets.all(AppSpacing.xl2),
              child: Column(
                mainAxisSize: MainAxisSize.min,
                children: [
                  Icon(Icons.fingerprint, size: 72, color: scheme.primary),
                  const SizedBox(height: AppSpacing.lg),
                  Text(
                    a.lockTitle,
                    style: Theme.of(context).textTheme.headlineSmall,
                  ),
                  const SizedBox(height: AppSpacing.sm),
                  Text(a.lockBody, textAlign: TextAlign.center),
                  const SizedBox(height: AppSpacing.xl2),
                  FilledButton.icon(
                    onPressed: _unlock,
                    icon: const Icon(Icons.lock_open),
                    label: Text(a.unlock),
                  ),
                  const SizedBox(height: AppSpacing.md),
                  TextButton(
                    onPressed: () async {
                      ref.read(appLockProvider.notifier).unlockForSignedOut();
                      await ref.read(authControllerProvider.notifier).logout();
                    },
                    child: Text(a.useAnotherAccount),
                  ),
                ],
              ),
            ),
          ),
        ),
      ),
    );
  }
}
