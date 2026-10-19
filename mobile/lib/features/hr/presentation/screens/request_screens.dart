import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import '../../../../core/l10n/core_strings.dart';
import '../../../../core/network/api_failure.dart';
import '../../../../core/permission/permission_gate.dart';
import '../../../../core/router/route_paths.dart';
import '../../../../core/theme/app_dimensions.dart';
import '../../../../core/utils/formatters.dart';
import '../../../../core/utils/validators.dart';
import '../../../../core/widgets/app_feedback.dart';
import '../../../../core/widgets/state_views.dart';
import '../../../../core/widgets/ui_kit.dart';
import '../../data/hr_api.dart';
import '../../domain/hr_models.dart';
import '../../l10n/hr_strings.dart';
import '../providers.dart';
import '../widgets/hr_common.dart';

/// GET /me/hr/requests with cancel.
class HrRequestsScreen extends ConsumerWidget {
  const HrRequestsScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final h = context.hrL10n;
    final value = ref.watch(hrRequestsProvider);
    final canCreate = PermissionGate.allows(ref, 'hr_self_service.create');
    return PermissionGate(
      permission: 'hr_self_service.read',
      child: Scaffold(
        appBar: AppBar(title: Text(h.requestsTitle)),
        floatingActionButton: canCreate
            ? FloatingActionButton.extended(
                onPressed: () => context.push(RoutePaths.hrRequestNew),
                icon: const Icon(Icons.add),
                label: Text(h.newRequest),
              )
            : null,
        body: value.when(
          skipLoadingOnRefresh: true,
          loading: () => const SkeletonList(),
          error: (e, _) => isNotLinked(e)
              ? NotLinkedView(
                  title: h.hrNotLinkedTitle,
                  body: h.hrNotLinkedBody,
                  onRetry: () => ref.invalidate(hrRequestsProvider),
                )
              : ErrorView(
                  error: e,
                  onRetry: () => ref.invalidate(hrRequestsProvider),
                ),
          data: (items) => RefreshIndicator(
            onRefresh: () => ref.refresh(hrRequestsProvider.future),
            child: HrBody(
              child: items.isEmpty
                  ? ListView(
                      physics: const AlwaysScrollableScrollPhysics(),
                      children: [
                        SizedBox(
                          height: 360,
                          child: EmptyView(
                            title: h.requestsEmptyTitle,
                            message: h.requestsEmptyBody,
                            icon: Icons.assignment_outlined,
                          ),
                        ),
                      ],
                    )
                  : ListView.separated(
                      physics: const AlwaysScrollableScrollPhysics(),
                      padding: const EdgeInsets.fromLTRB(
                        AppSpacing.lg,
                        AppSpacing.lg,
                        AppSpacing.lg,
                        96,
                      ),
                      itemCount: items.length,
                      separatorBuilder: (_, _) =>
                          const SizedBox(height: AppSpacing.sm),
                      itemBuilder: (_, i) => _RequestTile(item: items[i]),
                    ),
            ),
          ),
        ),
      ),
    );
  }
}

class _RequestTile extends ConsumerStatefulWidget {
  const _RequestTile({required this.item});
  final HrRequest item;

  @override
  ConsumerState<_RequestTile> createState() => _RequestTileState();
}

class _RequestTileState extends ConsumerState<_RequestTile> {
  bool _busy = false;

  Future<void> _cancel() async {
    final h = context.hrL10n;
    final ok = await confirmAction(
      context,
      title: h.cancelConfirm,
      confirmLabel: context.l10n.yes,
      destructive: true,
    );
    if (!ok || !mounted) return;
    setState(() => _busy = true);
    try {
      await ref.read(hrApiProvider).cancelRequest(widget.item.id);
      ref.invalidate(hrRequestsProvider);
      ref.invalidate(leaveRequestsProvider);
      if (mounted) showAppSnack(context, h.cancelled);
    } catch (e) {
      if (mounted) showErrorSnack(context, e);
    } finally {
      if (mounted) setState(() => _busy = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    final h = context.hrL10n;
    final it = widget.item;
    final text = Theme.of(context).textTheme;
    final scheme = Theme.of(context).colorScheme;
    final canUpdate = PermissionGate.allows(ref, 'hr_self_service.update');
    return AppSurfaceCard(
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              Expanded(
                child: Text(
                  it.title,
                  style: text.titleSmall,
                  maxLines: 2,
                  overflow: TextOverflow.ellipsis,
                ),
              ),
              const SizedBox(width: AppSpacing.sm),
              StatusChip(
                label: h.requestStatusLabel(it.status),
                tone: leaveStatusTone(it.status),
              ),
            ],
          ),
          const SizedBox(height: AppSpacing.xs),
          Text(
            '${h.requestTypeLabel(it.type)} · ${context.formatDate(it.createdAt)}',
            style: text.bodySmall?.copyWith(color: scheme.onSurfaceVariant),
          ),
          if (it.description != null)
            Padding(
              padding: const EdgeInsets.only(top: AppSpacing.xs),
              child: Text(
                it.description!,
                maxLines: 3,
                overflow: TextOverflow.ellipsis,
              ),
            ),
          if (it.approvalNote != null)
            Padding(
              padding: const EdgeInsets.only(top: AppSpacing.xs),
              child: Text(
                '${h.approvalNote}: ${it.approvalNote}',
                style: text.bodySmall,
              ),
            ),
          if (canUpdate && it.canCancel)
            Padding(
              padding: const EdgeInsets.only(top: AppSpacing.sm),
              child: OutlinedButton(
                onPressed: _busy ? null : _cancel,
                child: Text(h.cancelRequest),
              ),
            ),
        ],
      ),
    );
  }
}

