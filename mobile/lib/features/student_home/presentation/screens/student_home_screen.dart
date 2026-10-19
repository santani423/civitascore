import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../../core/widgets/notification_bell.dart';
import '../../../../core/widgets/state_views.dart';
import '../../../auth/presentation/controllers/auth_controller.dart';
import '../../domain/student_dashboard.dart';
import '../../l10n/student_home_strings.dart';
import '../providers.dart';
import '../widgets/home_sections.dart';

/// Student "Beranda" tab (`GET /student/dashboard` + finance summary card).
class StudentHomeScreen extends ConsumerWidget {
  const StudentHomeScreen({super.key});

  Future<void> _refresh(WidgetRef ref) async {
    ref.invalidate(unreadNotificationCountProvider);
    await refreshStudentHome(ref);
  }

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final dashboard = ref.watch(studentDashboardProvider);
    final user = ref.watch(currentUserProvider);
    return Scaffold(
      appBar: AppBar(
        title: Text(context.homeStr.title),
        actions: const [NotificationBellButton()],
      ),
      body: AsyncValueView<StudentDashboard>(
        value: dashboard,
        onRetry: () => _refresh(ref),
        data: (d) => RefreshIndicator(
          onRefresh: () => _refresh(ref),
          child: ListView(
            physics: const AlwaysScrollableScrollPhysics(),
            padding: const EdgeInsets.only(bottom: 32, top: 16),
            children: [
              Center(
                child: ConstrainedBox(
                  constraints: const BoxConstraints(maxWidth: 720),
                  child: _Content(
                    data: d,
                    fallbackName: user?.name,
                    canSeeFinance:
                        user?.hasPermission('student_finance.read') ?? false,
                  ),
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }
}

class _Content extends ConsumerWidget {
  const _Content({
    required this.data,
    required this.canSeeFinance,
    this.fallbackName,
  });
  final StudentDashboard data;
  final bool canSeeFinance;
  final String? fallbackName;

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final finance = canSeeFinance
        ? ref.watch(studentFinanceSummaryProvider)
        : null;
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        ProfileHeader(profile: data.profile, fallbackName: fallbackName),
        AlertsSection(alerts: data.alerts),
        if (data.academic != null)
          AcademicSection(academic: data.academic!, krs: data.krs),
        ScheduleSection(today: data.todaySchedule, nextClass: data.nextClass),
        if (data.attendance != null)
          AttendanceSection(attendance: data.attendance!),
        ExamsSection(exams: data.upcomingExams),
        AssignmentsSection(assignments: data.assignmentsDue),
        GradesSection(grades: data.recentGrades),
        AnnouncementsSection(
          latest: data.latestAnnouncements,
          unread: data.unreadAnnouncements,
        ),
        if (finance != null)
          FinanceCard(
            summary: finance.valueOrNull,
            error: finance.hasError ? finance.error : null,
            onRetry: () => ref.invalidate(studentFinanceSummaryProvider),
          ),
        QuickActionsGrid(canSeeFinance: canSeeFinance),
      ],
    );
  }
}
