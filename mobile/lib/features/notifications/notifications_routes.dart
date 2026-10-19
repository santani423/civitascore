import 'package:go_router/go_router.dart';

import '../../core/router/route_paths.dart';
import 'presentation/screens/notification_preferences_screen.dart';
import 'presentation/screens/notifications_screen.dart';

final List<RouteBase> notificationsRoutes = <RouteBase>[
  GoRoute(
    path: RoutePaths.notifications,
    builder: (_, _) => const NotificationsScreen(),
    routes: [
      GoRoute(
        path: 'preferences',
        builder: (_, _) => const NotificationPreferencesScreen(),
      ),
    ],
  ),
];
