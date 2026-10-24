import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import '../../../../core/permission/permission_gate.dart';
import '../../../../core/router/route_paths.dart';
import '../../../../core/theme/app_dimensions.dart';
import '../../../../core/utils/formatters.dart';
import '../../../../core/widgets/state_views.dart';
import '../../../../core/widgets/ui_kit.dart';
import '../../domain/exam_monitoring.dart';
import '../../l10n/exam_strings.dart';
import '../providers.dart';

StatusTone participantTone(ParticipantStatus s) => switch (s) {
  ParticipantStatus.notStarted => StatusTone.neutral,
  ParticipantStatus.inProgress => StatusTone.warning,
  ParticipantStatus.completed => StatusTone.success,
};

/// Baris peserta (NIM, nama, skor Raw/Penalti/Akhir/Grade, pelanggaran,
/// status). Skor dihitung backend; klien hanya menampilkan. Ketuk kartu untuk
/// Detail Report percobaan terakhir; ketuk jumlah pelanggaran untuk linimasa
/// pelanggaran saja.
class ParticipantTile extends ConsumerWidget {
  const ParticipantTile({
    super.key,
    required this.examId,
    required this.participant,
  });

  final String examId;
  final ExamParticipant participant;

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final s = context.examL10n;
    final text = Theme.of(context).textTheme;
    final p = participant;
    final hasScore = p.score != null;
    final attemptId = p.latestAttemptId;
    final canOpen =
        attemptId != null && PermissionGate.allows(ref, 'exam_attempts.read');
    return AppSurfaceCard(
      onTap: canOpen
          ? () => context.push(
              RoutePaths.lecturerExamAttemptReport(examId, attemptId),
            )
          : null,
      padding: const EdgeInsets.all(AppSpacing.md),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      p.studentName,
                      style: text.titleSmall,
                      maxLines: 1,
                      overflow: TextOverflow.ellipsis,
                    ),
                    Text(p.studentNim, style: text.bodySmall),
                  ],
                ),
              ),
              const SizedBox(width: AppSpacing.sm),
              StatusChip(
                label: s.participantStatus(p.status),
                tone: participantTone(p.status),
                dense: true,
              ),
            ],
          ),
          if (hasScore || p.violationCount > 0) ...[
            const SizedBox(height: AppSpacing.sm),
            Wrap(
              spacing: AppSpacing.md,
              runSpacing: AppSpacing.xs,
              children: [
                if (hasScore) ...[
                  _kv(context, s.rawScore, context.formatNumber(p.rawScore)),
                  _kv(context, s.penalty, context.formatNumber(p.penaltyScore)),
                  _kv(context, s.finalScore, context.formatNumber(p.score)),
                  if (p.grade != null) _kv(context, s.gradeLabel, p.grade!),
                  if (p.weightedScore != null)
                    _kv(
                      context,
                      s.weightedScore,
                      context.formatNumber(p.weightedScore),
                    ),
                ],
                if (p.violationCount > 0)
                  InkWell(
                    onTap: canOpen
                        ? () => showAttemptViolations(context, attemptId)
                        : null,
                    child: Text(
                      s.violationsCount(p.violationCount),
                      style: text.labelMedium?.copyWith(
                        color: context.palette.danger,
                        decoration: canOpen ? TextDecoration.underline : null,
                        decorationColor: context.palette.danger,
                      ),
                    ),
                  ),
              ],
            ),
          ],
          if (canOpen) ...[
            const SizedBox(height: AppSpacing.xs),
            Row(
              mainAxisAlignment: MainAxisAlignment.end,
              children: [
                Text(
                  s.openReport,
                  style: text.labelMedium?.copyWith(
                    color: Theme.of(context).colorScheme.primary,
                  ),
                ),
                Icon(
                  Icons.chevron_right,
                  size: 18,
                  color: Theme.of(context).colorScheme.primary,
                ),
              ],
            ),
          ],
        ],
      ),
    );
  }

  Widget _kv(BuildContext context, String k, String v) {
    final text = Theme.of(context).textTheme;
    return Text.rich(
      TextSpan(
        children: [
          TextSpan(
            text: '$k ',
            style: text.bodySmall?.copyWith(color: context.palette.mutedText),
          ),
          TextSpan(
            text: v,
            style: text.bodyMedium?.copyWith(fontWeight: FontWeight.w600),
          ),
        ],
      ),
    );
  }
}

Future<void> showAttemptViolations(BuildContext context, String attemptId) =>
    showModalBottomSheet<void>(
      context: context,
      isScrollControlled: true,
      showDragHandle: true,
      builder: (_) => DraggableScrollableSheet(
        expand: false,
        initialChildSize: 0.7,
        maxChildSize: 0.95,
        builder: (context, controller) =>
            _ViolationsBody(attemptId: attemptId, scrollController: controller),
      ),
    );

class _ViolationsBody extends ConsumerWidget {
  const _ViolationsBody({
    required this.attemptId,
    required this.scrollController,
  });

  final String attemptId;
  final ScrollController scrollController;

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final s = context.examL10n;
    final text = Theme.of(context).textTheme;
    final async = ref.watch(attemptViolationsProvider(attemptId));
    return AsyncValueView<AttemptViolations>(
      value: async,
      onRetry: () => ref.invalidate(attemptViolationsProvider(attemptId)),
      data: (d) => ListView(
        controller: scrollController,
        padding: const EdgeInsets.all(AppSpacing.lg),
        children: [
          Text(d.studentName, style: text.titleMedium),
          Text(d.studentNim, style: text.bodySmall),
          const SizedBox(height: AppSpacing.md),
          Wrap(
            spacing: AppSpacing.md,
            runSpacing: AppSpacing.xs,
            children: [
              InfoChip(
                label: s.rawScore,
                value: context.formatNumber(d.rawScore),
              ),
              InfoChip(
                label: s.penalty,
                value: context.formatNumber(d.penaltyScore),
              ),
              InfoChip(
                label: s.finalScore,
                value: context.formatNumber(d.score),
              ),
              if (d.grade != null)
                InfoChip(label: s.gradeLabel, value: d.grade!),
              if (d.weightedScore != null)
                InfoChip(
                  label: s.weightedScore,
                  value: context.formatNumber(d.weightedScore),
                ),
            ],
          ),
          SectionHeader(
            title: s.violationTimeline,
            padding: const EdgeInsets.only(
              top: AppSpacing.lg,
              bottom: AppSpacing.sm,
            ),
          ),
          if (d.violations.isEmpty)
            Text(s.noAlerts, style: text.bodyMedium)
          else
            for (final v in d.violations)
              ListTile(
                contentPadding: EdgeInsets.zero,
                leading: CircleAvatar(child: Text('${v.sequenceNumber}')),
                title: Text(v.label ?? v.type),
                subtitle: Text(
                  '${context.formatDateTime(v.occurredAt)} • ${s.penaltyPoints}: ${context.formatNumber(v.penaltyPoints)}',
                ),
              ),
        ],
      ),
    );
  }
}

class InfoChip extends StatelessWidget {
  const InfoChip({super.key, required this.label, required this.value});
  final String label;
  final String value;

  @override
  Widget build(BuildContext context) {
    final text = Theme.of(context).textTheme;
    return Text.rich(
      TextSpan(
        children: [
          TextSpan(
            text: '$label ',
            style: text.bodySmall?.copyWith(color: context.palette.mutedText),
          ),
          TextSpan(
            text: value,
            style: text.bodyMedium?.copyWith(fontWeight: FontWeight.w600),
          ),
        ],
      ),
    );
  }
}
