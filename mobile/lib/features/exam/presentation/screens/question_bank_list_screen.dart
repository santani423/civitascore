import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../../core/l10n/core_strings.dart';
import '../../../../core/permission/permission_gate.dart';
import '../../../../core/theme/app_dimensions.dart';
import '../../../../core/widgets/app_feedback.dart';
import '../../../../core/widgets/paged_list.dart';
import '../../../../core/widgets/state_views.dart';
import '../../../../core/widgets/ui_kit.dart';
import '../../data/exam_api.dart';
import '../../domain/own_class.dart';
import '../../domain/question_bank_item.dart';
import '../../l10n/exam_strings.dart';
import '../providers.dart';
import '../widgets/question_editor_sheet.dart';

/// Bank soal per mata kuliah (CRUD). Dengan [pickForExamId] layar berubah
/// menjadi pemilih butir untuk diterapkan ke ujian
/// (`POST /exams/{id}/questions/apply-bank`).
class QuestionBankListScreen extends ConsumerStatefulWidget {
  const QuestionBankListScreen({super.key, this.pickForExamId});

  final String? pickForExamId;

  @override
  ConsumerState<QuestionBankListScreen> createState() =>
      _QuestionBankListScreenState();
}

class _QuestionBankListScreenState
    extends ConsumerState<QuestionBankListScreen> {
  final _selected = <String>{};
  final _search = TextEditingController();
  bool _applying = false;

  bool get _picking => widget.pickForExamId != null;

  @override
  void dispose() {
    _search.dispose();
    super.dispose();
  }

  List<OwnClass> _courses() {
    final classes = ref.read(ownClassesProvider).valueOrNull ?? const [];
    final seen = <String>{};
    return [
      for (final c in classes)
        if (c.courseId != null && seen.add(c.courseId!)) c,
    ];
  }

  @override
  Widget build(BuildContext context) {
    final s = context.examL10n;
    final core = context.l10n;
    final state = ref.watch(bankListProvider);
    final query = ref.watch(bankQueryProvider);
    final notifier = ref.read(bankListProvider.notifier);
    final canCreate = PermissionGate.allows(ref, 'question_bank.create');
    final courses = _courses();
    ref.watch(ownClassesProvider);

    return Scaffold(
      appBar: AppBar(title: Text(_picking ? s.pickExamTitle : s.bankTitle)),
      floatingActionButton: _picking
          ? (_selected.isEmpty
                ? null
                : FloatingActionButton.extended(
                    onPressed: _applying ? null : _apply,
                    icon: const Icon(Icons.playlist_add_check),
                    label: Text(s.applySelected(_selected.length)),
                  ))
          : (canCreate
                ? FloatingActionButton.extended(
                    onPressed: () => _openEditor(context),
                    icon: const Icon(Icons.add),
                    label: Text(s.addBankItem),
                  )
                : null),
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
                  AppSpacing.sm,
                ),
                child: Column(
                  children: [
                    TextField(
                      controller: _search,
                      textInputAction: TextInputAction.search,
                      decoration: InputDecoration(
                        hintText: s.bankSearchHint,
                        prefixIcon: const Icon(Icons.search),
                      ),
                      onSubmitted: (v) =>
                          ref.read(bankQueryProvider.notifier).state = query
                              .copyWith(search: v.trim()),
                    ),
                    if (courses.isNotEmpty) ...[
                      const SizedBox(height: AppSpacing.sm),
                      DropdownButtonFormField<String?>(
                        isExpanded: true,
                        initialValue: query.courseId,
                        decoration: InputDecoration(labelText: s.courseLabel),
                        items: [
                          DropdownMenuItem<String?>(
                            value: null,
                            child: Text(s.allCourses),
                          ),
                          for (final c in courses)
                            DropdownMenuItem<String?>(
                              value: c.courseId,
                              child: Text(
                                c.courseName ?? c.classCode,
                                overflow: TextOverflow.ellipsis,
                              ),
                            ),
                        ],
                        onChanged: (v) =>
                            ref
                                .read(bankQueryProvider.notifier)
                                .state = v == null
                            ? query.copyWith(clearCourse: true)
                            : query.copyWith(courseId: v),
                      ),
                    ],
                  ],
                ),
              ),
              Expanded(
                child: PagedListView<QuestionBankItem>(
                  state: state,
                  onRefresh: notifier.refresh,
                  onLoadMore: notifier.loadMore,
                  padding: const EdgeInsets.fromLTRB(
                    AppSpacing.lg,
                    0,
                    AppSpacing.lg,
                    88,
                  ),
                  empty: EmptyView(
                    icon: Icons.library_books_outlined,
                    title: s.bankEmptyTitle,
                    message: s.bankEmptyBody,
                  ),
                  itemBuilder: (context, item) => _BankTile(
                    item: item,
                    picking: _picking,
                    selected: _selected.contains(item.id),
                    onToggle: () => setState(() {
                      if (!_selected.remove(item.id)) _selected.add(item.id);
                    }),
                    onEdit: () => _openEditor(context, item: item),
                    onDelete: () => _delete(context, item),
                    canEdit: PermissionGate.allows(ref, 'question_bank.update'),
                    canDelete: PermissionGate.allows(
                      ref,
                      'question_bank.delete',
                    ),
                    deleteTooltip: core.delete,
                  ),
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }

  Future<void> _openEditor(
    BuildContext context, {
    QuestionBankItem? item,
  }) async {
    final s = context.examL10n;
    final api = ref.read(examApiProvider);
    final saved = await showQuestionEditor(
      context,
      QuestionEditorSheet(
        title: item == null ? s.addBankItem : s.editBankItem,
        initialText: item?.questionText ?? '',
        initialPoints: item?.points ?? 1,
        initialOptions: item?.options,
        courses: _courses(),
        initialCourseId: item?.courseId,
        onSave:
            ({
              required text,
              required points,
              required options,
              courseId,
            }) async {
              if (item == null) {
                await api.createBankItem(
                  courseId: courseId,
                  questionText: text,
                  points: points,
                  options: options,
                );
              } else {
                await api.updateBankItem(
                  item.id,
                  courseId: courseId,
                  questionText: text,
                  points: points,
                  options: options,
                );
              }
            },
      ),
    );
    if (saved == true) {
      ref.invalidate(bankListProvider);
      if (context.mounted) showAppSnack(context, s.bankSaved);
    }
  }

  Future<void> _delete(BuildContext context, QuestionBankItem item) async {
    final s = context.examL10n;
    final ok = await confirmAction(
      context,
      title: s.deleteBankTitle,
      message: item.questionText,
      confirmLabel: context.l10n.delete,
      destructive: true,
    );
    if (!ok || !context.mounted) return;
    try {
      await ref.read(examApiProvider).deleteBankItem(item.id);
      ref.invalidate(bankListProvider);
      if (context.mounted) showAppSnack(context, s.bankDeleted);
    } catch (e) {
      if (context.mounted) showErrorSnack(context, e);
    }
  }

  Future<void> _apply() async {
    final s = context.examL10n;
    setState(() => _applying = true);
    try {
      await ref
          .read(examApiProvider)
          .applyBank(widget.pickForExamId!, _selected.toList());
      if (!mounted) return;
      showAppSnack(context, s.applied);
      Navigator.of(context).pop();
    } catch (e) {
      if (mounted) showErrorSnack(context, e);
    } finally {
      if (mounted) setState(() => _applying = false);
    }
  }
}

class _BankTile extends StatelessWidget {
  const _BankTile({
    required this.item,
    required this.picking,
    required this.selected,
    required this.onToggle,
    required this.onEdit,
    required this.onDelete,
    required this.canEdit,
    required this.canDelete,
    required this.deleteTooltip,
  });

  final QuestionBankItem item;
  final bool picking;
  final bool selected;
  final VoidCallback onToggle;
  final VoidCallback onEdit;
  final VoidCallback onDelete;
  final bool canEdit;
  final bool canDelete;
  final String deleteTooltip;

  @override
  Widget build(BuildContext context) {
    final s = context.examL10n;
    final text = Theme.of(context).textTheme;
    final correct = item.options.where((o) => o.isCorrect).length;
    return AppSurfaceCard(
      onTap: picking ? onToggle : (canEdit ? onEdit : null),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          if (picking) Checkbox(value: selected, onChanged: (_) => onToggle()),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  item.questionText,
                  style: text.bodyMedium,
                  maxLines: 3,
                  overflow: TextOverflow.ellipsis,
                ),
                const SizedBox(height: AppSpacing.xs),
                Text(
                  [
                    ?item.courseName,
                    s.questionSummary(item.options.length, correct),
                    s.usageCount(item.usageCount),
                  ].join(' • '),
                  style: text.bodySmall,
                ),
              ],
            ),
          ),
          if (!picking && canDelete)
            IconButton(
              tooltip: deleteTooltip,
              icon: const Icon(Icons.delete_outline),
              onPressed: onDelete,
            ),
        ],
      ),
    );
  }
}
