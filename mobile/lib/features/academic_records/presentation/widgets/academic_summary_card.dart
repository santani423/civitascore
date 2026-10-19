import 'package:flutter/material.dart';

import '../../../../core/theme/app_dimensions.dart';
import '../../../../core/widgets/ui_kit.dart';
import '../../domain/academic_models.dart';
import '../../l10n/academic_records_strings.dart';
import 'common.dart';

StatusTone studentStatusTone(String? v) => switch (v) {
  'active' => StatusTone.success,
  'leave' => StatusTone.warning,
  'graduated' => StatusTone.info,
  'inactive' || 'dropped_out' || 'resigned' => StatusTone.danger,
  _ => StatusTone.neutral,
};

/// IPK / IPS / SKS summary (hub header, history, profile).
class AcademicSummaryCard extends StatelessWidget {
  const AcademicSummaryCard({
    super.key,
    required this.summary,
    this.detailed = false,
  });
  final AcademicSummary summary;
  final bool detailed;

  @override
  Widget build(BuildContext context) {
    final a = context.acadL10n;
    final text = Theme.of(context).textTheme;
    final scheme = Theme.of(context).colorScheme;
    final s = summary;
    return AppSurfaceCard(
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
                      s.name,
                      style: text.titleMedium,
                      maxLines: 1,
                      overflow: TextOverflow.ellipsis,
                    ),
                    Text(
                      '${s.nim} · ${s.studyProgram}',
                      style: text.bodySmall?.copyWith(
                        color: scheme.onSurfaceVariant,
                      ),
                      maxLines: 2,
                      overflow: TextOverflow.ellipsis,
                    ),
                  ],
                ),
              ),
              if (s.status != null)
                StatusChip(
                  label: a.statusLabel(s.status!.value, s.status!.label),
                  tone: studentStatusTone(s.status!.value),
                ),
            ],
          ),
          const SizedBox(height: AppSpacing.md),
          StatRow(
            items: [
              StatTile(
                label: a.ipk,
                value: fmtGpa(context, s.ipk),
                icon: Icons.school_outlined,
                tone: StatusTone.primary,
              ),
              StatTile(
                label: a.ips,
                value: fmtGpa(context, s.lastIps?.ips),
                icon: Icons.insights_outlined,
                tone: StatusTone.accent,
              ),
              StatTile(
                label: a.sks,
                value: s.remainingCredits == null
                    ? '${s.passedCredits}'
                    : '${s.passedCredits}/${s.totalCredits}',
                icon: Icons.layers_outlined,
              ),
            ],
          ),
          if (detailed) ...[
            const SizedBox(height: AppSpacing.md),
            if (s.semester != null)
              InfoRow(label: a.semester, value: '${s.semester}'),
            if (s.faculty != null) InfoRow(label: a.faculty, value: s.faculty!),
            if (s.admissionYear != null)
              InfoRow(label: a.admissionYear, value: '${s.admissionYear}'),
            if (s.currentTerm != null)
              InfoRow(label: a.currentTerm, value: s.currentTerm!.label),
            InfoRow(
              label: a.currentTermCredits,
              value: s.maxCredits == null
                  ? '${s.currentTermCredits}'
                  : '${s.currentTermCredits}/${s.maxCredits}',
            ),
            if (s.remainingCredits != null)
              InfoRow(
                label: a.remainingCredits,
                value: '${s.remainingCredits}',
              ),
            if (s.advisorName != null)
              InfoRow(label: a.advisor, value: s.advisorName!),
            if (s.curriculumName != null)
              InfoRow(label: a.curriculum, value: s.curriculumName!),
          ],
        ],
      ),
    );
  }
}
