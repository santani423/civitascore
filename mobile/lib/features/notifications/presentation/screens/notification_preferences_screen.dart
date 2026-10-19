import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../../core/pending/pending_feature_screen.dart';
import '../../../../core/pending/pending_features.dart';
import '../../../../core/theme/app_dimensions.dart';
import '../../../../core/widgets/app_feedback.dart';
import '../../../../core/widgets/state_views.dart';
import '../../../../core/widgets/ui_kit.dart';
import '../../data/notifications_api.dart';
import '../../domain/notification_item.dart';
import '../../l10n/notifications_strings.dart';
import '../providers.dart';

/// `/notifications/preferences` — per-channel preferences
/// (`GET/POST /notification-preferences`, type `*`).
class NotificationPreferencesScreen extends ConsumerStatefulWidget {
  const NotificationPreferencesScreen({super.key});

  @override
  ConsumerState<NotificationPreferencesScreen> createState() =>
      _NotificationPreferencesScreenState();
}

class _NotificationPreferencesScreenState
    extends ConsumerState<NotificationPreferencesScreen> {
  final _busy = <String>{};

  Future<void> _toggle(String channel, bool value) async {
    final s = context.nt;
    if (channel == 'database' && !value) {
      final ok = await confirmAction(
        context,
        title: s.disableInAppTitle,
        message: s.disableInAppBody,
        confirmLabel: s.disable,
        destructive: true,
      );
      if (!ok || !mounted) return;
    }
    setState(() => _busy.add(channel));
    try {
      await ref
          .read(notificationsApiProvider)
          .setPreference(channel: channel, enabled: value);
      ref.invalidate(notificationPreferencesProvider);
      await ref.read(notificationPreferencesProvider.future);
      if (mounted) showAppSnack(context, s.saved);
    } catch (e) {
      if (mounted) showErrorSnack(context, e);
    } finally {
      if (mounted) setState(() => _busy.remove(channel));
    }
  }

  @override
  Widget build(BuildContext context) {
    final s = context.nt;
    final prefs = ref.watch(notificationPreferencesProvider);
    return Scaffold(
      appBar: AppBar(title: Text(s.prefsTitle)),
      body: Center(
        child: ConstrainedBox(
          constraints: const BoxConstraints(maxWidth: 720),
          child: AsyncValueView<List<NotificationPreference>>(
            value: prefs,
            onRetry: () => ref.invalidate(notificationPreferencesProvider),
            data: (list) => ListView(
              padding: const EdgeInsets.all(AppSpacing.lg),
              children: [
                Text(s.prefsIntro),
                const SizedBox(height: AppSpacing.md),
                AppBanner(message: s.prefsPerEventNote),
                const SizedBox(height: AppSpacing.lg),
                for (final ch in kImplementedChannels)
                  SwitchListTile(
                    title: Text(s.channelLabel(ch)),
                    value: channelEnabled(list, ch),
                    onChanged: _busy.contains(ch)
                        ? null
                        : (v) => _toggle(ch, v),
                  ),
                for (final ch in kPendingChannels)
                  SwitchListTile(
                    title: Text(s.channelLabel(ch)),
                    subtitle: Text(s.channelUnavailable),
                    value: false,
                    onChanged: null,
                  ),
                const SizedBox(height: AppSpacing.lg),
                PendingFeatureTile(
                  feature: pendingFeatureByKey('push_notifications')!,
                ),
              ],
            ),
          ),
        ),
      ),
    );
  }
}
