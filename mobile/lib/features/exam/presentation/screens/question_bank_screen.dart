import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../../core/l10n/core_strings.dart';
import '../../../../core/permission/permission_gate.dart';
import '../../../../core/theme/app_dimensions.dart';
import '../../../../core/widgets/app_feedback.dart';
import '../../../../core/widgets/state_views.dart';
import '../../../../core/widgets/ui_kit.dart';
import '../../data/exam_api.dart';
import '../../domain/exam_question.dart';
import '../../domain/question_selection_mode.dart';
import '../../l10n/exam_strings.dart';
import '../providers.dart';
import '../widgets/question_editor_sheet.dart';
import 'question_bank_list_screen.dart';

/// Penyunting soal satu ujian (tambah/ubah/hapus soal & opsi, tandai soal
/// untuk mode manual, terapkan dari bank soal). Terkunci setelah publish.
class QuestionBankScreen extends ConsumerWidget {
  const QuestionBankScreen({super.key, required this.examId});

  final String examId;

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final s = context.examL10n;
    final questions = ref.watch(examQuestionsProvider(examId));
    final exam = ref.watch(examProvider(examId)).valueOrNull;
    final locked = exam?.isPublished ?? false;
    final canEdit =
        !locked && PermissionGate.allows(ref, 'exams.create,exams.update');
    final manual = exam?.questionSelectionMode == QuestionSelectionMode.manual;

    return Scaffold(
      appBar: AppBar(
        title: Text(s.questionsTitle(questions.valueOrNull?.length ?? 0)),
        actions: [
          if (canEdit)
            IconButton(
              tooltip: s.applyFromBank,
              icon: const Icon(Icons.library_add_outlined),
              onPressed: () async {
                await Navigator.of(context).push<void>(
                  MaterialPageRoute(
                    builder: (_) =>
                        QuestionBankListScreen(pickForExamId: examId),
                  ),
                );
                ref.invalidate(examQuestionsProvider(examId));
                ref.invalidate(examProvider(examId));
              },
            ),
        ],
      ),
      floatingActionButton: canEdit
          ? FloatingActionButton.extended(
              onPressed: () => _openEditor(context, ref),
              icon: const Icon(Icons.add),
              label: Text(s.addQuestion),
            )
          : null,
      body: Center(
        child: ConstrainedBox(
          constraints: const BoxConstraints(maxWidth: 720),
          child: AsyncValueView<List<ExamQuestion>>(
            value: questions,
            onRetry: () => ref.invalidate(examQuestionsProvider(examId)),
            isEmpty: (l) => l.isEmpty,
            empty: EmptyView(
              icon: Icons.quiz_outlined,
              title: s.emptyQuestionsTitle,
              message: s.emptyQuestionsBody,
            ),
            data: (list) => RefreshIndicator(
              onRefresh: () async {
                ref.invalidate(examQuestionsProvider(examId));
                await ref.read(examQuestionsProvider(examId).future);
              },
              child: ListView(
                physics: const AlwaysScrollableScrollPhysics(),
                padding: const EdgeInsets.all(AppSpacing.lg),
                children: [
                  if (locked) ...[
                    AppBanner(message: s.lockedBanner, tone: StatusTone.info),
                    const SizedBox(height: AppSpacing.md),
                  ],
                  for (final q in list)
                    Padding(
                      padding: const EdgeInsets.only(bottom: AppSpacing.sm),
                      child: _QuestionTile(
                        question: q,
                        index: list.indexOf(q) + 1,
                        canEdit: canEdit,
                        showSelectToggle: manual,
                        onEdit: () => _openEditor(context, ref, question: q),
                        onDelete: () => _delete(context, ref, q),
                        onToggle: (v) => _toggle(context, ref, q, v),
                      ),
                    ),
                  const SizedBox(height: 80),
                ],
              ),
            ),
          ),
        ),
      ),
    );
  }

  Future<void> _openEditor(
    BuildContext context,
    WidgetRef ref, {
    ExamQuestion? question,
  }) async {
    final s = context.examL10n;
    final api = ref.read(examApiProvider);
    final saved = await showQuestionEditor(
      context,
      QuestionEditorSheet(
        title: question == null ? s.addQuestion : s.editQuestion,
        initialText: question?.questionText ?? '',
        initialPoints: question?.points ?? 1,
        initialOptions: question?.options,
        onSave:
            ({
              required text,
              required points,
              required options,
              courseId,
            }) async {
              if (question == null) {
                await api.addQuestion(
                  examId,
                  questionText: text,
                  points: points,
                  options: options,
                );
              } else {
                await api.updateQuestion(
                  question.id,
                  questionText: text,
                  points: points,
                  options: options,
                );
              }
            },
      ),
    );
    if (saved == true) {
      ref.invalidate(examQuestionsProvider(examId));
      ref.invalidate(examProvider(examId));
    }
  }

  Future<void> _delete(
    BuildContext context,
    WidgetRef ref,
    ExamQuestion q,
  ) async {
    final s = context.examL10n;
    final ok = await confirmAction(
      context,
      title: s.deleteQuestionTitle,
      message: q.questionText,
      confirmLabel: context.l10n.delete,
      destructive: true,
    );
    if (!ok || !context.mounted) return;
    try {
      await ref.read(examApiProvider).deleteQuestion(q.id);
      ref.invalidate(examQuestionsProvider(examId));
      ref.invalidate(examProvider(examId));
    } catch (e) {
      if (context.mounted) showErrorSnack(context, e);
    }
  }

  Future<void> _toggle(
    BuildContext context,
    WidgetRef ref,
    ExamQuestion q,
    bool value,
  ) async {
    try {
      await ref.read(examApiProvider).updateQuestion(q.id, isSelected: value);
      ref.invalidate(examQuestionsProvider(examId));
    } catch (e) {
      if (context.mounted) showErrorSnack(context, e);
    }
  }
}

class _QuestionTile extends StatelessWidget {
  const _QuestionTile({
    required this.question,
    required this.index,
    required this.canEdit,
    required this.showSelectToggle,
    required this.onEdit,
    required this.onDelete,
    required this.onToggle,
  });

  final ExamQuestion question;
  final int index;
  final bool canEdit;
  final bool showSelectToggle;
  final VoidCallback onEdit;
  final VoidCallback onDelete;
  final ValueChanged<bool> onToggle;

  @override
  Widget build(BuildContext context) {
    final s = context.examL10n;
    final text = Theme.of(context).textTheme;
    final correct = question.options.where((o) => o.isCorrect).length;
    return AppSurfaceCard(
      onTap: canEdit ? onEdit : null,
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  '$index. ${question.questionText}',
                  style: text.bodyMedium,
                  maxLines: 3,
                  overflow: TextOverflow.ellipsis,
                ),
                const SizedBox(height: AppSpacing.xs),
                Text(
                  [
                    s.questionSummary(question.options.length, correct),
                    if (showSelectToggle && !question.isSelected)
                      s.notSelectedManual,
                  ].join(' • '),
                  style: text.bodySmall,
                ),
              ],
            ),
          ),
          if (showSelectToggle)
            Semantics(
              label: s.selectedToggle,
              child: Switch(
                value: question.isSelected,
                onChanged: canEdit ? onToggle : null,
              ),
            ),
          if (canEdit)
            IconButton(
              tooltip: context.l10n.delete,
              icon: const Icon(Icons.delete_outline),
              onPressed: onDelete,
            ),
        ],
      ),
    );
  }
}
