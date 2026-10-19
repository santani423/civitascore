import 'package:go_router/go_router.dart';

import '../../core/permission/permission_gate.dart';
import 'presentation/screens/class_attendance_screen.dart';
import 'presentation/screens/class_grades_screen.dart';

/// Lecturer attendance & grade entry (no tab screen).
final List<RouteBase> lecturerAssessmentRoutes = <RouteBase>[
  GoRoute(
    path: '/l/classes/:classSectionId/attendance',
    builder: (_, state) => PermissionGate(
      permission: 'attendance.read',
      child: ClassAttendanceScreen(
        classSectionId: state.pathParameters['classSectionId']!,
      ),
    ),
  ),
  GoRoute(
    path: '/l/classes/:classSectionId/grades',
    builder: (_, state) => PermissionGate(
      permission: 'grades.read',
      child: ClassGradesScreen(
        classSectionId: state.pathParameters['classSectionId']!,
      ),
    ),
  ),
];
