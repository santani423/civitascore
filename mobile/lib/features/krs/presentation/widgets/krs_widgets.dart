import 'package:flutter/material.dart';

import '../../../../core/l10n/core_strings.dart';
import '../../../../core/theme/app_dimensions.dart';
import '../../../../core/utils/formatters.dart';
import '../../domain/krs_models.dart';

/// Centers and constrains content on wide screens.
class KrsConstrained extends StatelessWidget {
  const KrsConstrained({super.key, required this.child});
  final Widget child;
  @override
  Widget build(BuildContext context) => Center(
    child: ConstrainedBox(
      constraints: const BoxConstraints(maxWidth: 720),
      child: child,
    ),
  );
}

/// "Senin 08.00–09.40 · R101" lines for a class section.
List<String> scheduleLines(BuildContext context, KrsClassSection s) => [
  for (final sc in s.schedules)
    '${context.l10n.dayName(sc.dayOfWeek)} ${context.formatClock(sc.startTime)}–${context.formatClock(sc.endTime)}'
        '${sc.room == null || sc.room!.isEmpty ? '' : ' · ${sc.room}'}',
];

class MetaLine extends StatelessWidget {
  const MetaLine({
    super.key,
    required this.icon,
    required this.text,
    this.color,
  });
  final IconData icon;
  final String text;
  final Color? color;
  @override
  Widget build(BuildContext context) {
    final c = color ?? Theme.of(context).colorScheme.onSurfaceVariant;
    return Padding(
      padding: const EdgeInsets.only(top: AppSpacing.xs),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Icon(icon, size: 16, color: c),
          const SizedBox(width: AppSpacing.xs),
          Expanded(
            child: Text(
              text,
              style: Theme.of(context).textTheme.bodySmall?.copyWith(color: c),
            ),
          ),
        ],
      ),
    );
  }
}

/// Schedule + lecturer meta lines shared by KRS item and offering cards.
class ClassMeta extends StatelessWidget {
  const ClassMeta({super.key, required this.section, required this.noSchedule});
  final KrsClassSection section;
  final String noSchedule;
  @override
  Widget build(BuildContext context) {
    final lines = scheduleLines(context, section);
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        if (lines.isEmpty)
          MetaLine(icon: Icons.schedule, text: noSchedule)
        else
          for (final l in lines) MetaLine(icon: Icons.schedule, text: l),
        if (section.lecturer?.name.isNotEmpty ?? false)
          MetaLine(icon: Icons.person_outline, text: section.lecturer!.name),
      ],
    );
  }
}
