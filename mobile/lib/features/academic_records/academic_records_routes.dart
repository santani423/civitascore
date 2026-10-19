import 'package:go_router/go_router.dart';

import '../../core/router/route_paths.dart';
import 'presentation/screens/documents_screen.dart';
import 'presentation/screens/grades_screen.dart';
import 'presentation/screens/history_screen.dart';
import 'presentation/screens/khs_screen.dart';
import 'presentation/screens/profile_screen.dart';
import 'presentation/screens/transcript_screen.dart';

export 'presentation/screens/academic_hub_screen.dart' show AcademicHubScreen;

final List<RouteBase> academicRecordsRoutes = <RouteBase>[
  GoRoute(
    path: RoutePaths.studentProfile,
    builder: (_, _) => const StudentProfileScreen(),
  ),
  GoRoute(
    path: RoutePaths.studentAcademicHistory,
    builder: (_, _) => const AcademicHistoryScreen(),
  ),
  GoRoute(
    path: RoutePaths.studentGrades,
    builder: (_, _) => const GradesScreen(),
  ),
  GoRoute(path: RoutePaths.studentKhs, builder: (_, _) => const KhsScreen()),
  GoRoute(
    path: RoutePaths.studentTranscript,
    builder: (_, _) => const TranscriptScreen(),
  ),
  GoRoute(
    path: RoutePaths.studentDocuments,
    builder: (_, _) => const DocumentsScreen(),
  ),
];
