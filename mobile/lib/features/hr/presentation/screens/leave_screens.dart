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

/// GET /me/hr/leave-requests with submit / cancel actions.
class LeaveRequestsScreen extends ConsumerWidget {
  const LeaveRequestsScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final h = context.hrL10n;
    final value = ref.watch(leaveRequestsProvider);
    final quota = ref.watch(hrProfileProvider).valueOrNull?.quota;
    final canCreate = PermissionGate.allows(ref, 'hr_self_service.create');
    return PermissionGate(
      permission: 'hr_self_service.read',
      child: Scaffold(
        appBar: AppBar(title: Text(h.leaveTitle)),
        floatingActionButton: canCreate
            ? FloatingActionButton.extended(
                onPressed: () => context.push(RoutePaths.hrLeaveRequestNew),
                icon: const Icon(Icons.add),
                label: Text(h.newLeave),
              )
            : null,
        body: value.when(
          skipLoadingOnRefresh: true,
          loading: () => const SkeletonList(),
          error: (e, _) => isNotLinked(e)
              ? NotLinkedView(
                  title: h.hrNotLinkedTitle,
                  body: h.hrNotLinkedBody,
                  onRetry: () => ref.invalidate(leaveRequestsProvider),
                )
              : ErrorView(
                  error: e,
                  onRetry: () => ref.invalidate(leaveRequestsProvider),
                ),
          data: (items) => RefreshIndicator(
            onRefresh: () async {
              ref.invalidate(hrProfileProvider);
              ref.invalidate(leaveRequestsProvider);
              await ref.read(leaveRequestsProvider.future);
            },
            child: HrBody(
              child: items.isEmpty
                  ? ListView(
                      physics: const AlwaysScrollableScrollPhysics(),
                      children: [
                        if (quota != null) _QuotaCard(quota: quota),
                        SizedBox(
                          height: 320,
                          child: EmptyView(
                            title: h.leaveEmptyTitle,
                            message: h.leaveEmptyBody,
                            icon: Icons.event_busy_outlined,
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
                      itemCount: items.length + 1,
                      separatorBuilder: (_, _) =>
                          const SizedBox(height: AppSpacing.sm),
                      itemBuilder: (context, i) => i == 0
                          ? (quota != null
                                ? _QuotaCard(quota: quota)
                                : const SizedBox.shrink())
                          : _LeaveTile(item: items[i - 1]),
                    ),
            ),
          ),
        ),
      ),
    );
  }
}

class _QuotaCard extends StatelessWidget {
  const _QuotaCard({required this.quota});
  final LeaveQuota quota;

  @override
  Widget build(BuildContext context) {
    final h = context.hrL10n;
    return Padding(
      padding: const EdgeInsets.all(AppSpacing.lg),
      child: AppBanner(
        title: h.leaveQuota,
        message:
            '${h.quotaLine(quota.year, quota.remaining, quota.quota)} · ${h.quotaUsed(quota.used)}\n${h.quotaNote}',
      ),
    );
  }
}

class _LeaveTile extends ConsumerStatefulWidget {
  const _LeaveTile({required this.item});
  final LeaveRequest item;

  @override
  ConsumerState<_LeaveTile> createState() => _LeaveTileState();
}

class _LeaveTileState extends ConsumerState<_LeaveTile> {
  bool _busy = false;

  Future<void> _run(Future<void> Function() action, String done) async {
    setState(() => _busy = true);
    try {
      await action();
      ref.invalidate(leaveRequestsProvider);
      ref.invalidate(hrProfileProvider);
      if (mounted) showAppSnack(context, done);
    } catch (e) {
      if (mounted) showErrorSnack(context, e);
    } finally {
      if (mounted) setState(() => _busy = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    final h = context.hrL10n;
    final s = context.l10n;
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
                  h.leaveTypeLabel(it.leaveType),
                  style: text.titleSmall,
                  maxLines: 1,
                  overflow: TextOverflow.ellipsis,
                ),
              ),
              const SizedBox(width: AppSpacing.sm),
              StatusChip(
                label: h.leaveStatusLabel(it.status),
                tone: leaveStatusTone(it.status),
              ),
            ],
          ),
          const SizedBox(height: AppSpacing.xs),
          Text(
            '${h.period(context.formatDate(it.startDate), context.formatDate(it.endDate))}'
            '${it.days == null ? '' : ' · ${h.days(it.days!)}'}',
            style: text.bodyMedium,
          ),
          if (it.reason != null)
            Padding(
              padding: const EdgeInsets.only(top: AppSpacing.xs),
              child: Text(
                it.reason!,
                maxLines: 3,
                overflow: TextOverflow.ellipsis,
                style: text.bodySmall?.copyWith(color: scheme.onSurfaceVariant),
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
          if (canUpdate && (it.canSubmit || it.canCancel))
            Padding(
              padding: const EdgeInsets.only(top: AppSpacing.sm),
              child: Wrap(
                spacing: AppSpacing.sm,
                children: [
                  if (it.canSubmit)
                    FilledButton(
                      onPressed: _busy
                          ? null
                          : () async {
                              final ok = await confirmAction(
                                context,
                                title: h.submitConfirm,
                              );
                              if (ok) {
                                await _run(
                                  () => ref
                                      .read(hrApiProvider)
                                      .submitLeave(it.id),
                                  h.submitted,
                                );
                              }
                            },
                      child: Text(h.submitDraft),
                    ),
                  if (it.canCancel)
                    OutlinedButton(
                      onPressed: _busy
                          ? null
                          : () async {
                              final ok = await confirmAction(
                                context,
                                title: h.cancelConfirm,
                                confirmLabel: s.yes,
                                destructive: true,
                              );
                              if (ok) {
                                await _run(
                                  () => ref
                                      .read(hrApiProvider)
                                      .cancelLeave(it.id),
                                  h.cancelled,
                                );
                              }
                            },
                      child: Text(h.cancelRequest),
                    ),
                ],
              ),
            ),
        ],
      ),
    );
  }
}

