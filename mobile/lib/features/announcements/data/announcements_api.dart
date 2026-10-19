import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../core/network/api_client.dart';
import '../../../core/network/api_envelope.dart';
import '../domain/announcement.dart';

class AnnouncementsApi {
  AnnouncementsApi(this._api);
  final ApiClient _api;

  /// `GET /student/announcements`.
  Future<Paginated<Announcement>> list({
    required int page,
    String filter = 'all',
    String search = '',
  }) => _api.getPage(
    '/student/announcements',
    query: {
      'page': page,
      'per_page': 15,
      if (filter != 'all') 'filter': filter,
      if (search.trim().isNotEmpty) 'search': search.trim(),
    },
    item: Announcement.fromJson,
    cache: true,
  );

  /// Unread total: `filter=unread` total equals `meta.unread_count`.
  Future<int> unreadCount() async {
    final page = await _api.getPage<Announcement>(
      '/student/announcements',
      query: {'filter': 'unread', 'per_page': 1},
      item: Announcement.fromJson,
      cache: true,
    );
    return page.meta.total;
  }

  /// `GET /student/announcements/{id}`.
  Future<Announcement> detail(String id) => _api.get(
    '/student/announcements/$id',
    decode: (d) => Announcement.fromJson(asMap(d)),
    cache: true,
  );

  /// `POST /student/announcements/{id}/read`.
  Future<void> markRead(String id) =>
      _api.post<void>('/student/announcements/$id/read', decode: (_) {});
}

final announcementsApiProvider = Provider<AnnouncementsApi>(
  (ref) => AnnouncementsApi(ref.watch(apiClientProvider)),
);
