import 'package:go_router/go_router.dart';

import '../../core/router/route_paths.dart';
import 'presentation/screens/announcement_detail_screen.dart';
import 'presentation/screens/announcements_screen.dart';

final List<RouteBase> announcementsRoutes = <RouteBase>[
  GoRoute(
    path: RoutePaths.studentAnnouncements,
    builder: (_, _) => const AnnouncementsScreen(),
  ),
  GoRoute(
    path: '/s/announcements/:id',
    builder: (_, state) =>
        AnnouncementDetailScreen(id: state.pathParameters['id']!),
  ),
];
