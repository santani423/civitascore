import 'package:go_router/go_router.dart';

import '../../core/router/route_paths.dart';
import 'presentation/screens/hr_profile_screen.dart';
import 'presentation/screens/leave_screens.dart';
import 'presentation/screens/lecturer_profile_screen.dart';
import 'presentation/screens/request_screens.dart';

final List<RouteBase> hrRoutes = <RouteBase>[
  GoRoute(
    path: RoutePaths.lecturerProfile,
    builder: (_, _) => const LecturerProfileScreen(),
  ),
  GoRoute(
    path: RoutePaths.hrProfile,
    builder: (_, _) => const HrProfileScreen(),
  ),
  GoRoute(
    path: RoutePaths.hrLeaveRequests,
    builder: (_, _) => const LeaveRequestsScreen(),
  ),
  GoRoute(
    path: RoutePaths.hrLeaveRequestNew,
    builder: (_, _) => const LeaveRequestFormScreen(),
  ),
  GoRoute(
    path: RoutePaths.hrRequests,
    builder: (_, _) => const HrRequestsScreen(),
  ),
  GoRoute(
    path: RoutePaths.hrRequestNew,
    builder: (_, _) => const HrRequestFormScreen(),
  ),
];
