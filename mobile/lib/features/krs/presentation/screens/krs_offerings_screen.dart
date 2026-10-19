import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import '../../../../core/l10n/core_strings.dart';
import '../../../../core/network/api_failure.dart';
import '../../../../core/router/route_paths.dart';
import '../../../../core/theme/app_dimensions.dart';
import '../../../../core/widgets/app_feedback.dart';
import '../../../../core/widgets/state_views.dart';
import '../../../../core/widgets/ui_kit.dart';
import '../../domain/krs_models.dart';
import '../../l10n/krs_strings.dart';
import '../providers.dart';
import '../widgets/krs_widgets.dart';

enum _Filter { all, available, selected }

class KrsOfferingsScreen extends ConsumerStatefulWidget {
  const KrsOfferingsScreen({super.key});
  @override
  ConsumerState<KrsOfferingsScreen> createState() => _KrsOfferingsScreenState();
}

class _KrsOfferingsScreenState extends ConsumerState<KrsOfferingsScreen> {
  String _query = '';
  _Filter _filter = _Filter.all;
  String? _addingId;
  String? _blockedMessage;

  Future<void> _add(KrsOfferingClass c) async {
    if (_addingId != null) return;
    final k = context.krs;
    setState(() {
      _addingId = c.section.id;
      _blockedMessage = null;
    });
    try {
      await ref.read(krsOverviewProvider.notifier).addItem(c.section.id);
      if (mounted) showAppSnack(context, k.added);
    } catch (e) {
      if (!mounted) return;
      setState(() => _addingId = null);
      if (e is ApiFailure && e.code == 'ACADEMIC_BLOCKED') {
        setState(() => _blockedMessage = e.message ?? k.blockedTitle);
      } else if (e is ApiFailure && e.code == 'SCHEDULE_CONFLICT') {
        await _conflictDialog(e);
      } else if (e is ApiFailure && e.kind == ApiFailureKind.conflict) {
        showAppSnack(
          context,
          e.message ?? context.l10n.errorFor(e),
          isError: true,
        );
      } else {
        showErrorSnack(context, e);
      }
    } finally {
      if (mounted) setState(() => _addingId = null);
    }
  }

