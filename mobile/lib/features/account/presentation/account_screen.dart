import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import '../../../core/l10n/core_strings.dart';
import '../../../core/router/route_paths.dart';
import '../../../core/session/app_experience.dart';
import '../../../core/theme/app_dimensions.dart';
import '../../../core/widgets/app_feedback.dart';
import '../../../core/widgets/notification_bell.dart';
import '../../../core/widgets/service_card.dart';
import '../../../core/widgets/ui_kit.dart';
import '../../auth/presentation/controllers/auth_controller.dart';
import '../l10n/account_strings.dart';

/// "Akun" tab for both experiences: profile shortcut, services, settings,
/// pending-backend catalogue, role switch and sign out.
class AccountScreen extends ConsumerWidget {
  const AccountScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final s = context.l10n;
    final a = context.accountL10n;
    final session = ref.watch(authControllerProvider).valueOrNull;
    final user = session?.user;
    final experience = ref.watch(activeExperienceProvider);
    final isStudent = experience == AppExperience.student;
    final experiences = user == null
        ? const <AppExperience>[]
        : availableExperiences(user);
    final text = Theme.of(context).textTheme;
    final scheme = Theme.of(context).colorScheme;

    final roleLabels = <String>[
      if (user?.isStudent ?? false) s.roleStudent,
      if (user?.isLecturer ?? false) s.roleLecturer,
      if (user?.isAdvisor ?? false) s.roleAdvisor,
    ];

    return Scaffold(
      appBar: AppBar(
        title: Text(a.title),
        actions: const [NotificationBellButton()],
      ),
      // Gutter halaman disediakan ListView agar kartu layanan (ServiceCard)
      // tidak menempel ke tepi layar.
      body: ListView(
        padding: const EdgeInsets.fromLTRB(
          AppSpacing.lg,
          0,
          AppSpacing.lg,
          AppSpacing.xl2,
        ),
        children: [
          Padding(
            padding: const EdgeInsets.only(
              top: AppSpacing.lg,
              bottom: AppSpacing.sm,
            ),
            child: AppSurfaceCard(
              onTap: () => context.push(
                isStudent
                    ? RoutePaths.studentProfile
                    : RoutePaths.lecturerProfile,
              ),
              child: Row(
                children: [
                  CircleAvatar(
                    radius: 28,
                    backgroundColor: scheme.primaryContainer,
                    child: Text(
                      (user?.name.isNotEmpty ?? false)
                          ? user!.name.characters.first.toUpperCase()
                          : '?',
                      style: text.titleLarge?.copyWith(
                        color: scheme.onPrimaryContainer,
                      ),
                    ),
                  ),
                  const SizedBox(width: AppSpacing.lg),
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(
                          user?.name ?? '-',
                          style: text.titleMedium,
                          maxLines: 2,
                          overflow: TextOverflow.ellipsis,
                        ),
                        Text(
                          user?.nim ?? user?.email ?? '',
                          style: text.bodySmall,
                        ),
                        const SizedBox(height: 6),
                        Wrap(
                          spacing: 6,
                          runSpacing: 4,
                          children: [
                            for (final r in roleLabels)
                              StatusChip(
                                label: r,
                                tone: StatusTone.primary,
                                dense: true,
                              ),
                          ],
                        ),
                        if (session?.restoredOffline ?? false) ...[
                          const SizedBox(height: 6),
                          StatusChip(
                            label: a.offlineProfile,
                            tone: StatusTone.warning,
                            dense: true,
                          ),
                        ],
                      ],
                    ),
                  ),
                  const Icon(Icons.chevron_right),
                ],
              ),
            ),
          ),
          ServiceCard(
            icon: Icons.badge_outlined,
            title: a.myProfile,
            subtitle: isStudent
                ? a.myProfileSubtitleStudent
                : a.myProfileSubtitleLecturer,
            onTap: () => context.push(
              isStudent
                  ? RoutePaths.studentProfile
                  : RoutePaths.lecturerProfile,
            ),
          ),
          if (isStudent) ...[
            ServiceCard(
              icon: Icons.assignment_outlined,
              title: a.services,
              subtitle: a.servicesSubtitle,
              onTap: () => context.push(RoutePaths.studentRequests),
            ),
            ServiceCard(
              icon: Icons.campaign_outlined,
              title: s.announcements,
              onTap: () => context.push(RoutePaths.studentAnnouncements),
            ),
          ] else if (user?.hasPermission('hr_self_service.read') ?? false)
            ServiceCard(
              icon: Icons.work_outline,
              title: a.hrSelfService,
              subtitle: a.hrSubtitle,
              onTap: () => context.push(RoutePaths.hrProfile),
            ),
          ServiceCard(
            icon: Icons.notifications_none_rounded,
            title: s.notifications,
            onTap: () => context.push(RoutePaths.notifications),
          ),
          ServiceCard(
            icon: Icons.hourglass_top_rounded,
            title: s.pendingFeaturesTitle,
            subtitle: a.pendingSubtitle,
            onTap: () => context.push(
              isStudent
                  ? RoutePaths.pendingFeaturesStudent
                  : ((user?.isLecturer ?? false)
                        ? RoutePaths.pendingFeaturesLecturer
                        : RoutePaths.pendingFeaturesAdvisor),
            ),
          ),
          ServiceCard(
            icon: Icons.settings_outlined,
            title: s.settings,
            onTap: () => context.push(RoutePaths.settings),
          ),
          if (experiences.length > 1)
            ServiceCard(
              icon: Icons.swap_horiz_rounded,
              title: a.switchExperience,
              subtitle: isStudent ? s.roleLecturer : s.roleStudent,
              onTap: () async {
                final next = isStudent
                    ? AppExperience.lecturer
                    : AppExperience.student;
                await ref
                    .read(activeExperienceProvider.notifier)
                    .switchTo(next);
                if (context.mounted) {
                  context.go(
                    next == AppExperience.student
                        ? RoutePaths.studentHome
                        : RoutePaths.lecturerHome,
                  );
                }
              },
            ),
          const SizedBox(height: AppSpacing.lg),
          Padding(
            padding: EdgeInsets.zero,
            child: OutlinedButton.icon(
              key: const Key('account-logout'),
              style: OutlinedButton.styleFrom(foregroundColor: scheme.error),
              icon: const Icon(Icons.logout),
              label: Text(s.logout),
              onPressed: () async {
                final ok = await confirmAction(
                  context,
                  title: s.logout,
                  message: s.logoutConfirm,
                  destructive: true,
                  confirmLabel: s.logout,
                );
                if (ok) {
                  await ref.read(authControllerProvider.notifier).logout();
                }
              },
            ),
          ),
        ],
      ),
    );
  }
}
