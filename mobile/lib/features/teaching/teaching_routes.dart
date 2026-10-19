import 'package:go_router/go_router.dart';

import 'presentation/screens/assignment_submissions_screen.dart';
import 'presentation/screens/class_assignments_screen.dart';
import 'presentation/screens/class_hub_screen.dart';
import 'presentation/screens/class_materials_screen.dart';
import 'presentation/screens/class_students_screen.dart';
import 'presentation/screens/schedule_screens.dart';
import 'presentation/screens/student_record_screen.dart';

export 'presentation/screens/schedule_screens.dart' show LecturerScheduleScreen;
export 'presentation/screens/teaching_home_screen.dart' show TeachingHomeScreen;

final List<RouteBase> teachingRoutes = <RouteBase>[
  GoRoute(
    path: '/l/classes/:classSectionId',
    builder: (_, s) =>
        ClassHubScreen(classSectionId: s.pathParameters['classSectionId']!),
  ),
  GoRoute(
    path: '/l/classes/:classSectionId/students',
    builder: (_, s) => ClassStudentsScreen(
      classSectionId: s.pathParameters['classSectionId']!,
    ),
  ),
  GoRoute(
    path: '/l/classes/:classSectionId/materials',
    builder: (_, s) => ClassMaterialsScreen(
      classSectionId: s.pathParameters['classSectionId']!,
    ),
  ),
  GoRoute(
    path: '/l/classes/:classSectionId/assignments',
    builder: (_, s) => ClassAssignmentsScreen(
      classSectionId: s.pathParameters['classSectionId']!,
    ),
  ),
  GoRoute(
    path: '/l/assignments/:assignmentId/submissions',
    builder: (_, s) => AssignmentSubmissionsScreen(
      assignmentId: s.pathParameters['assignmentId']!,
    ),
  ),
  GoRoute(
    path: '/l/calendar',
    builder: (_, _) => const LecturerCalendarScreen(),
  ),
  GoRoute(
    path: '/l/students/:studentId',
    builder: (_, s) =>
        StudentRecordScreen(studentId: s.pathParameters['studentId']!),
  ),
];
