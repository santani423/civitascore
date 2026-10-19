import 'package:flutter/material.dart';

import '../../../../core/theme/app_dimensions.dart';
import '../../../../core/utils/formatters.dart';
import '../../../../core/widgets/ui_kit.dart';
import '../../domain/models.dart';
import '../../l10n/schedule_attendance_strings.dart';

class ScheduleSlotCard extends StatelessWidget {
  const ScheduleSlotCard({super.key, required this.slot, this.status});
  final ScheduleSlot slot;

  /// upcoming | ongoing | finished (today only).
  final String? status;

  @override
  Widget build(BuildContext context) {
    final text = Theme.of(context).textTheme;
    final scheme = Theme.of(context).colorScheme;
    final sa = context.sa;
    final meta = <String>[
      ?slot.classCode,
      if (slot.credits != null) sa.classCredits(slot.credits!),
    ].join(' · ');
    return Padding(
      padding: const EdgeInsets.only(bottom: AppSpacing.sm),
      child: AppSurfaceCard(
        child: Row(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            SizedBox(
              width: 56,
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    context.formatClock(slot.startTime),
                    style: text.titleSmall,
                  ),
                  Text(
                    context.formatClock(slot.endTime),
                    style: text.bodySmall?.copyWith(
                      color: scheme.onSurfaceVariant,
                    ),
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
                    slot.courseName,
                    style: text.titleSmall,
                    maxLines: 2,
                    overflow: TextOverflow.ellipsis,
                  ),
                  if (meta.isNotEmpty)
                    Text(
                      meta,
                      style: text.bodySmall?.copyWith(
                        color: scheme.onSurfaceVariant,
                      ),
                      overflow: TextOverflow.ellipsis,
                    ),
                  const SizedBox(height: AppSpacing.xs),
                  if (slot.room != null)
                    _Line(Icons.room_outlined, '${sa.room} ${slot.room}'),
                  if (slot.lecturer != null)
                    _Line(Icons.person_outline, slot.lecturer!),
                  if (status != null || slot.isPendingApproval)
                    Padding(
                      padding: const EdgeInsets.only(top: AppSpacing.xs),
                      child: Wrap(
                        spacing: AppSpacing.xs,
                        runSpacing: AppSpacing.xs,
                        children: [
                          if (status != null)
                            StatusChip(
                              label: switch (status) {
                                'ongoing' => sa.statusOngoing,
                                'finished' => sa.statusFinished,
                                _ => sa.statusUpcoming,
                              },
                              tone: switch (status) {
                                'ongoing' => StatusTone.success,
                                'finished' => StatusTone.neutral,
                                _ => StatusTone.info,
                              },
                              dense: true,
                            ),
                          if (slot.isPendingApproval)
                            StatusChip(
                              label: sa.pendingApproval,
                              tone: StatusTone.warning,
                              dense: true,
                            ),
                        ],
                      ),
                    ),
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }
}

class _Line extends StatelessWidget {
  const _Line(this.icon, this.label);
  final IconData icon;
  final String label;

  @override
  Widget build(BuildContext context) {
    final scheme = Theme.of(context).colorScheme;
    return Padding(
      padding: const EdgeInsets.only(top: 2),
      child: Row(
        children: [
          Icon(icon, size: 14, color: scheme.onSurfaceVariant),
          const SizedBox(width: 4),
          Expanded(
            child: Text(
              label,
              style: Theme.of(context).textTheme.bodySmall,
              overflow: TextOverflow.ellipsis,
            ),
          ),
        ],
      ),
    );
  }
}
