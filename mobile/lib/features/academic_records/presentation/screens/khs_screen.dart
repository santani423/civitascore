import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../../core/theme/app_dimensions.dart';
import '../../../../core/utils/file_actions.dart';
import '../../../../core/widgets/state_views.dart';
import '../../../../core/widgets/ui_kit.dart';
import '../../../auth/presentation/controllers/auth_controller.dart';
import '../../domain/academic_models.dart';
import '../../l10n/academic_records_strings.dart';
import '../providers.dart';
import '../widgets/common.dart';

class KhsScreen extends ConsumerStatefulWidget {
  const KhsScreen({super.key});
  @override
  ConsumerState<KhsScreen> createState() => _KhsScreenState();
}

class _KhsScreenState extends ConsumerState<KhsScreen> {
  String? _termId;

  @override
  Widget build(BuildContext context) {
    final a = context.acadL10n;
    final value = ref.watch(khsProvider(_termId));
    final nim = ref.watch(currentUserProvider)?.nim ?? 'mhs';
    final data = value.valueOrNull;
    final term = data?.term;
    return Scaffold(
      appBar: AppBar(
        title: Text(a.khsTitle),
        actions: [
          if (term != null)
            IconButton(
              tooltip: a.downloadPdf,
              icon: const Icon(Icons.picture_as_pdf_outlined),
              onPressed: () => downloadAndOpen(
                context,
                ref,
                path: '/student/documents/khs/${term.id}',
                filename: 'khs-$nim-${term.label}.pdf',
              ),
            ),
        ],
      ),
      body: AsyncValueView<KhsData>(
        value: value,
        onRetry: () => ref.invalidate(khsProvider(_termId)),
        isEmpty: (d) => d.terms.isEmpty && d.term == null,
        empty: EmptyView(message: a.khsEmpty),
        data: (d) => RefreshList(
          onRefresh: () async => ref.refresh(khsProvider(_termId).future),
          children: [
            if (d.terms.isNotEmpty)
              DropdownButtonFormField<String>(
                key: ValueKey(d.term?.id),
                initialValue: d.term?.id,
                isExpanded: true,
                decoration: InputDecoration(labelText: a.chooseTerm),
                items: [
                  for (final t in d.terms)
                    DropdownMenuItem(
                      value: t.id,
                      child: Text(t.label, overflow: TextOverflow.ellipsis),
                    ),
                ],
                onChanged: (v) => setState(() => _termId = v),
              ),
            const SizedBox(height: AppSpacing.lg),
            if (d.summary != null) ...[
              StatRow(
                items: [
                  StatTile(
                    label: a.ips,
                    value: fmtGpa(context, d.summary!.ips),
                    icon: Icons.insights_outlined,
                    tone: StatusTone.primary,
                  ),
                  StatTile(
                    label: a.ipk,
                    value: fmtGpa(context, d.summary!.ipk),
                    icon: Icons.school_outlined,
                    tone: StatusTone.accent,
                  ),
                  StatTile(
                    label: a.termCredits,
                    value: '${d.summary!.termCredits}',
                    icon: Icons.layers_outlined,
                  ),
                ],
              ),
              const SizedBox(height: AppSpacing.md),
              InfoRow(
                label: a.takenCredits,
                value: '${d.summary!.takenCredits}',
              ),
              InfoRow(
                label: a.cumulativeCredits,
                value: '${d.summary!.cumulativeCredits}',
              ),
              if (!d.summary!.allGraded)
                Padding(
                  padding: const EdgeInsets.only(top: AppSpacing.sm),
                  child: AppBanner(
                    message: a.notAllGraded,
                    tone: StatusTone.warning,
                  ),
                ),
            ],
            const SizedBox(height: AppSpacing.lg),
            AppSurfaceCard(
              child: Column(
                children: [
                  for (var i = 0; i < d.rows.length; i++) ...[
                    if (i > 0) const Divider(height: 1),
                    CourseGradeTile(
                      code: d.rows[i].courseCode,
                      name: d.rows[i].courseName,
                      credits: d.rows[i].credits,
                      score: d.rows[i].score,
                      letter: d.rows[i].letterGrade,
                      qualityPoints: d.rows[i].qualityPoints,
                      isPassing: d.rows[i].isPassing,
                      isGraded: d.rows[i].isGraded,
                    ),
                  ],
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }
}
