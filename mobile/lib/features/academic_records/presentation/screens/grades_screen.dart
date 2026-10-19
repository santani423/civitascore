import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../../core/theme/app_dimensions.dart';
import '../../../../core/widgets/state_views.dart';
import '../../../../core/widgets/ui_kit.dart';
import '../../domain/academic_models.dart';
import '../../l10n/academic_records_strings.dart';
import '../providers.dart';
import '../widgets/common.dart';

class GradesScreen extends ConsumerWidget {
  const GradesScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final a = context.acadL10n;
    final value = ref.watch(gradesProvider);
    return Scaffold(
      appBar: AppBar(title: Text(a.gradesTitle)),
      body: AsyncValueView<GradesOverview>(
        value: value,
        onRetry: () => ref.invalidate(gradesProvider),
        isEmpty: (d) => d.terms.isEmpty,
        empty: EmptyView(message: a.gradesEmpty),
        data: (d) => RefreshList(
          onRefresh: () async => ref.refresh(gradesProvider.future),
          children: [
            StatRow(
              items: [
                StatTile(
                  label: a.ipk,
                  value: fmtGpa(context, d.ipk),
                  icon: Icons.school_outlined,
                  tone: StatusTone.primary,
                ),
                StatTile(
                  label: a.totalCredits,
                  value: '${d.totalCredits}',
                  icon: Icons.layers_outlined,
                ),
                StatTile(
                  label: a.passedCredits,
                  value: '${d.passedCredits}',
                  icon: Icons.check_circle_outline,
                  tone: StatusTone.success,
                ),
              ],
            ),
            const SizedBox(height: AppSpacing.lg),
            for (final t in d.terms) _TermCard(term: t),
          ],
        ),
      ),
    );
  }
}

class _TermCard extends StatelessWidget {
  const _TermCard({required this.term});
  final GradeTerm term;

  @override
  Widget build(BuildContext context) {
    final a = context.acadL10n;
    final text = Theme.of(context).textTheme;
    final scheme = Theme.of(context).colorScheme;
    return Padding(
      padding: const EdgeInsets.only(bottom: AppSpacing.md),
      child: AppSurfaceCard(
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              children: [
                Expanded(
                  child: Text(
                    term.term.label,
                    style: text.titleMedium,
                    overflow: TextOverflow.ellipsis,
                  ),
                ),
                StatusChip(
                  label: '${a.ips} ${fmtGpa(context, term.ips)}',
                  tone: StatusTone.primary,
                ),
              ],
            ),
            Text(
              [
                if (term.semesterNumber != null)
                  a.semesterN(term.semesterNumber!),
                a.creditsN(term.totalCredits),
                '${a.gradedCredits} ${term.gradedCredits}',
              ].join(' · '),
              style: text.bodySmall?.copyWith(color: scheme.onSurfaceVariant),
            ),
            const Divider(height: AppSpacing.xl),
            for (var i = 0; i < term.rows.length; i++) ...[
              if (i > 0) const Divider(height: 1),
              CourseGradeTile(
                code: term.rows[i].courseCode,
                name: term.rows[i].courseName,
                credits: term.rows[i].credits,
                score: term.rows[i].score,
                letter: term.rows[i].letterGrade,
                qualityPoints: term.rows[i].qualityPoints,
                isPassing: term.rows[i].isPassing,
                isGraded: term.rows[i].isGraded,
              ),
            ],
          ],
        ),
      ),
    );
  }
}
