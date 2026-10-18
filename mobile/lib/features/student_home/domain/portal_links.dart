import '../../../core/router/route_paths.dart';

/// Maps a web `/portal/...` link from dashboard alerts to an app route.
/// Returns null for unknown / missing links (no navigation).
String? routeForPortalLink(String? link) {
  if (link == null) return null;
  var path = link.trim();
  final cut = path.indexOf(RegExp(r'[?#]'));
  if (cut >= 0) path = path.substring(0, cut);
  while (path.length > 1 && path.endsWith('/')) {
    path = path.substring(0, path.length - 1);
  }
  final segments = path.split('/').where((s) => s.isNotEmpty).toList();
  if (segments.isEmpty || segments.first != 'portal') return null;
  final rest = segments.skip(1).toList();
  if (rest.isEmpty) return RoutePaths.studentHome;
  final id = rest.length == 2 ? rest[1] : null;
  if (rest.length > 2) return null;
  switch (rest.first) {
    case 'akademik':
      return id == null ? RoutePaths.studentProfile : null;
    case 'krs':
      return id == null ? RoutePaths.studentKrs : null;
    case 'ujian':
      return id == null ? RoutePaths.studentExams : RoutePaths.studentExam(id);
    case 'tugas':
      return id == null
          ? RoutePaths.studentAssignments
          : RoutePaths.studentAssignment(id);
    case 'nilai':
      return id == null ? RoutePaths.studentGrades : null;
    case 'absensi':
      return id == null ? RoutePaths.studentAttendance : null;
    case 'jadwal':
      return id == null ? RoutePaths.studentSchedule : null;
    case 'pengumuman':
      return id == null
          ? RoutePaths.studentAnnouncements
          : RoutePaths.studentAnnouncement(id);
    case 'keuangan':
      return id == null ? RoutePaths.studentFinance : null;
    case 'kalender':
      return id == null ? RoutePaths.studentCalendar : null;
    default:
      return null;
  }
}
