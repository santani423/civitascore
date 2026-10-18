import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import '../l10n/core_strings.dart';
import '../network/api_client.dart';
import '../network/api_envelope.dart';
import '../router/route_paths.dart';

/// Unread in-app notifications (`GET /notifications/unread-count`, available
/// to every authenticated user). Invalidate after marking items read.
final unreadNotificationCountProvider = FutureProvider.autoDispose<int>((
  ref,
) async {
  // Response: `data: {unread_count}`.
  return ref
      .watch(apiClientProvider)
      .get(
        '/notifications/unread-count',
        decode: (d) => asInt(asMap(d)['unread_count']) ?? 0,
      );
});

/// App-bar bell with unread badge → notification centre.
class NotificationBellButton extends ConsumerWidget {
  const NotificationBellButton({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final count = ref.watch(unreadNotificationCountProvider).valueOrNull ?? 0;
    return IconButton(
      tooltip: context.l10n.notifications,
      onPressed: () => context.push(RoutePaths.notifications),
      icon: Badge(
        isLabelVisible: count > 0,
        label: Text(count > 99 ? '99+' : '$count'),
        child: const Icon(Icons.notifications_none_rounded),
      ),
    );
  }
}
