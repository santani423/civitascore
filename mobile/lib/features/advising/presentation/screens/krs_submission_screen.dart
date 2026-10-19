import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import '../../../../core/l10n/core_strings.dart';
import '../../../../core/permission/permission_gate.dart';
import '../../../../core/router/route_paths.dart';
import '../../../../core/theme/app_dimensions.dart';
import '../../../../core/utils/formatters.dart';
import '../../../../core/widgets/app_feedback.dart';
import '../../../../core/widgets/state_views.dart';
import '../../../../core/widgets/ui_kit.dart';
import '../../data/advising_api.dart';
import '../../domain/krs_submission.dart';
import '../../l10n/advising_strings.dart';
import '../providers.dart';

class KrsSubmissionScreen extends ConsumerStatefulWidget {
  const KrsSubmissionScreen({super.key, required this.id});
  final String id;

  @override
  ConsumerState<KrsSubmissionScreen> createState() => _State();
}

class _State extends ConsumerState<KrsSubmissionScreen> {
  bool _busy = false;

  Future<void> _decide(bool approve) async {
    final s = context.advisingL10n;
    String? note;
    if (approve) {
      final ok = await confirmAction(
        context,
        title: s.approveConfirmTitle,
        message: s.approveConfirmBody,
        confirmLabel: s.approve,
      );
      if (!ok) return;
    } else {
      note = await showDialog<String>(
        context: context,
        builder: (_) => const _RejectDialog(),
      );
      if (note == null) return;
    }
    if (!mounted) return;
    setState(() => _busy = true);
    try {
      final api = ref.read(advisingApiProvider);
      final msg = approve
          ? await api.approve(widget.id)
          : await api.reject(widget.id, note: note!);
      ref.invalidate(krsDetailProvider(widget.id));
      ref.invalidate(krsListProvider);
      if (mounted) {
        showAppSnack(context, msg ?? (approve ? s.approved : s.rejected));
      }
    } catch (e) {
      if (mounted) showErrorSnack(context, e);
    } finally {
      if (mounted) setState(() => _busy = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    final s = context.advisingL10n;
    final value = ref.watch(krsDetailProvider(widget.id));
    final canDecide = PermissionGate.allows(
      ref,
      'krs_advising.approve,krs.approve',
    );
    return Scaffold(
      appBar: AppBar(title: Text(s.detailTitle)),
      body: AsyncValueView<KrsSubmission>(
        value: value,
        onRetry: () => ref.invalidate(krsDetailProvider(widget.id)),
        data: (k) => RefreshIndicator(
          onRefresh: () => ref.refresh(krsDetailProvider(widget.id).future),
          child: Center(
            child: ConstrainedBox(
              constraints: const BoxConstraints(maxWidth: 720),
              child: ListView(
                physics: const AlwaysScrollableScrollPhysics(),
                padding: const EdgeInsets.all(AppSpacing.lg),
                children: [
                  _header(context, k),
                  if (k.overLimit)
                    Padding(
                      padding: const EdgeInsets.only(top: AppSpacing.md),
                      child: AppBanner(
                        message: s.overLimit,
                        tone: StatusTone.warning,
                      ),
                    ),
                  SectionHeader(
                    title: s.courses,
                    padding: const EdgeInsets.only(
                      top: AppSpacing.xl,
                      bottom: AppSpacing.sm,
                    ),
                  ),
                  if (k.items.isEmpty)
                    Text(s.noItems)
                  else
                    for (final it in k.items) _item(context, it),
                  if (k.decisionNote != null || k.decidedAt != null)
                    _decision(context, k),
                  if (k.student != null)
                    Padding(
                      padding: const EdgeInsets.only(top: AppSpacing.lg),
                      child: OutlinedButton.icon(
                        icon: const Icon(Icons.school_outlined),
                        label: Text(s.openAcademicRecord),
                        onPressed: () => context.push(
                          RoutePaths.lecturerStudent(k.student!.id),
                        ),
                      ),
                    ),
                ],
              ),
            ),
          ),
        ),
      ),
      bottomNavigationBar: value.valueOrNull?.isSubmitted == true && canDecide
          ? SafeArea(
              child: Padding(
                padding: const EdgeInsets.all(AppSpacing.lg),
                child: Row(
                  children: [
                    Expanded(
                      child: OutlinedButton(
                        onPressed: _busy ? null : () => _decide(false),
                        child: Text(s.reject, textAlign: TextAlign.center),
                      ),
                    ),
                    const SizedBox(width: AppSpacing.md),
                    Expanded(
                      child: FilledButton(
                        onPressed: _busy ? null : () => _decide(true),
                        child: Text(s.approve),
                      ),
                    ),
                  ],
                ),
              ),
            )
          : null,
    );
  }

  Widget _header(BuildContext context, KrsSubmission k) {
    final s = context.advisingL10n;
    final st = k.student;
    return AppSurfaceCard(
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              Expanded(
                child: Text(
                  st?.name ?? '—',
                  style: Theme.of(context).textTheme.titleMedium,
                ),
              ),
              StatusChip(
                label: s.statusLabel(k.status, k.statusLabel),
                tone: s.statusTone(k.status),
              ),
            ],
          ),
          const SizedBox(height: AppSpacing.sm),
          if (st?.nim != null) InfoRow(label: s.nim, value: st!.nim!),
          if (st?.studyProgram != null)
            InfoRow(label: s.studyProgram, value: st!.studyProgram!),
          if (st?.admissionYear != null)
            InfoRow(label: s.admissionYear, value: '${st!.admissionYear}'),
          if (k.termLabel != null) InfoRow(label: s.term, value: k.termLabel!),
          InfoRow(
            label: s.totalCredits,
            value: s.creditsOfLimit(k.totalCredits, k.maxCredits),
          ),
          if (k.ips != null)
            InfoRow(label: s.ips, value: context.formatNumber(k.ips)),
          if (k.ipk != null)
            InfoRow(label: s.ipk, value: context.formatNumber(k.ipk)),
          if (k.submittedAt != null)
            InfoRow(
              label: s.submittedAt,
              value: context.formatDateTime(k.submittedAt),
            ),
        ],
      ),
    );
  }

  Widget _item(BuildContext context, KrsItem it) {
    final s = context.advisingL10n;
    final l = context.l10n;
    final text = Theme.of(context).textTheme;
    final sched = it.schedules
        .map(
          (e) =>
              '${l.dayName(e.dayOfWeek)} ${context.formatClock(e.startTime)}–'
              '${context.formatClock(e.endTime)}'
              '${e.room == null ? '' : ' • ${e.room}'}',
        )
        .join('\n');
    return Padding(
      padding: const EdgeInsets.only(bottom: AppSpacing.sm),
      child: AppSurfaceCard(
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(it.courseName ?? '—', style: text.titleSmall),
            Text(
              [
                if (it.courseCode != null) it.courseCode!,
                if (it.classCode != null) it.classCode!,
                s.credits(it.credits ?? 0),
              ].join(' • '),
              style: text.bodySmall,
            ),
            if (it.lecturerName != null)
              Text('${s.lecturer}: ${it.lecturerName}', style: text.bodySmall),
            if (sched.isNotEmpty) Text(sched, style: text.bodySmall),
          ],
        ),
      ),
    );
  }

  Widget _decision(BuildContext context, KrsSubmission k) {
    final s = context.advisingL10n;
    return Padding(
      padding: const EdgeInsets.only(top: AppSpacing.lg),
      child: AppSurfaceCard(
        child: Column(
          children: [
            if (k.decidedAt != null)
              InfoRow(
                label: s.decidedAt,
                value: context.formatDateTime(k.decidedAt),
              ),
            if (k.decidedBy != null)
              InfoRow(label: s.decidedBy, value: k.decidedBy!),
            if (k.decisionNote != null)
              InfoRow(label: s.decisionNote, value: k.decisionNote!),
          ],
        ),
      ),
    );
  }
}

