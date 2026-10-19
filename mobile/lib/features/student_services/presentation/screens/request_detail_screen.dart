import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import '../../../../core/router/route_paths.dart';
import '../../../../core/theme/app_dimensions.dart';
import '../../../../core/utils/file_actions.dart';
import '../../../../core/utils/formatters.dart';
import '../../../../core/widgets/app_feedback.dart';
import '../../../../core/widgets/state_views.dart';
import '../../../../core/widgets/ui_kit.dart';
import '../../../auth/presentation/controllers/auth_controller.dart';
import '../../data/student_services_api.dart';
import '../../domain/student_request.dart';
import '../../l10n/student_services_strings.dart';
import '../providers.dart';
import '../widgets/request_widgets.dart';

class RequestDetailScreen extends ConsumerStatefulWidget {
  const RequestDetailScreen({super.key, required this.requestId});
  final String requestId;

  @override
  ConsumerState<RequestDetailScreen> createState() => _State();
}

class _State extends ConsumerState<RequestDetailScreen> {
  bool _busy = false;

  void _refreshAll() {
    ref.invalidate(studentRequestDetailProvider(widget.requestId));
    ref.invalidate(studentRequestsProvider);
  }

  Future<void> _run(
    Future<StudentRequest> Function(StudentServicesApi api) action,
    String successMessage,
  ) async {
    setState(() => _busy = true);
    try {
      await action(ref.read(studentServicesApiProvider));
      if (mounted) showAppSnack(context, successMessage);
    } catch (e) {
      if (mounted) showErrorSnack(context, e);
    } finally {
      _refreshAll();
      if (mounted) setState(() => _busy = false);
    }
  }

  Future<void> _cancel(StudentRequest r) async {
    final sv = context.sv;
    final ok = await confirmAction(
      context,
      title: sv.cancelConfirmTitle,
      message: sv.cancelConfirmBody,
      confirmLabel: sv.cancelConfirmAction,
      destructive: true,
    );
    if (!ok || !mounted) return;
    await _run((api) => api.cancel(r.id), sv.cancelled);
  }

  @override
  Widget build(BuildContext context) {
    final sv = context.sv;
    final value = ref.watch(studentRequestDetailProvider(widget.requestId));
    return Scaffold(
      appBar: AppBar(title: Text(sv.detailTitle)),
      body: AsyncValueView<StudentRequest>(
        value: value,
        onRetry: _refreshAll,
        data: (r) => RefreshIndicator(
          onRefresh: () async {
            _refreshAll();
            await ref.read(
              studentRequestDetailProvider(widget.requestId).future,
            );
          },
          child: ConstrainedBody(
            child: ListView(
              physics: const AlwaysScrollableScrollPhysics(),
              padding: const EdgeInsets.all(AppSpacing.lg),
              children: [
                _Header(request: r),
                const SizedBox(height: AppSpacing.lg),
                _Info(request: r),
                const SizedBox(height: AppSpacing.lg),
                _Timeline(request: r),
                const SizedBox(height: AppSpacing.lg),
                _Actions(
                  request: r,
                  busy: _busy,
                  onEdit: () =>
                      context.push('${RoutePaths.studentRequest(r.id)}/edit'),
                  onSubmit: () =>
                      _run((api) => api.submit(r.id), sv.submittedOk),
                  onCancel: () => _cancel(r),
                ),
              ],
            ),
          ),
        ),
      ),
    );
  }
}

class _Header extends StatelessWidget {
  const _Header({required this.request});
  final StudentRequest request;

  @override
  Widget build(BuildContext context) {
    final text = Theme.of(context).textTheme;
    final sv = context.sv;
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text(request.title, style: text.titleLarge),
        const SizedBox(height: AppSpacing.xs),
        Text(
          sv.typeLabel(request.typeValue, request.typeLabel),
          style: text.bodyMedium?.copyWith(color: context.palette.mutedText),
        ),
        const SizedBox(height: AppSpacing.sm),
        RequestStatusChip(request: request, dense: false),
      ],
    );
  }
}

class _Info extends StatelessWidget {
  const _Info({required this.request});
  final StudentRequest request;

  @override
  Widget build(BuildContext context) {
    final sv = context.sv;
    final r = request;
    final rows = <Widget>[
      if (r.termLabel != null) InfoRow(label: sv.term, value: r.termLabel!),
      if (r.letterTypeLabel != null || r.payload['letter_type'] != null)
        InfoRow(
          label: sv.letterType,
          value: sv.letterTypeLabel(
            '${r.payload['letter_type'] ?? ''}',
            r.letterTypeLabel,
          ),
        ),
      if (r.payload['purpose'] != null)
        InfoRow(label: sv.purpose, value: '${r.payload['purpose']}'),
      for (final e in r.changes.entries)
        InfoRow(
          label: sv.fieldLabel(e.key),
          value: e.key == 'gender'
              ? sv.genderLabel('${e.value}')
              : '${e.value}',
        ),
      if (r.description != null && r.description!.isNotEmpty)
        InfoRow(label: sv.description, value: r.description!),
      if (r.attachment != null)
        InfoRow(
          label: sv.attachment,
          value: r.attachment!.name ?? r.attachment!.id,
        ),
      InfoRow(label: sv.created, value: context.formatDateTime(r.createdAt)),
      if (r.submittedAt != null)
        InfoRow(
          label: sv.submitted,
          value: context.formatDateTime(r.submittedAt),
        ),
      if (r.isSubmitted && r.currentStepName != null)
        InfoRow(label: sv.currentStep, value: r.currentStepName!),
      if (r.decidedAt != null)
        InfoRow(
          label: sv.decidedAt,
          value: context.formatDateTime(r.decidedAt),
        ),
      if (r.reviewer != null) InfoRow(label: sv.reviewer, value: r.reviewer!),
      if (r.decisionNote != null && r.decisionNote!.isNotEmpty)
        InfoRow(label: sv.decisionNote, value: r.decisionNote!),
      if (r.cancelledAt != null)
        InfoRow(
          label: sv.cancelledAt,
          value: context.formatDateTime(r.cancelledAt),
        ),
    ];
    return AppSurfaceCard(
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(sv.details, style: Theme.of(context).textTheme.titleSmall),
          const SizedBox(height: AppSpacing.xs),
          ...rows,
        ],
      ),
    );
  }
}

