import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import '../../../../core/pending/pending_feature_screen.dart';
import '../../../../core/pending/pending_features.dart';
import '../../../../core/router/route_paths.dart';
import '../../../../core/theme/app_dimensions.dart';
import '../../../../core/utils/formatters.dart';
import '../../../../core/widgets/state_views.dart';
import '../../../../core/widgets/ui_kit.dart';
import '../../domain/models.dart';
import '../../l10n/schedule_attendance_strings.dart';
import '../providers.dart';
import '../widgets/constrained_body.dart';

String pctLabel(BuildContext c, double? p) =>
    p == null ? '—' : '${c.formatNumber(p, maxDecimals: 1)}%';

class AttendanceCounts extends StatelessWidget {
  const AttendanceCounts({super.key, required this.totals});
  final AttendanceTotals totals;

  @override
  Widget build(BuildContext context) {
    final sa = context.sa;
    return Wrap(
      spacing: AppSpacing.xs,
      runSpacing: AppSpacing.xs,
      children: [
        StatusChip(
          label: '${sa.present} ${totals.present}',
          tone: StatusTone.success,
          dense: true,
        ),
        StatusChip(
          label: '${sa.sick} ${totals.sick}',
          tone: StatusTone.info,
          dense: true,
        ),
        StatusChip(
          label: '${sa.permitted} ${totals.permitted}',
          tone: StatusTone.warning,
          dense: true,
        ),
        StatusChip(
          label: '${sa.absent} ${totals.absent}',
          tone: StatusTone.danger,
          dense: true,
        ),
      ],
    );
  }
}

class StudentAttendanceScreen extends ConsumerStatefulWidget {
  const StudentAttendanceScreen({super.key});

  @override
  ConsumerState<StudentAttendanceScreen> createState() =>
      _StudentAttendanceScreenState();
}

class _StudentAttendanceScreenState
    extends ConsumerState<StudentAttendanceScreen> {
  String? _termId;

  @override
  Widget build(BuildContext context) {
    final sa = context.sa;
    final value = ref.watch(studentAttendanceProvider(_termId));
    return Scaffold(
      appBar: AppBar(title: Text(sa.attendanceTitle)),
      body: SafeArea(
        child: ConstrainedBody(
          child: AsyncValueView<AttendanceSummary>(
            value: value,
            onRetry: () => ref.invalidate(studentAttendanceProvider(_termId)),
            data: (d) => RefreshIndicator(
              onRefresh: () async {
                ref.invalidate(studentAttendanceProvider(_termId));
                await ref.read(studentAttendanceProvider(_termId).future);
              },
              child: ListView(
                physics: const AlwaysScrollableScrollPhysics(),
                padding: const EdgeInsets.all(AppSpacing.lg),
                children: _children(context, d),
              ),
            ),
          ),
        ),
      ),
    );
  }

  List<Widget> _children(BuildContext context, AttendanceSummary d) {
    final sa = context.sa;
    final text = Theme.of(context).textTheme;
    final min = d.minimumPercent;
    final below = d.belowMinimum;
    return [
      if (d.terms.length > 1)
        Padding(
          padding: const EdgeInsets.only(bottom: AppSpacing.md),
          child: DropdownButtonFormField<String>(
            key: const Key('term_selector'),
            initialValue: d.term?.id ?? _termId,
            isExpanded: true,
            decoration: InputDecoration(labelText: sa.term),
            items: [
              for (final t in d.terms)
                DropdownMenuItem(
                  value: t.id,
                  child: Text(t.label, overflow: TextOverflow.ellipsis),
                ),
            ],
            onChanged: (v) => setState(() => _termId = v),
          ),
        )
      else if (d.term != null)
        Padding(
          padding: const EdgeInsets.only(bottom: AppSpacing.md),
          child: Text('${sa.term}: ${d.term!.label}', style: text.labelLarge),
        ),
      if (min != null && below.isNotEmpty)
        Padding(
          padding: const EdgeInsets.only(bottom: AppSpacing.md),
          child: AppBanner(
            tone: StatusTone.warning,
            message: sa.belowMinimumBanner(
              context.formatNumber(min, maxDecimals: 1),
              below.length,
            ),
          ),
        ),
      if (d.courses.isEmpty)
        EmptyViewInline(
          title: sa.attendanceEmptyTitle,
          message: sa.attendanceEmptyBody,
        )
      else ...[
        AppSurfaceCard(
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text(sa.overall, style: text.labelLarge),
              Text(
                pctLabel(context, d.overall.percentage),
                style: text.headlineSmall,
              ),
              Text(sa.meetings(d.overall.totalMeetings), style: text.bodySmall),
              const SizedBox(height: AppSpacing.sm),
              AttendanceCounts(totals: d.overall),
            ],
          ),
        ),
        const SizedBox(height: AppSpacing.md),
        FilledButton.icon(
          key: const Key('check_in_button'),
          onPressed: () async {
            await context.push(RoutePaths.studentAttendanceCheckIn);
            ref.invalidate(studentAttendanceProvider(_termId));
          },
          icon: const Icon(Icons.qr_code_scanner),
          label: Text(sa.checkInAction),
        ),
        const SizedBox(height: AppSpacing.md),
        for (final c in d.courses) _CourseCard(course: c, minimum: min),
      ],
      const SizedBox(height: AppSpacing.md),
      PendingFeatureTile(
        feature: pendingFeatureByKey('attendance_permissions')!,
      ),
    ];
  }
}

