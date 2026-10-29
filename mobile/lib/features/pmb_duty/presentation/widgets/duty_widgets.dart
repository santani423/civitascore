import 'package:flutter/material.dart';

import '../../../../core/theme/app_dimensions.dart';
import '../../../../core/utils/formatters.dart';
import '../../../../core/widgets/ui_kit.dart';
import '../../domain/pmb_duty_models.dart';
import '../../l10n/pmb_duty_strings.dart';

StatusTone attendanceTone(PmbAttendance a) => switch (a) {
  PmbAttendance.registered => StatusTone.neutral,
  PmbAttendance.present => StatusTone.success,
  PmbAttendance.absent => StatusTone.warning,
  PmbAttendance.excused => StatusTone.info,
  PmbAttendance.disqualified => StatusTone.danger,
};

/// "17 Okt 2026 08.00–09.30".
String sessionTimeRange(BuildContext context, DateTime? from, DateTime? to) {
  if (from == null) return '—';
  final end = to == null ? '' : '–${context.formatTime(to)}';
  return '${context.formatDateTime(from)}$end';
}

/// Constrains content on wide screens.
class DutyBody extends StatelessWidget {
  const DutyBody({super.key, required this.child});
  final Widget child;

  @override
  Widget build(BuildContext context) => Center(
    child: ConstrainedBox(
      constraints: const BoxConstraints(maxWidth: 720),
      child: child,
    ),
  );
}

class DutySessionCard extends StatelessWidget {
  const DutySessionCard({
    super.key,
    required this.session,
    required this.now,
    this.onTap,
  });
  final DutySession session;
  final DateTime now;
  final VoidCallback? onTap;

  @override
  Widget build(BuildContext context) {
    final s = context.pmbDuty;
    final text = Theme.of(context).textTheme;
    final details = [
      s.modeLabel(session.mode),
      if (session.component != null) session.component!,
      session.room ?? s.online,
    ].join(' · ');
    return AppSurfaceCard(
      onTap: onTap,
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Expanded(
                child: Text(
                  session.schedule ?? s.sessionTitle(session.code),
                  style: text.titleMedium,
                  maxLines: 2,
                  overflow: TextOverflow.ellipsis,
                ),
              ),
              const SizedBox(width: AppSpacing.sm),
              // Codes are free text; cap them so the title keeps its room.
              ConstrainedBox(
                constraints: const BoxConstraints(maxWidth: 120),
                child: Text(
                  session.code,
                  style: text.labelLarge,
                  maxLines: 1,
                  overflow: TextOverflow.ellipsis,
                ),
              ),
            ],
          ),
          const SizedBox(height: AppSpacing.xs),
          Text(
            sessionTimeRange(context, session.startsAt, session.endsAt),
            style: text.bodyMedium,
          ),
          Text(details, style: text.bodySmall),
          const SizedBox(height: AppSpacing.sm),
          Wrap(
            spacing: AppSpacing.xs,
            runSpacing: AppSpacing.xs,
            children: [
              if (session.isOngoingAt(now))
                StatusChip(
                  dense: true,
                  tone: StatusTone.success,
                  icon: Icons.circle,
                  label: s.ongoing,
                ),
              StatusChip(
                dense: true,
                tone: StatusTone.neutral,
                icon: Icons.groups_outlined,
                label: s.participants(session.participantsCount),
              ),
              for (final r in PmbExaminerRole.values)
                if (session.roles.contains(r))
                  StatusChip(
                    dense: true,
                    tone: StatusTone.primary,
                    label: s.roleLabel(r),
                  ),
              if (session.scoresLocked)
                StatusChip(
                  dense: true,
                  tone: StatusTone.warning,
                  icon: Icons.lock_outline,
                  label: s.scoresLocked,
                ),
            ],
          ),
        ],
      ),
    );
  }
}
