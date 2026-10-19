import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../../core/l10n/core_strings.dart';
import '../../../../core/network/api_client.dart';
import '../../../../core/network/api_failure.dart';
import '../../../../core/theme/app_dimensions.dart';
import '../../../../core/utils/file_actions.dart';
import '../../../../core/utils/formatters.dart';
import '../../../../core/widgets/app_feedback.dart';
import '../../../../core/widgets/state_views.dart';
import '../../../../core/widgets/ui_kit.dart';
import '../../data/learning_api.dart';
import '../../domain/learning_models.dart';
import '../../l10n/learning_strings.dart';
import '../providers.dart';
import '../widgets/learning_widgets.dart';

class AssignmentDetailScreen extends ConsumerWidget {
  const AssignmentDetailScreen({super.key, required this.assignmentId});
  final String assignmentId;

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final s = context.learningL10n;
    final value = ref.watch(assignmentProvider(assignmentId));
    return Scaffold(
      appBar: AppBar(title: Text(s.assignmentTitle)),
      body: LearningBody(
        child: AsyncValueView<AssignmentItem>(
          value: value,
          onRetry: () => ref.invalidate(assignmentProvider(assignmentId)),
          data: (a) => RefreshIndicator(
            onRefresh: () async {
              ref.invalidate(assignmentProvider(assignmentId));
              await ref
                  .read(assignmentProvider(assignmentId).future)
                  .catchError((_) => a);
            },
            child: _Body(assignment: a),
          ),
        ),
      ),
    );
  }
}

class _Body extends ConsumerWidget {
  const _Body({required this.assignment});
  final AssignmentItem assignment;

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final s = context.learningL10n;
    final theme = Theme.of(context);
    final a = assignment;
    final sub = a.submission;
    return ListView(
      physics: const AlwaysScrollableScrollPhysics(),
      padding: const EdgeInsets.all(AppSpacing.lg),
      children: [
        Row(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Expanded(child: Text(a.title, style: theme.textTheme.titleLarge)),
            const SizedBox(width: AppSpacing.sm),
            StatusChip(label: s.statusLabel(a), tone: assignmentTone(a.status)),
          ],
        ),
        if (a.course != null)
          Padding(
            padding: const EdgeInsets.only(top: 4),
            child: Text(
              [a.course!.name, ?a.course!.lecturer].join(' • '),
              style: theme.textTheme.bodySmall,
            ),
          ),
        const SizedBox(height: AppSpacing.md),
        AppSurfaceCard(
          child: Column(
            children: [
              InfoRow(label: s.due, value: context.formatDateTime(a.dueAt)),
              if (a.maxScore != null)
                InfoRow(label: s.maxScore, value: a.maxScore!),
            ],
          ),
        ),
        SectionHeader(
          title: s.instructions,
          padding: const EdgeInsets.only(top: AppSpacing.lg, bottom: 4),
        ),
        Text(
          (a.description ?? '').trim().isEmpty
              ? s.noInstructions
              : a.description!,
        ),
        if (a.attachment != null) ...[
          SectionHeader(
            title: s.attachment,
            padding: const EdgeInsets.only(top: AppSpacing.lg, bottom: 4),
          ),
          _FileTile(file: a.attachment!),
        ],
        SectionHeader(
          title: s.rules,
          padding: const EdgeInsets.only(top: AppSpacing.lg, bottom: 4),
        ),
        AppSurfaceCard(
          child: Column(
            children: [
              if (a.allowedExtensions.isNotEmpty)
                InfoRow(
                  label: s.allowedFiles,
                  value: a.allowedExtensions.join(', '),
                ),
              InfoRow(label: s.maxFileSize, value: '${a.maxFileSizeMb} MB'),
              InfoRow(
                label: s.lateBadge,
                value: a.allowLate ? s.lateAllowed : s.lateNotAllowed,
              ),
              InfoRow(
                label: s.resubmitSection,
                value: a.allowResubmission
                    ? s.resubmitAllowed
                    : s.resubmitNotAllowed,
              ),
            ],
          ),
        ),
        if (sub != null) ...[
          SectionHeader(
            title: s.mySubmission,
            padding: const EdgeInsets.only(top: AppSpacing.lg, bottom: 4),
          ),
          AppSurfaceCard(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                InfoRow(
                  label: s.submittedAt,
                  value: context.formatDateTime(sub.submittedAt),
                  trailing: sub.isLate
                      ? StatusChip(
                          label: s.lateBadge,
                          tone: StatusTone.warning,
                          dense: true,
                        )
                      : null,
                ),
                InfoRow(
                  label: s.submissionCount,
                  value: '${sub.submissionCount}',
                ),
                if (sub.notes != null && sub.notes!.isNotEmpty) ...[
                  const SizedBox(height: AppSpacing.sm),
                  Text(s.notes, style: theme.textTheme.labelMedium),
                  Text(sub.notes!),
                ],
                if (sub.file != null) ...[
                  const SizedBox(height: AppSpacing.sm),
                  _FileTile(file: sub.file!),
                ],
              ],
            ),
          ),
          SectionHeader(
            title: s.grade,
            padding: const EdgeInsets.only(top: AppSpacing.lg, bottom: 4),
          ),
          AppSurfaceCard(
            child: sub.gradedAt == null && sub.score == null
                ? Text(s.notGradedYet)
                : Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      InfoRow(
                        label: s.score,
                        value: '${sub.score ?? '—'} / ${a.maxScore ?? '—'}',
                      ),
                      if (sub.gradedAt != null)
                        InfoRow(
                          label: s.gradedAt,
                          value: context.formatDateTime(sub.gradedAt),
                        ),
                      if (sub.gradedBy != null)
                        InfoRow(label: s.gradedBy, value: sub.gradedBy!),
                      if (sub.feedback != null && sub.feedback!.isNotEmpty) ...[
                        const SizedBox(height: AppSpacing.sm),
                        Text(s.feedback, style: theme.textTheme.labelMedium),
                        Text(sub.feedback!),
                      ],
                    ],
                  ),
          ),
        ],
        const SizedBox(height: AppSpacing.lg),
        if (a.canSubmit)
          SubmissionForm(assignment: a)
        else
          AppBanner(
            message: s.cannotSubmitReason(a),
            tone: StatusTone.neutral,
            icon: Icons.lock_outline,
          ),
      ],
    );
  }
}