class EmptyViewInline extends StatelessWidget {
  const EmptyViewInline({super.key, required this.title, this.message});
  final String title;
  final String? message;

  @override
  Widget build(BuildContext context) => Padding(
    padding: const EdgeInsets.symmetric(vertical: AppSpacing.xl2),
    child: Column(
      children: [
        const Icon(Icons.fact_check_outlined, size: 40),
        const SizedBox(height: AppSpacing.sm),
        Text(title, style: Theme.of(context).textTheme.titleMedium),
        if (message != null) Text(message!, textAlign: TextAlign.center),
      ],
    ),
  );
}

class _CourseCard extends StatelessWidget {
  const _CourseCard({required this.course, required this.minimum});
  final AttendanceCourse course;
  final double? minimum;

  @override
  Widget build(BuildContext context) {
    final sa = context.sa;
    final text = Theme.of(context).textTheme;
    final scheme = Theme.of(context).colorScheme;
    final p = course.totals.percentage;
    return Padding(
      padding: const EdgeInsets.only(bottom: AppSpacing.sm),
      child: AppSurfaceCard(
        onTap: () =>
            context.push(RoutePaths.studentAttendanceDetail(course.krsItemId)),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              children: [
                Expanded(
                  child: Text(
                    course.courseName,
                    style: text.titleSmall,
                    maxLines: 2,
                    overflow: TextOverflow.ellipsis,
                  ),
                ),
                const SizedBox(width: AppSpacing.sm),
                Text(
                  pctLabel(context, p),
                  style: text.titleMedium?.copyWith(
                    color: course.isBelowMinimum ? scheme.error : null,
                  ),
                ),
              ],
            ),
            Text(
              [
                ?course.courseCode,
                ?course.classCode,
                ?course.lecturer,
              ].join(' · '),
              style: text.bodySmall?.copyWith(color: scheme.onSurfaceVariant),
              overflow: TextOverflow.ellipsis,
            ),
            const SizedBox(height: AppSpacing.sm),
            LinearProgressIndicator(
              value: ((p ?? 0) / 100).clamp(0.0, 1.0),
              color: course.isBelowMinimum ? scheme.error : null,
              minHeight: 6,
              borderRadius: BorderRadius.circular(3),
            ),
            const SizedBox(height: AppSpacing.sm),
            AttendanceCounts(totals: course.totals),
            if (course.isBelowMinimum)
              Padding(
                padding: const EdgeInsets.only(top: AppSpacing.xs),
                child: StatusChip(
                  label: sa.belowMinimum,
                  tone: StatusTone.danger,
                  dense: true,
                ),
              ),
          ],
        ),
      ),
    );
  }
}