  Future<void> _conflictDialog(ApiFailure e) {
    final k = context.krs;
    final raw = e.details?['conflicts'];
    final lines = <String>[
      if (raw is List)
        for (final c in raw)
          if (c is Map)
            '${c['course_name'] ?? ''} — ${c['schedule'] ?? ''}'
          else
            '$c',
    ];
    return showDialog<void>(
      context: context,
      builder: (ctx) => AlertDialog(
        title: Text(k.conflictTitle),
        content: Column(
          mainAxisSize: MainAxisSize.min,
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(e.message ?? k.scheduleConflict),
            if (lines.isNotEmpty) ...[
              const SizedBox(height: AppSpacing.sm),
              Text(k.conflictWith),
              for (final l in lines) Text('• $l'),
            ],
          ],
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(ctx),
            child: Text(context.l10n.close),
          ),
        ],
      ),
    );
  }

  bool _matches(KrsOffering o) {
    final q = _query.trim().toLowerCase();
    if (q.isNotEmpty &&
        !o.course.name.toLowerCase().contains(q) &&
        !o.course.code.toLowerCase().contains(q)) {
      return false;
    }
    return switch (_filter) {
      _Filter.all => true,
      _Filter.selected => o.isSelected,
      _Filter.available => !o.isSelected && o.blockers.isEmpty,
    };
  }

  @override
  Widget build(BuildContext context) {
    final k = context.krs;
    final offerings = ref.watch(krsOfferingsProvider);
    final overview = ref.watch(krsOverviewProvider).valueOrNull;
    final canEdit = overview?.canEdit ?? false;
    return Scaffold(
      appBar: AppBar(title: Text(k.offeringsTitle)),
      body: KrsConstrained(
        child: Column(
          children: [
            Padding(
              padding: const EdgeInsets.fromLTRB(
                AppSpacing.lg,
                AppSpacing.md,
                AppSpacing.lg,
                AppSpacing.xs,
              ),
              child: Column(
                children: [
                  TextField(
                    decoration: InputDecoration(
                      hintText: k.searchHint,
                      prefixIcon: const Icon(Icons.search),
                    ),
                    onChanged: (v) => setState(() => _query = v),
                  ),
                  const SizedBox(height: AppSpacing.sm),
                  Align(
                    alignment: Alignment.centerLeft,
                    child: Wrap(
                      spacing: AppSpacing.sm,
                      children: [
                        for (final f in _Filter.values)
                          ChoiceChip(
                            label: Text(switch (f) {
                              _Filter.all => k.filterAll,
                              _Filter.available => k.filterAvailable,
                              _Filter.selected => k.filterSelected,
                            }),
                            selected: _filter == f,
                            onSelected: (_) => setState(() => _filter = f),
                          ),
                      ],
                    ),
                  ),
                  if (overview != null)
                    Padding(
                      padding: const EdgeInsets.only(top: AppSpacing.sm),
                      child: Align(
                        alignment: Alignment.centerLeft,
                        child: Text(
                          k.creditsOf(
                            '${overview.totalCredits}',
                            overview.maxCredits?.toString(),
                          ),
                          style: Theme.of(context).textTheme.labelLarge,
                        ),
                      ),
                    ),
                  if (_blockedMessage != null)
                    Padding(
                      padding: const EdgeInsets.only(top: AppSpacing.sm),
                      child: AppBanner(
                        title: k.blockedTitle,
                        message: _blockedMessage!,
                        tone: StatusTone.danger,
                        action: OutlinedButton(
                          onPressed: () =>
                              context.push(RoutePaths.studentClearance),
                          child: Text(k.blockedAction),
                        ),
                      ),
                    ),
                  if (overview != null && !canEdit)
                    Padding(
                      padding: const EdgeInsets.only(top: AppSpacing.sm),
                      child: AppBanner(
                        message: overview.notices.isEmpty
                            ? k.editLocked
                            : '${k.editLocked}\n${overview.notices.join('\n')}',
                        tone: StatusTone.warning,
                      ),
                    ),
                ],
              ),
            ),
            Expanded(
              child: AsyncValueView<KrsOfferings>(
                value: offerings,
                onRetry: () => ref.invalidate(krsOfferingsProvider),
                data: (d) {
                  final list = d.courses.where(_matches).toList();
                  return RefreshIndicator(
                    onRefresh: () => ref.refresh(krsOfferingsProvider.future),
                    child: list.isEmpty
                        ? ListView(
                            physics: const AlwaysScrollableScrollPhysics(),
                            children: [
                              EmptyView(
                                title: k.emptyOfferingsTitle,
                                message: k.emptyOfferingsBody,
                              ),
                            ],
                          )
                        : ListView.builder(
                            physics: const AlwaysScrollableScrollPhysics(),
                            padding: const EdgeInsets.all(AppSpacing.lg),
                            itemCount: list.length,
                            itemBuilder: (_, i) =>
                                _offeringCard(context, list[i], canEdit),
                          ),
                  );
                },
              ),
            ),
          ],
        ),
      ),
    );
  }

  Widget _offeringCard(BuildContext context, KrsOffering o, bool canEdit) {
    final k = context.krs;
    final theme = Theme.of(context);
    final scheme = theme.colorScheme;
    return Padding(
      padding: const EdgeInsets.only(bottom: AppSpacing.md),
      child: AppSurfaceCard(
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(o.course.name, style: theme.textTheme.titleSmall),
            const SizedBox(height: AppSpacing.xs),
            Wrap(
              spacing: AppSpacing.sm,
              runSpacing: AppSpacing.xs,
              crossAxisAlignment: WrapCrossAlignment.center,
              children: [
                Text(
                  '${o.course.code} · ${o.course.credits} ${k.credits}'
                  '${o.course.semesterLevel == null ? '' : ' · ${k.semesterLevel(o.course.semesterLevel!)}'}',
                  style: theme.textTheme.bodySmall,
                ),
                if (o.isSelected)
                  StatusChip(
                    label: k.selected,
                    tone: StatusTone.success,
                    dense: true,
                  ),
                if (o.isRetake)
                  StatusChip(
                    label: k.retake,
                    tone: StatusTone.warning,
                    dense: true,
                  ),
                if (o.bestGrade != null)
                  StatusChip(label: k.bestGrade(o.bestGrade!), dense: true),
              ],
            ),
            if (o.prerequisites.isNotEmpty) ...[
              const SizedBox(height: AppSpacing.sm),
              Text(k.prerequisites, style: theme.textTheme.labelMedium),
              for (final p in o.prerequisites)
                MetaLine(
                  icon: p.satisfied
                      ? Icons.check_circle_outline
                      : Icons.cancel_outlined,
                  text: k.prerequisiteLine(p.display, p.minGrade),
                  color: p.satisfied ? null : scheme.error,
                ),
            ],
            for (final b in o.blockers)
              MetaLine(icon: Icons.block, text: b, color: scheme.error),
            const Divider(height: AppSpacing.xl),
            for (final c in o.classes) _classRow(context, o, c, canEdit),
          ],
        ),
      ),
    );
  }

  Widget _classRow(
    BuildContext context,
    KrsOffering o,
    KrsOfferingClass c,
    bool canEdit,
  ) {
    final k = context.krs;
    final theme = Theme.of(context);
    final adding = _addingId == c.section.id;
    final enabled =
        canEdit &&
        _addingId == null &&
        !c.isFull &&
        !c.isSelected &&
        !o.isSelected &&
        o.blockers.isEmpty;
    return Padding(
      padding: const EdgeInsets.only(bottom: AppSpacing.md),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Wrap(
                  spacing: AppSpacing.sm,
                  crossAxisAlignment: WrapCrossAlignment.center,
                  children: [
                    Text(
                      c.section.classCode,
                      style: theme.textTheme.titleSmall,
                    ),
                    if (c.isSelected)
                      StatusChip(
                        label: k.selected,
                        tone: StatusTone.success,
                        dense: true,
                      ),
                    if (c.isFull)
                      StatusChip(
                        label: k.full,
                        tone: StatusTone.danger,
                        dense: true,
                      ),
                  ],
                ),
                ClassMeta(section: c.section, noSchedule: k.noSchedule),
                MetaLine(
                  icon: Icons.event_seat_outlined,
                  text: k.seats(c.seatsLeft, c.capacity),
                ),
                for (final x in c.conflicts)
                  MetaLine(
                    icon: Icons.warning_amber_rounded,
                    text: '${k.scheduleConflict}: $x',
                    color: theme.colorScheme.error,
                  ),
              ],
            ),
          ),
          const SizedBox(width: AppSpacing.sm),
          FilledButton(
            onPressed: enabled ? () => _add(c) : null,
            child: adding
                ? const SizedBox(
                    width: 16,
                    height: 16,
                    child: CircularProgressIndicator(strokeWidth: 2),
                  )
                : Text(k.add),
          ),
        ],
      ),
    );
  }
}
