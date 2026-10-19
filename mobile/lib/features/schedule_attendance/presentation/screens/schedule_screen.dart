import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../../core/l10n/core_strings.dart';
import '../../../../core/theme/app_dimensions.dart';
import '../../../../core/utils/formatters.dart';
import '../../../../core/widgets/state_views.dart';
import '../../../../core/widgets/ui_kit.dart';
import '../../domain/models.dart';
import '../../l10n/schedule_attendance_strings.dart';
import '../providers.dart';
import '../widgets/constrained_body.dart';
import '../widgets/schedule_widgets.dart';

class StudentScheduleScreen extends ConsumerWidget {
  const StudentScheduleScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final value = ref.watch(studentScheduleProvider);
    return Scaffold(
      appBar: AppBar(title: Text(context.sa.scheduleTitle)),
      body: SafeArea(
        child: ConstrainedBody(
          child: AsyncValueView<ScheduleOverview>(
            value: value,
            onRetry: () => ref.invalidate(studentScheduleProvider),
            isEmpty: (d) => d.slots.isEmpty,
            empty: EmptyView(
              icon: Icons.event_busy_outlined,
              title: context.sa.scheduleEmptyTitle,
              message: context.sa.scheduleEmptyBody,
            ),
            data: (d) => _ScheduleBody(overview: d),
          ),
        ),
      ),
    );
  }
}

class _ScheduleBody extends ConsumerWidget {
  const _ScheduleBody({required this.overview});
  final ScheduleOverview overview;

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final todayDay = (overview.now ?? DateTime.now()).weekday;
    final sa = context.sa;
    return DefaultTabController(
      length: 7,
      initialIndex: todayDay - 1,
      child: Column(
        children: [
          Padding(
            padding: const EdgeInsets.fromLTRB(
              AppSpacing.lg,
              AppSpacing.sm,
              AppSpacing.lg,
              0,
            ),
            child: _Header(overview: overview),
          ),
          TabBar(
            isScrollable: true,
            tabAlignment: TabAlignment.start,
            tabs: [
              for (var d = 1; d <= 7; d++)
                Tab(
                  child: Row(
                    mainAxisSize: MainAxisSize.min,
                    children: [
                      Text(context.l10n.dayShort(d)),
                      if (d == todayDay) ...[
                        const SizedBox(width: 4),
                        Tooltip(
                          message: sa.todayBadge,
                          child: Icon(
                            Icons.circle,
                            size: 7,
                            color: Theme.of(context).colorScheme.primary,
                          ),
                        ),
                      ],
                    ],
                  ),
                ),
            ],
          ),
          Expanded(
            child: TabBarView(
              children: [
                for (var d = 1; d <= 7; d++)
                  _DayList(
                    day: d,
                    isToday: d == todayDay,
                    overview: overview,
                    onRefresh: () async {
                      ref.invalidate(studentScheduleProvider);
                      await ref.read(studentScheduleProvider.future);
                    },
                  ),
              ],
            ),
          ),
        ],
      ),
    );
  }
}

class _Header extends StatelessWidget {
  const _Header({required this.overview});
  final ScheduleOverview overview;

  @override
  Widget build(BuildContext context) {
    final sa = context.sa;
    final next = overview.nextClass;
    final text = Theme.of(context).textTheme;
    if (next == null) {
      return AppBanner(message: sa.noClassToday, tone: StatusTone.info);
    }
    final when = next.startsAt != null
        ? context.formatDateTime(next.startsAt)
        : '${context.l10n.dayName(next.dayOfWeek)} ${context.formatClock(next.startTime)}';
    return AppSurfaceCard(
      padding: const EdgeInsets.all(AppSpacing.md),
      child: Row(
        children: [
          Icon(Icons.schedule, color: Theme.of(context).colorScheme.primary),
          const SizedBox(width: AppSpacing.md),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(sa.nextClass, style: text.labelMedium),
                Text(
                  next.courseName,
                  style: text.titleSmall,
                  maxLines: 1,
                  overflow: TextOverflow.ellipsis,
                ),
                Text(
                  [when, ?next.room].join(' · '),
                  style: text.bodySmall,
                  maxLines: 1,
                  overflow: TextOverflow.ellipsis,
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }
}

class _DayList extends StatelessWidget {
  const _DayList({
    required this.day,
    required this.isToday,
    required this.overview,
    required this.onRefresh,
  });
  final int day;
  final bool isToday;
  final ScheduleOverview overview;
  final Future<void> Function() onRefresh;

  String? _statusFor(ScheduleSlot s) {
    if (!isToday) return null;
    for (final t in overview.today) {
      if (t.krsItemId == s.krsItemId && t.startTime == s.startTime) {
        return t.status;
      }
    }
    return null;
  }

  @override
  Widget build(BuildContext context) {
    final slots = overview.slotsForDay(day);
    final sa = context.sa;
    return RefreshIndicator(
      onRefresh: onRefresh,
      child: ListView(
        physics: const AlwaysScrollableScrollPhysics(),
        padding: const EdgeInsets.all(AppSpacing.lg),
        children: [
          if (isToday)
            Padding(
              padding: const EdgeInsets.only(bottom: AppSpacing.sm),
              child: Align(
                alignment: Alignment.centerLeft,
                child: StatusChip(
                  label: sa.todayBadge,
                  tone: StatusTone.primary,
                  icon: Icons.today,
                ),
              ),
            ),
          if (slots.isEmpty)
            Padding(
              padding: const EdgeInsets.symmetric(vertical: AppSpacing.xl2),
              child: Center(child: Text(sa.noClassesThisDay)),
            ),
          for (final s in slots)
            ScheduleSlotCard(slot: s, status: _statusFor(s)),
        ],
      ),
    );
  }
}
