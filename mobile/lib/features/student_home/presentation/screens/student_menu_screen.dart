import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../../core/l10n/core_strings.dart';
import '../../../../core/router/route_paths.dart';
import '../../../../core/theme/app_dimensions.dart';
import '../../../../core/widgets/service_card.dart';
import '../../../../core/widgets/ui_kit.dart';
import '../../../auth/presentation/controllers/auth_controller.dart';
import '../../l10n/student_home_strings.dart';
import '../widgets/home_navigation.dart';

/// Kategori chip di Menu Utama. [key] dipakai pada query `?kategori=` supaya
/// kartu beranda dapat membuka menu dengan chip yang sudah terpilih.
enum StudentMenuCategory {
  all(null),
  academic('akademik'),
  studentAffairs('kemahasiswaan'),
  services('layanan');

  const StudentMenuCategory(this.key);
  final String? key;

  static StudentMenuCategory fromKey(String? key) =>
      StudentMenuCategory.values.firstWhere(
        (c) => c.key != null && c.key == key,
        orElse: () => StudentMenuCategory.all,
      );
}

/// Satu layanan di Menu Utama. [permission] mengikuti gate baca backend;
/// item tanpa izin disembunyikan, bukan hanya dinonaktifkan.
class StudentMenuEntry {
  const StudentMenuEntry({
    required this.category,
    required this.icon,
    required this.title,
    required this.description,
    required this.route,
    this.permission,
    this.comingSoon = false,
  });

  final StudentMenuCategory category;
  final IconData icon;
  final String title;
  final String description;
  final String route;
  final String? permission;

  /// Layanan yang backend-nya belum ada (lihat `core/pending`).
  final bool comingSoon;
}

