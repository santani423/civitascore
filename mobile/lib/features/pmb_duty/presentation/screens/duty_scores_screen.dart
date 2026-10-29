import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../../core/theme/app_dimensions.dart';
import '../../../../core/utils/formatters.dart';
import '../../../../core/widgets/app_feedback.dart';
import '../../../../core/widgets/state_views.dart';
import '../../../../core/widgets/ui_kit.dart';
import '../../domain/pmb_duty_models.dart';
import '../../l10n/pmb_duty_strings.dart';
import '../providers.dart';
import '../widgets/duty_widgets.dart';
import '../widgets/score_edit_sheet.dart';

/// MOB-OTH-03 — examiner/interviewer score entry for one session.
class DutyScoresScreen extends ConsumerStatefulWidget {
  const DutyScoresScreen({super.key, required this.sessionId});
  final String sessionId;

  @override
  ConsumerState<DutyScoresScreen> createState() => _DutyScoresScreenState();
}

class _DutyScoresScreenState extends ConsumerState<DutyScoresScreen> {
  /// A sheet (or a "save & next" chain) is open; ignore further taps.
  bool _editing = false;

  Future<void> _open(ScoreSheet sheet, String participantId) async {
    if (_editing) return;
    _editing = true;
    try {
      await _edit(sheet, participantId);
    } finally {
      _editing = false;
    }
  }

  Future<void> _edit(ScoreSheet sheet, String participantId) async {
    final index = sheet.rows.indexWhere(
      (r) => r.participantId == participantId,
    );
    if (index < 0) return;
    final result = await showModalBottomSheet<ScoreEditResult>(
      context: context,
      isScrollControlled: true,
      showDragHandle: true,
      builder: (_) => ScoreEditSheet(
        sessionId: widget.sessionId,
        component: sheet.component,
        row: sheet.rows[index],
        hasNext: _nextScorable(sheet, index) != null,
      ),
    );
    if (!mounted) return;
    // Always reload: a save may have completed even if the sheet was
    // dragged away, and scoring a Registered participant marks them
    // Present in the session detail.
    ref.invalidate(dutyScoreSheetProvider(widget.sessionId));
    ref.invalidate(dutySessionProvider(widget.sessionId));
    if (result != ScoreEditResult.savedNext) return;
    final ScoreSheet fresh;
    try {
      fresh = await ref.read(dutyScoreSheetProvider(widget.sessionId).future);
    } catch (e) {
      if (mounted) showErrorSnack(context, e);
      return;
    }
    if (!mounted || fresh.locked) return;
    // Rows may have moved (participant moved/rescheduled): locate by id.
    final at = fresh.rows.indexWhere((r) => r.participantId == participantId);
    final next = _nextScorable(fresh, at < 0 ? index - 1 : at);
    if (next != null) await _edit(fresh, fresh.rows[next].participantId);
  }

  int? _nextScorable(ScoreSheet sheet, int index) {
    for (var i = index + 1; i < sheet.rows.length; i++) {
      if (sheet.rows[i].attendance.scorable) return i;
    }
    return null;
  }

  @override
  Widget build(BuildContext context) {
    final s = context.pmbDuty;
    final provider = dutyScoreSheetProvider(widget.sessionId);
    final value = ref.watch(provider);
    return Scaffold(
      appBar: AppBar(title: Text(s.scores)),
      body: AsyncValueView<ScoreSheet>(
        value: value,
        onRetry: () => ref.invalidate(provider),
        data: (sheet) => RefreshIndicator(
          onRefresh: () async {
            ref.invalidate(provider);
            await ref.read(provider.future);
          },
          child: DutyBody(
            child: ListView(
              physics: const AlwaysScrollableScrollPhysics(),
              padding: const EdgeInsets.all(AppSpacing.lg),
              children: [
                _ComponentCard(sheet: sheet),
                const SizedBox(height: AppSpacing.md),
                if (sheet.locked)
                  AppBanner(
                    message: s.lockedBanner,
                    tone: StatusTone.warning,
                    icon: Icons.lock_outline,
                  )
                else
                  AppBanner(message: s.registeredHint),
                const SizedBox(height: AppSpacing.md),
                if (sheet.rows.isEmpty)
                  EmptyView(
                    title: s.noParticipantsTitle,
                    icon: Icons.groups_outlined,
                  ),
                for (var i = 0; i < sheet.rows.length; i++)
                  Padding(
                    padding: const EdgeInsets.only(bottom: AppSpacing.sm),
                    child: _ScoreTile(
                      row: sheet.rows[i],
                      component: sheet.component,
                      onTap: sheet.locked || !sheet.rows[i].attendance.scorable
                          ? null
                          : () => _open(sheet, sheet.rows[i].participantId),
                    ),
                  ),
              ],
            ),
          ),
        ),
      ),
    );
  }
}

