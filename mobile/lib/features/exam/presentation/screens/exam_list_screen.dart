import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import '../../../../core/l10n/core_strings.dart';
import '../../../../core/permission/permission_gate.dart';
import '../../../../core/router/route_paths.dart';
import '../../../../core/theme/app_dimensions.dart';
import '../../../../core/utils/formatters.dart';
import '../../../../core/widgets/paged_list.dart';
import '../../../../core/widgets/state_views.dart';
import '../../../../core/widgets/ui_kit.dart';
import '../../domain/exam.dart';
import '../../l10n/exam_strings.dart';
import '../providers.dart';

class ExamListScreen extends ConsumerWidget {
  const ExamListScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final s = context.examL10n;
    final core = context.l10n;
    final state = ref.watch(examListProvider);
    final filter = ref.watch(examPublishedFilterProvider);
    final notifier = ref.read(examListProvider.notifier);

    return Scaffold(
      appBar: AppBar(
        title: Text(s.title),
        actions: [
          if (PermissionGate.allows(ref, 'question_bank.read'))
            IconButton(
              tooltip: s.questionBank,
              icon: const Icon(Icons.library_books_outlined),
              onPressed: () => context.push(RoutePaths.lecturerQuestionBank),
            ),
        ],
      ),
      floatingActionButton: PermissionGate.allows(ref, 'exams.create')
          ? FloatingActionButton.extended(
              onPressed: () => context.push(RoutePaths.lecturerExamNew),
              icon: const Icon(Icons.add),
              label: Text(s.newExam),
            )
          : null,
      body: Center(
        child: ConstrainedBox(
          constraints: const BoxConstraints(maxWidth: 720),
          child: Column(
            children: [
              Padding(
                padding: const EdgeInsets.fromLTRB(
                  AppSpacing.lg,
                  AppSpacing.sm,
                  AppSpacing.lg,
                  0,
                ),
                child: Wrap(
                  spacing: AppSpacing.sm,
                  children: [
                    ChoiceChip(
                      label: Text(core.all),
                      selected: filter == null,
                      onSelected: (_) =>
                          ref.read(examPublishedFilterProvider.notifier).state =
                              null,
                    ),
                    ChoiceChip(
                      label: Text(s.draft),
                      selected: filter == false,
                      onSelected: (_) =>
                          ref.read(examPublishedFilterProvider.notifier).state =
                              false,
                    ),
                    ChoiceChip(
                      label: Text(s.published),
                      selected: filter == true,
                      onSelected: (_) =>
                          ref.read(examPublishedFilterProvider.notifier).state =
                              true,
                    ),
                  ],
                ),
              ),
              Expanded(
                child: PagedListView<Exam>(
                  state: state,
                  onRefresh: notifier.refresh,
                  onLoadMore: notifier.loadMore,
                  padding: const EdgeInsets.all(AppSpacing.lg),
                  empty: EmptyView(
                    icon: Icons.assignment_outlined,
                    title: s.emptyListTitle,
                    message: s.emptyListBody,
                  ),
                  itemBuilder: (context, exam) => _ExamCard(exam: exam),
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }
}

class _ExamCard extends StatelessWidget {
  const _ExamCard({required this.exam});
  final Exam exam;

  @override
  Widget build(BuildContext context) {
    final s = context.examL10n;
    final text = Theme.of(context).textTheme;
    final schedule = exam.startsAt == null
        ? null
        : context.formatDateTime(exam.startsAt);
    return AppSurfaceCard(
      onTap: () => context.push(RoutePaths.lecturerExam(exam.id)),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              Expanded(
                child: Text(
                  exam.title,
                  style: text.titleSmall,
                  maxLines: 2,
                  overflow: TextOverflow.ellipsis,
                ),
              ),
              const SizedBox(width: AppSpacing.sm),
              StatusChip(
                label: exam.isPublished ? s.published : s.draft,
                tone: exam.isPublished
                    ? StatusTone.success
                    : StatusTone.neutral,
                dense: true,
              ),
            ],
          ),
          const SizedBox(height: AppSpacing.xs),
          Text(
            [?exam.courseName, ?exam.classCode].join(' • '),
            style: text.bodySmall,
            maxLines: 1,
            overflow: TextOverflow.ellipsis,
          ),
          const SizedBox(height: AppSpacing.xs),
          Text(
            s.perParticipantInfo(
              exam.questionsPerParticipant,
              exam.questionPoolSize,
            ),
            style: text.bodySmall,
          ),
          Text(
            [s.durationMinutes(exam.durationMinutes), ?schedule].join(' • '),
            style: text.bodySmall,
          ),
        ],
      ),
    );
  }
}
