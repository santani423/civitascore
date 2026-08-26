import 'package:flutter/material.dart';

import '../theme/app_colors.dart';
import '../theme/app_dimensions.dart';

enum AppButtonVariant { primary, secondary, outline, ghost, danger }

enum AppButtonSize { sm, md, lg }

class AppButton extends StatelessWidget {
  const AppButton({
    super.key,
    required this.label,
    this.onPressed,
    this.variant = AppButtonVariant.primary,
    this.size = AppButtonSize.md,
    this.isLoading = false,
    this.leftIcon,
    this.expand = false,
  });

  final String label;
  final VoidCallback? onPressed;
  final AppButtonVariant variant;
  final AppButtonSize size;
  final bool isLoading;
  final IconData? leftIcon;
  final bool expand;

  double get _height => switch (size) {
    AppButtonSize.sm => 32,
    AppButtonSize.md => 40,
    AppButtonSize.lg => 48,
  };

  @override
  Widget build(BuildContext context) {
    final scheme = Theme.of(context).colorScheme;
    final isDisabled = onPressed == null || isLoading;

    final content = isLoading
        ? SizedBox(
            width: 18,
            height: 18,
            child: CircularProgressIndicator(
              strokeWidth: 2,
              color:
                  variant == AppButtonVariant.outline ||
                      variant == AppButtonVariant.ghost
                  ? scheme.primary
                  : scheme.onPrimary,
            ),
          )
        : Row(
            mainAxisSize: MainAxisSize.min,
            children: [
              if (leftIcon != null) ...[
                Icon(leftIcon, size: 18),
                const SizedBox(width: AppSpacing.sm),
              ],
              Text(label),
            ],
          );

    final shape = RoundedRectangleBorder(
      borderRadius: BorderRadius.circular(AppRadius.sm),
    );
    final minimumSize = Size(expand ? double.infinity : 0, _height);

    final button = switch (variant) {
      AppButtonVariant.primary => FilledButton(
        onPressed: isDisabled ? null : onPressed,
        style: FilledButton.styleFrom(shape: shape, minimumSize: minimumSize),
        child: content,
      ),
      AppButtonVariant.secondary => FilledButton(
        onPressed: isDisabled ? null : onPressed,
        style: FilledButton.styleFrom(
          backgroundColor: scheme.secondary,
          foregroundColor: scheme.onSecondary,
          shape: shape,
          minimumSize: minimumSize,
        ),
        child: content,
      ),
      AppButtonVariant.outline => OutlinedButton(
        onPressed: isDisabled ? null : onPressed,
        style: OutlinedButton.styleFrom(shape: shape, minimumSize: minimumSize),
        child: content,
      ),
      AppButtonVariant.ghost => TextButton(
        onPressed: isDisabled ? null : onPressed,
        style: TextButton.styleFrom(shape: shape, minimumSize: minimumSize),
        child: content,
      ),
      AppButtonVariant.danger => FilledButton(
        onPressed: isDisabled ? null : onPressed,
        style: FilledButton.styleFrom(
          backgroundColor: AppColorsLight.danger,
          foregroundColor: Colors.white,
          shape: shape,
          minimumSize: minimumSize,
        ),
        child: content,
      ),
    };

    return expand ? SizedBox(width: double.infinity, child: button) : button;
  }
}
