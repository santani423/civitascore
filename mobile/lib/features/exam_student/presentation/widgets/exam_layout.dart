import 'package:flutter/material.dart';

/// Centers content and caps its width on tablets / wide screens.
class ExamConstrained extends StatelessWidget {
  const ExamConstrained({super.key, required this.child});
  final Widget child;

  @override
  Widget build(BuildContext context) => Center(
    child: ConstrainedBox(
      constraints: const BoxConstraints(maxWidth: 720),
      child: child,
    ),
  );
}