/// Describes how the component is scored (range, pass/fail or rubric).
String scoringRule(BuildContext context, ScoreComponent c) {
  final s = context.pmbDuty;
  if (c.isPassFail) return s.passFailLabel;
  if (c.hasRubric) {
    return s.rubricLabel(
      c.criteria
          .map((k) => '${k.title} (${context.formatNumber(k.weight)}%)')
          .join(', '),
    );
  }
  return s.rangeLabel(
    context.formatNumber(c.minScore),
    context.formatNumber(c.maxScore),
  );
}

class _ComponentCard extends StatelessWidget {
  const _ComponentCard({required this.sheet});
  final ScoreSheet sheet;

  @override
  Widget build(BuildContext context) {
    final s = context.pmbDuty;
    final text = Theme.of(context).textTheme;
    final scorable = sheet.rows.where((r) => r.attendance.scorable).length;
    return AppSurfaceCard(
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(sheet.component.name, style: text.titleMedium),
          Text(
            '${s.sessionTitle(sheet.code)} · ${context.formatDateTime(sheet.startsAt)}',
            style: text.bodySmall,
          ),
          const SizedBox(height: AppSpacing.sm),
          Text(scoringRule(context, sheet.component), style: text.bodyMedium),
          const SizedBox(height: AppSpacing.sm),
          StatusChip(
            dense: true,
            tone: sheet.scoredCount >= scorable && scorable > 0
                ? StatusTone.success
                : StatusTone.info,
            label: s.scoredSummary(sheet.scoredCount, scorable),
          ),
        ],
      ),
    );
  }
}

class _ScoreTile extends StatelessWidget {
  const _ScoreTile({required this.row, required this.component, this.onTap});
  final ScoreRow row;
  final ScoreComponent component;
  final VoidCallback? onTap;

  String _value(BuildContext context) {
    final s = context.pmbDuty;
    final score = row.score;
    if (!row.attendance.scorable) return s.notScorable;
    if (score == null || score.isEmpty) return s.notScored;
    if (component.isPassFail) {
      return score.passed == null
          ? s.notScored
          : (score.passed! ? s.passed : s.failed);
    }
    final raw = score.rawScore ?? component.rubricRaw(score.rubricScores);
    return raw == null ? s.notScored : context.formatNumber(raw);
  }

  @override
  Widget build(BuildContext context) {
    final s = context.pmbDuty;
    final text = Theme.of(context).textTheme;
    final note = row.score?.note;
    return AppSurfaceCard(
      onTap: onTap,
      padding: const EdgeInsets.all(AppSpacing.md),
      child: Row(
        children: [
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  row.displayName,
                  style: text.titleSmall,
                  maxLines: 2,
                  overflow: TextOverflow.ellipsis,
                ),
                Text(row.number, style: text.bodySmall),
                const SizedBox(height: AppSpacing.xs),
                StatusChip(
                  dense: true,
                  tone: attendanceTone(row.attendance),
                  label: s.attendanceLabel(row.attendance),
                ),
                if (note != null && note.isNotEmpty)
                  Padding(
                    padding: const EdgeInsets.only(top: AppSpacing.xs),
                    child: Text(
                      note,
                      style: text.bodySmall,
                      maxLines: 2,
                      overflow: TextOverflow.ellipsis,
                    ),
                  ),
              ],
            ),
          ),
          const SizedBox(width: AppSpacing.sm),
          // Capped (not flexible) so it stays right-aligned and can never
          // squeeze the name column at large font sizes.
          ConstrainedBox(
            constraints: const BoxConstraints(maxWidth: 140),
            child: Text(
              _value(context),
              style: row.scored ? text.titleMedium : text.bodyMedium,
              textAlign: TextAlign.end,
              maxLines: 2,
              overflow: TextOverflow.ellipsis,
            ),
          ),
          if (onTap != null) const Icon(Icons.chevron_right),
        ],
      ),
    );
  }
}
