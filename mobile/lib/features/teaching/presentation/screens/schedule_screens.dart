import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import 'package:intl/intl.dart';

import '../../../../core/l10n/app_language.dart';
import '../../../../core/l10n/core_strings.dart';
import '../../../../core/router/route_paths.dart';
import '../../../../core/theme/app_dimensions.dart';
import '../../../../core/utils/formatters.dart';
import '../../../../core/widgets/notification_bell.dart';
import '../../../../core/widgets/state_views.dart';
import '../../../../core/widgets/ui_kit.dart';
import '../../domain/teaching_models.dart';
import '../../l10n/teaching_strings.dart';
import '../providers.dart';
import '../widgets/common.dart';

class LecturerScheduleScreen extends ConsumerStatefulWidget {
  const LecturerScheduleScreen({super.key});
  @override
  ConsumerState<LecturerScheduleScreen> createState() => _ScheduleState();
}

class _ScheduleState extends ConsumerState<LecturerScheduleScreen> {
  late int _day = DateTime.now().weekday;

  @override
  Widget build(BuildContext context) {
    final t = context.tr;
    final l = context.l10n;
    final today = DateTime.now().weekday;
    final classes = ref.watch(teachingClassesProvider);
    Future<void> refresh() async {
      ref.invalidate(teachingClassesProvider);
      try {
        await ref.read(teachingClassesProvider.future);
      } catch (_) {}
    }

    return Scaffold(
      appBar: AppBar(
        title: Text(t.scheduleTitle),
        actions: [
          IconButton(
            tooltip: t.academicCalendar,
            icon: const Icon(Icons.event_note_outlined),
            onPressed: () => context.push(RoutePaths.lecturerCalendar),
          ),
          const NotificationBellButton(),
        ],
      ),
      body: WideBody(
        child: Column(
          children: [
            SizedBox(
              height: 64,
              child: ListView(
                scrollDirection: Axis.horizontal,
                padding: const EdgeInsets.symmetric(
                  horizontal: AppSpacing.md,
                  vertical: AppSpacing.sm,
                ),
                children: [
                  for (var d = 1; d <= 7; d++)
                    Padding(
                      padding: const EdgeInsets.only(right: AppSpacing.sm),
                      child: ChoiceChip(
                        key: ValueKey('day-$d'),
                        selected: _day == d,
                        avatar: d == today
                            ? const Icon(Icons.circle, size: 8)
                            : null,
                        label: Text(l.dayShort(d)),
                        onSelected: (_) => setState(() => _day = d),
                      ),
                    ),
                ],
              ),
            ),
            Expanded(
              child: AsyncValueView<List<TeachingClass>>(
                value: classes,
                onRetry: refresh,
                data: (list) {
                  final slots = slotsForDay(list, _day);
                  return RefreshIndicator(
                    onRefresh: refresh,
                    child: ListView(
                      physics: const AlwaysScrollableScrollPhysics(),
                      padding: kPagePadding,
                      children: [
                        if (_day == today)
                          Padding(
                            padding: const EdgeInsets.only(
                              bottom: AppSpacing.sm,
                            ),
                            child: Align(
                              alignment: Alignment.centerLeft,
                              child: StatusChip(
                                label: t.todayBadge,
                                tone: StatusTone.primary,
                                dense: true,
                              ),
                            ),
                          ),
                        if (slots.isEmpty)
                          SizedBox(
                            height: 220,
                            child: EmptyView(
                              icon: Icons.event_busy_outlined,
                              title: t.noSlotsTitle,
                              message: t.noSlotsBody(l.dayName(_day)),
                            ),
                          )
                        else
                          for (final s in slots) _SlotCard(slot: s),
                        const SizedBox(height: AppSpacing.md),
                        AppBanner(message: t.weeklyNote),
                        const SizedBox(height: AppSpacing.md),
                        AppSurfaceCard(
                          padding: EdgeInsets.zero,
                          child: MenuTile(
                            icon: Icons.calendar_month_outlined,
                            title: t.academicCalendar,
                            subtitle: t.academicCalendarSubtitle,
                            onTap: () =>
                                context.push(RoutePaths.lecturerCalendar),
                          ),
                        ),
                      ],
                    ),
                  );
                },
              ),
            ),
          ],
        ),
      ),
    );
  }
}

