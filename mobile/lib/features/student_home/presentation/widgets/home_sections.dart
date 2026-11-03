import 'package:flutter/material.dart';

import '../../../../core/l10n/core_strings.dart';
import '../../../../core/router/route_paths.dart';
import '../../../../core/theme/app_dimensions.dart';
import '../../../../core/utils/formatters.dart';
import '../../../../core/widgets/home_hero.dart';
import '../../../../core/widgets/ui_kit.dart';
import '../../domain/finance_summary.dart';
import '../../domain/portal_links.dart';
import '../../domain/student_dashboard.dart';
import '../../l10n/student_home_strings.dart';
import 'home_navigation.dart';

const _hPad = EdgeInsets.symmetric(horizontal: AppSpacing.lg);

StatusTone toneForSeverity(String severity) => switch (severity) {
  'success' => StatusTone.success,
  'warning' => StatusTone.warning,
  'danger' => StatusTone.danger,
  _ => StatusTone.info,
};

/// Greeting + identity (name, NIM, prodi, semester, status).
class ProfileHeader extends StatelessWidget {
  const ProfileHeader({super.key, required this.profile, this.fallbackName});
  final HomeProfile? profile;
  final String? fallbackName;

  String _greeting(BuildContext context) {
    final h = DateTime.now().hour;
    final s = context.homeStr;
    if (h < 11) return s.greetingMorning;
    if (h < 15) return s.greetingNoon;
    if (h < 18) return s.greetingAfternoon;
    return s.greetingNight;
  }

  @override
  Widget build(BuildContext context) {
    final s = context.homeStr;
    final name = profile?.name.isNotEmpty == true
        ? profile!.name
        : (fallbackName ?? '');
    final details = <String>[
      if (profile?.nim.isNotEmpty == true) '${s.nim} ${profile!.nim}',
      if (profile?.studyProgram != null) profile!.studyProgram!,
      if (profile?.semester != null) '${s.semester} ${profile!.semester}',
    ];
    return Padding(
      padding: _hPad,
      child: HomeHeroCard(
        kicker: _greeting(context),
        title: name,
        initial: name,
        roles: [
          ...details,
          if (profile?.statusLabel != null) profile!.statusLabel!,
        ],
      ),
    );
  }
}

class AlertsSection extends StatelessWidget {
  const AlertsSection({super.key, required this.alerts});
  final List<HomeAlert> alerts;

  @override
  Widget build(BuildContext context) {
    if (alerts.isEmpty) return const SizedBox.shrink();
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        SectionHeader(title: context.homeStr.alerts),
        for (final a in alerts)
          Padding(
            padding: const EdgeInsets.fromLTRB(
              AppSpacing.lg,
              0,
              AppSpacing.lg,
              AppSpacing.sm,
            ),
            child: _AlertTile(alert: a),
          ),
      ],
    );
  }
}

class _AlertTile extends StatelessWidget {
  const _AlertTile({required this.alert});
  final HomeAlert alert;

  @override
  Widget build(BuildContext context) {
    final route = routeForPortalLink(alert.link);
    final banner = AppBanner(
      message: alert.message,
      tone: toneForSeverity(alert.severity),
    );
    if (route == null) return banner;
    return InkWell(
      borderRadius: BorderRadius.circular(AppRadius.lg),
      onTap: () => openAppRoute(context, route),
      child: banner,
    );
  }
}

class AcademicSection extends StatelessWidget {
  const AcademicSection({super.key, required this.academic, this.krs});
  final HomeAcademic academic;
  final HomeKrs? krs;

