import 'package:go_router/go_router.dart';

import 'presentation/screens/attendance_detail_screen.dart';
import 'presentation/screens/attendance_screen.dart';
import 'presentation/screens/calendar_screen.dart';
import 'presentation/screens/schedule_screen.dart';

final List<RouteBase> scheduleAttendanceRoutes = <RouteBase>[
  GoRoute(
    path: '/s/schedule',
    builder: (_, _) => const StudentScheduleScreen(),
  ),
  GoRoute(
    path: '/s/calendar',
    builder: (_, _) => const StudentCalendarScreen(),
  ),
  GoRoute(
    path: '/s/attendance',
    builder: (_, _) => const StudentAttendanceScreen(),
    routes: [
      GoRoute(
        path: ':krsItemId',
        builder: (_, state) => StudentAttendanceDetailScreen(
          krsItemId: state.pathParameters['krsItemId']!,
        ),
      ),
    ],
  ),
];
