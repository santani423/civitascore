import 'package:go_router/go_router.dart';

import '../../core/permission/permission_gate.dart';
import 'presentation/providers.dart';
import 'presentation/screens/duty_scores_screen.dart';
import 'presentation/screens/duty_session_screen.dart';
import 'presentation/screens/duty_sessions_screen.dart';

/// PMB exam duties (MOB-OTH-01..03). Conditional module: the entry point on
/// the lecturer home only appears when sessions are assigned; session scope
/// itself is enforced by the backend (`ExamService::assertSessionStaff`).
final List<RouteBase> pmbDutyRoutes = <RouteBase>[
  GoRoute(
    path: '/l/pmb/sessions',
    builder: (_, _) => PermissionGate(
      permission: kPmbDutyPermissions.join(','),
      child: const DutySessionsScreen(),
    ),
  ),
  GoRoute(
    path: '/l/pmb/sessions/:sessionId',
    builder: (_, state) => PermissionGate(
      permission: kPmbAttendancePermission,
      child: DutySessionScreen(sessionId: state.pathParameters['sessionId']!),
    ),
  ),
  GoRoute(
    path: '/l/pmb/sessions/:sessionId/scores',
    builder: (_, state) => PermissionGate(
      permission: kPmbDutyPermissions.join(','),
      child: DutyScoresScreen(sessionId: state.pathParameters['sessionId']!),
    ),
  ),
];