  @override
  Widget build(BuildContext context) {
    final s = context.homeStr;
    final text = Theme.of(context).textTheme;
    final termCredits = academic.currentTermCredits;
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        SectionHeader(title: s.academicSummary),
        Padding(
          padding: _hPad,
          child: Row(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Expanded(
                child: StatTile(
                  label: s.ipk,
                  value: academic.ipk == null
                      ? '—'
                      : context.formatNumber(academic.ipk),
                  icon: Icons.school_outlined,
                  onTap: () => openAppRoute(context, RoutePaths.studentGrades),
                ),
              ),
              const SizedBox(width: AppSpacing.sm),
              Expanded(
                child: StatTile(
                  label: s.ips,
                  value: academic.ips == null
                      ? '—'
                      : context.formatNumber(academic.ips),
                  icon: Icons.trending_up,
                  tone: StatusTone.accent,
                  onTap: () => openAppRoute(context, RoutePaths.studentKhs),
                ),
              ),
              const SizedBox(width: AppSpacing.sm),
              Expanded(
                child: StatTile(
                  label: s.creditsPassed,
                  value:
                      '${academic.passedCredits ?? academic.totalCredits ?? 0}',
                  icon: Icons.menu_book_outlined,
                  tone: StatusTone.info,
                ),
              ),
            ],
          ),
        ),
        if (krs != null || academic.advisorName != null || termCredits != null)
          Padding(
            padding: const EdgeInsets.fromLTRB(
              AppSpacing.lg,
              AppSpacing.sm,
              AppSpacing.lg,
              0,
            ),
            child: AppSurfaceCard(
              onTap: krs == null
                  ? null
                  : () => openAppRoute(context, RoutePaths.studentKrs),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  if (academic.termLabel != null)
                    Text(academic.termLabel!, style: text.titleSmall),
                  if (termCredits != null)
                    InfoRow(
                      label: s.creditsThisTerm,
                      value: s.creditsOf(termCredits, academic.maxCredits),
                    ),
                  if (krs != null)
                    InfoRow(
                      label: s.krsStatus,
                      value: krs!.statusLabel ?? krs!.status,
                    ),
                  if (academic.advisorName != null)
                    InfoRow(label: s.advisor, value: academic.advisorName!),
                ],
              ),
            ),
          ),
      ],
    );
  }
}

class ScheduleSection extends StatelessWidget {
  const ScheduleSection({
    super.key,
    required this.today,
    required this.nextClass,
  });
  final List<HomeClassSlot> today;
  final HomeClassSlot? nextClass;

  @override
  Widget build(BuildContext context) {
    final s = context.homeStr;
    final showNext =
        nextClass != null &&
        !today.any(
          (t) =>
              t.courseName == nextClass!.courseName &&
              t.startTime == nextClass!.startTime &&
              t.status == 'upcoming',
        );
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        SectionHeader(
          title: s.todayClasses,
          action: context.l10n.seeAll,
          onAction: () => openAppRoute(context, RoutePaths.studentSchedule),
        ),
        Padding(
          padding: _hPad,
          child: today.isEmpty
              ? AppSurfaceCard(
                  child: Text(
                    s.noClassToday,
                    style: Theme.of(context).textTheme.bodyMedium,
                  ),
                )
              : Column(children: [for (final t in today) _SlotCard(slot: t)]),
        ),
        if (showNext) ...[
          SectionHeader(title: s.nextClass),
          Padding(
            padding: _hPad,
            child: _SlotCard(slot: nextClass!, showDay: true),
          ),
        ],
      ],
    );
  }
}

class _SlotCard extends StatelessWidget {
  const _SlotCard({required this.slot, this.showDay = false});
  final HomeClassSlot slot;
  final bool showDay;

  @override
  Widget build(BuildContext context) {
    final s = context.homeStr;
    final text = Theme.of(context).textTheme;
    final (label, tone) = switch (slot.status) {
      'ongoing' => (s.statusOngoing, StatusTone.success),
      'finished' => (s.statusFinished, StatusTone.neutral),
      'upcoming' => (s.statusUpcoming, StatusTone.info),
      _ => (null, StatusTone.neutral),
    };
    final time =
        '${context.formatClock(slot.startTime)} – ${context.formatClock(slot.endTime)}';
    final when = showDay
        ? [
            if (slot.date != null) context.formatDate(slot.date),
            time,
          ].join(' · ')
        : time;
    final meta = [
      if (slot.room != null) slot.room!,
      if (slot.lecturer != null) slot.lecturer!,
    ].join(' · ');
    return Padding(
      padding: const EdgeInsets.only(bottom: AppSpacing.sm),
      child: AppSurfaceCard(
        onTap: () => openAppRoute(context, RoutePaths.studentSchedule),
        padding: const EdgeInsets.all(AppSpacing.md),
        child: Row(
          children: [
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    slot.courseName,
                    style: text.titleSmall,
                    maxLines: 2,
                    overflow: TextOverflow.ellipsis,
                  ),
                  Text(when, style: text.bodySmall),
                  if (meta.isNotEmpty)
                    Text(
                      meta,
                      style: text.bodySmall,
                      maxLines: 1,
                      overflow: TextOverflow.ellipsis,
                    ),
                  if (slot.isPendingApproval)
                    Padding(
                      padding: const EdgeInsets.only(top: AppSpacing.xs),
                      child: StatusChip(
                        label: s.pendingApproval,
                        tone: StatusTone.warning,
                        dense: true,
                      ),
                    ),
                ],
              ),
            ),
            if (label != null) ...[
              const SizedBox(width: AppSpacing.sm),
              StatusChip(label: label, tone: tone, dense: true),
            ],
          ],
        ),
      ),
    );
  }
}

