import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../features/auth/presentation/controllers/auth_controller.dart';
import '../l10n/core_strings.dart';
import '../theme/app_dimensions.dart';
import '../widgets/state_views.dart';
import '../widgets/ui_kit.dart';

class SplashScreen extends ConsumerWidget {
  const SplashScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final auth = ref.watch(authControllerProvider);
    final scheme = Theme.of(context).colorScheme;
    if (auth.hasError) {
      return Scaffold(
        body: ErrorView(
          error: auth.error!,
          onRetry: () => ref.invalidate(authControllerProvider),
        ),
      );
    }
    return Scaffold(
      body: Center(
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            Container(
              width: 72,
              height: 72,
              decoration: BoxDecoration(
                color: scheme.primary,
                borderRadius: BorderRadius.circular(AppRadius.xl),
              ),
              child: Icon(
                Icons.school_rounded,
                size: 40,
                color: scheme.onPrimary,
              ),
            ),
            const SizedBox(height: AppSpacing.lg),
            Text(
              context.l10n.appName,
              style: Theme.of(context).textTheme.titleLarge,
            ),
            const SizedBox(height: AppSpacing.xl2),
            const SizedBox(
              width: 28,
              height: 28,
              child: CircularProgressIndicator(strokeWidth: 2.6),
            ),
          ],
        ),
      ),
    );
  }
}

/// Signed in, but the account has neither a student nor a lecturer role.
class NoAccessScreen extends ConsumerWidget {
  const NoAccessScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final s = context.l10n;
    return Scaffold(
      body: MessageView(
        icon: Icons.person_off_outlined,
        tone: StatusTone.warning,
        title: s.noExperienceTitle,
        message: s.noExperienceBody,
        action: FilledButton.icon(
          onPressed: () => ref.read(authControllerProvider.notifier).logout(),
          icon: const Icon(Icons.logout),
          label: Text(s.logout),
        ),
      ),
    );
  }
}
