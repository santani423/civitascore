import 'package:go_router/go_router.dart';

import '../../core/router/route_paths.dart';
import 'presentation/screens/krs_history_screen.dart';
import 'presentation/screens/krs_offerings_screen.dart';
import 'presentation/screens/krs_screen.dart';

final List<RouteBase> krsRoutes = <RouteBase>[
  GoRoute(
    path: RoutePaths.studentKrs,
    builder: (_, _) => const KrsScreen(),
    routes: [
      GoRoute(path: 'offerings', builder: (_, _) => const KrsOfferingsScreen()),
      GoRoute(path: 'history', builder: (_, _) => const KrsHistoryScreen()),
    ],
  ),
];
