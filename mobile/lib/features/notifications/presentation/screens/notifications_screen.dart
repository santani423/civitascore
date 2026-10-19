import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import '../../../../core/pending/pending_feature_screen.dart';
import '../../../../core/pending/pending_features.dart';
import '../../../../core/router/route_paths.dart';
import '../../../../core/session/app_experience.dart';
import '../../../../core/theme/app_dimensions.dart';
import '../../../../core/utils/formatters.dart';
import '../../../../core/widgets/app_feedback.dart';
import '../../../../core/widgets/paged_list.dart';
import '../../../../core/widgets/state_views.dart';
import '../../../../core/widgets/ui_kit.dart';
import '../../domain/notification_item.dart';
import '../../domain/notification_link_mapper.dart';
import '../../l10n/notifications_strings.dart';
import '../providers.dart';

/// Notification centre for every role (`/notifications`).
class NotificationsScreen extends ConsumerWidget {
  const NotificationsScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final s = context.nt;
    final state = ref.watch(notificationsProvider);
    final unreadOnly = ref.watch(notificationsUnreadOnlyProvider);
    final experience = ref.watch(activeExperienceProvider);
    final notifier = ref.read(notificationsProvider.notifier);
    final hasUnread = state.valueOrNull?.items.any((i) => !i.isRead) ?? false;

    Future<void> markAll() async {
      try {
        await notifier.markAllRead();
        if (context.mounted) showAppSnack(context, s.allMarkedRead);
      } catch (e) {
        if (context.mounted) showErrorSnack(context, e);
      }
    }

    return Scaffold(
      appBar: AppBar(
        title: Text(s.title),
        actions: [
          IconButton(
            tooltip: s.markAllRead,
            onPressed: hasUnread ? markAll : null,
            icon: const Icon(Icons.done_all_rounded),
          ),
          IconButton(
            tooltip: s.preferences,
            onPressed: () =>
                context.push('${RoutePaths.notifications}/preferences'),
            icon: const Icon(Icons.tune_rounded),
          ),
        ],
      ),
      body: Center(
        child: ConstrainedBox(
          constraints: const BoxConstraints(maxWidth: 720),
          child: PagedListView<NotificationItem>(
            state: state,
            onRefresh: notifier.refresh,
            onLoadMore: notifier.loadMore,
            padding: const EdgeInsets.fromLTRB(
              AppSpacing.lg,
              AppSpacing.sm,
              AppSpacing.lg,
              AppSpacing.lg,
            ),
            header: _Header(
              unreadOnly: unreadOnly,
              showLecturerPending: experience == AppExperience.lecturer,
              onToggle: (v) =>
                  ref.read(notificationsUnreadOnlyProvider.notifier).state = v,
            ),
            empty: EmptyView(
              icon: Icons.notifications_none_rounded,
              title: unreadOnly ? s.emptyUnread : s.emptyTitle,
              message: s.emptyBody,
            ),
            itemBuilder: (context, item) => _NotificationTile(
              item: item,
              onTap: () => _open(context, ref, item, experience),
            ),
          ),
        ),
      ),
    );
  }

  Future<void> _open(
    BuildContext context,
    WidgetRef ref,
    NotificationItem item,
    AppExperience? experience,
  ) async {
    final target = mapNotificationLink(item.link, experience);
    try {
      await ref.read(notificationsProvider.notifier).markRead(item);
    } catch (e) {
      if (context.mounted) showErrorSnack(context, e);
    }
    if (target == null || !context.mounted) return;
    if (isTabRoute(target)) {
      context.go(target);
    } else {
      context.push(target);
    }
  }
}

class _Header extends StatelessWidget {
  const _Header({
    required this.unreadOnly,
    required this.onToggle,
    required this.showLecturerPending,
  });
  final bool unreadOnly;
  final ValueChanged<bool> onToggle;
  final bool showLecturerPending;

  @override
  Widget build(BuildContext context) {
    final s = context.nt;
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Align(
          alignment: Alignment.centerLeft,
          child: FilterChip(
            label: Text(s.unreadOnly),
            selected: unreadOnly,
            onSelected: onToggle,
          ),
        ),
        if (showLecturerPending) ...[
          const SizedBox(height: AppSpacing.sm),
          PendingFeatureTile(
            feature: pendingFeatureByKey('lecturer_announcements')!,
          ),
        ],
        const SizedBox(height: AppSpacing.sm),
      ],
    );
  }
}

class _NotificationTile extends StatelessWidget {
  const _NotificationTile({required this.item, required this.onTap});
  final NotificationItem item;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    final s = context.nt;
    final scheme = Theme.of(context).colorScheme;
    final text = Theme.of(context).textTheme;
    final message =
        item.message ??
        (item.eventKey == null ? s.legacyMessage : s.fallbackMessage);
    return Semantics(
      label: item.isRead ? null : s.unreadBadge,
      child: AppSurfaceCard(
        onTap: onTap,
        child: Row(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Padding(
              padding: const EdgeInsets.only(top: 6, right: AppSpacing.md),
              child: Icon(
                item.isRead ? Icons.circle_outlined : Icons.circle,
                size: 10,
                color: item.isRead ? scheme.outline : scheme.primary,
              ),
            ),
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    message,
                    style: text.bodyMedium?.copyWith(
                      fontWeight: item.isRead
                          ? FontWeight.w400
                          : FontWeight.w600,
                    ),
                  ),
                  const SizedBox(height: AppSpacing.xs),
                  Text(
                    context.formatDateTime(item.createdAt),
                    style: text.bodySmall?.copyWith(
                      color: scheme.onSurfaceVariant,
                    ),
                  ),
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }
}
