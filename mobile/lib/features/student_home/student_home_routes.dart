import 'package:go_router/go_router.dart';

export 'presentation/screens/student_home_screen.dart' show StudentHomeScreen;

/// The home tab has no detail routes of its own; every card navigates to
/// routes owned by other modules.
final List<RouteBase> studentHomeRoutes = <RouteBase>[];
