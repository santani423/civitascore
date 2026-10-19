import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import '../../../../core/router/route_paths.dart';
import '../../../../core/theme/app_dimensions.dart';
import '../../../../core/utils/formatters.dart';
import '../../../../core/widgets/paged_list.dart';
import '../../../../core/widgets/state_views.dart';
import '../../../../core/widgets/ui_kit.dart';
import '../../domain/announcement.dart';
import '../../l10n/announcements_strings.dart';
import '../providers.dart';

/// Student announcement feed (`/s/announcements`).
class AnnouncementsScreen extends ConsumerStatefulWidget {
  const AnnouncementsScreen({super.key});

  @override
  ConsumerState<AnnouncementsScreen> createState() =>
      _AnnouncementsScreenState();
}

class _AnnouncementsScreenState extends ConsumerState<AnnouncementsScreen> {
  final _search = TextEditingController();

  @override
  void dispose() {
    _search.dispose();
    super.dispose();
  }

  void _submit(String v) =>
      ref.read(announcementSearchProvider.notifier).state = v.trim();

  @override
  Widget build(BuildContext context) {
    final s = context.an;
    final state = ref.watch(announcementsProvider);
    final filter = ref.watch(announcementFilterProvider);
    final unread = ref.watch(announcementUnreadCountProvider).valueOrNull ?? 0;
    final notifier = ref.read(announcementsProvider.notifier);

    return Scaffold(
      appBar: AppBar(title: Text(s.title)),
      body: Center(
        child: ConstrainedBox(
          constraints: const BoxConstraints(maxWidth: 720),
          child: PagedListView<Announcement>(
            state: state,
            onRefresh: () async {
              ref.invalidate(announcementUnreadCountProvider);
              await notifier.refresh();
            },
            onLoadMore: notifier.loadMore,
            padding: const EdgeInsets.fromLTRB(
              AppSpacing.lg,
              AppSpacing.sm,
              AppSpacing.lg,
              AppSpacing.lg,
            ),
            header: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                TextField(
                  controller: _search,
                  maxLength: kAnnouncementSearchMax,
                  textInputAction: TextInputAction.search,
                  onSubmitted: _submit,
                  decoration: InputDecoration(
                    hintText: s.searchHint,
                    counterText: '',
                    prefixIcon: const Icon(Icons.search),
                    suffixIcon: IconButton(
                      tooltip: s.clearSearch,
                      icon: const Icon(Icons.clear),
                      onPressed: () {
                        _search.clear();
                        _submit('');
                      },
                    ),
                  ),
                ),
                const SizedBox(height: AppSpacing.sm),
                SizedBox(
                  height: 48,
                  child: ListView(
                    scrollDirection: Axis.horizontal,
                    children: [
                      for (final f in kAnnouncementFilters)
                        Padding(
                          padding: const EdgeInsets.only(right: AppSpacing.sm),
                          child: ChoiceChip(
                            label: Text(s.filterLabel(f)),
                            selected: filter == f,
                            onSelected: (_) =>
                                ref
                                        .read(
                                          announcementFilterProvider.notifier,
                                        )
                                        .state =
                                    f,
                          ),
                        ),
                    ],
                  ),
                ),
                if (unread > 0)
                  Padding(
                    padding: const EdgeInsets.only(bottom: AppSpacing.sm),
                    child: Text(
                      s.unreadCount(unread),
                      style: Theme.of(context).textTheme.labelMedium,
                    ),
                  ),
              ],
            ),
            empty: EmptyView(
              icon: Icons.campaign_outlined,
              title: s.emptyTitle,
              message: s.emptyBody,
            ),
            itemBuilder: (context, a) => _AnnouncementCard(
              a: a,
              onTap: () async {
                await context.push(RoutePaths.studentAnnouncement(a.id));
                ref.invalidate(announcementUnreadCountProvider);
                ref.invalidate(announcementsProvider);
              },
            ),
          ),
        ),
      ),
    );
  }
}

class _AnnouncementCard extends StatelessWidget {
  const _AnnouncementCard({required this.a, required this.onTap});
  final Announcement a;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    final s = context.an;
    final text = Theme.of(context).textTheme;
    final scheme = Theme.of(context).colorScheme;
    return AppSurfaceCard(
      onTap: onTap,
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Wrap(
            spacing: AppSpacing.sm,
            children: [
              if (a.isPinned)
                StatusChip(
                  label: s.pinned,
                  tone: StatusTone.accent,
                  icon: Icons.push_pin_outlined,
                  dense: true,
                ),
              if (!a.isRead)
                StatusChip(label: s.unread, tone: StatusTone.info, dense: true),
            ],
          ),
          if (a.isPinned || !a.isRead) const SizedBox(height: AppSpacing.xs),
          Text(
            a.title,
            maxLines: 2,
            overflow: TextOverflow.ellipsis,
            style: text.titleSmall?.copyWith(
              fontWeight: a.isRead ? FontWeight.w500 : FontWeight.w700,
            ),
          ),
          const SizedBox(height: AppSpacing.xs),
          Text(
            plainTextFromBody(a.body),
            maxLines: 2,
            overflow: TextOverflow.ellipsis,
            style: text.bodySmall?.copyWith(color: scheme.onSurfaceVariant),
          ),
          const SizedBox(height: AppSpacing.xs),
          Text(
            context.formatDateTime(a.publishedAt),
            style: text.labelSmall?.copyWith(color: scheme.onSurfaceVariant),
          ),
        ],
      ),
    );
  }
}
