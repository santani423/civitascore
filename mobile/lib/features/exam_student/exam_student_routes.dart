import 'package:go_router/go_router.dart';

import 'presentation/screens/exam_attempt_screen.dart';
import 'presentation/screens/exam_detail_screen.dart';
import 'presentation/screens/exam_list_screen.dart';
import 'presentation/screens/exam_result_screen.dart';

final List<RouteBase> examStudentRoutes = <RouteBase>[
  GoRoute(path: '/s/exams', builder: (_, _) => const ExamListScreen()),
  GoRoute(
    path: '/s/exams/:examId',
    builder: (_, state) =>
        ExamDetailScreen(examId: state.pathParameters['examId']!),
  ),
  GoRoute(
    path: '/s/exam-attempts/:attemptId',
    builder: (_, state) =>
        ExamAttemptScreen(attemptId: state.pathParameters['attemptId']!),
  ),
  GoRoute(
    path: '/s/exam-attempts/:attemptId/result',
    builder: (_, state) =>
        ExamResultScreen(attemptId: state.pathParameters['attemptId']!),
  ),
];
