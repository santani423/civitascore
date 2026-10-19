import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../../core/l10n/core_strings.dart';
import '../../../../core/network/api_failure.dart';
import '../../../../core/theme/app_dimensions.dart';
import '../../../../core/utils/file_actions.dart';
import '../../../../core/utils/formatters.dart';
import '../../../../core/utils/validators.dart';
import '../../../../core/widgets/app_feedback.dart';
import '../../../../core/widgets/state_views.dart';
import '../../../../core/widgets/ui_kit.dart';
import '../../../auth/presentation/controllers/auth_controller.dart';
import '../../data/teaching_api.dart';
import '../../domain/teaching_models.dart';
import '../../l10n/teaching_strings.dart';
import '../providers.dart';
import '../widgets/common.dart';

StatusTone _tone(String status) => switch (status) {
  'graded' => StatusTone.success,
  'submitted' => StatusTone.info,
  'late' => StatusTone.warning,
  'missed' => StatusTone.danger,
  _ => StatusTone.neutral,
};

String _fmt(double v) =>
    v == v.roundToDouble() ? v.toInt().toString() : v.toString();

class AssignmentSubmissionsScreen extends ConsumerWidget {
  const AssignmentSubmissionsScreen({super.key, required this.assignmentId});
  final String assignmentId;

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final t = context.tr;
    final data = ref.watch(submissionsProvider(assignmentId));
    final user = ref.watch(currentUserProvider);
    final canGrade =
        user?.hasPermission('assignment_submissions.update') ?? false;
    Future<void> refresh() async {
      ref.invalidate(submissionsProvider(assignmentId));
      try {
        await ref.read(submissionsProvider(assignmentId).future);
      } catch (_) {}
    }

    return Scaffold(
      appBar: AppBar(title: Text(t.submissionsTitle)),
      body: WideBody(
        child: AsyncValueView<SubmissionsResult>(
          value: data,
          onRetry: refresh,
          isEmpty: (r) => r.rows.isEmpty,
          empty: EmptyView(
            icon: Icons.groups_outlined,
            title: t.noStudentsTitle,
            message: t.noStudentsBody,
          ),
          data: (r) => RefreshIndicator(
            onRefresh: refresh,
            child: ListView.separated(
              physics: const AlwaysScrollableScrollPhysics(),
              padding: kPagePadding,
              itemCount: r.rows.length + 1,
              separatorBuilder: (_, _) => const SizedBox(height: AppSpacing.sm),
              itemBuilder: (context, i) {
                if (i == 0) {
                  final a = r.assignment;
                  return AppSurfaceCard(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(
                          a.title,
                          style: Theme.of(context).textTheme.titleMedium,
                        ),
                        const SizedBox(height: 2),
                        Text(
                          '${t.dueAt(context.formatDateTime(a.dueAt))} · ${t.maxScore(_fmt(a.maxScore))}',
                          style: Theme.of(context).textTheme.bodySmall,
                        ),
                      ],
                    ),
                  );
                }
                final row = r.rows[i - 1];
                final sub = row.submission;
                return AppSurfaceCard(
                  onTap: () => _showDetail(
                    context,
                    row: row,
                    maxScore: r.assignment.maxScore,
                    canGrade: canGrade,
                    onGraded: refresh,
                  ),
                  child: Row(
                    children: [
                      Expanded(
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Text(
                              row.name,
                              style: Theme.of(context).textTheme.titleSmall,
                              maxLines: 1,
                              overflow: TextOverflow.ellipsis,
                            ),
                            Text(
                              row.nim,
                              style: Theme.of(context).textTheme.bodySmall,
                            ),
                          ],
                        ),
                      ),
                      const SizedBox(width: AppSpacing.sm),
                      Column(
                        crossAxisAlignment: CrossAxisAlignment.end,
                        children: [
                          StatusChip(
                            label: t.statusLabel(row.status),
                            tone: _tone(row.status),
                            dense: true,
                          ),
                          if (sub?.score != null)
                            Padding(
                              padding: const EdgeInsets.only(top: 4),
                              child: Text(
                                '${_fmt(sub!.score!)} / ${_fmt(r.assignment.maxScore)}',
                                style: Theme.of(context).textTheme.labelMedium,
                              ),
                            ),
                        ],
                      ),
                    ],
                  ),
                );
              },
            ),
          ),
        ),
      ),
    );
  }

  void _showDetail(
    BuildContext context, {
    required SubmissionRow row,
    required double maxScore,
    required bool canGrade,
    required Future<void> Function() onGraded,
  }) {
    showModalBottomSheet<void>(
      context: context,
      isScrollControlled: true,
      showDragHandle: true,
      builder: (_) => _SubmissionSheet(
        row: row,
        maxScore: maxScore,
        canGrade: canGrade,
        onGraded: onGraded,
      ),
    );
  }
}

class _SubmissionSheet extends ConsumerStatefulWidget {
  const _SubmissionSheet({
    required this.row,
    required this.maxScore,
    required this.canGrade,
    required this.onGraded,
  });
  final SubmissionRow row;
  final double maxScore;
  final bool canGrade;
  final Future<void> Function() onGraded;

  @override
  ConsumerState<_SubmissionSheet> createState() => _SheetState();
}