/// Katalog layanan mahasiswa. Hanya navigasi: tidak ada data tiruan.
List<StudentMenuEntry> studentMenuCatalog(BuildContext context) {
  final s = context.homeStr;
  const academic = StudentMenuCategory.academic;
  const affairs = StudentMenuCategory.studentAffairs;
  const services = StudentMenuCategory.services;
  return [
    StudentMenuEntry(
      category: academic,
      icon: Icons.assignment_outlined,
      title: s.menuKrsTitle,
      description: s.menuKrsDesc,
      route: RoutePaths.studentKrs,
      permission: 'krs_self_service.read',
    ),
    StudentMenuEntry(
      category: academic,
      icon: Icons.description_outlined,
      title: s.menuKhsTitle,
      description: s.menuKhsDesc,
      route: RoutePaths.studentKhs,
      permission: 'student_portal.read',
    ),
    StudentMenuEntry(
      category: academic,
      icon: Icons.history_edu_outlined,
      title: s.menuTranscriptTitle,
      description: s.menuTranscriptDesc,
      route: RoutePaths.studentTranscript,
      permission: 'student_portal.read',
    ),
    StudentMenuEntry(
      category: academic,
      icon: Icons.grade_outlined,
      title: s.menuGradesTitle,
      description: s.menuGradesDesc,
      route: RoutePaths.studentGrades,
      permission: 'student_portal.read',
    ),
    StudentMenuEntry(
      category: academic,
      icon: Icons.calendar_view_week_outlined,
      title: s.menuScheduleTitle,
      description: s.menuScheduleDesc,
      route: RoutePaths.studentSchedule,
      permission: 'student_portal.read',
    ),
    StudentMenuEntry(
      category: academic,
      icon: Icons.how_to_reg_outlined,
      title: s.menuAttendanceTitle,
      description: s.menuAttendanceDesc,
      route: RoutePaths.studentAttendance,
      permission: 'student_portal.read',
    ),
    StudentMenuEntry(
      category: academic,
      icon: Icons.event_outlined,
      title: s.menuCalendarTitle,
      description: s.menuCalendarDesc,
      route: RoutePaths.studentCalendar,
      permission: 'student_portal.read',
    ),
    StudentMenuEntry(
      category: academic,
      icon: Icons.menu_book_outlined,
      title: s.menuCoursesTitle,
      description: s.menuCoursesDesc,
      route: RoutePaths.studentCourses,
      permission: 'student_portal.read',
    ),
    StudentMenuEntry(
      category: academic,
      icon: Icons.library_books_outlined,
      title: s.menuMaterialsTitle,
      description: s.menuMaterialsDesc,
      route: RoutePaths.studentMaterials,
      permission: 'student_portal.read',
    ),
    StudentMenuEntry(
      category: academic,
      icon: Icons.task_outlined,
      title: s.menuAssignmentsTitle,
      description: s.menuAssignmentsDesc,
      route: RoutePaths.studentAssignments,
      permission: 'student_portal.read',
    ),
    StudentMenuEntry(
      category: academic,
      icon: Icons.quiz_outlined,
      title: s.menuExamsTitle,
      description: s.menuExamsDesc,
      route: RoutePaths.studentExams,
      permission: 'exam_participation.read',
    ),
    StudentMenuEntry(
      category: academic,
      icon: Icons.folder_outlined,
      title: s.menuDocumentsTitle,
      description: s.menuDocumentsDesc,
      route: RoutePaths.studentDocuments,
      permission: 'student_portal.read',
    ),
    StudentMenuEntry(
      category: affairs,
      icon: Icons.school_outlined,
      title: s.menuAwardsTitle,
      description: s.menuAwardsDesc,
      route: RoutePaths.studentAwards,
      permission: 'student_finance.read',
    ),
    StudentMenuEntry(
      category: affairs,
      icon: Icons.groups_outlined,
      title: s.menuActivitiesTitle,
      description: s.menuActivitiesDesc,
      route: RoutePaths.pendingFeature('student_affairs'),
      comingSoon: true,
    ),
    StudentMenuEntry(
      category: services,
      icon: Icons.account_balance_wallet_outlined,
      title: s.menuFinanceTitle,
      description: s.menuFinanceDesc,
      route: RoutePaths.studentFinance,
      permission: 'student_finance.read',
    ),
    StudentMenuEntry(
      category: services,
      icon: Icons.mail_outline,
      title: s.menuRequestsTitle,
      description: s.menuRequestsDesc,
      route: RoutePaths.studentRequests,
      permission: 'student_requests.read',
    ),
    StudentMenuEntry(
      category: services,
      icon: Icons.campaign_outlined,
      title: s.menuInfoTitle,
      description: s.menuInfoDesc,
      route: RoutePaths.studentAnnouncements,
      permission: 'student_portal.read',
    ),
    StudentMenuEntry(
      category: services,
      icon: Icons.notifications_outlined,
      title: s.menuNotificationsTitle,
      description: s.menuNotificationsDesc,
      route: RoutePaths.notifications,
    ),
  ];
}

/// Warna ikon per kategori: akademik biru, kemahasiswaan hijau, layanan oranye.
StatusTone _toneFor(StudentMenuCategory category) => switch (category) {
  StudentMenuCategory.academic => StatusTone.info,
  StudentMenuCategory.studentAffairs => StatusTone.success,
  _ => StatusTone.accent,
};

/// Menu Utama mahasiswa: cari layanan, filter kategori, dan daftar kartu.
class StudentMenuScreen extends ConsumerStatefulWidget {
  const StudentMenuScreen({super.key, this.initialCategory});

  /// Nilai query `kategori` (lihat [StudentMenuCategory.key]).
  final String? initialCategory;

  @override
  ConsumerState<StudentMenuScreen> createState() => _StudentMenuScreenState();
}

class _StudentMenuScreenState extends ConsumerState<StudentMenuScreen> {
  late StudentMenuCategory _category = StudentMenuCategory.fromKey(
    widget.initialCategory,
  );
  final _controller = TextEditingController();
  bool _searching = false;
  String _query = '';

