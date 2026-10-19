import 'package:go_router/go_router.dart';

import '../../core/router/route_paths.dart';
import 'presentation/screens/request_detail_screen.dart';
import 'presentation/screens/request_form_screen.dart';
import 'presentation/screens/requests_screen.dart';

/// Student administrative requests & letters. `new` is declared before
/// `:id`; the draft editor is nested under the request path.
final List<RouteBase> studentServicesRoutes = <RouteBase>[
  GoRoute(
    path: RoutePaths.studentRequests,
    builder: (context, state) => const RequestsScreen(),
  ),
  GoRoute(
    path: RoutePaths.studentRequestNew,
    builder: (context, state) => const RequestFormScreen(),
  ),
  GoRoute(
    path: '${RoutePaths.studentRequests}/:id',
    builder: (context, state) =>
        RequestDetailScreen(requestId: state.pathParameters['id']!),
  ),
  GoRoute(
    path: '${RoutePaths.studentRequests}/:id/edit',
    builder: (context, state) =>
        RequestEditScreen(requestId: state.pathParameters['id']!),
  ),
];
