import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:intl/intl.dart';

import '../../../../core/l10n/app_language.dart';
import '../../../../core/l10n/core_strings.dart';
import '../../../../core/theme/app_dimensions.dart';
import '../../../../core/utils/formatters.dart';
import '../../../../core/widgets/state_views.dart';
import '../../../../core/widgets/ui_kit.dart';
import '../../domain/models.dart';
import '../../l10n/schedule_attendance_strings.dart';
import '../providers.dart';
import '../widgets/constrained_body.dart';

StatusTone calendarTone(String category) => switch (category) {
  'holiday' => StatusTone.danger,
  'midterm_exam' || 'final_exam' || 'exam' => StatusTone.warning,
  'assignment_deadline' => StatusTone.accent,
  'registration' || 'krs' => StatusTone.info,
  'lecture' => StatusTone.primary,
  'semester_start' || 'semester_end' || 'graduation' => StatusTone.success,
  _ => StatusTone.neutral,
};

class StudentCalendarScreen extends ConsumerStatefulWidget {
  const StudentCalendarScreen({super.key});

  @override
  ConsumerState<StudentCalendarScreen> createState() =>
      _StudentCalendarScreenState();
}

class _StudentCalendarScreenState extends ConsumerState<StudentCalendarScreen> {
  late DateTime _month = DateTime(DateTime.now().year, DateTime.now().month);

  void _shift(int delta) =>
      setState(() => _month = DateTime(_month.year, _month.month + delta));

  @override
  Widget build(BuildContext context) {
    final sa = context.sa;
    final value = ref.watch(studentCalendarProvider(_month));
    final title = DateFormat.yMMMM(
      AppLanguage.of(context).intlTag,
    ).format(_month);
    return Scaffold(
      appBar: AppBar(title: Text(sa.calendarTitle)),
      body: SafeArea(
        child: ConstrainedBody(
          child: Column(
            children: [
              Row(
                children: [
                  IconButton(
                    tooltip: sa.previousMonth,
                    onPressed: () => _shift(-1),
                    icon: const Icon(Icons.chevron_left),
                  ),
                  Expanded(
                    child: Text(
                      title,
                      textAlign: TextAlign.center,
                      style: Theme.of(context).textTheme.titleMedium,
                    ),
                  ),
                  IconButton(
                    tooltip: sa.nextMonth,
                    onPressed: () => _shift(1),
                    icon: const Icon(Icons.chevron_right),
                  ),
                ],
              ),
              Expanded(
                child: AsyncValueView<List<CalendarEvent>>(
                  value: value,
                  onRetry: () =>
                      ref.invalidate(studentCalendarProvider(_month)),
                  isEmpty: (d) => d.isEmpty,
                  empty: EmptyView(
                    icon: Icons.event_outlined,
                    title: sa.calendarEmpty,
                  ),
                  data: (events) => _Agenda(
                    events: events,
                    onRefresh: () async {
                      ref.invalidate(studentCalendarProvider(_month));
                      await ref.read(studentCalendarProvider(_month).future);
                    },
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

class _Agenda extends StatelessWidget {
  const _Agenda({required this.events, required this.onRefresh});
  final List<CalendarEvent> events;
  final Future<void> Function() onRefresh;

  @override
  Widget build(BuildContext context) {
    final sa = context.sa;
    final now = DateTime.now();
    final today = DateTime(now.year, now.month, now.day);
    bool isPast(CalendarEvent e) {
      final end = e.end ?? e.start;
      if (end == null) return false;
      if (e.allDay) {
        return DateTime(end.year, end.month, end.day).isBefore(today);
      }
      return end.isBefore(now);
    }

    final upcoming = events.where((e) => !isPast(e)).toList();
    final past = events.where(isPast).toList().reversed.toList();
    return RefreshIndicator(
      onRefresh: onRefresh,
      child: ListView(
        physics: const AlwaysScrollableScrollPhysics(),
        padding: const EdgeInsets.all(AppSpacing.lg),
        children: [
          if (upcoming.isNotEmpty) SectionHeader(title: sa.upcoming),
          for (final e in upcoming) _EventTile(event: e),
          if (past.isNotEmpty) SectionHeader(title: sa.past),
          for (final e in past) _EventTile(event: e, muted: true),
        ],
      ),
    );
  }
}

class _EventTile extends StatelessWidget {
  const _EventTile({required this.event, this.muted = false});
  final CalendarEvent event;
  final bool muted;

  @override
  Widget build(BuildContext context) {
    final sa = context.sa;
    final text = Theme.of(context).textTheme;
    final scheme = Theme.of(context).colorScheme;
    final (fg, bg) = context.toneColors(calendarTone(event.category));
    final start = event.start;
    final timeLabel = event.allDay
        ? sa.allDay
        : [
            context.formatTime(event.start),
            if (event.end != null) context.formatTime(event.end),
          ].join(' – ');
    return Padding(
      padding: const EdgeInsets.only(bottom: AppSpacing.sm),
      child: Opacity(
        opacity: muted ? 0.65 : 1,
        child: AppSurfaceCard(
          padding: const EdgeInsets.all(AppSpacing.md),
          child: Row(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Container(
                width: 52,
                padding: const EdgeInsets.symmetric(vertical: 6),
                decoration: BoxDecoration(
                  color: bg,
                  borderRadius: BorderRadius.circular(AppRadius.sm),
                ),
                child: Column(
                  children: [
                    Text(
                      start == null ? '—' : '${start.day}',
                      style: text.titleMedium?.copyWith(color: fg),
                    ),
                    Text(
                      start == null ? '' : context.l10n.dayShort(start.weekday),
                      style: text.labelSmall?.copyWith(color: fg),
                    ),
                  ],
                ),
              ),
              const SizedBox(width: AppSpacing.md),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      event.title,
                      style: text.titleSmall,
                      maxLines: 2,
                      overflow: TextOverflow.ellipsis,
                    ),
                    Text(
                      [timeLabel, ?event.location].join(' · '),
                      style: text.bodySmall?.copyWith(
                        color: scheme.onSurfaceVariant,
                      ),
                      overflow: TextOverflow.ellipsis,
                    ),
                    if (event.description != null)
                      Text(
                        event.description!,
                        style: text.bodySmall,
                        maxLines: 2,
                        overflow: TextOverflow.ellipsis,
                      ),
                    const SizedBox(height: AppSpacing.xs),
                    StatusChip(
                      label: sa.categoryLabel(
                        event.category,
                        event.categoryLabel,
                      ),
                      tone: calendarTone(event.category),
                      dense: true,
                    ),
                  ],
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }
}
