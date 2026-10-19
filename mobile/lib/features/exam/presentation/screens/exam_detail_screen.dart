import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../../core/permission/permission_gate.dart';
import '../../../../core/widgets/state_views.dart';
import '../../domain/exam.dart';
import '../../l10n/exam_strings.dart';
import '../providers.dart';
import '../widgets/grades_tab.dart';
import '../widgets/monitor_tab.dart';
import '../widgets/overview_tab.dart';
import '../widgets/recap_tab.dart';

/// Detail ujian dosen: ringkasan, pantau peserta, rekap, rentang nilai.
class ExamDetailScreen extends ConsumerWidget {
  const ExamDetailScreen({super.key, required this.examId});

  final String examId;

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final s = context.examL10n;
    final async = ref.watch(examProvider(examId));
    final canMonitor = PermissionGate.allows(ref, 'exam_attempts.read');

    final tabs = <(String, Widget Function(Exam))>[
      (s.tabOverview, (e) => OverviewTab(exam: e)),
      if (canMonitor) (s.tabMonitor, (e) => MonitorTab(examId: e.id)),
      (s.tabRecap, (e) => RecapTab(examId: e.id)),
      (s.tabGrades, (e) => GradesTab(examId: e.id)),
    ];

    return DefaultTabController(
      length: tabs.length,
      child: Scaffold(
        appBar: AppBar(
          title: Text(async.valueOrNull?.title ?? s.title),
          bottom: TabBar(
            isScrollable: true,
            tabAlignment: TabAlignment.start,
            tabs: [for (final t in tabs) Tab(text: t.$1)],
          ),
        ),
        body: async.when(
          skipLoadingOnRefresh: true,
          skipLoadingOnReload: true,
          loading: () => const SkeletonList(),
          error: (e, _) => ErrorView(
            error: e,
            onRetry: () => ref.invalidate(examProvider(examId)),
          ),
          data: (exam) => Center(
            child: ConstrainedBox(
              constraints: const BoxConstraints(maxWidth: 720),
              child: TabBarView(children: [for (final t in tabs) t.$2(exam)]),
            ),
          ),
        ),
      ),
    );
  }
}