/// POST /me/hr/requests.
class HrRequestFormScreen extends ConsumerStatefulWidget {
  const HrRequestFormScreen({super.key});

  @override
  ConsumerState<HrRequestFormScreen> createState() =>
      _HrRequestFormScreenState();
}

class _HrRequestFormScreenState extends ConsumerState<HrRequestFormScreen> {
  final _formKey = GlobalKey<FormState>();
  final _title = TextEditingController();
  final _description = TextEditingController();
  final _changes = {
    'name': TextEditingController(),
    'email': TextEditingController(),
    'phone': TextEditingController(),
    'address': TextEditingController(),
    'birth_place': TextEditingController(),
  };
  String _type = kHrRequestTypes.first;
  String? _fileId;
  bool _busy = false;
  bool _changesError = false;
  Map<String, String> _serverErrors = {};

  @override
  void dispose() {
    _title.dispose();
    _description.dispose();
    for (final c in _changes.values) {
      c.dispose();
    }
    super.dispose();
  }

  Future<void> _submit() async {
    final valid = _formKey.currentState!.validate();
    final changes = {
      for (final e in _changes.entries)
        if (e.value.text.trim().isNotEmpty) e.key: e.value.text.trim(),
    };
    final isChange = _type == 'data_change';
    setState(() {
      _changesError = isChange && changes.isEmpty;
      _serverErrors = {};
    });
    if (!valid || _changesError) return;
    setState(() => _busy = true);
    try {
      await ref
          .read(hrApiProvider)
          .createRequest(
            type: _type,
            title: _title.text.trim(),
            description: _description.text.trim(),
            attachmentFileId: _fileId,
            changes: isChange ? changes : null,
          );
      ref.invalidate(hrRequestsProvider);
      if (!mounted) return;
      showAppSnack(context, context.hrL10n.requestSent);
      context.pop();
    } catch (e) {
      if (!mounted) return;
      if (e is ApiFailure && e.kind == ApiFailureKind.validation) {
        setState(() {
          _serverErrors = {
            for (final k in ['title', 'description'])
              if (e.fieldError(k) != null) k: e.fieldError(k)!,
          };
        });
      }
      showErrorSnack(context, e);
    } finally {
      if (mounted) setState(() => _busy = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    final h = context.hrL10n;
    final s = context.l10n;
    final changeLabels = {
      'name': h.name,
      'email': h.email,
      'phone': h.phone,
      'address': h.address,
      'birth_place': h.birthPlace,
    };
    return PermissionGate(
      permission: 'hr_self_service.create',
      child: Scaffold(
        appBar: AppBar(title: Text(h.newRequest)),
        body: HrBody(
          child: Form(
            key: _formKey,
            child: ListView(
              padding: const EdgeInsets.all(AppSpacing.lg),
              children: [
                DropdownButtonFormField<String>(
                  initialValue: _type,
                  isExpanded: true,
                  decoration: InputDecoration(labelText: h.requestType),
                  items: [
                    for (final t in kHrRequestTypes)
                      DropdownMenuItem(
                        value: t,
                        child: Text(h.requestTypeLabel(t)),
                      ),
                  ],
                  onChanged: _busy ? null : (v) => setState(() => _type = v!),
                ),
                const SizedBox(height: AppSpacing.md),
                TextFormField(
                  controller: _title,
                  enabled: !_busy,
                  decoration: InputDecoration(
                    labelText: h.title,
                    errorText: _serverErrors['title'],
                  ),
                  validator: Validators.compose([
                    Validators.required(s),
                    Validators.maxLength(s, 255),
                  ]),
                ),
                const SizedBox(height: AppSpacing.md),
                TextFormField(
                  controller: _description,
                  enabled: !_busy,
                  maxLines: 4,
                  maxLength: 2000,
                  decoration: InputDecoration(
                    labelText: '${h.description} (${s.optional})',
                    errorText: _serverErrors['description'],
                  ),
                ),
                if (_type == 'data_change') ...[
                  const SizedBox(height: AppSpacing.sm),
                  SectionHeader(title: h.changesSection),
                  Text(
                    h.changesHint,
                    style: Theme.of(context).textTheme.bodySmall?.copyWith(
                      color: _changesError
                          ? Theme.of(context).colorScheme.error
                          : null,
                    ),
                  ),
                  for (final e in _changes.entries)
                    Padding(
                      padding: const EdgeInsets.only(top: AppSpacing.md),
                      child: TextFormField(
                        controller: e.value,
                        enabled: !_busy,
                        keyboardType: switch (e.key) {
                          'email' => TextInputType.emailAddress,
                          'phone' => TextInputType.phone,
                          _ => TextInputType.text,
                        },
                        decoration: InputDecoration(
                          labelText: changeLabels[e.key],
                        ),
                        validator: e.key == 'email'
                            ? Validators.email(s)
                            : null,
                      ),
                    ),
                ],
                const SizedBox(height: AppSpacing.md),
                AttachmentField(
                  upload: ref.read(hrApiProvider).upload,
                  fileId: _fileId,
                  onChanged: (v) => setState(() => _fileId = v),
                ),
                const SizedBox(height: AppSpacing.xl),
                FilledButton(
                  onPressed: _busy ? null : _submit,
                  child: Text(h.submitRequest),
                ),
              ],
            ),
          ),
        ),
      ),
    );
  }
}
