import 'package:flutter/material.dart';

import '../../../../core/theme/app_dimensions.dart';
import '../../domain/teaching_models.dart';
import '../../../../core/l10n/core_strings.dart';

/// Constrains content on wide screens.
class WideBody extends StatelessWidget {
  const WideBody({super.key, required this.child});
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

/// "Senin 08:00–10:00 · R.101" lines of a class.
String slotLine(BuildContext context, ClassSlot s) {
  final l = context.l10n;
  final time = '${s.startTime}–${s.endTime}';
  final room = (s.room == null || s.room!.isEmpty) ? '' : ' · ${s.room}';
  return '${l.dayShort(s.dayOfWeek)} $time$room';
}

class SlotLines extends StatelessWidget {
  const SlotLines({super.key, required this.slots, required this.emptyText});
  final List<ClassSlot> slots;
  final String emptyText;
  @override
  Widget build(BuildContext context) {
    final style = Theme.of(context).textTheme.bodySmall;
    if (slots.isEmpty) return Text(emptyText, style: style);
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        for (final s in slots)
          Padding(
            padding: const EdgeInsets.only(top: 2),
            child: Text(
              slotLine(context, s),
              style: style,
              maxLines: 1,
              overflow: TextOverflow.ellipsis,
            ),
          ),
      ],
    );
  }
}

const kPagePadding = EdgeInsets.all(AppSpacing.lg);
