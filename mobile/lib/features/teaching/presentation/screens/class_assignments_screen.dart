import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import '../../../../core/l10n/core_strings.dart';
import '../../../../core/network/api_failure.dart';
import '../../../../core/router/route_paths.dart';
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

const kSelectableExtensions = [
  'pdf',
  'doc',
  'docx',
  'xls',
  'xlsx',
  'jpg',
  'jpeg',
  'png',
];

String _fmtScore(double v) =>
    v == v.roundToDouble() ? v.toInt().toString() : v.toString();

class ClassAssignmentsScreen extends ConsumerWidget {
  const ClassAssignmentsScreen({super.key, required this.classSectionId});
  final String classSectionId;

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final t = context.tr;
    final s = context.l10n;
    final user = ref.watch(currentUserProvider);
    bool can(String p) => user?.hasPermission(p) ?? false;
    final list = ref.watch(assignmentsProvider(classSectionId));
    Future<void> refresh() async {
      ref.invalidate(assignmentsProvider(classSectionId));
      try {
        await ref.read(assignmentsProvider(classSectionId).future);
      } catch (_) {}
    }

    Future<void> openForm([TeachingAssignment? a]) async {
      final saved = await Navigator.of(context).push<bool>(
        MaterialPageRoute(
          fullscreenDialog: true,
          builder: (_) => AssignmentFormScreen(
            classSectionId: classSectionId,
            assignment: a,
          ),
        ),
      );
      if (saved == true) await refresh();
    }

    Future<void> togglePublish(TeachingAssignment a) async {
      try {
        await ref.read(teachingApiProvider).updateAssignment(a.id, {
          'is_published': !a.isPublished,
        });
        await refresh();
        if (context.mounted) {
          showAppSnack(
            context,
            a.isPublished ? t.unpublishedNow : t.publishedNow,
          );
        }
      } catch (e) {
        if (context.mounted) showErrorSnack(context, e);
      }
    }

    Future<void> delete(TeachingAssignment a) async {
      final ok = await confirmAction(
        context,
        title: t.deleteAssignmentTitle,
        message: t.deleteAssignmentBody,
        confirmLabel: s.delete,
        destructive: true,
      );
      if (!ok) return;
      try {
        await ref.read(teachingApiProvider).deleteAssignment(a.id);
        await refresh();
        if (context.mounted) showAppSnack(context, t.assignmentDeleted);
      } catch (e) {
        if (context.mounted) showErrorSnack(context, e);
      }
    }

