import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:url_launcher/url_launcher.dart';

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

IconData _typeIcon(String type) => switch (type) {
  'file' => Icons.insert_drive_file_outlined,
  'link' => Icons.link,
  'video' => Icons.play_circle_outline,
  _ => Icons.notes_outlined,
};

class ClassMaterialsScreen extends ConsumerWidget {
  const ClassMaterialsScreen({super.key, required this.classSectionId});
  final String classSectionId;

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final t = context.tr;
    final s = context.l10n;
    final user = ref.watch(currentUserProvider);
    bool can(String p) => user?.hasPermission(p) ?? false;
    final materials = ref.watch(materialsProvider(classSectionId));
    Future<void> refresh() async {
      ref.invalidate(materialsProvider(classSectionId));
      try {
        await ref.read(materialsProvider(classSectionId).future);
      } catch (_) {}
    }

    Future<void> openForm([CourseMaterial? m]) async {
      final saved = await Navigator.of(context).push<bool>(
        MaterialPageRoute(
          fullscreenDialog: true,
          builder: (_) =>
              MaterialFormScreen(classSectionId: classSectionId, material: m),
        ),
      );
      if (saved == true) await refresh();
    }

    Future<void> togglePublish(CourseMaterial m) async {
      try {
        await ref.read(teachingApiProvider).updateMaterial(m.id, {
          'is_published': !m.isPublished,
        });
        await refresh();
        if (context.mounted) {
          showAppSnack(
            context,
            m.isPublished ? t.unpublishedNow : t.publishedNow,
          );
        }
      } catch (e) {
        if (context.mounted) showErrorSnack(context, e);
      }
    }

    Future<void> delete(CourseMaterial m) async {
      final ok = await confirmAction(
        context,
        title: t.deleteMaterialTitle,
        message: t.deleteMaterialBody,
        confirmLabel: s.delete,
        destructive: true,
      );
      if (!ok) return;
      try {
        await ref.read(teachingApiProvider).deleteMaterial(m.id);
        await refresh();
        if (context.mounted) showAppSnack(context, t.materialDeleted);
      } catch (e) {
        if (context.mounted) showErrorSnack(context, e);
      }
    }

    Future<void> open(CourseMaterial m) async {
      if (m.file != null) {
        await downloadAndOpen(
          context,
          ref,
          path: '/file-uploads/${m.file!.id}/download',
          filename: m.file!.name,
        );
      } else if (m.url != null) {
        final uri = Uri.tryParse(m.url!);
        final ok =
            uri != null &&
            await launchUrl(uri, mode: LaunchMode.externalApplication);
        if (!ok && context.mounted) {
          showAppSnack(context, t.cannotOpenLink, isError: true);
        }
      }
    }

