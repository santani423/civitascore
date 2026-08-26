import 'package:flutter/material.dart';

import '../theme/app_dimensions.dart';

class AppCard extends StatelessWidget {
  const AppCard({
    super.key,
    required this.child,
    this.title,
    this.noPadding = false,
  });

  final Widget child;
  final String? title;
  final bool noPadding;

  @override
  Widget build(BuildContext context) {
    return Card(
      margin: EdgeInsets.zero,
      child: Padding(
        padding: noPadding
            ? EdgeInsets.zero
            : const EdgeInsets.all(AppSpacing.lg),
        child: title == null
            ? child
            : Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Padding(
                    padding: EdgeInsets.symmetric(
                      horizontal: noPadding ? AppSpacing.lg : 0,
                      vertical: noPadding ? AppSpacing.lg : 0,
                    ),
                    child: Text(
                      title!,
                      style: Theme.of(context).textTheme.titleMedium,
                    ),
                  ),
                  child,
                ],
              ),
      ),
    );
  }
}
