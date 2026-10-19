import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../core/network/api_envelope.dart';
import '../../../core/widgets/paged_list.dart';
import '../data/announcements_api.dart';
import '../domain/announcement.dart';

final announcementFilterProvider = StateProvider.autoDispose<String>(
  (ref) => 'all',
);
final announcementSearchProvider = StateProvider.autoDispose<String>(
  (ref) => '',
);

class AnnouncementsNotifier extends PagedNotifier<Announcement> {
  @override
  Future<PagedState<Announcement>> build() {
    ref.watch(announcementFilterProvider);
    ref.watch(announcementSearchProvider);
    return super.build();
  }

  @override
  Future<Paginated<Announcement>> fetchPage(int page) => ref
      .read(announcementsApiProvider)
      .list(
        page: page,
        filter: ref.read(announcementFilterProvider),
        search: ref.read(announcementSearchProvider),
      );
}

final announcementsProvider =
    AsyncNotifierProvider.autoDispose<
      AnnouncementsNotifier,
      PagedState<Announcement>
    >(AnnouncementsNotifier.new);

final announcementUnreadCountProvider = FutureProvider.autoDispose<int>(
  (ref) => ref.watch(announcementsApiProvider).unreadCount(),
);

final announcementDetailProvider = FutureProvider.autoDispose
    .family<Announcement, String>(
      (ref, id) => ref.watch(announcementsApiProvider).detail(id),
    );