  @override
  void dispose() {
    _controller.dispose();
    super.dispose();
  }

  void _toggleSearch() {
    setState(() {
      _searching = !_searching;
      if (!_searching) {
        _controller.clear();
        _query = '';
      }
    });
  }

  @override
  Widget build(BuildContext context) {
    final s = context.homeStr;
    final user = ref.watch(currentUserProvider);
    final entries = studentMenuCatalog(context).where((e) {
      if (e.permission != null && user?.hasPermission(e.permission!) != true) {
        return false;
      }
      if (_category != StudentMenuCategory.all && e.category != _category) {
        return false;
      }
      if (_query.isEmpty) return true;
      final q = _query.toLowerCase();
      return e.title.toLowerCase().contains(q) ||
          e.description.toLowerCase().contains(q);
    }).toList();

    final scheme = Theme.of(context).colorScheme;
    final text = Theme.of(context).textTheme;
    return Scaffold(
      appBar: AppBar(
        title: _searching
            ? TextField(
                controller: _controller,
                autofocus: true,
                textInputAction: TextInputAction.search,
                decoration: InputDecoration(
                  hintText: s.menuSearch,
                  border: InputBorder.none,
                ),
                onChanged: (v) => setState(() => _query = v.trim()),
              )
            : Text(s.menuTitle),
        actions: [
          IconButton(
            tooltip: _searching ? context.l10n.close : s.menuSearchTooltip,
            icon: Icon(_searching ? Icons.close : Icons.search),
            onPressed: _toggleSearch,
          ),
        ],
      ),
      body: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          SingleChildScrollView(
            scrollDirection: Axis.horizontal,
            padding: const EdgeInsets.symmetric(
              horizontal: AppSpacing.lg,
              vertical: AppSpacing.sm,
            ),
            child: Row(
              children: [
                for (final c in StudentMenuCategory.values)
                  Padding(
                    padding: const EdgeInsets.only(right: AppSpacing.sm),
                    child: ChoiceChip(
                      label: Text(_categoryLabel(s, c)),
                      selected: _category == c,
                      showCheckmark: false,
                      // Chip terpilih berwarna orange penuh (mockup Menu Utama).
                      selectedColor: scheme.primary,
                      backgroundColor: scheme.surface,
                      side: _category == c
                          ? BorderSide.none
                          : BorderSide(color: scheme.outlineVariant),
                      labelStyle: text.labelLarge?.copyWith(
                        color: _category == c
                            ? scheme.onPrimary
                            : scheme.onSurface,
                      ),
                      onSelected: (_) => setState(() => _category = c),
                    ),
                  ),
              ],
            ),
          ),
          Expanded(
            child: entries.isEmpty
                ? Center(child: Text(s.menuNoResults))
                : ListView(
                    padding: const EdgeInsets.fromLTRB(
                      AppSpacing.lg,
                      AppSpacing.sm,
                      AppSpacing.lg,
                      AppSpacing.xl3,
                    ),
                    children: [
                      for (final e in entries)
                        ServiceCard(
                          icon: e.icon,
                          title: e.title,
                          subtitle: e.description,
                          tone: _toneFor(e.category),
                          footer: e.comingSoon
                              ? StatusChip(
                                  label: s.menuComingSoon,
                                  tone: StatusTone.neutral,
                                  dense: true,
                                )
                              : null,
                          onTap: () => openAppRoute(context, e.route),
                        ),
                    ],
                  ),
          ),
        ],
      ),
    );
  }

  String _categoryLabel(StudentHomeStrings s, StudentMenuCategory c) =>
      switch (c) {
        StudentMenuCategory.all => s.menuCategoryAll,
        StudentMenuCategory.academic => s.qaAcademic,
        StudentMenuCategory.studentAffairs => s.qaStudentAffairs,
        StudentMenuCategory.services => s.menuCategoryServices,
      };
}