class AttendanceSection extends StatelessWidget {
  const AttendanceSection({super.key, required this.attendance});
  final HomeAttendance attendance;

  @override
  Widget build(BuildContext context) {
    final s = context.homeStr;
    final text = Theme.of(context).textTheme;
    final below = attendance.belowMinimum;
    final min = attendance.minimumPercent;
    final overall = attendance.overallPercentage;
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        SectionHeader(
          title: s.attendance,
          action: context.l10n.seeAll,
          onAction: () => openAppRoute(context, RoutePaths.studentAttendance),
        ),
        Padding(
          padding: _hPad,
          child: AppSurfaceCard(
            onTap: () => openAppRoute(context, RoutePaths.studentAttendance),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                if (overall == null)
                  Text(s.attendanceNoData, style: text.bodyMedium)
                else
                  InfoRow(
                    label: s.attendanceOverall,
                    value: '${context.formatNumber(overall, maxDecimals: 1)}%',
                  ),
                if (min != null && overall != null)
                  Text(
                    s.minimum(context.formatNumber(min, maxDecimals: 1)),
                    style: text.bodySmall,
                  ),
                if (below.isEmpty && overall != null)
                  Text(s.attendanceAllGood, style: text.bodySmall),
                for (final c in below)
                  Padding(
                    padding: const EdgeInsets.only(top: AppSpacing.sm),
                    child: AppBanner(
                      tone: StatusTone.danger,
                      message:
                          '${c.courseName} · ${c.percentage == null ? '—' : '${context.formatNumber(c.percentage, maxDecimals: 1)}%'}',
                    ),
                  ),
              ],
            ),
          ),
        ),
      ],
    );
  }
}

class ExamsSection extends StatelessWidget {
  const ExamsSection({super.key, required this.exams});
  final List<HomeExam> exams;

  @override
  Widget build(BuildContext context) {
    final s = context.homeStr;
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        SectionHeader(
          title: s.upcomingExams,
          action: context.l10n.seeAll,
          onAction: () => openAppRoute(context, RoutePaths.studentExams),
        ),
        Padding(
          padding: _hPad,
          child: exams.isEmpty
              ? AppSurfaceCard(
                  child: Text(
                    s.noExams,
                    style: Theme.of(context).textTheme.bodyMedium,
                  ),
                )
              : Column(
                  children: [
                    for (final e in exams)
                      _ListCard(
                        title: e.title,
                        subtitle: [
                          if (e.courseName != null) e.courseName!,
                          if (e.startsAt != null)
                            context.formatDateTime(e.startsAt),
                        ].join(' · '),
                        chipLabel: switch (e.status) {
                          'available' => s.examOpen,
                          'in_progress' => s.examInProgress,
                          _ => s.examSoon,
                        },
                        chipTone: switch (e.status) {
                          'available' || 'in_progress' => StatusTone.danger,
                          _ => StatusTone.info,
                        },
                        onTap: e.id.isEmpty
                            ? null
                            : () => openAppRoute(
                                context,
                                RoutePaths.studentExam(e.id),
                              ),
                      ),
                  ],
                ),
        ),
      ],
    );
  }
}

class AssignmentsSection extends StatelessWidget {
  const AssignmentsSection({super.key, required this.assignments});
  final List<HomeAssignment> assignments;

  @override
  Widget build(BuildContext context) {
    final s = context.homeStr;
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        SectionHeader(
          title: s.assignmentsDue,
          action: context.l10n.seeAll,
          onAction: () => openAppRoute(context, RoutePaths.studentAssignments),
        ),
        Padding(
          padding: _hPad,
          child: assignments.isEmpty
              ? AppSurfaceCard(
                  child: Text(
                    s.noAssignments,
                    style: Theme.of(context).textTheme.bodyMedium,
                  ),
                )
              : Column(
                  children: [
                    for (final a in assignments)
                      _ListCard(
                        title: a.title,
                        subtitle: [
                          if (a.courseName != null) a.courseName!,
                          '${s.due} ${context.formatDateTime(a.dueAt)}',
                        ].join(' · '),
                        onTap: a.id.isEmpty
                            ? null
                            : () => openAppRoute(
                                context,
                                RoutePaths.studentAssignment(a.id),
                              ),
                      ),
                  ],
                ),
        ),
      ],
    );
  }
}