class _SheetState extends ConsumerState<_SubmissionSheet> {
  final _formKey = GlobalKey<FormState>();
  late final _score = TextEditingController(
    text: widget.row.submission?.score == null
        ? ''
        : _fmt(widget.row.submission!.score!),
  );
  late final _feedback = TextEditingController(
    text: widget.row.submission?.feedback ?? '',
  );
  bool _busy = false;
  String? _scoreError;

  @override
  void dispose() {
    _score.dispose();
    _feedback.dispose();
    super.dispose();
  }

  Future<void> _save() async {
    setState(() => _scoreError = null);
    if (!_formKey.currentState!.validate()) return;
    setState(() => _busy = true);
    try {
      final fb = _feedback.text.trim();
      await ref
          .read(teachingApiProvider)
          .gradeSubmission(
            widget.row.submission!.id,
            score: double.parse(_score.text.trim().replaceAll(',', '.')),
            feedback: fb.isEmpty ? null : fb,
          );
      if (!mounted) return;
      showAppSnack(context, context.tr.gradeSaved);
      Navigator.of(context).pop();
      await widget.onGraded();
    } on ApiFailure catch (e) {
      if (!mounted) return;
      setState(() {
        _busy = false;
        _scoreError = e.fieldError('score');
      });
      showErrorSnack(context, e);
    } catch (e) {
      if (!mounted) return;
      setState(() => _busy = false);
      showErrorSnack(context, e);
    }
  }

  @override
  Widget build(BuildContext context) {
    final t = context.tr;
    final s = context.l10n;
    final row = widget.row;
    final sub = row.submission;
    final text = Theme.of(context).textTheme;
    return Padding(
      padding: EdgeInsets.only(
        bottom: MediaQuery.of(context).viewInsets.bottom,
      ),
      child: SingleChildScrollView(
        padding: const EdgeInsets.fromLTRB(
          AppSpacing.lg,
          0,
          AppSpacing.lg,
          AppSpacing.lg,
        ),
        child: Form(
          key: _formKey,
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            mainAxisSize: MainAxisSize.min,
            children: [
              Text(row.name, style: text.titleMedium),
              Text(row.nim, style: text.bodySmall),
              const SizedBox(height: AppSpacing.sm),
              Wrap(
                spacing: AppSpacing.sm,
                children: [
                  StatusChip(
                    label: t.statusLabel(row.status),
                    tone: _tone(row.status),
                    dense: true,
                  ),
                  if (sub?.isLate ?? false)
                    StatusChip(
                      label: t.late,
                      tone: StatusTone.warning,
                      dense: true,
                    ),
                ],
              ),
              const SizedBox(height: AppSpacing.md),
              if (sub == null)
                AppBanner(message: t.notSubmittedYet)
              else ...[
                InfoRow(
                  label: t.submittedAt,
                  value: context.formatDateTime(sub.submittedAt),
                ),
                InfoRow(
                  label: t.submissionCount,
                  value: '${sub.submissionCount}',
                ),
                if (sub.notes != null && sub.notes!.isNotEmpty)
                  InfoRow(label: t.studentNotes, value: sub.notes!),
                if (sub.gradedBy != null)
                  InfoRow(label: t.gradedBy, value: sub.gradedBy!),
                const SizedBox(height: AppSpacing.sm),
                if (sub.file != null)
                  OutlinedButton.icon(
                    onPressed: () => downloadAndOpen(
                      context,
                      ref,
                      path: '/file-uploads/${sub.file!.id}/download',
                      filename: sub.file!.name,
                    ),
                    icon: const Icon(Icons.download_outlined),
                    label: Text(
                      '${t.downloadFile}: ${sub.file!.name}',
                      overflow: TextOverflow.ellipsis,
                    ),
                  )
                else
                  Text(t.noFile, style: text.bodySmall),
                if (widget.canGrade) ...[
                  const SizedBox(height: AppSpacing.lg),
                  Text(t.gradeSubmission, style: text.titleSmall),
                  const SizedBox(height: AppSpacing.sm),
                  TextFormField(
                    controller: _score,
                    keyboardType: const TextInputType.numberWithOptions(
                      decimal: true,
                    ),
                    decoration: InputDecoration(
                      labelText: t.fieldScore(_fmt(widget.maxScore)),
                      errorText: _scoreError,
                    ),
                    validator: Validators.compose([
                      Validators.required(s),
                      Validators.numberInRange(
                        s,
                        0,
                        widget.maxScore == widget.maxScore.roundToDouble()
                            ? widget.maxScore.toInt()
                            : widget.maxScore,
                      ),
                    ]),
                  ),
                  const SizedBox(height: AppSpacing.sm),
                  TextFormField(
                    controller: _feedback,
                    maxLines: 3,
                    maxLength: 5000,
                    decoration: InputDecoration(
                      labelText: '${t.feedback} · ${s.optional}',
                      alignLabelWithHint: true,
                    ),
                  ),
                  const SizedBox(height: AppSpacing.sm),
                  SizedBox(
                    width: double.infinity,
                    child: FilledButton(
                      onPressed: _busy ? null : _save,
                      child: _busy
                          ? const SizedBox(
                              height: 20,
                              width: 20,
                              child: CircularProgressIndicator(strokeWidth: 2),
                            )
                          : Text(s.save),
                    ),
                  ),
                ],
              ],
            ],
          ),
        ),
      ),
    );
  }
}
