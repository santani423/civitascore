import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../../core/l10n/core_strings.dart';
import '../../../../core/theme/app_dimensions.dart';
import '../../../../core/utils/formatters.dart';
import '../../../../core/widgets/state_views.dart';
import '../../../../core/widgets/ui_kit.dart';
import '../../domain/teaching_models.dart';
import '../../l10n/teaching_strings.dart';
import '../providers.dart';
import '../widgets/common.dart';

class StudentRecordScreen extends ConsumerWidget {
  const StudentRecordScreen({super.key, required this.studentId});
  final String studentId;

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final t = context.tr;
    final student = ref.watch(studentRecordProvider(studentId));
    final transcript = ref.watch(transcriptProvider(studentId));
    Future<void> refresh() async {
      ref.invalidate(studentRecordProvider(studentId));
      ref.invalidate(transcriptProvider(studentId));
      try {
        await ref.read(studentRecordProvider(studentId).future);
      } catch (_) {}
    }

    return Scaffold(
      appBar: AppBar(title: Text(t.studentTitle)),
      body: WideBody(
        child: AsyncValueView<StudentRecord>(
          value: student,
          onRetry: refresh,
          data: (s) => RefreshIndicator(
            onRefresh: refresh,
            child: ListView(
              physics: const AlwaysScrollableScrollPhysics(),
              padding: kPagePadding,
              children: [
                AppSurfaceCard(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        s.name,
                        style: Theme.of(context).textTheme.titleLarge,
                      ),
                      const SizedBox(height: AppSpacing.sm),
                      InfoRow(label: t.nim, value: s.nim),
                      if (s.studyProgram != null)
                        InfoRow(label: t.studyProgram, value: s.studyProgram!),
                      if (s.admissionYear != null)
                        InfoRow(
                          label: t.admissionYear,
                          value: '${s.admissionYear}',
                        ),
                      if (s.email != null)
                        InfoRow(label: t.email, value: s.email!),
                      InfoRow(
                        label: t.status,
                        value: t.studentStatus(s.status),
                      ),
                    ],
                  ),
                ),
                SectionHeader(title: t.transcript),
                transcript.when(
                  skipLoadingOnRefresh: true,
                  loading: () => const SizedBox(
                    height: 160,
                    child: SkeletonList(itemCount: 2, itemHeight: 60),
                  ),
                  error: (e, _) => SizedBox(
                    height: 260,
                    child: ErrorView(
                      error: e,
                      onRetry: () =>
                          ref.invalidate(transcriptProvider(studentId)),
                    ),
                  ),
                  data: (tr) => _TranscriptView(tr: tr),
                ),
              ],
            ),
          ),
        ),
      ),
    );
  }
}

class _TranscriptView extends StatelessWidget {
  const _TranscriptView({required this.tr});
  final Transcript tr;
  @override
  Widget build(BuildContext context) {
    final t = context.tr;
    if (tr.terms.isEmpty) {
      return AppBanner(message: t.noTranscript);
    }
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Row(
          children: [
            Expanded(
              child: StatTile(
                label: t.gpa,
                value: context.formatNumber(tr.ipk),
                icon: Icons.school_outlined,
              ),
            ),
            const SizedBox(width: AppSpacing.sm),
            Expanded(
              child: StatTile(
                label: t.totalSks,
                value: '${tr.totalSks}',
                icon: Icons.menu_book_outlined,
                tone: StatusTone.info,
              ),
            ),
            const SizedBox(width: AppSpacing.sm),
            Expanded(
              child: StatTile(
                label: t.passedSks,
                value: '${tr.passedSks}',
                icon: Icons.check_circle_outline,
                tone: StatusTone.success,
              ),
            ),
          ],
        ),
        const SizedBox(height: AppSpacing.md),
        Text(t.termsTitle, style: Theme.of(context).textTheme.titleSmall),
        for (final term in tr.terms)
          ListTile(
            contentPadding: EdgeInsets.zero,
            title: Text(term.label),
            subtitle: Text(context.l10n.sks(term.sks)),
            trailing: Text(
              '${t.ip} ${term.ip == null ? '—' : context.formatNumber(term.ip)}',
              style: Theme.of(context).textTheme.titleSmall,
            ),
          ),
      ],
    );
  }
}