class GradesSection extends StatelessWidget {
  const GradesSection({super.key, required this.grades});
  final List<HomeGrade> grades;

  @override
  Widget build(BuildContext context) {
    if (grades.isEmpty) return const SizedBox.shrink();
    final s = context.homeStr;
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        SectionHeader(
          title: s.recentGrades,
          action: context.l10n.seeAll,
          onAction: () => openAppRoute(context, RoutePaths.studentGrades),
        ),
        Padding(
          padding: _hPad,
          child: Column(
            children: [
              for (final g in grades)
                _ListCard(
                  title: g.courseName,
                  subtitle: g.courseCode ?? '',
                  chipLabel: g.letterGrade,
                  chipTone: StatusTone.success,
                  onTap: () => openAppRoute(context, RoutePaths.studentGrades),
                ),
            ],
          ),
        ),
      ],
    );
  }
}

class AnnouncementsSection extends StatelessWidget {
  const AnnouncementsSection({
    super.key,
    required this.latest,
    required this.unread,
  });
  final List<HomeAnnouncement> latest;
  final int unread;

  @override
  Widget build(BuildContext context) {
    final s = context.homeStr;
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        SectionHeader(
          title: s.announcements,
          action: context.l10n.seeAll,
          onAction: () =>
              openAppRoute(context, RoutePaths.studentAnnouncements),
        ),
        Padding(
          padding: _hPad,
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              if (unread > 0)
                Padding(
                  padding: const EdgeInsets.only(bottom: AppSpacing.sm),
                  child: StatusChip(
                    label: s.unread(unread),
                    tone: StatusTone.info,
                    icon: Icons.mark_email_unread_outlined,
                  ),
                ),
              if (latest.isEmpty)
                AppSurfaceCard(
                  child: Text(
                    s.noAnnouncements,
                    style: Theme.of(context).textTheme.bodyMedium,
                  ),
                )
              else
                for (final a in latest)
                  _ListCard(
                    title: a.title,
                    subtitle: context.formatDate(a.publishedAt),
                    chipLabel: a.isPinned ? s.pinned : null,
                    chipTone: StatusTone.accent,
                    emphasized: !a.isRead,
                    onTap: a.id.isEmpty
                        ? null
                        : () => openAppRoute(
                            context,
                            RoutePaths.studentAnnouncement(a.id),
                          ),
                  ),
            ],
          ),
        ),
      ],
    );
  }
}

class _ListCard extends StatelessWidget {
  const _ListCard({
    required this.title,
    required this.subtitle,
    this.chipLabel,
    this.chipTone = StatusTone.neutral,
    this.emphasized = false,
    this.onTap,
  });
  final String title;
  final String subtitle;
  final String? chipLabel;
  final StatusTone chipTone;
  final bool emphasized;
  final VoidCallback? onTap;

  @override
  Widget build(BuildContext context) {
    final text = Theme.of(context).textTheme;
    return Padding(
      padding: const EdgeInsets.only(bottom: AppSpacing.sm),
      child: AppSurfaceCard(
        onTap: onTap,
        padding: const EdgeInsets.all(AppSpacing.md),
        child: Row(
          children: [
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    title,
                    style: emphasized ? text.titleSmall : text.bodyMedium,
                    maxLines: 2,
                    overflow: TextOverflow.ellipsis,
                  ),
                  if (subtitle.isNotEmpty)
                    Text(
                      subtitle,
                      style: text.bodySmall,
                      maxLines: 2,
                      overflow: TextOverflow.ellipsis,
                    ),
                ],
              ),
            ),
            if (chipLabel != null) ...[
              const SizedBox(width: AppSpacing.sm),
              StatusChip(label: chipLabel!, tone: chipTone, dense: true),
            ],
          ],
        ),
      ),
    );
  }
}

/// Finance card; has its own loading/error state so the rest of the
/// dashboard is never affected.
class FinanceCard extends StatelessWidget {
  const FinanceCard({super.key, this.summary, this.error, this.onRetry});
  final FinanceSummary? summary;
  final Object? error;
  final VoidCallback? onRetry;

