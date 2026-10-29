import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import '../../../../core/router/route_paths.dart';
import '../../../../core/theme/app_dimensions.dart';
import '../../../../core/widgets/state_views.dart';
import '../../../../core/widgets/ui_kit.dart';
import '../../domain/pmb_duty_models.dart';
import '../../l10n/pmb_duty_strings.dart';
import '../providers.dart';
import '../widgets/duty_widgets.dart';

/// MOB-OTH-01 — PMB exam sessions assigned to me.
class DutySessionsScreen extends ConsumerWidget {
  const DutySessionsScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final s = context.pmbDuty;
    final value = ref.watch(dutySessionsProvider);
    Future<void> refresh() async {
      ref.invalidate(dutySessionsProvider);
      await ref.read(dutySessionsProvider.future);
    }

    return Scaffold(
      appBar: AppBar(title: Text(s.moduleTitle)),
      body: AsyncValueView<List<DutySession>>(
        value: value,
        onRetry: () => ref.invalidate(dutySessionsProvider),
        isEmpty: (list) => list.isEmpty,
        empty: EmptyView(
          title: s.noSessionsTitle,
          message: s.noSessionsBody,
          icon: Icons.assignment_ind_outlined,
        ),
        data: (sessions) {
          final now = DateTime.now();
          final split = splitSessions(sessions, now);
          Widget card(DutySession d) => Padding(
            padding: const EdgeInsets.only(bottom: AppSpacing.sm),
            child: DutySessionCard(
              session: d,
              now: now,
              onTap: () => context.push(RoutePaths.lecturerPmbSession(d.id)),
            ),
          );
          return RefreshIndicator(
            onRefresh: refresh,
            child: DutyBody(
              child: ListView(
                physics: const AlwaysScrollableScrollPhysics(),
                padding: const EdgeInsets.all(AppSpacing.lg),
                children: [
                  if (split.active.isNotEmpty) ...[
                    SectionHeader(
                      title: s.activeSessions,
                      padding: const EdgeInsets.only(bottom: AppSpacing.sm),
                    ),
                    ...split.active.map(card),
                  ],
                  if (split.finished.isNotEmpty) ...[
                    SectionHeader(
                      title: s.finishedSessions,
                      padding: EdgeInsets.only(
                        top: split.active.isEmpty ? 0 : AppSpacing.lg,
                        bottom: AppSpacing.sm,
                      ),
                    ),
                    ...split.finished.map(card),
                  ],
                ],
              ),
            ),
          );
        },
      ),
    );
  }
}