    return Scaffold(
      appBar: AppBar(title: Text(t.materialsTitle)),
      floatingActionButton: can('course_materials.create')
          ? FloatingActionButton.extended(
              onPressed: () => openForm(),
              icon: const Icon(Icons.add),
              label: Text(t.addMaterial),
            )
          : null,
      body: WideBody(
        child: AsyncValueView<List<CourseMaterial>>(
          value: materials,
          onRetry: refresh,
          isEmpty: (l) => l.isEmpty,
          empty: EmptyView(
            icon: Icons.menu_book_outlined,
            title: t.noMaterialsTitle,
            message: t.noMaterialsBody,
          ),
          data: (list) => RefreshIndicator(
            onRefresh: refresh,
            child: ListView.separated(
              physics: const AlwaysScrollableScrollPhysics(),
              padding: const EdgeInsets.fromLTRB(
                AppSpacing.lg,
                AppSpacing.md,
                AppSpacing.lg,
                96,
              ),
              itemCount: list.length,
              separatorBuilder: (_, _) => const SizedBox(height: AppSpacing.sm),
              itemBuilder: (context, i) {
                final m = list[i];
                return AppSurfaceCard(
                  onTap: () => open(m),
                  padding: const EdgeInsets.fromLTRB(
                    AppSpacing.lg,
                    AppSpacing.md,
                    AppSpacing.xs,
                    AppSpacing.md,
                  ),
                  child: Row(
                    children: [
                      Icon(_typeIcon(m.type)),
                      const SizedBox(width: AppSpacing.md),
                      Expanded(
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Text(
                              m.title,
                              style: Theme.of(context).textTheme.titleSmall,
                              maxLines: 2,
                              overflow: TextOverflow.ellipsis,
                            ),
                            const SizedBox(height: 4),
                            Wrap(
                              spacing: AppSpacing.sm,
                              runSpacing: 4,
                              children: [
                                StatusChip(
                                  label: m.meetingNumber == null
                                      ? t.typeLabel(m.type, m.typeLabel)
                                      : '${t.meeting(m.meetingNumber!)} · ${t.typeLabel(m.type, m.typeLabel)}',
                                  dense: true,
                                ),
                                StatusChip(
                                  label: m.isPublished ? t.published : t.draft,
                                  tone: m.isPublished
                                      ? StatusTone.success
                                      : StatusTone.warning,
                                  dense: true,
                                ),
                              ],
                            ),
                            if (m.file != null)
                              Padding(
                                padding: const EdgeInsets.only(top: 4),
                                child: Text(
                                  '${m.file!.name} · ${formatBytes(m.file!.sizeBytes)}',
                                  style: Theme.of(context).textTheme.bodySmall,
                                  maxLines: 1,
                                  overflow: TextOverflow.ellipsis,
                                ),
                              ),
                          ],
                        ),
                      ),
                      if (can('course_materials.update') ||
                          can('course_materials.delete'))
                        PopupMenuButton<String>(
                          tooltip: s.edit,
                          onSelected: (v) {
                            switch (v) {
                              case 'edit':
                                openForm(m);
                              case 'publish':
                                togglePublish(m);
                              case 'delete':
                                delete(m);
                            }
                          },
                          itemBuilder: (_) => [
                            if (can('course_materials.update')) ...[
                              PopupMenuItem(value: 'edit', child: Text(s.edit)),
                              PopupMenuItem(
                                value: 'publish',
                                child: Text(
                                  m.isPublished ? t.unpublish : t.publish,
                                ),
                              ),
                            ],
                            if (can('course_materials.delete'))
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

class MaterialFormScreen extends ConsumerStatefulWidget {
  const MaterialFormScreen({
    super.key,
    required this.classSectionId,
    this.material,
  });
  final String classSectionId;
  final CourseMaterial? material;

  @override
  ConsumerState<MaterialFormScreen> createState() => _MaterialFormState();
}

class _MaterialFormState extends ConsumerState<MaterialFormScreen> {
  final _formKey = GlobalKey<FormState>();
  late final _title = TextEditingController(text: widget.material?.title);
  late final _meeting = TextEditingController(
    text: widget.material?.meetingNumber?.toString() ?? '',
  );
  late final _description = TextEditingController(
    text: widget.material?.description ?? '',
  );
  late final _url = TextEditingController(text: widget.material?.url ?? '');
  late String _type = widget.material?.type ?? 'file';
  late bool _published = widget.material?.isPublished ?? true;
  PickedUpload? _picked;
  String? _existingFileName;
  bool _busy = false;
  bool _fileMissing = false;
  Map<String, String> _serverErrors = {};

  @override
  void initState() {
    super.initState();
    _existingFileName = widget.material?.file?.name;
  }

  @override
  void dispose() {
    _title.dispose();
    _meeting.dispose();
    _description.dispose();
    _url.dispose();
    super.dispose();
  }

  String? _urlValidator(String? v) {
    final s = context.l10n;
    if (v == null || v.trim().isEmpty) return s.validationRequired;
    final u = Uri.tryParse(v.trim());
    if (u == null || !(u.scheme == 'http' || u.scheme == 'https')) {
      return context.tr.invalidUrl;
    }
    return null;
  }

  Future<void> _pick() async {
    final p = await pickUpload(context, allowCamera: false);
    if (p != null) {
      setState(() {
        _picked = p;
        _fileMissing = false;
      });
    }
  }

  Future<void> _submit() async {
    final t = context.tr;
    setState(() {
      _serverErrors = {};
      _fileMissing = false;
    });
    if (!_formKey.currentState!.validate()) return;
    if (_type == 'file' && _picked == null && _existingFileName == null) {
      setState(() => _fileMissing = true);
      return;
    }
    setState(() => _busy = true);
    final api = ref.read(teachingApiProvider);
    try {
      String? fileId;
      if (_type == 'file' && _picked != null) {
        fileId = await api.uploadFile(_picked!.path);
      }
      final meeting = int.tryParse(_meeting.text.trim());
      final desc = _description.text.trim();
      final body = <String, dynamic>{
        'title': _title.text.trim(),
        'type': _type,
        'meeting_number': meeting,
        'description': desc.isEmpty ? null : desc,
        'is_published': _published,
        if (_type == 'link' || _type == 'video') 'url': _url.text.trim(),
        'file_upload_id': ?fileId,
      };
      if (widget.material == null) {
        await api.createMaterial(widget.classSectionId, body);
      } else {
        await api.updateMaterial(widget.material!.id, body);
      }
      if (!mounted) return;
      showAppSnack(context, t.materialSaved);
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
    final editing = widget.material != null;
    return Scaffold(
      appBar: AppBar(title: Text(editing ? t.editMaterial : t.addMaterial)),
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
              const SizedBox(height: AppSpacing.sm),
              DropdownButtonFormField<String>(
                initialValue: _type,
                decoration: InputDecoration(labelText: t.fieldType),
                items: [
                  for (final k in const ['file', 'link', 'video', 'text'])
                    DropdownMenuItem(
                      value: k,
                      child: Text(t.typeLabel(k, null)),
                    ),
                ],
                onChanged: _busy
                    ? null
                    : (v) => setState(() => _type = v ?? _type),
              ),
              const SizedBox(height: AppSpacing.md),
              TextFormField(
                controller: _meeting,
                keyboardType: TextInputType.number,
                decoration: InputDecoration(
                  labelText: '${t.fieldMeeting} · ${s.optional}',
                  errorText: _serverErrors['meeting_number'],
                ),
                validator: Validators.numberInRange(s, 1, 32),
              ),
              const SizedBox(height: AppSpacing.md),
              if (_type == 'link' || _type == 'video') ...[
                TextFormField(
                  controller: _url,
                  keyboardType: TextInputType.url,
                  decoration: InputDecoration(
                    labelText: t.fieldUrl,
                    errorText: _serverErrors['url'],
                  ),
                  validator: _urlValidator,
                ),
                const SizedBox(height: AppSpacing.md),
              ],
              if (_type == 'file') ...[
                OutlinedButton.icon(
                  onPressed: _busy ? null : _pick,
                  icon: const Icon(Icons.attach_file),
                  label: Text(
                    (_picked != null || _existingFileName != null)
                        ? t.replaceFile
                        : t.pickFile,
                  ),
                ),
                if (_picked != null || _existingFileName != null)
                  Padding(
                    padding: const EdgeInsets.only(top: AppSpacing.sm),
                    child: Text(
                      _picked?.name ?? _existingFileName!,
                      maxLines: 2,
                      overflow: TextOverflow.ellipsis,
                    ),
                  ),
                if (_fileMissing || _serverErrors['file_upload_id'] != null)
                  Padding(
                    padding: const EdgeInsets.only(top: AppSpacing.xs),
                    child: Text(
                      _serverErrors['file_upload_id'] ?? t.fileRequired,
                      style: TextStyle(
                        color: Theme.of(context).colorScheme.error,
                      ),
                    ),
                  ),
                const SizedBox(height: AppSpacing.md),
              ],
              TextFormField(
                controller: _description,
                maxLines: _type == 'text' ? 8 : 3,
                maxLength: 10000,
                decoration: InputDecoration(
                  labelText: _type == 'text'
                      ? t.fieldText
                      : '${t.fieldDescription} · ${s.optional}',
                  alignLabelWithHint: true,
                  errorText: _serverErrors['description'],
                ),
                validator: _type == 'text' ? Validators.required(s) : null,
              ),
              SwitchListTile(
                contentPadding: EdgeInsets.zero,
                title: Text(t.fieldPublished),
                value: _published,
                onChanged: _busy ? null : (v) => setState(() => _published = v),
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
                    : Text(s.save),
              ),
            ],
          ),
        ),
      ),
    );
  }
}
