import 'package:flutter/material.dart';

import '../../../../core/theme/app_dimensions.dart';
import '../../../../core/utils/formatters.dart';
import '../../../../core/widgets/ui_kit.dart';
import '../../domain/krs_submission.dart';
import '../../l10n/advising_strings.dart';

class KrsCard extends StatelessWidget {
  const KrsCard({super.key, required this.item, required this.onTap});
  final KrsSubmission item;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    final s = context.advisingL10n;
    final text = Theme.of(context).textTheme;
    final scheme = Theme.of(context).colorScheme;
    final st = item.student;
    return Padding(
      padding: const EdgeInsets.symmetric(horizontal: AppSpacing.lg),
      child: AppSurfaceCard(
        onTap: onTap,
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              children: [
                Expanded(
                  child: Text(
                    st?.name ?? '—',
                    maxLines: 1,
                    overflow: TextOverflow.ellipsis,
                    style: text.titleSmall,
                  ),
                ),
                const SizedBox(width: AppSpacing.sm),
                StatusChip(
                  label: s.statusLabel(item.status, item.statusLabel),
                  tone: s.statusTone(item.status),
                  dense: true,
                ),
              ],
            ),
            const SizedBox(height: 2),
            Text(
              [
                if (st?.nim != null) st!.nim!,
                if (item.termLabel != null) item.termLabel!,
              ].join(' • '),
              maxLines: 1,
              overflow: TextOverflow.ellipsis,
              style: text.bodySmall?.copyWith(color: scheme.onSurfaceVariant),
            ),
            const SizedBox(height: AppSpacing.sm),
            Text(
              '${s.creditsOfLimit(item.totalCredits, item.maxCredits)}'
              ' • ${item.items.length} ${s.courses.toLowerCase()}',
              style: text.bodyMedium,
            ),
            if (item.submittedAt != null)
              Text(
                '${s.submittedAt}: ${context.formatDateTime(item.submittedAt)}',
                style: text.bodySmall?.copyWith(color: scheme.onSurfaceVariant),
              ),
          ],
        ),
      ),
    );
  }
}
