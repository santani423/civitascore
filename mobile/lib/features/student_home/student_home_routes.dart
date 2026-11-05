import 'package:go_router/go_router.dart';

import '../../core/router/route_paths.dart';
import 'presentation/screens/student_menu_screen.dart';

export 'presentation/screens/student_home_screen.dart' show StudentHomeScreen;

/// The home tab has no detail routes of its own; every card navigates to
/// routes owned by other modules. Menu Utama is the one exception: it is the
/// directory of all student services, so it lives with the home feature.
final List<RouteBase> studentHomeRoutes = <RouteBase>[
  GoRoute(
    path: RoutePaths.studentMenu,
    builder: (context, state) => StudentMenuScreen(
      initialCategory: state.uri.queryParameters['kategori'],
    ),
  ),
];
