import 'package:flutter/material.dart';

import '../theme/app_colors.dart';
import '../theme/app_dimensions.dart';

enum AppAlertVariant { info, success, warning, danger }

class AppAlert extends StatelessWidget {
  const AppAlert({
    super.key,
    required this.message,
    this.variant = AppAlertVariant.info,
    this.onDismiss,
  });

  final String message;
  final AppAlertVariant variant;
  final VoidCallback? onDismiss;

  (Color, Color, IconData) _style() => switch (variant) {
    AppAlertVariant.info => (
      AppColorsLight.info,
      const Color(0xFFE0F2FE),
      Icons.info_outline,
    ),
    AppAlertVariant.success => (
      AppColorsLight.success,
      const Color(0xFFDCFCE7),
      Icons.check_circle_outline,
    ),
    AppAlertVariant.warning => (
      AppColorsLight.warning,
      const Color(0xFFFEF3C7),
      Icons.warning_amber_outlined,
    ),
    AppAlertVariant.danger => (
      AppColorsLight.danger,
      const Color(0xFFFEE2E2),
      Icons.error_outline,
    ),
  };

  @override
  Widget build(BuildContext context) {
    final (fg, bg, icon) = _style();

    return Container(
      padding: const EdgeInsets.all(AppSpacing.md),
      decoration: BoxDecoration(
        color: bg,
        borderRadius: BorderRadius.circular(AppRadius.sm),
        border: Border.all(color: fg.withValues(alpha: 0.3)),
      ),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Icon(icon, color: fg, size: 20),
          const SizedBox(width: AppSpacing.sm),
          Expanded(
            child: Text(message, style: TextStyle(color: fg, fontSize: 13)),
          ),
          if (onDismiss != null)
            InkWell(
              onTap: onDismiss,
              child: Icon(Icons.close, color: fg, size: 18),
            ),
        ],
      ),
    );
  }
}