    return Scaffold(
      appBar: AppBar(title: Text(t.assignmentsTitle)),
      floatingActionButton: can('assignments.create')
          ? FloatingActionButton.extended(
              onPressed: () => openForm(),
              icon: const Icon(Icons.add),
              label: Text(t.addAssignment),
            )
          : null,
      body: WideBody(
        child: AsyncValueView<List<TeachingAssignment>>(
          value: list,
          onRetry: refresh,
          isEmpty: (l) => l.isEmpty,
          empty: EmptyView(
            icon: Icons.assignment_outlined,
            title: t.noAssignmentsTitle,
            message: t.noAssignmentsBody,
          ),
          data: (items) => RefreshIndicator(
            onRefresh: refresh,
            child: ListView.separated(
              physics: const AlwaysScrollableScrollPhysics(),
              padding: const EdgeInsets.fromLTRB(
                AppSpacing.lg,
                AppSpacing.md,
                AppSpacing.lg,
                96,
              ),
              itemCount: items.length,
              separatorBuilder: (_, _) => const SizedBox(height: AppSpacing.sm),
              itemBuilder: (context, i) {
                final a = items[i];
                return AppSurfaceCard(
                  padding: const EdgeInsets.fromLTRB(
                    AppSpacing.lg,
                    AppSpacing.md,
                    AppSpacing.xs,
                    AppSpacing.md,
                  ),
                  onTap: () => context.push(
                    RoutePaths.lecturerAssignmentSubmissions(a.id),
                  ),
                  child: Row(
                    children: [
                      Expanded(
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Text(
                              a.title,
                              style: Theme.of(context).textTheme.titleSmall,
                              maxLines: 2,
                              overflow: TextOverflow.ellipsis,
                            ),
                            const SizedBox(height: 2),
                            Text(
                              '${t.dueAt(context.formatDateTime(a.dueAt))} · ${t.maxScore(_fmtScore(a.maxScore))}',
                              style: Theme.of(context).textTheme.bodySmall,
                            ),
                            const SizedBox(height: 4),
                            Wrap(
                              spacing: AppSpacing.sm,
                              runSpacing: 4,
                              children: [
                                StatusChip(
                                  label: a.isPublished ? t.published : t.draft,
                                  tone: a.isPublished
                                      ? StatusTone.success
                                      : StatusTone.warning,
                                  dense: true,
                                ),
                                StatusChip(
                                  label: t.submittedOf(
                                    a.submissionsCount,
                                    a.gradedCount,
                                  ),
                                  dense: true,
                                ),
                              ],
                            ),
                          ],
                        ),
                      ),
                      if (can('assignments.update') ||
                          can('assignments.delete'))
                        PopupMenuButton<String>(
                          tooltip: s.edit,
                          onSelected: (v) {
                            switch (v) {
                              case 'edit':
                                openForm(a);
                              case 'publish':
                                togglePublish(a);
                              case 'delete':
                                delete(a);
                            }
                          },
                          itemBuilder: (_) => [
                            if (can('assignments.update')) ...[
                              PopupMenuItem(value: 'edit', child: Text(s.edit)),
                              PopupMenuItem(
                                value: 'publish',
                                child: Text(
                                  a.isPublished ? t.unpublish : t.publish,
                                ),
                              ),
                            ],
                            if (can('assignments.delete'))
                              PopupMenuItem(
                                value: 'delete',
                                child: Text(s.delete),
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
}

class AssignmentFormScreen extends ConsumerStatefulWidget {
  const AssignmentFormScreen({
    super.key,
    required this.classSectionId,
    this.assignment,
  });
  final String classSectionId;
  final TeachingAssignment? assignment;

  @override
  ConsumerState<AssignmentFormScreen> createState() => _AssignmentFormState();
}

class _AssignmentFormState extends ConsumerState<AssignmentFormScreen> {
  final _formKey = GlobalKey<FormState>();
  late final _title = TextEditingController(text: widget.assignment?.title);
  late final _description = TextEditingController(
    text: widget.assignment?.description ?? '',
  );
  late final _maxSize = TextEditingController(
    text: '${widget.assignment?.maxFileSizeMb ?? 10}',
  );
  late final _maxScore = TextEditingController(
    text: _fmtScore(widget.assignment?.maxScore ?? 100),
  );
  late DateTime? _due = widget.assignment?.dueAt;
  late bool _late = widget.assignment?.allowLate ?? false;
  late bool _resubmit = widget.assignment?.allowResubmission ?? false;
  late bool _published = widget.assignment?.isPublished ?? true;
  late final Set<String> _exts = {
    ...(widget.assignment?.allowedExtensions.isNotEmpty ?? false
        ? widget.assignment!.allowedExtensions
        : kSelectableExtensions),
  };
  PickedUpload? _picked;
  String? _attachmentName;
  bool _attachmentRemoved = false;
  bool _busy = false;
  bool _dueMissing = false;
  Map<String, String> _serverErrors = {};

  @override
  void initState() {
    super.initState();
    _attachmentName = widget.assignment?.attachment?.name;
  }

  @override
  void dispose() {
    _title.dispose();
    _description.dispose();
    _maxSize.dispose();
    _maxScore.dispose();
    super.dispose();
  }

  Future<void> _pickDue() async {
    final now = DateTime.now();
    final date = await showDatePicker(
      context: context,
      initialDate: _due ?? now.add(const Duration(days: 7)),
      firstDate: DateTime(now.year - 1),
      lastDate: DateTime(now.year + 3),
    );
    if (date == null || !mounted) return;
    final time = await showTimePicker(
      context: context,
      initialTime: TimeOfDay.fromDateTime(_due ?? DateTime(2000, 1, 1, 23, 59)),
    );
    if (time == null) return;
    setState(() {
      _due = DateTime(date.year, date.month, date.day, time.hour, time.minute);
      _dueMissing = false;
    });
  }

  Future<void> _pickAttachment() async {
    final p = await pickUpload(context, allowCamera: false);
    if (p != null) {
      setState(() {
        _picked = p;
        _attachmentRemoved = false;
      });
    }
  }

  Future<void> _submit() async {
    final t = context.tr;
    setState(() {
      _serverErrors = {};
      _dueMissing = false;
    });
    final valid = _formKey.currentState!.validate();
    if (_due == null) {
      setState(() => _dueMissing = true);
      return;
    }
    if (!valid) return;
    setState(() => _busy = true);
    final api = ref.read(teachingApiProvider);
    try {
      String? fileId;
      if (_picked != null) fileId = await api.uploadFile(_picked!.path);
      final desc = _description.text.trim();
      final exts = kSelectableExtensions.where(_exts.contains).toList();
      final body = <String, dynamic>{
        'title': _title.text.trim(),
        'description': desc.isEmpty ? null : desc,
        'due_at': _due!.toUtc().toIso8601String(),
        'allow_late_submission': _late,
        'allow_resubmission': _resubmit,
        'max_file_size_mb': int.parse(_maxSize.text.trim()),
        'allowed_extensions': exts,
        'max_score': double.parse(_maxScore.text.trim().replaceAll(',', '.')),
        'is_published': _published,
        if (fileId != null)
          'attachment_file_id': fileId
        else if (_attachmentRemoved)
          'attachment_file_id': null,
      };
      if (widget.assignment == null) {
        await api.createAssignment(widget.classSectionId, body);
      } else {
        await api.updateAssignment(widget.assignment!.id, body);
      }
      if (!mounted) return;
      showAppSnack(context, t.assignmentSaved);
      Navigator.of(context).pop(true);
    } on ApiFailure catch (e) {
      if (!mounted) return;
      setState(() {
        _busy = false;
        _serverErrors = {
          for (final f in e.fieldErrors.keys) f: e.fieldError(f) ?? '',
        };
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
    final editing = widget.assignment != null;
    final scheme = Theme.of(context).colorScheme;
    final attachment =
        _picked?.name ?? (_attachmentRemoved ? null : _attachmentName);
    return Scaffold(
      appBar: AppBar(title: Text(editing ? t.editAssignment : t.addAssignment)),
      body: WideBody(
        child: Form(
          key: _formKey,
          child: ListView(
            padding: kPagePadding,
            children: [
              TextFormField(
                controller: _title,
                maxLength: 255,
                decoration: InputDecoration(
                  labelText: t.fieldTitle,
                  errorText: _serverErrors['title'],
                ),
                validator: Validators.required(s),
              ),
              TextFormField(
                controller: _description,
                maxLines: 4,
                maxLength: 10000,
                decoration: InputDecoration(
                  labelText: '${t.fieldDescription} · ${s.optional}',
                  alignLabelWithHint: true,
                  errorText: _serverErrors['description'],
                ),
              ),
              const SizedBox(height: AppSpacing.sm),
              OutlinedButton.icon(
                onPressed: _busy ? null : _pickDue,
                icon: const Icon(Icons.event_outlined),
                label: Text(
                  _due == null
                      ? t.pickDueDate
                      : '${t.fieldDueDate}: ${context.formatDateTime(_due)}',
                ),
              ),
              if (_dueMissing || _serverErrors['due_at'] != null)
                Padding(
                  padding: const EdgeInsets.only(top: AppSpacing.xs),
                  child: Text(
                    _serverErrors['due_at'] ?? t.dueRequired,
                    style: TextStyle(color: scheme.error),
                  ),
                ),
              const SizedBox(height: AppSpacing.md),
              Row(
                children: [
                  Expanded(
                    child: TextFormField(
                      controller: _maxSize,
                      keyboardType: TextInputType.number,
                      decoration: InputDecoration(
                        labelText: t.fieldMaxFileSize,
                        errorText: _serverErrors['max_file_size_mb'],
                      ),
                      validator: Validators.compose([
                        Validators.required(s),
                        Validators.numberInRange(s, 1, 50),
                      ]),
                    ),
                  ),
                  const SizedBox(width: AppSpacing.md),
                  Expanded(
                    child: TextFormField(
                      controller: _maxScore,
                      keyboardType: const TextInputType.numberWithOptions(
                        decimal: true,
                      ),
                      decoration: InputDecoration(
                        labelText: t.fieldMaxScore,
                        errorText: _serverErrors['max_score'],
                      ),
                      validator: Validators.compose([
                        Validators.required(s),
                        Validators.numberInRange(s, 1, 1000),
                      ]),
                    ),
                  ),
                ],
              ),
              const SizedBox(height: AppSpacing.md),
              Text(
                t.allowedExtensions,
                style: Theme.of(context).textTheme.titleSmall,
              ),
              const SizedBox(height: AppSpacing.xs),
              Wrap(
                spacing: AppSpacing.sm,
                children: [
                  for (final e in kSelectableExtensions)
                    FilterChip(
                      label: Text(e),
                      selected: _exts.contains(e),
                      onSelected: _busy
                          ? null
                          : (v) => setState(() {
                              if (v) {
                                _exts.add(e);
                              } else if (_exts.length > 1) {
                                _exts.remove(e);
                              }
                            }),
                    ),
                ],
              ),
              SwitchListTile(
                contentPadding: EdgeInsets.zero,
                title: Text(t.allowLate),
                value: _late,
                onChanged: _busy ? null : (v) => setState(() => _late = v),
              ),
              SwitchListTile(
                contentPadding: EdgeInsets.zero,
                title: Text(t.allowResubmit),
                value: _resubmit,
                onChanged: _busy ? null : (v) => setState(() => _resubmit = v),
              ),
              SwitchListTile(
                contentPadding: EdgeInsets.zero,
                title: Text(t.fieldPublished),
                value: _published,
                onChanged: _busy ? null : (v) => setState(() => _published = v),
              ),
              Text(t.attachment, style: Theme.of(context).textTheme.titleSmall),
              const SizedBox(height: AppSpacing.xs),
              Row(
                children: [
                  Expanded(
                    child: Text(
                      attachment ?? t.noAttachment,
                      maxLines: 2,
                      overflow: TextOverflow.ellipsis,
                    ),
                  ),
                  if (attachment != null)
                    IconButton(
                      tooltip: t.removeAttachment,
                      icon: const Icon(Icons.close),
                      onPressed: _busy
                          ? null
                          : () => setState(() {
                              _picked = null;
                              _attachmentRemoved = true;
                            }),
                    ),
                  TextButton.icon(
                    onPressed: _busy ? null : _pickAttachment,
                    icon: const Icon(Icons.attach_file),
                    label: Text(t.pickFile),
                  ),
                ],
              ),
              if (_serverErrors['attachment_file_id'] != null)
                Text(
                  _serverErrors['attachment_file_id']!,
                  style: TextStyle(color: scheme.error),
                ),
              const SizedBox(height: AppSpacing.lg),
              FilledButton(
                onPressed: _busy ? null : _submit,
                child: _busy
                    ? const SizedBox(
                        height: 20,
                        width: 20,
                        child: CircularProgressIndicator(strokeWidth: 2),
                      )
                    : Text(s.save),
              ),
            ],
          ),
        ),
      ),
    );
  }
}