class _SlotCard extends StatelessWidget {
  const _SlotCard({required this.slot});
  final WeeklySlot slot;
  @override
  Widget build(BuildContext context) {
    final t = context.tr;
    final text = Theme.of(context).textTheme;
    final s = slot.slot;
    final room = (s.room == null || s.room!.isEmpty) ? null : s.room;
    return Padding(
      padding: const EdgeInsets.only(bottom: AppSpacing.sm),
      child: AppSurfaceCard(
        onTap: () => context.push(RoutePaths.lecturerClass(slot.cls.id)),
        child: Row(
          children: [
            SizedBox(
              width: 64,
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    context.formatClock(s.startTime),
                    style: text.titleMedium,
                  ),
                  Text(context.formatClock(s.endTime), style: text.bodySmall),
                ],
              ),
            ),
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    slot.cls.courseName,
                    style: text.titleSmall,
                    maxLines: 2,
                    overflow: TextOverflow.ellipsis,
                  ),
                  Text(
                    '${slot.cls.classCode}${room == null ? '' : ' · ${t.room} $room'}',
                    style: text.bodySmall,
                    maxLines: 1,
                    overflow: TextOverflow.ellipsis,
                  ),
                ],
              ),
            ),
            const Icon(Icons.chevron_right),
          ],
        ),
      ),
    );
  }
}

class LecturerCalendarScreen extends ConsumerStatefulWidget {
  const LecturerCalendarScreen({super.key});
  @override
  ConsumerState<LecturerCalendarScreen> createState() => _CalendarState();
}

class _CalendarState extends ConsumerState<LecturerCalendarScreen> {
  late DateTime _month = DateTime(DateTime.now().year, DateTime.now().month);

  void _shift(int delta) =>
      setState(() => _month = DateTime(_month.year, _month.month + delta));

  @override
  Widget build(BuildContext context) {
    final t = context.tr;
    final events = ref.watch(calendarProvider(_month));
    Future<void> refresh() async {
      ref.invalidate(calendarProvider(_month));
      try {
        await ref.read(calendarProvider(_month).future);
      } catch (_) {}
    }

    return Scaffold(
      appBar: AppBar(title: Text(t.academicCalendar)),
      body: WideBody(
        child: Column(
          children: [
            Row(
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              children: [
                IconButton(
                  tooltip: t.previousMonth,
                  icon: const Icon(Icons.chevron_left),
                  onPressed: () => _shift(-1),
                ),
                Text(
                  DateFormat.yMMMM(
                    AppLanguage.of(context).intlTag,
                  ).format(_month),
                  style: Theme.of(context).textTheme.titleMedium,
                ),
                IconButton(
                  tooltip: t.nextMonth,
                  icon: const Icon(Icons.chevron_right),
                  onPressed: () => _shift(1),
                ),
              ],
            ),
            Expanded(
              child: AsyncValueView<List<CalendarEvent>>(
                value: events,
                onRetry: refresh,
                isEmpty: (l) => l.isEmpty,
                empty: EmptyView(
                  icon: Icons.event_outlined,
                  title: t.noEventsTitle,
                  message: t.noEventsBody,
                ),
                data: (list) => RefreshIndicator(
                  onRefresh: refresh,
                  child: ListView.separated(
                    physics: const AlwaysScrollableScrollPhysics(),
                    padding: kPagePadding,
                    itemCount: list.length,
                    separatorBuilder: (_, _) =>
                        const SizedBox(height: AppSpacing.sm),
                    itemBuilder: (context, i) => _EventCard(e: list[i]),
                  ),
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }
}

class _EventCard extends StatelessWidget {
  const _EventCard({required this.e});
  final CalendarEvent e;
  @override
  Widget build(BuildContext context) {
    final text = Theme.of(context).textTheme;
    final same =
        e.endDate == null || e.startDate == null || e.endDate == e.startDate;
    final range = same
        ? context.formatDate(e.startDate)
        : '${context.formatDate(e.startDate)} – ${context.formatDate(e.endDate)}';
    return AppSurfaceCard(
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(e.title, style: text.titleSmall),
          const SizedBox(height: 2),
          Text(range, style: text.bodySmall),
          if (e.categoryLabel != null) ...[
            const SizedBox(height: AppSpacing.sm),
            StatusChip(label: e.categoryLabel!, dense: true),
          ],
          if (e.description != null && e.description!.isNotEmpty) ...[
            const SizedBox(height: AppSpacing.sm),
            Text(e.description!, style: text.bodySmall),
          ],
        ],
      ),
    );
  }
}
