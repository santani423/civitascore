import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import '../../../../core/pending/pending_feature_screen.dart';
import '../../../../core/pending/pending_features.dart';
import '../../../../core/router/route_paths.dart';
import '../../../../core/theme/app_dimensions.dart';
import '../../../../core/widgets/notification_bell.dart';
import '../../../../core/widgets/ui_kit.dart';
import '../../l10n/academic_records_strings.dart';
import '../providers.dart';
import '../widgets/academic_summary_card.dart';
import '../widgets/common.dart';

const _pendingKeys = [
  'edom',
  'short_semester',
  'graduation',
  'thesis',
  'student_affairs',
  'scholarships',
  'attendance_permissions',
];

/// Student "Akademik" tab.
class AcademicHubScreen extends ConsumerWidget {
  const AcademicHubScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final a = context.acadL10n;
    final summary = ref.watch(academicSummaryProvider);

    Widget entry(IconData icon, String title, String sub, String route) =>
        MenuTile(
          icon: icon,
          title: title,
          subtitle: sub,
          onTap: () => context.push(route),
        );

    return Scaffold(
      appBar: AppBar(
        title: Text(a.hubTitle),
        actions: const [NotificationBellButton()],
      ),
      body: RefreshList(
        onRefresh: () async => ref.refresh(academicSummaryProvider.future),
        children: [
          summary.when(
            skipLoadingOnRefresh: true,
            data: (s) => InkWell(
              onTap: () => context.push(RoutePaths.studentAcademicHistory),
              child: AcademicSummaryCard(summary: s),
            ),
            loading: () => const Padding(
              padding: EdgeInsets.all(AppSpacing.lg),
              child: Center(child: CircularProgressIndicator()),
            ),
            error: (_, _) => const SizedBox.shrink(),
          ),
          SectionHeader(
            title: a.menuTitle,
            padding: const EdgeInsets.only(top: AppSpacing.lg),
          ),
          entry(
            Icons.assignment_outlined,
            a.krs,
            a.krsSub,
            RoutePaths.studentKrs,
          ),
          entry(
            Icons.calendar_view_week_outlined,
            a.schedule,
            a.scheduleSub,
            RoutePaths.studentSchedule,
          ),
          entry(
            Icons.event_outlined,
            a.calendar,
            a.calendarSub,
            RoutePaths.studentCalendar,
          ),
          entry(
            Icons.fact_check_outlined,
            a.attendance,
            a.attendanceSub,
            RoutePaths.studentAttendance,
          ),
          entry(
            Icons.grade_outlined,
            a.grades,
            a.gradesSub,
            RoutePaths.studentGrades,
          ),
          entry(Icons.grading_outlined, a.khs, a.khsSub, RoutePaths.studentKhs),
          entry(
            Icons.workspace_premium_outlined,
            a.transcript,
            a.transcriptSub,
            RoutePaths.studentTranscript,
          ),
          entry(
            Icons.folder_outlined,
            a.documents,
            a.documentsSub,
            RoutePaths.studentDocuments,
          ),
          entry(
            Icons.timeline_outlined,
            a.history,
            a.historySub,
            RoutePaths.studentAcademicHistory,
          ),
          SectionHeader(
            title: a.pendingSection,
            padding: const EdgeInsets.only(top: AppSpacing.lg),
          ),
          for (final k in _pendingKeys)
            if (pendingFeatureByKey(k) != null)
              PendingFeatureTile(feature: pendingFeatureByKey(k)!),
        ],
      ),
    );
  }
}
