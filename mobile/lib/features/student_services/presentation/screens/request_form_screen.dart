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
import '../../data/student_services_api.dart';
import '../../domain/student_request.dart';
import '../../l10n/student_services_strings.dart';
import '../providers.dart';
import '../widgets/request_widgets.dart';

/// Edit route: loads the draft first, then shows the form.
class RequestEditScreen extends ConsumerWidget {
  const RequestEditScreen({super.key, required this.requestId});
  final String requestId;

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final value = ref.watch(studentRequestDetailProvider(requestId));
    return AsyncValueView<StudentRequest>(
      value: value,
      onRetry: () => ref.invalidate(studentRequestDetailProvider(requestId)),
      loading: Scaffold(
        appBar: AppBar(title: Text(context.sv.formTitleEdit)),
        body: const SkeletonList(),
      ),
      data: (r) => RequestFormScreen(initial: r),
    );
  }
}

class RequestFormScreen extends ConsumerStatefulWidget {
  const RequestFormScreen({super.key, this.initial});

  /// Existing draft to edit; null creates a new request.
  final StudentRequest? initial;

  @override
  ConsumerState<RequestFormScreen> createState() => _FormState();
}

class _FormState extends ConsumerState<RequestFormScreen> {
  final _formKey = GlobalKey<FormState>();
  final _title = TextEditingController();
  final _description = TextEditingController();
  final _purpose = TextEditingController();
  final _changeCtrls = <String, TextEditingController>{};
  final _selectedChanges = <String>{};

  RequestType _type = RequestType.leave;
  String? _letterType;
  String? _gender;
  DateTime? _birthDate;

  RequestAttachment? _existingAttachment;
  String? _newAttachmentId;
  String? _newAttachmentName;
  bool _attachmentChanged = false;
  bool _uploading = false;
  bool _busy = false;
  bool _changesError = false;
  ApiFailure? _failure;

  bool get _editing => widget.initial != null;

  TextEditingController _ctrl(String key) =>
      _changeCtrls.putIfAbsent(key, TextEditingController.new);

  @override
  void initState() {
    super.initState();
    final r = widget.initial;
    if (r == null) return;
    _type = r.type ?? RequestType.other;
    _title.text = r.title;
    _description.text = r.description ?? '';
    _purpose.text = '${r.payload['purpose'] ?? ''}';
    _letterType = r.payload['letter_type'] as String?;
    for (final e in r.changes.entries) {
      _selectedChanges.add(e.key);
      switch (e.key) {
        case 'gender':
          _gender = '${e.value}';
        case 'tanggal_lahir':
          _birthDate = DateTime.tryParse('${e.value}');
        default:
          _ctrl(e.key).text = '${e.value}';
      }
    }
    _existingAttachment = r.attachment;
  }

  @override
  void dispose() {
    _title.dispose();
    _description.dispose();
    _purpose.dispose();
    for (final c in _changeCtrls.values) {
      c.dispose();
    }
    super.dispose();
  }

  String _isoDate(DateTime d) =>
      '${d.year.toString().padLeft(4, '0')}-${d.month.toString().padLeft(2, '0')}-${d.day.toString().padLeft(2, '0')}';

  Map<String, dynamic> _payload() {
    switch (_type) {
      case RequestType.dataChange:
        final changes = <String, dynamic>{};
        for (final k in _selectedChanges) {
          switch (k) {
            case 'gender':
              if (_gender != null) changes[k] = _gender;
            case 'tanggal_lahir':
              if (_birthDate != null) changes[k] = _isoDate(_birthDate!);
            default:
              final v = _ctrl(k).text.trim();
              if (v.isNotEmpty) changes[k] = v;
          }
        }
        return {'changes': changes};
      case RequestType.letter:
        return {'letter_type': _letterType, 'purpose': _purpose.text.trim()};
      case RequestType.leave:
      case RequestType.reactivation:
      case RequestType.other:
        return <String, dynamic>{};
    }
  }

