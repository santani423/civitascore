import 'package:go_router/go_router.dart';

import '../../core/router/route_paths.dart';
import 'presentation/screens/assignment_detail_screen.dart';
import 'presentation/screens/assignments_screen.dart';
import 'presentation/screens/course_detail_screen.dart';
import 'presentation/screens/courses_screen.dart';
import 'presentation/screens/materials_screen.dart';

export 'presentation/screens/learning_hub_screen.dart' show LearningHubScreen;

final List<RouteBase> learningRoutes = <RouteBase>[
  GoRoute(
    path: RoutePaths.studentCourses,
    builder: (_, _) => const CoursesScreen(),
  ),
  GoRoute(
    path: '/s/courses/:classSectionId',
    builder: (_, state) => CourseDetailScreen(
      classSectionId: state.pathParameters['classSectionId']!,
    ),
  ),
  GoRoute(
    path: RoutePaths.studentMaterials,
    builder: (_, _) => const MaterialsScreen(),
  ),
  GoRoute(
    path: RoutePaths.studentAssignments,
    builder: (_, _) => const AssignmentsScreen(),
  ),
  GoRoute(
    path: '/s/assignments/:id',
    builder: (_, state) =>
        AssignmentDetailScreen(assignmentId: state.pathParameters['id']!),
  ),
];
