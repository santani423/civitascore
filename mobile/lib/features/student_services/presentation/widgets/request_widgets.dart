import 'package:flutter/material.dart';

import '../../../../core/widgets/ui_kit.dart';
import '../../domain/student_request.dart';
import '../../l10n/student_services_strings.dart';

class RequestStatusChip extends StatelessWidget {
  const RequestStatusChip({
    super.key,
    required this.request,
    this.dense = true,
  });
  final StudentRequest request;
  final bool dense;

  @override
  Widget build(BuildContext context) {
    final status = request.status;
    return StatusChip(
      label: context.sv.statusLabel(request.statusValue, request.statusLabel),
      tone: status?.tone ?? StatusTone.neutral,
      icon: status?.icon,
      dense: dense,
    );
  }
}

/// Centers content on wide screens.
class ConstrainedBody extends StatelessWidget {
  const ConstrainedBody({super.key, required this.child});
  final Widget child;

  @override
  Widget build(BuildContext context) => Center(
    child: ConstrainedBox(
      constraints: const BoxConstraints(maxWidth: 720),
      child: child,
    ),
  );
}
