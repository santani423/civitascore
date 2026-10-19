import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../../core/theme/app_dimensions.dart';
import '../../../../core/utils/formatters.dart';
import '../../../../core/widgets/state_views.dart';
import '../../../../core/widgets/ui_kit.dart';
import '../../domain/models.dart';
import '../../l10n/schedule_attendance_strings.dart';
import '../providers.dart';
import '../widgets/constrained_body.dart';
import 'attendance_screen.dart';

StatusTone meetingTone(String status) => switch (status) {
  'present' => StatusTone.success,
  'sick' => StatusTone.info,
  'permitted' => StatusTone.warning,
  'absent' => StatusTone.danger,
  _ => StatusTone.neutral,
};

class StudentAttendanceDetailScreen extends ConsumerWidget {
  const StudentAttendanceDetailScreen({super.key, required this.krsItemId});
  final String krsItemId;

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final sa = context.sa;
    final value = ref.watch(studentAttendanceDetailProvider(krsItemId));
    final text = Theme.of(context).textTheme;
    return Scaffold(
      appBar: AppBar(title: Text(sa.attendanceDetailTitle)),
      body: SafeArea(
        child: ConstrainedBody(
          child: AsyncValueView<AttendanceDetail>(
            value: value,
            onRetry: () =>
                ref.invalidate(studentAttendanceDetailProvider(krsItemId)),
            data: (d) => RefreshIndicator(
              onRefresh: () async {
                ref.invalidate(studentAttendanceDetailProvider(krsItemId));
                await ref.read(
                  studentAttendanceDetailProvider(krsItemId).future,
                );
              },
              child: ListView(
                physics: const AlwaysScrollableScrollPhysics(),
                padding: const EdgeInsets.all(AppSpacing.lg),
                children: [
                  if (d.isBelowMinimum)
                    Padding(
                      padding: const EdgeInsets.only(bottom: AppSpacing.md),
                      child: AppBanner(
                        tone: StatusTone.warning,
                        message: sa.belowMinimumBanner(
                          context.formatNumber(
                            d.minimumPercent,
                            maxDecimals: 1,
                          ),
                          1,
                        ),
                      ),
                    ),
                  AppSurfaceCard(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(d.courseName, style: text.titleMedium),
                        Text(
                          [
                            ?d.courseCode,
                            ?d.classCode,
                            ?d.lecturer,
                          ].join(' · '),
                          style: text.bodySmall,
                        ),
                        const SizedBox(height: AppSpacing.sm),
                        Text(
                          pctLabel(context, d.totals.percentage),
                          style: text.headlineSmall,
                        ),
                        Text(
                          sa.meetings(d.totals.totalMeetings),
                          style: text.bodySmall,
                        ),
                        if (d.minimumPercent != null)
                          Text(
                            sa.minimumNote(
                              context.formatNumber(
                                d.minimumPercent,
                                maxDecimals: 1,
                              ),
                            ),
                            style: text.bodySmall,
                          ),
                        const SizedBox(height: AppSpacing.sm),
                        AttendanceCounts(totals: d.totals),
                      ],
                    ),
                  ),
                  SectionHeader(title: sa.meetingsList),
                  if (d.meetings.isEmpty)
                    Padding(
                      padding: const EdgeInsets.all(AppSpacing.lg),
                      child: Center(child: Text(sa.noMeetings)),
                    ),
                  for (final m in d.meetings)
                    Padding(
                      padding: const EdgeInsets.only(bottom: AppSpacing.sm),
                      child: AppSurfaceCard(
                        padding: const EdgeInsets.all(AppSpacing.md),
                        child: Row(
                          children: [
                            Expanded(
                              child: Column(
                                crossAxisAlignment: CrossAxisAlignment.start,
                                children: [
                                  Text(
                                    sa.meetingNo(m.number),
                                    style: text.titleSmall,
                                  ),
                                  Text(
                                    context.formatDate(m.date),
                                    style: text.bodySmall,
                                  ),
                                  if (m.notes != null)
                                    Text(
                                      m.notes!,
                                      style: text.bodySmall,
                                      maxLines: 2,
                                      overflow: TextOverflow.ellipsis,
                                    ),
                                ],
                              ),
                            ),
                            StatusChip(
                              label: sa.meetingStatus(m.status, m.statusLabel),
                              tone: meetingTone(m.status),
                              dense: true,
                            ),
                          ],
                        ),
                      ),
                    ),
                ],
              ),
            ),
          ),
        ),
      ),
    );
  }
}
