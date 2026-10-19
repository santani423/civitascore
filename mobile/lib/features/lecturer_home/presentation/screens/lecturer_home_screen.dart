import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import '../../../../core/l10n/core_strings.dart';
import '../../../../core/pending/pending_feature_screen.dart';
import '../../../../core/pending/pending_features.dart';
import '../../../../core/router/route_paths.dart';
import '../../../../core/theme/app_dimensions.dart';
import '../../../../core/utils/formatters.dart';
import '../../../../core/widgets/notification_bell.dart';
import '../../../../core/widgets/ui_kit.dart';
import '../../../auth/presentation/controllers/auth_controller.dart';
import '../../domain/home_models.dart';
import '../../l10n/lecturer_home_strings.dart';
import '../providers.dart';

class LecturerHomeScreen extends ConsumerWidget {
  const LecturerHomeScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final user = ref.watch(currentUserProvider);
    final s = context.homeL10n;
    final l = context.l10n;
    final unread = ref.watch(unreadNotificationCountProvider).valueOrNull ?? 0;
    final roles = [
      if (user?.isLecturer ?? false) l.roleLecturer,
      if (user?.isAdvisor ?? false) l.roleAdvisor,
    ];
    Future<void> refresh() async {
      ref.invalidate(homeClassesProvider);
      ref.invalidate(homePendingKrsProvider);
      ref.invalidate(unreadNotificationCountProvider);
    }

    return Scaffold(
      appBar: AppBar(
        title: Text(l.navHome),
        actions: const [NotificationBellButton()],
      ),
      body: RefreshIndicator(
        onRefresh: refresh,
        child: Center(
          child: ConstrainedBox(
            constraints: const BoxConstraints(maxWidth: 720),
            child: ListView(
              physics: const AlwaysScrollableScrollPhysics(),
              padding: const EdgeInsets.all(AppSpacing.lg),
              children: [
                Text(
                  s.greeting(user?.name ?? ''),
                  style: Theme.of(context).textTheme.headlineSmall,
                ),
                if (roles.isNotEmpty)
                  Padding(
                    padding: const EdgeInsets.only(top: AppSpacing.sm),
                    child: Wrap(
                      spacing: AppSpacing.sm,
                      children: [
                        for (final r in roles)
                          StatusChip(label: r, tone: StatusTone.primary),
                      ],
                    ),
                  ),
                if (unread > 0)
                  Padding(
                    padding: const EdgeInsets.only(top: AppSpacing.md),
                    child: MenuTile(
                      icon: Icons.notifications_active_outlined,
                      title: s.unreadNotifications,
                      badge: '$unread',
                      onTap: () => context.push(RoutePaths.notifications),
                    ),
                  ),
                if (user?.isLecturer ?? false) const _TeachingSection(),
                if (user?.isAdvisor ?? false) const _AdvisingSection(),
                const SizedBox(height: AppSpacing.md),
                PendingFeatureTile(
                  feature: pendingFeatureByKey('lecturer_announcements')!,
                ),
                if (user?.isAdvisor ?? false)
                  PendingFeatureTile(feature: pendingFeatureByKey('advisees')!),
              ],
            ),
          ),
        ),
      ),
    );
  }
}

class _SectionError extends StatelessWidget {
  const _SectionError({required this.error, required this.onRetry});
  final Object error;
  final VoidCallback onRetry;

  @override
  Widget build(BuildContext context) => AppBanner(
    message: context.l10n.errorFor(error),
    title: context.homeL10n.sectionFailed,
    tone: StatusTone.danger,
    action: TextButton(onPressed: onRetry, child: Text(context.l10n.retry)),
  );
}

