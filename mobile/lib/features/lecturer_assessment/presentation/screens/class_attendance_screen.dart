import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../../core/theme/app_dimensions.dart';
import '../../../../core/utils/formatters.dart';
import '../../../../core/widgets/state_views.dart';
import '../../../../core/widgets/ui_kit.dart';
import '../../../auth/presentation/controllers/auth_controller.dart';
import '../../domain/assessment_models.dart';
import '../../l10n/assessment_strings.dart';
import '../providers.dart';
import '../widgets/status_helpers.dart';
import 'record_meeting_screen.dart';

class ClassAttendanceScreen extends ConsumerWidget {
  const ClassAttendanceScreen({super.key, required this.classSectionId});
  final String classSectionId;

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final s = context.assess;
    final sheet = ref.watch(attendanceSheetProvider(classSectionId));
    final user = ref.watch(currentUserProvider);
    final canWrite =
        user?.hasAnyPermission(['attendance.create', 'attendance.update']) ??
        false;

    Future<void> openRecord([Meeting? meeting]) async {
      final data = sheet.valueOrNull;
      if (data == null) return;
      await Navigator.of(context).push<bool>(
        MaterialPageRoute(
          builder: (_) => RecordMeetingScreen(
            classSectionId: classSectionId,
            sheet: data,
            meeting: meeting,
          ),
        ),
      );
    }

    return DefaultTabController(
      length: 2,
      child: Scaffold(
        appBar: AppBar(
          title: Text(s.attendanceTitle),
          bottom: TabBar(
            tabs: [
              Tab(text: s.tabMeetings),
              Tab(text: s.tabRecap),
            ],
          ),
        ),
        floatingActionButton: canWrite && sheet.hasValue
            ? FloatingActionButton.extended(
                onPressed: () => openRecord(),
                icon: const Icon(Icons.add),
                label: Text(s.recordMeeting),
              )
            : null,
        body: AsyncValueView<AttendanceSheet>(
          value: sheet,
          onRetry: () =>
              ref.invalidate(attendanceSheetProvider(classSectionId)),
          data: (d) => RefreshIndicator(
            onRefresh: () async {
              ref.invalidate(attendanceSheetProvider(classSectionId));
              await ref.read(attendanceSheetProvider(classSectionId).future);
            },
            child: TabBarView(
              children: [
                _MeetingsTab(sheet: d, onTap: canWrite ? openRecord : null),
                _RecapTab(sheet: d),
              ],
            ),
          ),
        ),
      ),
    );
  }
}

Widget _scrollEmpty(Widget child) => LayoutBuilder(
  builder: (_, c) => SingleChildScrollView(
    physics: const AlwaysScrollableScrollPhysics(),
    child: SizedBox(height: c.maxHeight, child: child),
  ),
);

class _MeetingsTab extends StatelessWidget {
  const _MeetingsTab({required this.sheet, this.onTap});
  final AttendanceSheet sheet;
  final void Function(Meeting)? onTap;

  @override
  Widget build(BuildContext context) {
    final s = context.assess;
    final meetings = sheet.meetings.reversed.toList();
    if (meetings.isEmpty) {
      return _scrollEmpty(
        EmptyView(
          title: s.noMeetingsTitle,
          message: s.noMeetingsBody,
          icon: Icons.event_available_outlined,
        ),
      );
    }
    return CenteredBody(
      child: ListView.separated(
        physics: const AlwaysScrollableScrollPhysics(),
        padding: const EdgeInsets.fromLTRB(
          AppSpacing.lg,
          AppSpacing.md,
          AppSpacing.lg,
          96,
        ),
        itemCount: meetings.length,
        separatorBuilder: (_, _) => const SizedBox(height: AppSpacing.sm),
        itemBuilder: (context, i) {
          final m = meetings[i];
          return AppSurfaceCard(
            onTap: onTap == null ? null : () => onTap!(m),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Row(
                  children: [
                    Expanded(
                      child: Text(
                        s.meetingLabel(m.number),
                        style: Theme.of(context).textTheme.titleMedium,
                        overflow: TextOverflow.ellipsis,
                      ),
                    ),
                    Text(context.formatDate(m.date)),
                  ],
                ),
                const SizedBox(height: AppSpacing.sm),
                Wrap(
                  spacing: AppSpacing.sm,
                  runSpacing: AppSpacing.xs,
                  children: [
                    for (final st in AttendanceStatus.values)
                      StatusChip(
                        dense: true,
                        tone: toneOf(st),
                        label: '${s.statusLabel(st)} ${m.count(st)}',
                      ),
                  ],
                ),
              ],
            ),
          );
        },
      ),
    );
  }
}

class _RecapTab extends StatelessWidget {
  const _RecapTab({required this.sheet});
  final AttendanceSheet sheet;

  @override
  Widget build(BuildContext context) {
    final s = context.assess;
    final recaps = sheet.recaps;
    if (recaps.isEmpty) {
      return _scrollEmpty(
        EmptyView(
          title: s.noStudentsTitle,
          message: s.noStudentsBody,
          icon: Icons.groups_outlined,
        ),
      );
    }
    return CenteredBody(
      child: ListView.separated(
        physics: const AlwaysScrollableScrollPhysics(),
        padding: const EdgeInsets.all(AppSpacing.lg),
        itemCount: recaps.length + (sheet.records.isEmpty ? 1 : 0),
        separatorBuilder: (_, _) => const SizedBox(height: AppSpacing.sm),
        itemBuilder: (context, i) {
          if (sheet.records.isEmpty && i == 0) {
            return AppBanner(message: s.recapEmpty);
          }
          final r = recaps[i - (sheet.records.isEmpty ? 1 : 0)];
          final pct = r.percentage;
          return AppSurfaceCard(
            child: Row(
              children: [
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        r.student.name,
                        style: Theme.of(context).textTheme.titleSmall,
                        maxLines: 2,
                        overflow: TextOverflow.ellipsis,
                      ),
                      Text(
                        r.student.nim,
                        style: Theme.of(context).textTheme.bodySmall,
                      ),
                      const SizedBox(height: 4),
                      Text(
                        s.recapSummary(
                          r.present,
                          r.permitted,
                          r.sick,
                          r.absent,
                        ),
                        style: Theme.of(context).textTheme.bodySmall,
                      ),
                    ],
                  ),
                ),
                const SizedBox(width: AppSpacing.md),
                Column(
                  crossAxisAlignment: CrossAxisAlignment.end,
                  children: [
                    Text(
                      pct == null
                          ? '—'
                          : '${context.formatNumber(pct, maxDecimals: 0)}%',
                      style: Theme.of(context).textTheme.titleMedium,
                    ),
                    if (r.atRisk)
                      StatusChip(
                        dense: true,
                        tone: StatusTone.danger,
                        label: s.atRisk,
                      ),
                  ],
                ),
              ],
            ),
          );
        },
      ),
    );
  }
}
