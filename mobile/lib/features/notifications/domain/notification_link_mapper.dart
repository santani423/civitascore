import '../../../core/router/route_paths.dart';
import '../../../core/session/app_experience.dart';

/// Maps the WEB `link` path stored on a notification (e.g. `/portal/krs`) to
/// the matching mobile route for the current experience. Returns null for
/// unknown links (the caller just marks the notification read).
String? mapNotificationLink(String? link, AppExperience? experience) {
  if (link == null || experience == null) return null;
  var path = link.trim();
  if (path.isEmpty) return null;
  final uri = Uri.tryParse(path);
  if (uri == null) return null;
  path = uri.path;
  while (path.length > 1 && path.endsWith('/')) {
    path = path.substring(0, path.length - 1);
  }
  final segments = path.split('/').where((s) => s.isNotEmpty).toList();
  if (segments.isEmpty) return null;

  switch (experience) {
    case AppExperience.student:
      return _student(segments);
    case AppExperience.lecturer:
      return _lecturer(segments);
  }
}

/// Paths that are bottom-bar tabs: navigate with `go`, not `push`.
bool isTabRoute(String path) => const {
  RoutePaths.studentHome,
  RoutePaths.studentAcademic,
  RoutePaths.studentLearning,
  RoutePaths.studentFinance,
  RoutePaths.studentAccount,
  RoutePaths.lecturerHome,
  RoutePaths.lecturerTeaching,
  RoutePaths.lecturerSchedule,
  RoutePaths.lecturerAdvising,
  RoutePaths.lecturerAccount,
}.contains(path);

String? _student(List<String> s) {
  if (s.first != 'portal' || s.length < 2) return null;
  final page = s[1];
  final id = s.length == 3 ? Uri.decodeComponent(s[2]) : null;
  final isRoot = s.length == 2;
  switch (page) {
    case 'krs':
      return isRoot ? RoutePaths.studentKrs : null;
    case 'jadwal-kuliah':
      return isRoot ? RoutePaths.studentSchedule : null;
    case 'ujian':
      return isRoot ? RoutePaths.studentExams : null;
    case 'tugas':
      if (isRoot) return RoutePaths.studentAssignments;
      return id == null ? null : RoutePaths.studentAssignment(id);
    case 'mata-kuliah':
      if (isRoot) return RoutePaths.studentCourses;
      return id == null ? null : RoutePaths.studentCourse(id);
    case 'materi':
      return isRoot ? RoutePaths.studentMaterials : null;
    case 'nilai':
      return isRoot ? RoutePaths.studentGrades : null;
    case 'khs':
      return isRoot ? RoutePaths.studentKhs : null;
    case 'transkrip':
      return isRoot ? RoutePaths.studentTranscript : null;
    case 'absensi':
      return isRoot ? RoutePaths.studentAttendance : null;
    case 'dokumen':
      return isRoot ? RoutePaths.studentDocuments : null;
    case 'kalender':
      return isRoot ? RoutePaths.studentCalendar : null;
    case 'pengajuan':
      return isRoot ? RoutePaths.studentRequests : null;
    case 'pengumuman':
      return isRoot ? RoutePaths.studentAnnouncements : null;
    case 'akademik':
      return isRoot ? RoutePaths.studentAcademic : null;
    case 'profil':
      return isRoot ? RoutePaths.studentProfile : null;
    case 'pengaturan':
      return isRoot ? RoutePaths.settings : null;
    case 'keuangan':
      if (isRoot) return RoutePaths.studentFinance;
      if (s.length == 4 && s[2] == 'tagihan') {
        return RoutePaths.studentInvoice(Uri.decodeComponent(s[3]));
      }
      return null;
  }
  return null;
}

String? _lecturer(List<String> s) {
  if (s.length == 2 && s[0] == 'akademik' && s[1] == 'persetujuan-krs') {
    return RoutePaths.lecturerAdvising;
  }
  return null;
}