class _TeachingSection extends ConsumerWidget {
  const _TeachingSection();

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final s = context.homeL10n;
    final l = context.l10n;
    final value = ref.watch(homeClassesProvider);
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        SectionHeader(
          title: s.todayTeaching,
          padding: const EdgeInsets.only(
            top: AppSpacing.xl,
            bottom: AppSpacing.sm,
          ),
          action: l.seeAll,
          onAction: () => context.push(RoutePaths.lecturerSchedule),
        ),
        value.when(
          loading: () => const SizedBox(
            height: 96,
            child: Center(child: CircularProgressIndicator()),
          ),
          error: (e, _) => _SectionError(
            error: e,
            onRetry: () => ref.invalidate(homeClassesProvider),
          ),
          data: (classes) {
            final now = DateTime.now();
            final today = slotsForDay(classes, now.weekday);
            final next = nextSlot(today, now);
            return Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Row(
                  children: [
                    Expanded(
                      child: StatTile(
                        label: s.classesTaught,
                        value: '${classes.length}',
                        icon: Icons.class_outlined,
                        onTap: () => context.push(RoutePaths.lecturerTeaching),
                      ),
                    ),
                    const SizedBox(width: AppSpacing.md),
                    Expanded(
                      child: StatTile(
                        label: s.todaySessions,
                        value: '${today.length}',
                        icon: Icons.today_outlined,
                        tone: StatusTone.info,
                      ),
                    ),
                  ],
                ),
                const SizedBox(height: AppSpacing.md),
                if (today.isEmpty)
                  Text(s.noClassToday)
                else
                  for (final slot in today)
                    _SlotTile(slot: slot, isNext: slot == next),
              ],
            );
          },
        ),
        SectionHeader(
          title: s.quickActions,
          padding: const EdgeInsets.only(
            top: AppSpacing.xl,
            bottom: AppSpacing.xs,
          ),
        ),
        MenuTile(
          icon: Icons.class_outlined,
          title: s.myClasses,
          onTap: () => context.push(RoutePaths.lecturerTeaching),
        ),
        MenuTile(
          icon: Icons.calendar_view_week_outlined,
          title: s.schedule,
          onTap: () => context.push(RoutePaths.lecturerSchedule),
        ),
        MenuTile(
          icon: Icons.quiz_outlined,
          title: s.exams,
          onTap: () => context.push(RoutePaths.lecturerExams),
        ),
        MenuTile(
          icon: Icons.library_books_outlined,
          title: s.questionBank,
          onTap: () => context.push(RoutePaths.lecturerQuestionBank),
        ),
        MenuTile(
          icon: Icons.event_outlined,
          title: s.calendar,
          onTap: () => context.push(RoutePaths.lecturerCalendar),
        ),
      ],
    );
  }
}

class _SlotTile extends StatelessWidget {
  const _SlotTile({required this.slot, required this.isNext});
  final TeachingSlot slot;
  final bool isNext;

  @override
  Widget build(BuildContext context) {
    final s = context.homeL10n;
    return Padding(
      padding: const EdgeInsets.only(bottom: AppSpacing.sm),
      child: AppSurfaceCard(
        onTap: () => context.push(RoutePaths.lecturerClass(slot.classId)),
        child: Row(
          children: [
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    slot.courseName,
                    maxLines: 2,
                    overflow: TextOverflow.ellipsis,
                    style: Theme.of(context).textTheme.titleSmall,
                  ),
                  Text(
                    '${context.formatClock(slot.startTime)}–'
                    '${context.formatClock(slot.endTime)}'
                    '${slot.room == null ? '' : ' • ${slot.room}'}'
                    '${slot.classCode == null ? '' : ' • ${slot.classCode}'}',
                    style: Theme.of(context).textTheme.bodySmall,
                  ),
                ],
              ),
            ),
            if (isNext)
              StatusChip(
                label: s.nextClass,
                tone: StatusTone.accent,
                dense: true,
              ),
          ],
        ),
      ),
    );
  }
}

class _AdvisingSection extends ConsumerWidget {
  const _AdvisingSection();

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final s = context.homeL10n;
    final value = ref.watch(homePendingKrsProvider);
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        SectionHeader(
          title: s.krsApprovals,
          padding: const EdgeInsets.only(
            top: AppSpacing.xl,
            bottom: AppSpacing.sm,
          ),
          action: context.l10n.seeAll,
          onAction: () => context.push(RoutePaths.lecturerAdvising),
        ),
        value.when(
          loading: () => const SizedBox(
            height: 72,
            child: Center(child: CircularProgressIndicator()),
          ),
          error: (e, _) => _SectionError(
            error: e,
            onRetry: () => ref.invalidate(homePendingKrsProvider),
          ),
          data: (sum) => sum.total == 0
              ? Text(s.noPendingKrs)
              : Column(
                  children: [
                    AppBanner(
                      message: s.pendingKrs(sum.total),
                      tone: StatusTone.warning,
                    ),
                    const SizedBox(height: AppSpacing.sm),
                    for (final k in sum.preview)
                      MenuTile(
                        icon: Icons.fact_check_outlined,
                        title: k.studentName ?? '—',
                        subtitle: k.nim,
                        onTap: () => context.push(
                          RoutePaths.lecturerKrsSubmission(k.id),
                        ),
                      ),
                  ],
                ),
        ),
      ],
    );
  }
}
