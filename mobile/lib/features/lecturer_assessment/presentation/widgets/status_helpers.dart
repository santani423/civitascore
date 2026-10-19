import 'package:flutter/material.dart';

import '../../../../core/widgets/ui_kit.dart';
import '../../domain/assessment_models.dart';

StatusTone toneOf(AttendanceStatus s) => switch (s) {
  AttendanceStatus.present => StatusTone.success,
  AttendanceStatus.permitted => StatusTone.info,
  AttendanceStatus.sick => StatusTone.warning,
  AttendanceStatus.absent => StatusTone.danger,
};

StatusTone letterTone(String? letter) => switch (letter) {
  'A' || 'AB' => StatusTone.success,
  'B' || 'BC' => StatusTone.info,
  'C' => StatusTone.warning,
  'D' || 'E' => StatusTone.danger,
  _ => StatusTone.neutral,
};

/// Constrains content on wide screens.
class CenteredBody extends StatelessWidget {
  const CenteredBody({super.key, required this.child});
  final Widget child;
  @override
  Widget build(BuildContext context) => Center(
    child: ConstrainedBox(
      constraints: const BoxConstraints(maxWidth: 720),
      child: child,
    ),
  );
}