  Future<void> _pickAttachment() async {
    final picked = await pickUpload(context);
    if (picked == null || !mounted) return;
    setState(() => _uploading = true);
    try {
      final id = await ref
          .read(studentServicesApiProvider)
          .uploadAttachment(picked.path, filename: picked.name);
      if (!mounted) return;
      setState(() {
        _newAttachmentId = id;
        _newAttachmentName = picked.name;
        _attachmentChanged = true;
      });
    } catch (e) {
      if (mounted) showErrorSnack(context, e);
    } finally {
      if (mounted) setState(() => _uploading = false);
    }
  }

  void _removeAttachment() => setState(() {
    _newAttachmentId = null;
    _newAttachmentName = null;
    _existingAttachment = null;
    _attachmentChanged = true;
  });

  Future<void> _save({required bool submit}) async {
    setState(() => _failure = null);
    final formOk = _formKey.currentState!.validate();
    var changesOk = true;
    if (_type == RequestType.dataChange) {
      changesOk =
          _payload()['changes'] is Map &&
          (_payload()['changes'] as Map).isNotEmpty;
      setState(() => _changesError = !changesOk);
    }
    if (!formOk || !changesOk) return;

    setState(() => _busy = true);
    final api = ref.read(studentServicesApiProvider);
    final sv = context.sv;
    final router = GoRouter.of(context);
    final body = <String, dynamic>{
      'title': _title.text.trim(),
      'description': _description.text.trim().isEmpty
          ? null
          : _description.text.trim(),
      'payload': _payload(),
      if (!_editing || _attachmentChanged)
        if (_newAttachmentId != null || _attachmentChanged)
          'attachment_file_id': _newAttachmentId,
    };
    StudentRequest saved;
    try {
      if (_editing) {
        saved = await api.update(widget.initial!.id, body);
      } else {
        saved = await api.create({'type': _type.value, ...body});
      }
    } catch (e) {
      if (!mounted) return;
      setState(() {
        _busy = false;
        if (e is ApiFailure) _failure = e;
      });
      showErrorSnack(context, e);
      return;
    }

    ref.invalidate(studentRequestsProvider);
    ref.invalidate(studentRequestDetailProvider(saved.id));

    var message = sv.draftSaved;
    if (submit) {
      try {
        await api.submit(saved.id);
        ref.invalidate(studentRequestDetailProvider(saved.id));
        message = sv.submittedOk;
      } catch (e) {
        if (!mounted) return;
        showAppSnack(
          context,
          '${sv.savedButNotSubmitted} ${context.l10n.errorFor(e)}',
          isError: true,
        );
        _leave(router, saved.id);
        return;
      }
    }
    if (!mounted) return;
    showAppSnack(context, message);
    _leave(router, saved.id);
  }

  void _leave(GoRouter router, String id) {
    if (_editing && router.canPop()) {
      router.pop();
    } else {
      router.pushReplacement(RoutePaths.studentRequest(id));
    }
  }

  String? _err(String field) => _failure?.fieldError(field);

