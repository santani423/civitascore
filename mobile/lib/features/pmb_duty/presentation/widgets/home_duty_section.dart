import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import '../../../../core/l10n/core_strings.dart';
import '../../../../core/router/route_paths.dart';
import '../../../../core/theme/app_dimensions.dart';
import '../../../../core/widgets/ui_kit.dart';
import '../../../auth/presentation/controllers/auth_controller.dart';
import '../../domain/pmb_duty_models.dart';
import '../../l10n/pmb_duty_strings.dart';
import '../providers.dart';
import 'duty_widgets.dart';

/// Lecturer home entry of the conditional "Tugas ujian PMB" module: renders
/// nothing unless sessions are assigned to the user.
class PmbDutyHomeSection extends ConsumerWidget {
  const PmbDutyHomeSection({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final user = ref.watch(currentUserProvider);
    if (user == null || !user.hasAnyPermission(kPmbDutyPermissions)) {
      return const SizedBox.shrink();
    }
    final sessions =
        ref.watch(homeDutySessionsProvider).valueOrNull ?? const [];
    if (sessions.isEmpty) return const SizedBox.shrink();
    final s = context.pmbDuty;
    final now = DateTime.now();
    final split = splitSessions(sessions, now);
    final next = split.active.isEmpty ? null : split.active.first;
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        SectionHeader(
          title: s.moduleTitle,
          padding: const EdgeInsets.only(
            top: AppSpacing.xl,
            bottom: AppSpacing.sm,
          ),
          action: context.l10n.seeAll,
          onAction: () => context.push(RoutePaths.lecturerPmbSessions),
        ),
        MenuTile(
          icon: Icons.assignment_ind_outlined,
          title: s.homeSummary(split.active.length, sessions.length),
          onTap: () => context.push(RoutePaths.lecturerPmbSessions),
        ),
        if (next != null)
          DutySessionCard(
            session: next,
            now: now,
            onTap: () => context.push(RoutePaths.lecturerPmbSession(next.id)),
          ),
      ],
    );
  }
}
