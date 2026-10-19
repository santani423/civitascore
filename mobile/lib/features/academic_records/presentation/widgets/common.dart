import 'package:flutter/material.dart';

import '../../../../core/theme/app_dimensions.dart';
import '../../../../core/utils/formatters.dart';
import '../../../../core/widgets/ui_kit.dart';
import '../../domain/academic_models.dart';
import '../../l10n/academic_records_strings.dart';

/// Constrains content on wide screens.
class ContentWidth extends StatelessWidget {
  const ContentWidth({super.key, required this.child});
  final Widget child;
  @override
  Widget build(BuildContext context) => Align(
    alignment: Alignment.topCenter,
    child: ConstrainedBox(
      constraints: const BoxConstraints(maxWidth: 720),
      child: child,
    ),
  );
}

/// Pull-to-refresh list with constrained width.
class RefreshList extends StatelessWidget {
  const RefreshList({
    super.key,
    required this.onRefresh,
    required this.children,
  });
  final Future<void> Function() onRefresh;
  final List<Widget> children;
  @override
  Widget build(BuildContext context) => RefreshIndicator(
    onRefresh: onRefresh,
    child: ContentWidth(
      child: ListView(
        physics: const AlwaysScrollableScrollPhysics(),
        padding: const EdgeInsets.all(AppSpacing.lg),
        children: children,
      ),
    ),
  );
}

String fmtGpa(BuildContext context, double? v) =>
    v == null ? '—' : context.formatNumber(v, maxDecimals: 2);

/// Small letter-grade chip with tone colour.
class LetterChip extends StatelessWidget {
  const LetterChip({super.key, required this.letter});
  final String? letter;
  @override
  Widget build(BuildContext context) =>
      StatusChip(label: letter ?? '—', tone: letterTone(letter), dense: true);
}

/// One course row used by grades, KHS and transcript.
class CourseGradeTile extends StatelessWidget {
  const CourseGradeTile({
    super.key,
    required this.code,
    required this.name,
    required this.credits,
    this.score,
    this.letter,
    this.qualityPoints,
    this.isPassing,
    this.isGraded = true,
    this.note,
  });
  final String code;
  final String name;
  final int credits;
  final double? score;
  final String? letter;
  final double? qualityPoints;
  final bool? isPassing;
  final bool isGraded;
  final String? note;

  @override
  Widget build(BuildContext context) {
    final a = context.acadL10n;
    final text = Theme.of(context).textTheme;
    final scheme = Theme.of(context).colorScheme;
    final sub = <String>[
      code,
      a.creditsN(credits),
      if (isGraded && score != null)
        '${a.score} ${context.formatNumber(score)}',
      if (isGraded && qualityPoints != null)
        '${a.qualityPoints} ${context.formatNumber(qualityPoints)}',
    ].join(' · ');
    return Padding(
      padding: const EdgeInsets.symmetric(vertical: AppSpacing.sm),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  name,
                  style: text.titleSmall,
                  maxLines: 2,
                  overflow: TextOverflow.ellipsis,
                ),
                const SizedBox(height: 2),
                Text(
                  sub,
                  style: text.bodySmall?.copyWith(
                    color: scheme.onSurfaceVariant,
                  ),
                  maxLines: 2,
                  overflow: TextOverflow.ellipsis,
                ),
                if (note != null)
                  Padding(
                    padding: const EdgeInsets.only(top: 4),
                    child: StatusChip(
                      label: note!,
                      tone: StatusTone.info,
                      dense: true,
                    ),
                  ),
              ],
            ),
          ),
          const SizedBox(width: AppSpacing.md),
          if (isGraded && letter != null)
            Column(
              crossAxisAlignment: CrossAxisAlignment.end,
              children: [
                LetterChip(letter: letter),
                if (isPassing != null)
                  Padding(
                    padding: const EdgeInsets.only(top: 4),
                    child: Text(
                      isPassing! ? a.passing : a.failing,
                      style: text.labelSmall?.copyWith(
                        color: scheme.onSurfaceVariant,
                      ),
                    ),
                  ),
              ],
            )
          else
            Text(
              a.notGraded,
              style: text.labelSmall?.copyWith(color: scheme.onSurfaceVariant),
            ),
        ],
      ),
    );
  }
}

/// Row of key figures (IPK / IPS / SKS).
class StatRow extends StatelessWidget {
  const StatRow({super.key, required this.items});
  final List<StatTile> items;
  @override
  Widget build(BuildContext context) => Row(
    children: [
      for (var i = 0; i < items.length; i++) ...[
        if (i > 0) const SizedBox(width: AppSpacing.sm),
        Expanded(child: items[i]),
      ],
    ],
  );
}