  @override
  Widget build(BuildContext context) {
    final s = context.homeStr;
    final text = Theme.of(context).textTheme;
    Widget body;
    if (summary != null) {
      final f = summary!;
      body = Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          if (!f.hasOutstanding)
            Text(s.financeAllPaid, style: text.bodyMedium)
          else ...[
            Text(s.outstanding, style: text.bodySmall),
            Text(context.formatMoney(f.outstanding), style: text.headlineSmall),
            if (f.hasArrears)
              Padding(
                padding: const EdgeInsets.only(top: AppSpacing.xs),
                child: StatusChip(
                  label: '${s.arrears} ${context.formatMoney(f.arrears)}',
                  tone: StatusTone.danger,
                  dense: true,
                ),
              ),
            if (f.nextDueDate != null)
              InfoRow(
                label: s.nextDue,
                value:
                    '${context.formatDate(f.nextDueDate)}${f.nextDueAmount == null ? '' : ' · ${context.formatMoney(f.nextDueAmount)}'}',
              ),
            InfoRow(label: s.openInvoices, value: '${f.openInvoices}'),
          ],
          if (f.pendingVerification > 0)
            Padding(
              padding: const EdgeInsets.only(top: AppSpacing.xs),
              child: StatusChip(
                label: '${f.pendingVerification} ${s.pendingVerification}',
                tone: StatusTone.warning,
                dense: true,
              ),
            ),
          if (f.blockedServices.isNotEmpty)
            Padding(
              padding: const EdgeInsets.only(top: AppSpacing.sm),
              child: AppBanner(
                tone: StatusTone.danger,
                message:
                    '${s.blockedServices}: ${f.blockedServices.join(', ')}',
              ),
            ),
        ],
      );
    } else if (error != null) {
      body = Row(
        children: [
          Expanded(child: Text(s.financeLoadFailed, style: text.bodyMedium)),
          if (onRetry != null)
            TextButton(onPressed: onRetry, child: Text(context.l10n.retry)),
        ],
      );
    } else {
      body = const LinearProgressIndicator();
    }
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        SectionHeader(title: s.finance),
        Padding(
          padding: _hPad,
          child: AppSurfaceCard(
            onTap: () => openAppRoute(context, RoutePaths.studentFinance),
            child: body,
          ),
        ),
      ],
    );
  }
}

class QuickActionsGrid extends StatelessWidget {
  const QuickActionsGrid({super.key, required this.canSeeFinance});
  final bool canSeeFinance;

  @override
  Widget build(BuildContext context) {
    final s = context.homeStr;
    final items = <(IconData, String, String)>[
      (Icons.assignment_outlined, s.qaKrs, RoutePaths.studentKrs),
      (
        Icons.calendar_view_week_outlined,
        s.qaSchedule,
        RoutePaths.studentSchedule,
      ),
      (Icons.grade_outlined, s.qaGrades, RoutePaths.studentGrades),
      (Icons.task_outlined, s.qaAssignments, RoutePaths.studentAssignments),
      (Icons.quiz_outlined, s.qaExams, RoutePaths.studentExams),
      if (canSeeFinance)
        (Icons.receipt_long_outlined, s.qaInvoices, RoutePaths.studentInvoices),
      (Icons.mail_outline, s.qaRequests, RoutePaths.studentRequests),
      (Icons.event_outlined, s.qaCalendar, RoutePaths.studentCalendar),
    ];
    final scheme = Theme.of(context).colorScheme;
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        SectionHeader(title: s.quickActions),
        Padding(
          padding: _hPad,
          child: LayoutBuilder(
            builder: (context, c) {
              final cols = c.maxWidth >= 560 ? 8 : 4;
              final w = (c.maxWidth - (cols - 1) * AppSpacing.sm) / cols;
              return Wrap(
                spacing: AppSpacing.sm,
                runSpacing: AppSpacing.sm,
                children: [
                  for (final (icon, label, path) in items)
                    SizedBox(
                      width: w,
                      child: InkWell(
                        borderRadius: BorderRadius.circular(AppRadius.lg),
                        onTap: () => openAppRoute(context, path),
                        child: Padding(
                          padding: const EdgeInsets.symmetric(
                            vertical: AppSpacing.sm,
                          ),
                          child: Column(
                            mainAxisSize: MainAxisSize.min,
                            children: [
                              CircleAvatar(
                                radius: 24,
                                backgroundColor: scheme.primaryContainer,
                                child: Icon(
                                  icon,
                                  color: scheme.onPrimaryContainer,
                                ),
                              ),
                              const SizedBox(height: AppSpacing.xs),
                              Text(
                                label,
                                maxLines: 2,
                                textAlign: TextAlign.center,
                                overflow: TextOverflow.ellipsis,
                                style: Theme.of(context).textTheme.labelSmall,
                              ),
                            ],
                          ),
                        ),
                      ),
                    ),
                ],
              );
            },
          ),
        ),
      ],
    );
  }
}
