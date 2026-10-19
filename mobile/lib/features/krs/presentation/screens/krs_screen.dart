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
import '../../domain/krs_models.dart';
import '../../l10n/krs_strings.dart';
import '../providers.dart';
import '../widgets/krs_widgets.dart';

class KrsScreen extends ConsumerStatefulWidget {
  const KrsScreen({super.key});
  @override
  ConsumerState<KrsScreen> createState() => _KrsScreenState();
}

class _KrsScreenState extends ConsumerState<KrsScreen> {
  bool _busy = false;

  Future<void> _run(Future<void> Function() action, String success) async {
    if (_busy) return;
    setState(() => _busy = true);
    try {
      await action();
      if (mounted) showAppSnack(context, success);
    } catch (e) {
      if (mounted) showErrorSnack(context, e);
    } finally {
      if (mounted) setState(() => _busy = false);
    }
  }

  Future<void> _remove(KrsItem item) async {
    final k = context.krs;
    final ok = await confirmAction(
      context,
      title: k.removeTitle,
      message: k.removeBody(item.classSection.course.name),
      confirmLabel: k.remove,
      destructive: true,
    );
    if (!ok) return;
    await _run(
      () => ref.read(krsOverviewProvider.notifier).removeItem(item.id),
      k.removed,
    );
  }

  Future<void> _submit(KrsOverview o) async {
    final k = context.krs;
    final ok = await confirmAction(
      context,
      title: k.submitTitle,
      message: k.submitBody('${o.totalCredits}', o.items.length),
      confirmLabel: k.submit,
    );
    if (!ok) return;
    await _run(
      () => ref.read(krsOverviewProvider.notifier).submit(),
      k.submitted,
    );
  }

  Future<void> _cancel() async {
    final k = context.krs;
    final ok = await confirmAction(
      context,
      title: k.cancelTitle,
      message: k.cancelBody,
      confirmLabel: k.cancelSubmission,
    );
    if (!ok) return;
    await _run(
      () => ref.read(krsOverviewProvider.notifier).cancel(),
      k.cancelled,
    );
  }

  @override
  Widget build(BuildContext context) {
    final k = context.krs;
    final value = ref.watch(krsOverviewProvider);
    final overview = value.valueOrNull;
    return Scaffold(
      appBar: AppBar(
        title: Text(k.title),
        actions: [
          IconButton(
            tooltip: k.history,
            icon: const Icon(Icons.history),
            onPressed: () => context.push(RoutePaths.studentKrsHistory),
          ),
          if (overview?.term != null)
            IconButton(
              tooltip: k.pdfTooltip,
              icon: const Icon(Icons.picture_as_pdf_outlined),
              onPressed: overview!.items.isEmpty
                  ? null
                  : () => downloadAndOpen(
                      context,
                      ref,
                      path: '/student/documents/krs',
                      filename: 'krs.pdf',
                      query: {'academic_term_id': overview.term!.id},
                    ),
            ),
        ],
      ),
      body: AsyncValueView<KrsOverview>(
        value: value,
        onRetry: () => ref.invalidate(krsOverviewProvider),
        data: (o) => RefreshIndicator(
          onRefresh: () => ref.refresh(krsOverviewProvider.future),
          child: _body(context, o),
        ),
      ),
    );
  }

