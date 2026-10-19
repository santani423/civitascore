import 'package:go_router/go_router.dart';

import 'presentation/screens/krs_submission_screen.dart';

export 'presentation/screens/advising_screen.dart' show AdvisingScreen;

final List<RouteBase> advisingRoutes = <RouteBase>[
  GoRoute(
    path: '/l/advising/krs/:id',
    builder: (_, state) => KrsSubmissionScreen(id: state.pathParameters['id']!),
  ),
];