  @override
  Widget build(BuildContext context) {
    final sv = context.sv;
    final s = context.l10n;
    final options = ref.watch(requestOptionsProvider);
    return Scaffold(
      appBar: AppBar(
        title: Text(_editing ? sv.formTitleEdit : sv.formTitleNew),
      ),
      body: AsyncValueView<RequestOptions>(
        value: options,
        onRetry: () => ref.invalidate(requestOptionsProvider),
        data: (opts) => ConstrainedBody(
          child: Form(
            key: _formKey,
            child: SingleChildScrollView(
              padding: const EdgeInsets.all(AppSpacing.lg),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.stretch,
                children: [
                  if (!_editing) ...[
                    DropdownButtonFormField<RequestType>(
                      initialValue: _type,
                      isExpanded: true,
                      decoration: InputDecoration(
                        labelText: sv.requestType,
                        errorText: _err('type'),
                      ),
                      items: [
                        for (final t in RequestType.values)
                          DropdownMenuItem(
                            value: t,
                            child: Row(
                              children: [
                                Icon(t.icon, size: 20),
                                const SizedBox(width: AppSpacing.sm),
                                Flexible(
                                  child: Text(
                                    sv.typeLabel(t.value, null),
                                    overflow: TextOverflow.ellipsis,
                                  ),
                                ),
                              ],
                            ),
                          ),
                      ],
                      onChanged: _busy
                          ? null
                          : (t) => setState(() {
                              _type = t ?? _type;
                              _changesError = false;
                            }),
                    ),
                  ] else
                    InfoRow(
                      label: sv.type,
                      value: sv.typeLabel(
                        _type.value,
                        widget.initial?.typeLabel,
                      ),
                    ),
                  const SizedBox(height: AppSpacing.sm),
                  AppBanner(message: sv.typeHint(_type)),
                  const SizedBox(height: AppSpacing.lg),
                  TextFormField(
                    controller: _title,
                    enabled: !_busy,
                    maxLength: 150,
                    textInputAction: TextInputAction.next,
                    decoration: InputDecoration(
                      labelText: sv.title,
                      errorText: _err('title'),
                    ),
                    validator: Validators.compose([
                      Validators.required(s),
                      (v) => (v != null && v.trim().length < 5)
                          ? sv.titleMin
                          : null,
                    ]),
                  ),
                  const SizedBox(height: AppSpacing.sm),
                  TextFormField(
                    controller: _description,
                    enabled: !_busy,
                    maxLength: 2000,
                    maxLines: 4,
                    minLines: 3,
                    decoration: InputDecoration(
                      labelText: '${sv.description} (${s.optional})',
                      alignLabelWithHint: true,
                      errorText: _err('description'),
                    ),
                  ),
                  if (_type == RequestType.letter) ..._letterFields(opts),
                  if (_type == RequestType.dataChange)
                    ..._dataChangeFields(opts),
                  const SizedBox(height: AppSpacing.lg),
                  _attachmentSection(),
                  const SizedBox(height: AppSpacing.xl2),
                  FilledButton.icon(
                    onPressed: (_busy || _uploading)
                        ? null
                        : () => _save(submit: true),
                    icon: const Icon(Icons.send_outlined),
                    label: Text(sv.submitRequest),
                  ),
                  const SizedBox(height: AppSpacing.sm),
                  OutlinedButton.icon(
                    onPressed: (_busy || _uploading)
                        ? null
                        : () => _save(submit: false),
                    icon: const Icon(Icons.save_outlined),
                    label: Text(sv.saveDraft),
                  ),
                  if (_busy) ...[
                    const SizedBox(height: AppSpacing.md),
                    const Center(child: CircularProgressIndicator()),
                  ],
                ],
              ),
            ),
          ),
        ),
      ),
    );
  }

  List<Widget> _letterFields(RequestOptions opts) {
    final sv = context.sv;
    final s = context.l10n;
    return [
      const SizedBox(height: AppSpacing.sm),
      DropdownButtonFormField<String>(
        initialValue: opts.letterTypes.any((o) => o.value == _letterType)
            ? _letterType
            : null,
        isExpanded: true,
        decoration: InputDecoration(
          labelText: sv.letterType,
          errorText: _err('payload.letter_type'),
        ),
        items: [
          for (final o in opts.letterTypes)
            DropdownMenuItem(
              value: o.value,
              child: Text(
                sv.letterTypeLabel(o.value, o.label),
                overflow: TextOverflow.ellipsis,
              ),
            ),
        ],
        validator: (v) => v == null ? s.validationRequired : null,
        onChanged: _busy ? null : (v) => setState(() => _letterType = v),
      ),
      const SizedBox(height: AppSpacing.md),
      TextFormField(
        controller: _purpose,
        enabled: !_busy,
        maxLength: 255,
        decoration: InputDecoration(
          labelText: sv.purpose,
          errorText: _err('payload.purpose'),
        ),
        validator: Validators.required(s),
      ),
    ];
  }

  List<Widget> _dataChangeFields(RequestOptions opts) {
    final sv = context.sv;
    final s = context.l10n;
    final fields = opts.dataChangeFields;
    final text = Theme.of(context).textTheme;
    return [
      const SizedBox(height: AppSpacing.lg),
      Text(sv.changesSection, style: text.titleSmall),
      if (_changesError || _err('payload.changes') != null)
        Padding(
          padding: const EdgeInsets.only(top: AppSpacing.xs),
          child: Text(
            _err('payload.changes') ?? sv.selectAtLeastOneChange,
            style: text.bodySmall?.copyWith(
              color: Theme.of(context).colorScheme.error,
            ),
          ),
        ),
      for (final f in fields) ...[
        CheckboxListTile(
          contentPadding: EdgeInsets.zero,
          controlAffinity: ListTileControlAffinity.leading,
          value: _selectedChanges.contains(f),
          title: Text(sv.fieldLabel(f)),
          onChanged: _busy
              ? null
              : (v) => setState(() {
                  v == true
                      ? _selectedChanges.add(f)
                      : _selectedChanges.remove(f);
                  _changesError = false;
                }),
        ),
        if (_selectedChanges.contains(f)) _changeInput(f, s),
      ],
    ];
  }

  Widget _changeInput(String key, CoreStrings s) {
    final sv = context.sv;
    final errKey = 'payload.changes.$key';
    switch (key) {
      case 'gender':
        return DropdownButtonFormField<String>(
          initialValue: _gender,
          decoration: InputDecoration(
            labelText: sv.fieldLabel(key),
            errorText: _err(errKey),
          ),
          items: [
            DropdownMenuItem(value: 'male', child: Text(sv.male)),
            DropdownMenuItem(value: 'female', child: Text(sv.female)),
          ],
          validator: (v) => v == null ? s.validationRequired : null,
          onChanged: _busy ? null : (v) => setState(() => _gender = v),
        );
      case 'tanggal_lahir':
        return FormField<DateTime>(
          initialValue: _birthDate,
          validator: (_) => _birthDate == null ? s.validationRequired : null,
          builder: (state) => InkWell(
            onTap: _busy
                ? null
                : () async {
                    final now = DateTime.now();
                    final d = await showDatePicker(
                      context: context,
                      initialDate:
                          _birthDate ??
                          DateTime(now.year - 20, now.month, now.day),
                      firstDate: DateTime(1940),
                      lastDate: now.subtract(const Duration(days: 1)),
                      helpText: sv.pickDate,
                    );
                    if (d != null) {
                      setState(() => _birthDate = d);
                      state.didChange(d);
                    }
                  },
            child: InputDecorator(
              decoration: InputDecoration(
                labelText: sv.fieldLabel(key),
                suffixIcon: const Icon(Icons.calendar_today_outlined),
                errorText: state.errorText ?? _err(errKey),
              ),
              child: Text(
                _birthDate == null
                    ? sv.pickDate
                    : context.formatDate(_birthDate),
              ),
            ),
          ),
        );
      default:
        return TextFormField(
          controller: _ctrl(key),
          enabled: !_busy,
          keyboardType: key == 'email' ? TextInputType.emailAddress : null,
          decoration: InputDecoration(
            labelText: sv.fieldLabel(key),
            errorText: _err(errKey),
          ),
          validator: Validators.compose([
            Validators.required(s),
            if (key == 'email') Validators.email(s),
            if (key == 'name') Validators.minLength(s, 3),
          ]),
        );
    }
  }

  Widget _attachmentSection() {
    final sv = context.sv;
    final text = Theme.of(context).textTheme;
    final name = _newAttachmentName ?? _existingAttachment?.name;
    final has = _newAttachmentId != null || _existingAttachment != null;
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text(sv.attachment, style: text.titleSmall),
        const SizedBox(height: AppSpacing.xs),
        if (has)
          ListTile(
            contentPadding: EdgeInsets.zero,
            leading: const Icon(Icons.attach_file),
            title: Text(
              name ?? '',
              maxLines: 1,
              overflow: TextOverflow.ellipsis,
            ),
            trailing: IconButton(
              tooltip: sv.removeAttachment,
              icon: const Icon(Icons.close),
              onPressed: _busy ? null : _removeAttachment,
            ),
          ),
        if (_err('attachment_file_id') != null)
          Text(
            _err('attachment_file_id')!,
            style: text.bodySmall?.copyWith(
              color: Theme.of(context).colorScheme.error,
            ),
          ),
        OutlinedButton.icon(
          onPressed: (_busy || _uploading) ? null : _pickAttachment,
          icon: _uploading
              ? const SizedBox(
                  width: 16,
                  height: 16,
                  child: CircularProgressIndicator(strokeWidth: 2),
                )
              : const Icon(Icons.upload_file_outlined),
          label: Text(
            _uploading
                ? sv.uploading
                : (has ? sv.replaceAttachment : sv.addAttachment),
          ),
        ),
      ],
    );
  }
}