class _FileTile extends ConsumerWidget {
  const _FileTile({required this.file});
  final FileRef file;
  @override
  Widget build(BuildContext context, WidgetRef ref) => MenuTile(
    icon: Icons.attach_file,
    title: file.name,
    subtitle: formatBytes(file.sizeBytes),
    trailing: const Icon(Icons.download_outlined),
    onTap: () => downloadAndOpen(
      context,
      ref,
      path: file.downloadPath,
      filename: file.name,
    ),
  );
}

/// Submit / resubmit form: optional file (two-step upload) and/or notes.
class SubmissionForm extends ConsumerStatefulWidget {
  const SubmissionForm({super.key, required this.assignment});
  final AssignmentItem assignment;
  @override
  ConsumerState<SubmissionForm> createState() => _SubmissionFormState();
}

class _SubmissionFormState extends ConsumerState<SubmissionForm> {
  final _notes = TextEditingController();
  PickedUpload? _file;
  bool _busy = false;
  String? _error;
  String? _notesError;

  @override
  void dispose() {
    _notes.dispose();
    super.dispose();
  }

  Future<void> _pick() async {
    final a = widget.assignment;
    final allowed = a.allowedExtensions
        .map((e) => e.toLowerCase())
        .where(kUploadExtensions.contains)
        .toList();
    final picked = await pickUpload(
      context,
      extensions: allowed.isEmpty ? kUploadExtensions : allowed,
      maxMb: a.maxFileSizeMb,
    );
    if (picked != null && mounted) {
      final ext = picked.name.contains('.')
          ? picked.name.split('.').last.toLowerCase()
          : '';
      if (a.allowedExtensions.isNotEmpty &&
          !a.allowedExtensions.map((e) => e.toLowerCase()).contains(ext)) {
        setState(() => _error = a.allowedExtensions.join(', '));
        return;
      }
      setState(() {
        _file = picked;
        _error = null;
      });
    }
  }

