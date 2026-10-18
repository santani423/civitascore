import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../core/network/api_envelope.dart';
import '../../../core/widgets/notification_bell.dart';
import '../../../core/widgets/paged_list.dart';
import '../data/notifications_api.dart';
import '../domain/notification_item.dart';

/// "Hanya belum dibaca" toggle.
final notificationsUnreadOnlyProvider = StateProvider.autoDispose<bool>(
  (ref) => false,
);

class NotificationsNotifier extends PagedNotifier<NotificationItem> {
  @override
  Future<PagedState<NotificationItem>> build() {
    ref.watch(notificationsUnreadOnlyProvider);
    return super.build();
  }

  @override
  Future<Paginated<NotificationItem>> fetchPage(int page) =>
      ref.read(notificationsApiProvider).list(
        page: page,
        unreadOnly: ref.read(notificationsUnreadOnlyProvider),
      );

  /// Marks one notification read (PATCH) and updates the list + bell.
  Future<void> markRead(NotificationItem item) async {
    if (item.isRead) return;
    await ref.read(notificationsApiProvider).markRead(item.id);
    final current = state.valueOrNull;
    if (current != null) {
      state = AsyncData(
        current.copyWith(
          items: [
            for (final i in current.items) i.id == item.id ? i.markedRead() : i,
          ],
        ),
      );
    }
    ref.invalidate(unreadNotificationCountProvider);
  }

  /// `POST /notifications/read-all`, then reload the list.
  Future<void> markAllRead() async {
    await ref.read(notificationsApiProvider).markAllRead();
    ref.invalidate(unreadNotificationCountProvider);
    ref.invalidateSelf();
  }
}

final notificationsProvider =
    AsyncNotifierProvider.autoDispose<
      NotificationsNotifier,
      PagedState<NotificationItem>
    >(NotificationsNotifier.new);

final notificationPreferencesProvider =
    FutureProvider.autoDispose<List<NotificationPreference>>(
      (ref) => ref.watch(notificationsApiProvider).preferences(),
    );
