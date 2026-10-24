import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../../core/l10n/core_strings.dart';
import '../../../../core/theme/app_dimensions.dart';
import '../../../../core/utils/file_actions.dart';
import '../../../../core/utils/formatters.dart';
import '../../../../core/widgets/state_views.dart';
import '../../../../core/widgets/ui_kit.dart';
import '../../domain/exam_monitoring.dart';
import '../../l10n/exam_strings.dart';
import '../providers.dart';
import 'participant_tile.dart';

/// Rekap nilai: ringkasan, pencarian nama/NIM, filter status (klien, endpoint
/// tanpa filter) dan ekspor CSV/PDF.
class RecapTab extends ConsumerStatefulWidget {
  const RecapTab({super.key, required this.examId});

  final String examId;

  @override
  ConsumerState<RecapTab> createState() => _RecapTabState();
}

class _RecapTabState extends ConsumerState<RecapTab> {
  String _query = '';
  ParticipantStatus? _status;

  @override
  Widget build(BuildContext context) {
    final s = context.examL10n;
    final core = context.l10n;
    final async = ref.watch(examRecapProvider(widget.examId));

    return AsyncValueView<ExamRecap>(
      value: async,
      onRetry: () => ref.invalidate(examRecapProvider(widget.examId)),
      data: (recap) {
        final q = _query.trim().toLowerCase();
        final list = recap.participants.where((p) {
          if (_status != null && p.status != _status) return false;
          if (q.isEmpty) return true;
          return p.studentName.toLowerCase().contains(q) ||
              p.studentNim.toLowerCase().contains(q);
        }).toList();
        final sum = recap.summary;
        return RefreshIndicator(
          onRefresh: () async {
            ref.invalidate(examRecapProvider(widget.examId));
            await ref.read(examRecapProvider(widget.examId).future);
          },
          child: ListView(
            physics: const AlwaysScrollableScrollPhysics(),
            padding: const EdgeInsets.all(AppSpacing.lg),
            children: [
              LayoutBuilder(
                builder: (context, c) {
                  final w = (c.maxWidth - 2 * AppSpacing.sm) / 3;
                  final tiles = <Widget>[
                    StatTile(
                      label: s.statTotal,
                      value: '${sum.totalParticipants}',
                      icon: Icons.groups_outlined,
                    ),
                    StatTile(
                      label: s.statCompleted,
                      value: '${sum.completed}',
                      icon: Icons.check_circle_outline,
                      tone: StatusTone.success,
                    ),
                    StatTile(
                      label: s.statInProgress,
                      value: '${sum.inProgress}',
                      icon: Icons.timelapse,
                      tone: StatusTone.warning,
                    ),
                    StatTile(
                      label: s.statNotStarted,
                      value: '${sum.notStarted}',
                      icon: Icons.hourglass_empty,
                      tone: StatusTone.neutral,
                    ),
                    StatTile(
                      label: s.statAverage,
                      value: context.formatNumber(sum.averageScore),
                      icon: Icons.functions,
                      tone: StatusTone.info,
                    ),
                    StatTile(
                      label: '${s.statHighest} / ${s.statLowest}',
                      value:
                          '${context.formatNumber(sum.highestScore, maxDecimals: 1)} / ${context.formatNumber(sum.lowestScore, maxDecimals: 1)}',
                      icon: Icons.swap_vert,
                      tone: StatusTone.accent,
                    ),
                  ];
                  return Wrap(
                    spacing: AppSpacing.sm,
                    runSpacing: AppSpacing.sm,
                    children: [
                      for (final t in tiles) SizedBox(width: w, child: t),
                    ],
                  );
                },
              ),
              const SizedBox(height: AppSpacing.md),
              Wrap(
                spacing: AppSpacing.sm,
                children: [
                  OutlinedButton.icon(
                    icon: const Icon(Icons.table_chart_outlined),
                    label: Text(s.exportCsv),
                    onPressed: () => _export('csv'),
                  ),
                  OutlinedButton.icon(
                    icon: const Icon(Icons.picture_as_pdf_outlined),
                    label: Text(s.exportPdf),
                    onPressed: () => _export('pdf'),
                  ),
                ],
              ),
              const SizedBox(height: AppSpacing.md),
              TextField(
                decoration: InputDecoration(
                  hintText: s.searchHint,
                  prefixIcon: const Icon(Icons.search),
                ),
                onChanged: (v) => setState(() => _query = v),
              ),
              const SizedBox(height: AppSpacing.sm),
              Wrap(
                spacing: AppSpacing.sm,
                children: [
                  ChoiceChip(
                    label: Text(core.all),
                    selected: _status == null,
                    onSelected: (_) => setState(() => _status = null),
                  ),
                  for (final st in ParticipantStatus.values)
                    ChoiceChip(
                      label: Text(s.participantStatus(st)),
                      selected: _status == st,
                      onSelected: (_) => setState(() => _status = st),
                    ),
                ],
              ),
              const SizedBox(height: AppSpacing.md),
              if (list.isEmpty)
                Padding(
                  padding: const EdgeInsets.all(AppSpacing.xl),
                  child: Center(child: Text(s.noMatches)),
                )
              else
                for (final p in list)
                  Padding(
                    padding: const EdgeInsets.only(bottom: AppSpacing.sm),
                    child: ParticipantTile(
                      examId: widget.examId,
                      participant: p,
                    ),
                  ),
            ],
          ),
        );
      },
    );
  }

  Future<void> _export(String format) => downloadAndOpen(
    context,
    ref,
    path: '/exams/${widget.examId}/recap/export',
    query: {'format': format},
    filename: 'rekap-nilai-${widget.examId}.$format',
  );
}
