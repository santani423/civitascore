import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import '../../../../core/network/api_failure.dart';
import '../../../../core/pending/pending_feature_screen.dart';
import '../../../../core/pending/pending_features.dart';
import '../../../../core/permission/permission_gate.dart';
import '../../../../core/router/route_paths.dart';
import '../../../../core/theme/app_dimensions.dart';
import '../../../../core/utils/formatters.dart';
import '../../../../core/widgets/state_views.dart';
import '../../../../core/widgets/ui_kit.dart';
import '../../domain/hr_models.dart';
import '../../l10n/hr_strings.dart';
import '../providers.dart';
import '../widgets/hr_common.dart';

/// GET /me/hr/profile + menu to leave & requests.
class HrProfileScreen extends ConsumerWidget {
  const HrProfileScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final h = context.hrL10n;
    final value = ref.watch(hrProfileProvider);
    return PermissionGate(
      permission: 'hr_self_service.read',
      child: Scaffold(
        appBar: AppBar(title: Text(h.hrTitle)),
        body: value.when(
          skipLoadingOnRefresh: true,
          loading: () => const SkeletonList(),
          error: (e, _) => isNotLinked(e)
              ? NotLinkedView(
                  title: h.hrNotLinkedTitle,
                  body: h.hrNotLinkedBody,
                  serverMessage: (e as ApiFailure).message,
                  onRetry: () => ref.invalidate(hrProfileProvider),
                )
              : ErrorView(
                  error: e,
                  onRetry: () => ref.invalidate(hrProfileProvider),
                ),
          data: (p) => RefreshIndicator(
            onRefresh: () => ref.refresh(hrProfileProvider.future),
            child: HrBody(child: _Content(profile: p)),
          ),
        ),
      ),
    );
  }
}

class _Content extends StatelessWidget {
  const _Content({required this.profile});
  final HrProfile profile;

  @override
  Widget build(BuildContext context) {
    final h = context.hrL10n;
    final p = profile;
    final q = p.quota;
    final unit = [
      p.unit,
      p.studyProgramName,
      p.facultyName,
    ].whereType<String>().where((e) => e.isNotEmpty).toList();
    return ListView(
      physics: const AlwaysScrollableScrollPhysics(),
      padding: const EdgeInsets.all(AppSpacing.lg),
      children: [
        if (q != null) ...[
          StatTile(
            label: h.leaveQuota,
            value: h.quotaLine(q.year, q.remaining, q.quota),
            icon: Icons.beach_access_outlined,
          ),
          const SizedBox(height: AppSpacing.md),
        ],
        AppSurfaceCard(
          child: Column(
            children: [
              InfoRow(label: h.name, value: p.name),
              InfoRow(label: h.employeeType, value: p.employeeType ?? '—'),
              InfoRow(label: h.nip, value: p.nip ?? '—'),
              InfoRow(label: h.nidn, value: p.nidn ?? '—'),
              InfoRow(label: h.position, value: p.position ?? '—'),
              InfoRow(label: h.unit, value: unit.isEmpty ? '—' : unit.first),
              InfoRow(label: h.academicRank, value: p.academicRank ?? '—'),
              InfoRow(
                label: h.rank,
                value: [
                  p.rankName,
                  p.rankGrade,
                ].whereType<String>().join(' / ').ifEmpty('—'),
              ),
              InfoRow(label: h.serdos, value: p.serdosNumber ?? '—'),
              InfoRow(label: h.expertise, value: p.expertise ?? '—'),
              InfoRow(label: h.lecturerStatus, value: p.lecturerStatus ?? '—'),
              InfoRow(
                label: h.highestEducation,
                value: p.highestEducation ?? '—',
              ),
              InfoRow(label: h.supervisor, value: p.supervisorName ?? '—'),
              InfoRow(label: h.joinedAt, value: context.formatDate(p.joinedAt)),
            ],
          ),
        ),
        const SizedBox(height: AppSpacing.md),
        MenuTile(
          icon: Icons.event_busy_outlined,
          title: h.menuLeave,
          subtitle: h.menuLeaveSub,
          onTap: () => context.push(RoutePaths.hrLeaveRequests),
        ),
        MenuTile(
          icon: Icons.assignment_outlined,
          title: h.menuRequests,
          subtitle: h.menuRequestsSub,
          onTap: () => context.push(RoutePaths.hrRequests),
        ),
        PendingFeatureTile(
          feature: pendingFeatureByKey('employee_attendance')!,
        ),
      ],
    );
  }
}

extension on String {
  String ifEmpty(String fallback) => isEmpty ? fallback : this;
}
