import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../../core/theme/app_dimensions.dart';
import '../../../../core/utils/file_actions.dart';
import '../../../../core/utils/formatters.dart';
import '../../../../core/widgets/state_views.dart';
import '../../../../core/widgets/ui_kit.dart';
import '../../data/announcements_api.dart';
import '../../domain/announcement.dart';
import '../../l10n/announcements_strings.dart';
import '../providers.dart';

/// `/s/announcements/:id` — marks the announcement read once opened.
class AnnouncementDetailScreen extends ConsumerStatefulWidget {
  const AnnouncementDetailScreen({super.key, required this.id});
  final String id;

  @override
  ConsumerState<AnnouncementDetailScreen> createState() =>
      _AnnouncementDetailScreenState();
}

class _AnnouncementDetailScreenState
    extends ConsumerState<AnnouncementDetailScreen> {
  bool _markSent = false;

  Future<void> _markRead(Announcement a) async {
    if (_markSent || a.isRead) return;
    _markSent = true;
    try {
      await ref.read(announcementsApiProvider).markRead(a.id);
      ref.invalidate(announcementUnreadCountProvider);
      ref.invalidate(announcementsProvider);
    } catch (_) {
      _markSent = false; // retried on next open; not worth blocking reading
    }
  }

  @override
  Widget build(BuildContext context) {
    final s = context.an;
    final value = ref.watch(announcementDetailProvider(widget.id));
    ref.listen(announcementDetailProvider(widget.id), (_, next) {
      final a = next.valueOrNull;
      if (a != null) _markRead(a);
    });
    final first = value.valueOrNull;
    if (first != null) {
      WidgetsBinding.instance.addPostFrameCallback((_) => _markRead(first));
    }
    return Scaffold(
      appBar: AppBar(title: Text(s.detailTitle)),
      body: Center(
        child: ConstrainedBox(
          constraints: const BoxConstraints(maxWidth: 720),
          child: AsyncValueView<Announcement>(
            value: value,
            onRetry: () =>
                ref.invalidate(announcementDetailProvider(widget.id)),
            data: (a) => _Body(a: a),
          ),
        ),
      ),
    );
  }
}

class _Body extends ConsumerWidget {
  const _Body({required this.a});
  final Announcement a;

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final s = context.an;
    final text = Theme.of(context).textTheme;
    final scheme = Theme.of(context).colorScheme;
    final att = a.attachment;
    return ListView(
      padding: const EdgeInsets.all(AppSpacing.lg),
      children: [
        if (a.isPinned)
          Align(
            alignment: Alignment.centerLeft,
            child: StatusChip(
              label: s.pinned,
              tone: StatusTone.accent,
              icon: Icons.push_pin_outlined,
            ),
          ),
        if (a.isPinned) const SizedBox(height: AppSpacing.sm),
        Text(a.title, style: text.titleLarge),
        const SizedBox(height: AppSpacing.xs),
        Text(
          [
            context.formatDateTime(a.publishedAt),
            if (a.creatorName != null) '${s.by} ${a.creatorName}',
          ].join(' · '),
          style: text.bodySmall?.copyWith(color: scheme.onSurfaceVariant),
        ),
        const SizedBox(height: AppSpacing.lg),
        SelectableText(plainTextFromBody(a.body), style: text.bodyMedium),
        if (att != null) ...[
          const SizedBox(height: AppSpacing.xl),
          SectionHeader(title: s.attachment),
          AppSurfaceCard(
            child: Row(
              children: [
                const Icon(Icons.attach_file),
                const SizedBox(width: AppSpacing.md),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        att.name,
                        maxLines: 2,
                        overflow: TextOverflow.ellipsis,
                      ),
                      if (att.sizeBytes != null)
                        Text(formatBytes(att.sizeBytes), style: text.bodySmall),
                    ],
                  ),
                ),
                IconButton(
                  tooltip: s.download,
                  icon: const Icon(Icons.download_rounded),
                  onPressed: () => downloadAndOpen(
                    context,
                    ref,
                    path: '/file-uploads/${att.id}/download',
                    filename: att.name,
                  ),
                ),
              ],
            ),
          ),
        ],
      ],
    );
  }
}