/// POST /me/hr/leave-requests (draft or submit), attachment via two-step upload.
class LeaveRequestFormScreen extends ConsumerStatefulWidget {
  const LeaveRequestFormScreen({super.key});

  @override
  ConsumerState<LeaveRequestFormScreen> createState() =>
      _LeaveRequestFormScreenState();
}

class _LeaveRequestFormScreenState
    extends ConsumerState<LeaveRequestFormScreen> {
  final _formKey = GlobalKey<FormState>();
  final _reason = TextEditingController();
  String _type = kLeaveTypes.first;
  DateTime? _start;
  DateTime? _end;
  String? _fileId;
  bool _busy = false;
  bool _dateError = false;
  bool _fileError = false;
  Map<String, String> _serverErrors = {};

  @override
  void dispose() {
    _reason.dispose();
    super.dispose();
  }

  Future<DateTime?> _pick(DateTime? initial) {
    final now = DateTime.now();
    return showDatePicker(
      context: context,
      initialDate: initial ?? now,
      firstDate: DateTime(now.year - 1),
      lastDate: DateTime(now.year + 2),
    );
  }

  Future<void> _submit(bool submit) async {
    final s = context.l10n;
    final valid = _formKey.currentState!.validate();
    final datesOk = _start != null && _end != null && !_end!.isBefore(_start!);
    final fileOk = !(submit && _type == 'sick' && _fileId == null);
    setState(() {
      _dateError = !datesOk;
      _fileError = !fileOk;
      _serverErrors = {};
    });
    if (!valid || !datesOk || !fileOk) return;
    setState(() => _busy = true);
    try {
      await ref
          .read(hrApiProvider)
          .createLeave(
            leaveType: _type,
            startDate: _start!,
            endDate: _end!,
            reason: _reason.text.trim(),
            attachmentFileId: _fileId,
            submit: submit,
          );
      ref.invalidate(leaveRequestsProvider);
      ref.invalidate(hrProfileProvider);
      if (!mounted) return;
      showAppSnack(
        context,
        submit ? context.hrL10n.submitted : s.savedSuccessfully,
      );
      context.pop();
    } catch (e) {
      if (!mounted) return;
      if (e is ApiFailure && e.kind == ApiFailureKind.validation) {
        setState(() {
          _serverErrors = {
            for (final k in ['start_date', 'end_date', 'reason'])
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
    final scheme = Theme.of(context).colorScheme;
    return PermissionGate(
      permission: 'hr_self_service.create',
      child: Scaffold(
        appBar: AppBar(title: Text(h.newLeave)),
        body: HrBody(
          child: Form(
            key: _formKey,
            child: ListView(
              padding: const EdgeInsets.all(AppSpacing.lg),
              children: [
                AppBanner(message: h.quotaNote),
                const SizedBox(height: AppSpacing.lg),
                DropdownButtonFormField<String>(
                  initialValue: _type,
                  isExpanded: true,
                  decoration: InputDecoration(labelText: h.leaveType),
                  items: [
                    for (final t in kLeaveTypes)
                      DropdownMenuItem(
                        value: t,
                        child: Text(h.leaveTypeLabel(t)),
                      ),
                  ],
                  onChanged: _busy ? null : (v) => setState(() => _type = v!),
                ),
                const SizedBox(height: AppSpacing.md),
                Row(
                  children: [
                    Expanded(
                      child: _DateField(
                        label: h.startDate,
                        value: _start,
                        enabled: !_busy,
                        error: _serverErrors['start_date'],
                        onTap: () async {
                          final d = await _pick(_start);
                          if (d != null) setState(() => _start = d);
                        },
                      ),
                    ),
                    const SizedBox(width: AppSpacing.md),
                    Expanded(
                      child: _DateField(
                        label: h.endDate,
                        value: _end,
                        enabled: !_busy,
                        error: _serverErrors['end_date'],
                        onTap: () async {
                          final d = await _pick(_end ?? _start);
                          if (d != null) setState(() => _end = d);
                        },
                      ),
                    ),
                  ],
                ),
                if (_dateError)
                  Padding(
                    padding: const EdgeInsets.only(top: AppSpacing.xs),
                    child: Text(
                      _start == null || _end == null
                          ? h.pickDate
                          : s.validationDateOrder,
                      style: Theme.of(
                        context,
                      ).textTheme.bodySmall?.copyWith(color: scheme.error),
                    ),
                  ),
                const SizedBox(height: AppSpacing.md),
                TextFormField(
                  controller: _reason,
                  enabled: !_busy,
                  maxLines: 4,
                  maxLength: 2000,
                  decoration: InputDecoration(
                    labelText: h.reason,
                    errorText: _serverErrors['reason'],
                  ),
                  validator: Validators.compose([
                    Validators.required(s),
                    Validators.maxLength(s, 2000),
                  ]),
                ),
                const SizedBox(height: AppSpacing.md),
                AttachmentField(
                  upload: ref.read(hrApiProvider).upload,
                  fileId: _fileId,
                  onChanged: (v) => setState(() {
                    _fileId = v;
                    _fileError = false;
                  }),
                  helper: _type == 'sick' ? h.attachmentSickRequired : null,
                  errorText: _fileError ? h.attachmentSickRequired : null,
                ),
                const SizedBox(height: AppSpacing.xl),
                FilledButton(
                  onPressed: _busy ? null : () => _submit(true),
                  child: Text(h.submitLeave),
                ),
                const SizedBox(height: AppSpacing.sm),
                OutlinedButton(
                  onPressed: _busy ? null : () => _submit(false),
                  child: Text(h.saveDraft),
                ),
              ],
            ),
          ),
        ),
      ),
    );
  }
}

class _DateField extends StatelessWidget {
  const _DateField({
    required this.label,
    required this.value,
    required this.onTap,
    required this.enabled,
    this.error,
  });
  final String label;
  final DateTime? value;
  final VoidCallback onTap;
  final bool enabled;
  final String? error;

  @override
  Widget build(BuildContext context) => InkWell(
    onTap: enabled ? onTap : null,
    child: InputDecorator(
      decoration: InputDecoration(
        labelText: label,
        errorText: error,
        suffixIcon: const Icon(Icons.calendar_today_outlined, size: 18),
      ),
      child: Text(
        value == null ? '—' : context.formatDate(value),
        maxLines: 1,
        overflow: TextOverflow.ellipsis,
      ),
    ),
  );
}