  Future<void> _submit() async {
    final s = context.learningL10n;
    final a = widget.assignment;
    if (_file == null && _notes.text.trim().isEmpty) {
      setState(() => _error = s.needFileOrNotes);
      return;
    }
    if (a.hasSubmission || a.isPastDue) {
      final ok = await confirmAction(
        context,
        title: a.isPastDue ? s.confirmLateTitle : s.confirmResubmitTitle,
        message: a.isPastDue ? s.lateWarning : s.confirmResubmitBody,
        confirmLabel: context.l10n.submit,
      );
      if (!ok || !mounted) return;
    }
    setState(() {
      _busy = true;
      _error = null;
      _notesError = null;
    });
    try {
      String? fileId;
      if (_file != null) {
        fileId = await ref.read(apiClientProvider).uploadFile(_file!.path);
      }
      await ref
          .read(learningApiProvider)
          .submit(a.id, fileUploadId: fileId, notes: _notes.text);
      invalidateAssignments(ref, id: a.id);
      if (!mounted) return;
      showAppSnack(context, s.submittedMsg);
      setState(() {
        _file = null;
        _notes.clear();
      });
    } catch (e) {
      if (!mounted) return;
      if (e is ApiFailure && e.fieldErrors.isNotEmpty) {
        setState(() {
          _error = e.fieldError('file_upload_id');
          _notesError = e.fieldError('notes');
        });
        if (_error == null && _notesError == null) showErrorSnack(context, e);
      } else {
        showErrorSnack(context, e);
      }
    } finally {
      if (mounted) setState(() => _busy = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    final s = context.learningL10n;
    final a = widget.assignment;
    final theme = Theme.of(context);
    final resubmit = a.hasSubmission;
    return Column(
      crossAxisAlignment: CrossAxisAlignment.stretch,
      children: [
        Text(
          resubmit ? s.resubmitSection : s.submitSection,
          style: theme.textTheme.titleMedium,
        ),
        const SizedBox(height: AppSpacing.sm),
        if (a.isPastDue)
          Padding(
            padding: const EdgeInsets.only(bottom: AppSpacing.sm),
            child: AppBanner(
              message: s.lateWarning,
              tone: StatusTone.warning,
              icon: Icons.schedule,
            ),
          ),
        TextField(
          controller: _notes,
          minLines: 3,
          maxLines: 8,
          maxLength: 5000,
          enabled: !_busy,
          decoration: InputDecoration(
            labelText: s.notes,
            hintText: s.notesHint,
            errorText: _notesError,
            alignLabelWithHint: true,
          ),
        ),
        const SizedBox(height: AppSpacing.sm),
        if (_file != null)
          ListTile(
            contentPadding: EdgeInsets.zero,
            leading: const Icon(Icons.attach_file),
            title: Text(_file!.name, overflow: TextOverflow.ellipsis),
            subtitle: Text(formatBytes(_file!.size)),
            trailing: IconButton(
              tooltip: s.removeFile,
              icon: const Icon(Icons.close),
              onPressed: _busy ? null : () => setState(() => _file = null),
            ),
          ),
        OutlinedButton.icon(
          onPressed: _busy ? null : _pick,
          icon: const Icon(Icons.upload_file),
          label: Text(_file == null ? s.chooseFileBtn : s.replaceFileBtn),
        ),
        if (_error != null)
          Padding(
            padding: const EdgeInsets.only(top: AppSpacing.sm),
            child: Text(
              _error!,
              style: TextStyle(color: theme.colorScheme.error),
            ),
          ),
        const SizedBox(height: AppSpacing.md),
        FilledButton(
          onPressed: _busy ? null : _submit,
          child: _busy
              ? const SizedBox(
                  height: 20,
                  width: 20,
                  child: CircularProgressIndicator(strokeWidth: 2),
                )
              : Text(resubmit ? s.resubmitBtn : s.submitBtn),
        ),
      ],
    );
  }
}
