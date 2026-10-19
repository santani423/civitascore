import 'package:go_router/go_router.dart';

import '../../core/permission/permission_gate.dart';
import '../../core/router/route_paths.dart';
import 'presentation/screens/exam_detail_screen.dart';
import 'presentation/screens/exam_form_screen.dart';
import 'presentation/screens/exam_list_screen.dart';
import 'presentation/screens/exam_preview_screen.dart';
import 'presentation/screens/question_bank_list_screen.dart';
import 'presentation/screens/question_bank_screen.dart';

/// Lecturer exam management & question bank. Static paths (`/l/exams/new`)
/// are registered before the `:id` patterns.
final List<RouteBase> examRoutes = <RouteBase>[
  GoRoute(
    path: RoutePaths.lecturerExams,
    builder: (_, _) =>
        const PermissionGate(permission: 'exams.read', child: ExamListScreen()),
  ),
  GoRoute(
    path: RoutePaths.lecturerExamNew,
    builder: (_, _) => const PermissionGate(
      permission: 'exams.create',
      child: ExamFormScreen(),
    ),
  ),
  GoRoute(
    path: RoutePaths.lecturerQuestionBank,
    builder: (_, _) => const PermissionGate(
      permission: 'question_bank.read',
      child: QuestionBankListScreen(),
    ),
  ),
  GoRoute(
    path: '/l/exams/:id',
    builder: (_, state) => PermissionGate(
      permission: 'exams.read',
      child: ExamDetailScreen(examId: state.pathParameters['id']!),
    ),
  ),
  GoRoute(
    path: '/l/exams/:id/edit',
    builder: (_, state) => PermissionGate(
      permission: 'exams.update',
      child: ExamFormScreen(examId: state.pathParameters['id']),
    ),
  ),
  GoRoute(
    path: '/l/exams/:id/questions',
    builder: (_, state) => PermissionGate(
      permission: 'exams.read',
      child: QuestionBankScreen(examId: state.pathParameters['id']!),
    ),
  ),
  GoRoute(
    path: '/l/exams/:id/preview',
    builder: (_, state) => PermissionGate(
      permission: 'exams.read',
      child: ExamPreviewScreen(examId: state.pathParameters['id']!),
    ),
  ),
];