/// Local note dialog (mandatory, min 5 characters like the backend rule).
class _RejectDialog extends StatefulWidget {
  const _RejectDialog();

  @override
  State<_RejectDialog> createState() => _RejectDialogState();
}

class _RejectDialogState extends State<_RejectDialog> {
  final _controller = TextEditingController();
  final _formKey = GlobalKey<FormState>();

  @override
  void dispose() {
    _controller.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final s = context.advisingL10n;
    final l = context.l10n;
    return AlertDialog(
      title: Text(s.rejectTitle),
      content: Form(
        key: _formKey,
        child: TextFormField(
          controller: _controller,
          autofocus: true,
          maxLines: 4,
          maxLength: 1000,
          decoration: InputDecoration(labelText: s.rejectLabel),
          validator: (v) {
            final t = v?.trim() ?? '';
            if (t.isEmpty) return l.validationRequired;
            if (t.length < 5) return s.rejectLabel;
            return null;
          },
        ),
      ),
      actions: [
        TextButton(
          onPressed: () => Navigator.pop(context),
          child: Text(l.cancel),
        ),
        FilledButton(
          onPressed: () {
            if (_formKey.currentState!.validate()) {
              Navigator.pop(context, _controller.text.trim());
            }
          },
          child: Text(s.reject),
        ),
      ],
    );
  }
}