class _Timeline extends StatelessWidget {
  const _Timeline({required this.request});
  final StudentRequest request;

  @override
  Widget build(BuildContext context) {
    final sv = context.sv;
    final text = Theme.of(context).textTheme;
    final scheme = Theme.of(context).colorScheme;
    final history = request.history;
    return AppSurfaceCard(
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(sv.timeline, style: text.titleSmall),
          const SizedBox(height: AppSpacing.sm),
          if (history.isEmpty)
            Text(
              sv.noHistory,
              style: text.bodyMedium?.copyWith(
                color: context.palette.mutedText,
              ),
            )
          else
            for (var i = 0; i < history.length; i++)
              IntrinsicHeight(
                child: Row(
                  crossAxisAlignment: CrossAxisAlignment.stretch,
                  children: [
                    SizedBox(
                      width: 20,
                      child: Column(
                        children: [
                          const SizedBox(height: 4),
                          Container(
                            width: 10,
                            height: 10,
                            decoration: BoxDecoration(
                              color: scheme.primary,
                              shape: BoxShape.circle,
                            ),
                          ),
                          if (i < history.length - 1)
                            Expanded(
                              child: Container(
                                width: 2,
                                color: scheme.outlineVariant,
                              ),
                            ),
                        ],
                      ),
                    ),
                    Expanded(
                      child: Padding(
                        padding: const EdgeInsets.only(bottom: AppSpacing.md),
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Text(
                              history[i].description?.isNotEmpty == true
                                  ? history[i].description!
                                  : sv.historyEvent(history[i].event),
                              style: text.bodyMedium,
                            ),
                            Text(
                              context.formatDateTime(history[i].createdAt),
                              style: text.bodySmall?.copyWith(
                                color: context.palette.mutedText,
                              ),
                            ),
                          ],
                        ),
                      ),
                    ),
                  ],
                ),
              ),
        ],
      ),
    );
  }
}

class _Actions extends ConsumerWidget {
  const _Actions({
    required this.request,
    required this.busy,
    required this.onEdit,
    required this.onSubmit,
    required this.onCancel,
  });
  final StudentRequest request;
  final bool busy;
  final VoidCallback onEdit;
  final VoidCallback onSubmit;
  final VoidCallback onCancel;

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final sv = context.sv;
    final user = ref.watch(currentUserProvider);
    final canUpdate = user?.hasPermission('student_requests.update') ?? false;
    final r = request;
    return Column(
      crossAxisAlignment: CrossAxisAlignment.stretch,
      children: [
        if (r.hasLetter)
          FilledButton.icon(
            onPressed: busy
                ? null
                : () => downloadAndOpen(
                    context,
                    ref,
                    path: '/student/requests/${r.id}/letter',
                    filename: 'surat-${r.id}.pdf',
                  ),
            icon: const Icon(Icons.picture_as_pdf_outlined),
            label: Text(sv.downloadLetter),
          ),
        if (canUpdate && r.isDraft) ...[
          const SizedBox(height: AppSpacing.sm),
          FilledButton.icon(
            onPressed: busy ? null : onSubmit,
            icon: const Icon(Icons.send_outlined),
            label: Text(sv.submitNow),
          ),
          const SizedBox(height: AppSpacing.sm),
          OutlinedButton.icon(
            onPressed: busy ? null : onEdit,
            icon: const Icon(Icons.edit_outlined),
            label: Text(sv.editDraft),
          ),
        ],
        if (canUpdate && r.canCancel) ...[
          const SizedBox(height: AppSpacing.sm),
          OutlinedButton.icon(
            style: OutlinedButton.styleFrom(
              foregroundColor: Theme.of(context).colorScheme.error,
            ),
            onPressed: busy ? null : onCancel,
            icon: const Icon(Icons.close),
            label: Text(sv.cancelRequest),
          ),
        ],
        if (r.status == RequestStatus.rejected) ...[
          const SizedBox(height: AppSpacing.md),
          AppBanner(
            tone: StatusTone.info,
            title: sv.resubmitPendingTitle,
            message: sv.resubmitPendingBody,
          ),
        ],
        if (busy) ...[
          const SizedBox(height: AppSpacing.md),
          const Center(child: CircularProgressIndicator()),
        ],
      ],
    );
  }
}
