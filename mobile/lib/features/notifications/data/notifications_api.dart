import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../core/network/api_client.dart';
import '../../../core/network/api_envelope.dart';
import '../domain/notification_item.dart';

class NotificationsApi {
  NotificationsApi(this._api);
  final ApiClient _api;

  /// `GET /notifications?unread=&page=&per_page=`.
  Future<Paginated<NotificationItem>> list({
    required int page,
    bool unreadOnly = false,
  }) => _api.getPage(
    '/notifications',
    query: {'page': page, 'per_page': 20, if (unreadOnly) 'unread': 1},
    item: NotificationItem.fromJson,
    cache: true,
  );

  /// `PATCH /notifications/{id}/read`.
  Future<void> markRead(String id) =>
      _api.patch<void>('/notifications/$id/read', decode: (_) {});

  /// `POST /notifications/read-all`.
  Future<void> markAllRead() =>
      _api.post<void>('/notifications/read-all', decode: (_) {});

  /// `GET /notification-preferences`.
  Future<List<NotificationPreference>> preferences() => _api.getList(
    '/notification-preferences',
    item: NotificationPreference.fromJson,
  );

  /// `POST /notification-preferences` (upsert by channel + type).
  Future<void> setPreference({
    required String channel,
    required bool enabled,
    String type = '*',
  }) => _api.post<void>(
    '/notification-preferences',
    data: {
      'channel': channel,
      'notification_type': type,
      'is_enabled': enabled,
    },
    decode: (_) {},
  );
}

final notificationsApiProvider = Provider<NotificationsApi>(
  (ref) => NotificationsApi(ref.watch(apiClientProvider)),
);
