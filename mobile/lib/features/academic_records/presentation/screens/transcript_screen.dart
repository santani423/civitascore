import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../../core/theme/app_dimensions.dart';
import '../../../../core/utils/file_actions.dart';
import '../../../../core/widgets/state_views.dart';
import '../../../../core/widgets/ui_kit.dart';
import '../../domain/academic_models.dart';
import '../../l10n/academic_records_strings.dart';
import '../providers.dart';
import '../widgets/common.dart';

class TranscriptScreen extends ConsumerWidget {
  const TranscriptScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final a = context.acadL10n;
    final value = ref.watch(transcriptProvider);
    final text = Theme.of(context).textTheme;
    final scheme = Theme.of(context).colorScheme;
    return Scaffold(
      appBar: AppBar(
        title: Text(a.transcriptTitle),
        actions: [
          if (value.valueOrNull?.rows.isNotEmpty ?? false)
            IconButton(
              tooltip: a.downloadPdf,
              icon: const Icon(Icons.picture_as_pdf_outlined),
              onPressed: () => downloadAndOpen(
                context,
                ref,
                path: '/student/documents/transcript',
                filename: 'transkrip-${value.value!.nim}.pdf',
              ),
            ),
        ],
      ),
      body: AsyncValueView<TranscriptData>(
        value: value,
        onRetry: () => ref.invalidate(transcriptProvider),
        isEmpty: (d) => d.rows.isEmpty,
        empty: EmptyView(message: a.transcriptEmpty),
        data: (d) => RefreshList(
          onRefresh: () async => ref.refresh(transcriptProvider.future),
          children: [
            AppSurfaceCard(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(a.studentData, style: text.titleSmall),
                  const SizedBox(height: AppSpacing.sm),
                  InfoRow(label: a.name, value: d.name),
                  InfoRow(label: a.nim, value: d.nim),
                  InfoRow(label: a.studyProgram, value: d.studyProgram),
                  if (d.faculty != null)
                    InfoRow(label: a.faculty, value: d.faculty!),
                  if (d.admissionYear != null)
                    InfoRow(
                      label: a.admissionYear,
                      value: '${d.admissionYear}',
                    ),
                ],
              ),
            ),
            const SizedBox(height: AppSpacing.md),
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
            if (d.terms.isNotEmpty) ...[
              SectionHeader(
                title: a.ipPerTerm,
                padding: const EdgeInsets.only(top: AppSpacing.lg),
              ),
              AppSurfaceCard(
                child: Column(
                  children: [
                    for (final t in d.terms)
                      InfoRow(
                        label: t.label,
                        value:
                            '${a.ips} ${fmtGpa(context, t.ip)} · ${a.creditsN(t.sks ?? 0)}',
                      ),
                  ],
                ),
              ),
            ],
            SectionHeader(
              title: '${a.courses} (${d.courseCount})',
              padding: const EdgeInsets.only(top: AppSpacing.lg),
            ),
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
                      note: d.rows[i].attempts > 1
                          ? a.attemptsN(d.rows[i].attempts)
                          : null,
                    ),
                  ],
                ],
              ),
            ),
            Padding(
              padding: const EdgeInsets.only(top: AppSpacing.md),
              child: Text(
                a.bestAttemptNote,
                style: text.bodySmall?.copyWith(color: scheme.onSurfaceVariant),
              ),
            ),
          ],
        ),
      ),
    );
  }
}
