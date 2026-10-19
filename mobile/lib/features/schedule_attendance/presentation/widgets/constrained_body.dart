import 'package:flutter/material.dart';

/// Centers content and caps its width on wide screens.
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