  Widget _body(BuildContext context, KrsOverview o) {
    final k = context.krs;
    final theme = Theme.of(context);
    if (o.term == null) {
      return ListView(
        physics: const AlwaysScrollableScrollPhysics(),
        children: [
          const SizedBox(height: 80),
          MessageView(
            icon: Icons.event_busy_outlined,
            title: k.noTermTitle,
            message: k.noTermBody,
          ),
        ],
      );
    }
    final term = o.term!;
    final tone = krsStatusTone(o.status);
    final sub = o.submission;
    final blocked = o.notices.any((n) => n.toLowerCase().contains('cekal'));
    final max = o.maxCredits;
    final progress = (max == null || max == 0)
        ? null
        : (o.totalCredits / max).clamp(0.0, 1.0);
    return ListView(
      physics: const AlwaysScrollableScrollPhysics(),
      padding: const EdgeInsets.all(AppSpacing.lg),
      children: [
        KrsConstrained(
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.stretch,
            children: [
              AppSurfaceCard(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Row(
                      children: [
                        Expanded(
                          child: Text(
                            term.label,
                            style: theme.textTheme.titleMedium,
                          ),
                        ),
                        StatusChip(
                          label: k.statusLabel(o.status, o.statusLabel),
                          tone: tone,
                        ),
                      ],
                    ),
                    const SizedBox(height: AppSpacing.sm),
                    InfoRow(
                      label: k.krsPeriod,
                      value:
                          term.krsStartDate == null && term.krsEndDate == null
                          ? k.periodUnset
                          : '${context.formatDate(term.krsStartDate)} – ${context.formatDate(term.krsEndDate)}',
                      trailing: StatusChip(
                        label: term.isKrsOpen ? k.periodOpen : k.periodClosed,
                        tone: term.isKrsOpen
                            ? StatusTone.success
                            : StatusTone.neutral,
                        dense: true,
                      ),
                    ),
                    if (o.academicAdvisor != null)
                      InfoRow(label: k.advisor, value: o.academicAdvisor!.name),
                    const SizedBox(height: AppSpacing.md),
                    Row(
                      children: [
                        Expanded(
                          child: Text(
                            k.creditsOf('${o.totalCredits}', max?.toString()),
                            style: theme.textTheme.titleSmall,
                          ),
                        ),
                        if (o.remainingCredits != null)
                          Text(
                            o.isOverLimit
                                ? k.overLimit
                                : k.creditsRemaining('${o.remainingCredits}'),
                            style: theme.textTheme.bodySmall,
                          ),
                      ],
                    ),
                    if (progress != null)
                      Padding(
                        padding: const EdgeInsets.only(top: AppSpacing.xs),
                        child: LinearProgressIndicator(
                          value: progress,
                          minHeight: 8,
                          borderRadius: BorderRadius.circular(AppRadius.full),
                          color: o.isOverLimit ? theme.colorScheme.error : null,
                        ),
                      ),
                    Padding(
                      padding: const EdgeInsets.only(top: AppSpacing.xs),
                      child: Text(
                        k.creditsLimitHint,
                        style: theme.textTheme.bodySmall?.copyWith(
                          color: theme.colorScheme.onSurfaceVariant,
                        ),
                      ),
                    ),
                  ],
                ),
              ),
              if (o.status == 'submitted')
                _gap(
                  AppBanner(message: k.submittedHint, tone: StatusTone.info),
                ),
              if (o.status == 'approved')
                _gap(
                  AppBanner(message: k.approvedHint, tone: StatusTone.success),
                ),
              if (o.status == 'rejected')
                _gap(
                  AppBanner(message: k.rejectedHint, tone: StatusTone.danger),
                ),
              if (sub != null &&
                  (sub.decisionNote != null || sub.decidedAt != null))
                _gap(_decisionCard(context, sub)),
              if (o.notices.isNotEmpty)
                _gap(
                  AppBanner(
                    title: k.notices,
                    message: o.notices.join('\n'),
                    tone: StatusTone.warning,
                    action: blocked
                        ? OutlinedButton(
                            onPressed: () =>
                                context.push(RoutePaths.studentClearance),
                            child: Text(k.blockedAction),
                          )
                        : null,
                  ),
                ),
              SectionHeader(title: k.selectedCourses),
              if (o.items.isEmpty)
                EmptyView(
                  icon: Icons.menu_book_outlined,
                  title: k.emptyItemsTitle,
                  message: k.emptyItemsBody,
                )
              else
                for (final item in o.items) _itemCard(context, o, item),
              const SizedBox(height: AppSpacing.md),
              if (o.canEdit)
                OutlinedButton.icon(
                  onPressed: _busy
                      ? null
                      : () => context.push(RoutePaths.studentKrsOfferings),
                  icon: const Icon(Icons.add),
                  label: Text(k.addCourses),
                ),
              if (o.canSubmit)
                Padding(
                  padding: const EdgeInsets.only(top: AppSpacing.sm),
                  child: FilledButton.icon(
                    onPressed: _busy ? null : () => _submit(o),
                    icon: const Icon(Icons.send_outlined),
                    label: Text(k.submit),
                  ),
                ),
              if (o.canCancelSubmission)
                Padding(
                  padding: const EdgeInsets.only(top: AppSpacing.sm),
                  child: OutlinedButton.icon(
                    onPressed: _busy ? null : _cancel,
                    icon: const Icon(Icons.undo),
                    label: Text(k.cancelSubmission),
                  ),
                ),
              TextButton.icon(
                onPressed: () => context.push(RoutePaths.studentKrsHistory),
                icon: const Icon(Icons.history),
                label: Text(k.viewHistory),
              ),
            ],
          ),
        ),
      ],
    );
  }

  Widget _gap(Widget w) => Padding(
    padding: const EdgeInsets.only(top: AppSpacing.md),
    child: w,
  );

  Widget _decisionCard(BuildContext context, KrsSubmission sub) {
    final k = context.krs;
    return AppSurfaceCard(
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(k.decision, style: Theme.of(context).textTheme.titleSmall),
          if (sub.decidedBy != null)
            InfoRow(label: k.decidedBy, value: sub.decidedBy!),
          if (sub.decidedAt != null)
            InfoRow(
              label: k.decidedAt,
              value: context.formatDateTime(sub.decidedAt),
            ),
          if (sub.decisionNote != null && sub.decisionNote!.isNotEmpty)
            InfoRow(label: k.advisorNote, value: sub.decisionNote!),
        ],
      ),
    );
  }

  Widget _itemCard(BuildContext context, KrsOverview o, KrsItem item) {
    final k = context.krs;
    final s = item.classSection;
    final theme = Theme.of(context);
    final removable = o.canEdit && item.status == 'draft';
    return Padding(
      padding: const EdgeInsets.only(bottom: AppSpacing.sm),
      child: AppSurfaceCard(
        child: Row(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    s.course.name,
                    style: theme.textTheme.titleSmall,
                    maxLines: 2,
                    overflow: TextOverflow.ellipsis,
                  ),
                  const SizedBox(height: AppSpacing.xs),
                  Wrap(
                    spacing: AppSpacing.sm,
                    runSpacing: AppSpacing.xs,
                    crossAxisAlignment: WrapCrossAlignment.center,
                    children: [
                      Text(
                        '${s.course.code} · ${s.classCode} · ${s.course.credits} ${k.credits}',
                        style: theme.textTheme.bodySmall,
                      ),
                      StatusChip(
                        label: k.statusLabel(item.status, item.statusLabel),
                        tone: krsStatusTone(item.status),
                        dense: true,
                      ),
                    ],
                  ),
                  ClassMeta(section: s, noSchedule: k.noSchedule),
                ],
              ),
            ),
            if (removable)
              IconButton(
                tooltip: k.remove,
                icon: const Icon(Icons.delete_outline),
                color: theme.colorScheme.error,
                onPressed: _busy ? null : () => _remove(item),
              ),
          ],
        ),
      ),
    );
  }
}
