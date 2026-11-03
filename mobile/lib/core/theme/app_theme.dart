import 'package:flutter/material.dart';
import 'package:flutter/services.dart';

import 'app_colors.dart';
import 'app_dimensions.dart';
import 'app_palette.dart';
import 'app_text_theme.dart';

/// Material 3 themes built from the Civitas One brand tokens
/// (`docs/RANCANGAN_FLUTTER_CIVITAS_ONE.md` §5.1). Dark mode is a designed
/// palette, not an inversion.
abstract final class AppTheme {
  static ThemeData light() => _build(
    brightness: Brightness.light,
    scheme:
        ColorScheme.fromSeed(
          seedColor: AppColorsLight.primary,
          brightness: Brightness.light,
        ).copyWith(
          primary: AppColorsLight.primary,
          onPrimary: Colors.white,
          primaryContainer: AppColorsLight.primaryLight,
          onPrimaryContainer: AppColorsLight.primaryHover,
          secondary: AppColorsLight.accent,
          onSecondary: Colors.white,
          secondaryContainer: AppColorsLight.primaryLight,
          onSecondaryContainer: AppColorsLight.primaryHover,
          error: AppColorsLight.danger,
          surface: AppColorsLight.surface,
          onSurface: AppColorsLight.textPrimary,
          onSurfaceVariant: AppColorsLight.textSecondary,
          outline: AppColorsLight.borderStrong,
          outlineVariant: AppColorsLight.border,
          surfaceContainerLowest: Colors.white,
          surfaceContainerLow: AppColorsLight.background,
          surfaceContainer: AppColorsLight.surfaceHover,
          surfaceContainerHigh: AppColorsLight.cream,
        ),
    background: AppColorsLight.background,
    palette: AppPalette.light,
  );

  static ThemeData dark() => _build(
    brightness: Brightness.dark,
    scheme:
        ColorScheme.fromSeed(
          seedColor: AppColorsDark.primary,
          brightness: Brightness.dark,
        ).copyWith(
          primary: AppColorsDark.primary,
          onPrimary: AppColorsDark.textInverted,
          primaryContainer: AppColorsDark.primaryLight,
          onPrimaryContainer: AppColorsDark.primaryHover,
          secondary: AppColorsDark.accent,
          onSecondary: AppColorsDark.textInverted,
          secondaryContainer: AppColorsDark.primaryLight,
          onSecondaryContainer: AppColorsDark.primaryHover,
          error: AppColorsDark.danger,
          surface: AppColorsDark.surface,
          onSurface: AppColorsDark.textPrimary,
          onSurfaceVariant: AppColorsDark.textSecondary,
          outline: AppColorsDark.borderStrong,
          outlineVariant: AppColorsDark.border,
          surfaceContainerLowest: AppColorsDark.background,
          surfaceContainerLow: AppColorsDark.background,
          surfaceContainer: AppColorsDark.surfaceHover,
          surfaceContainerHigh: AppColorsDark.surfaceElevated,
        ),
    background: AppColorsDark.background,
    palette: AppPalette.dark,
  );

  static ThemeData _build({
    required Brightness brightness,
    required ColorScheme scheme,
    required Color background,
    required AppPalette palette,
  }) {
    final text = AppTextTheme.build(scheme.onSurface, scheme.onSurfaceVariant);
    final radius = BorderRadius.circular(AppRadius.md);
    final isDark = brightness == Brightness.dark;

    return ThemeData(
      useMaterial3: true,
      brightness: brightness,
      colorScheme: scheme,
      scaffoldBackgroundColor: background,
      textTheme: text,
      extensions: [palette],
      visualDensity: VisualDensity.standard,
      splashFactory: InkSparkle.splashFactory,
      appBarTheme: AppBarTheme(
        backgroundColor: background,
        foregroundColor: scheme.onSurface,
        surfaceTintColor: Colors.transparent,
        elevation: 0,
        scrolledUnderElevation: 0.5,
        centerTitle: false,
        titleTextStyle: text.titleLarge,
        systemOverlayStyle: isDark
            ? SystemUiOverlayStyle.light
            : SystemUiOverlayStyle.dark,
      ),
      cardTheme: CardThemeData(
        color: scheme.surface,
        surfaceTintColor: Colors.transparent,
        elevation: 0,
        margin: EdgeInsets.zero,
        shape: RoundedRectangleBorder(
          borderRadius: BorderRadius.circular(AppRadius.lg),
          side: BorderSide(color: scheme.outlineVariant),
        ),
      ),
      dividerTheme: DividerThemeData(
        color: scheme.outlineVariant,
        space: 1,
        thickness: 1,
      ),
      listTileTheme: ListTileThemeData(
        iconColor: scheme.onSurfaceVariant,
        titleTextStyle: text.bodyLarge?.copyWith(fontWeight: FontWeight.w500),
        subtitleTextStyle: text.bodySmall,
        contentPadding: const EdgeInsets.symmetric(horizontal: AppSpacing.lg),
      ),
      inputDecorationTheme: InputDecorationTheme(
        filled: true,
        fillColor: scheme.surface,
        contentPadding: const EdgeInsets.symmetric(
          horizontal: 14,
          vertical: 14,
        ),
        border: OutlineInputBorder(
          borderRadius: radius,
          borderSide: BorderSide(color: scheme.outline),
        ),
        enabledBorder: OutlineInputBorder(
          borderRadius: radius,
          borderSide: BorderSide(color: scheme.outline),
        ),
        focusedBorder: OutlineInputBorder(
          borderRadius: radius,
          borderSide: BorderSide(color: scheme.primary, width: 2),
        ),
        errorBorder: OutlineInputBorder(
          borderRadius: radius,
          borderSide: BorderSide(color: scheme.error),
        ),
        focusedErrorBorder: OutlineInputBorder(
          borderRadius: radius,
          borderSide: BorderSide(color: scheme.error, width: 2),
        ),
        labelStyle: text.bodyMedium?.copyWith(color: scheme.onSurfaceVariant),
        hintStyle: text.bodyMedium?.copyWith(color: palette.mutedText),
      ),
      filledButtonTheme: FilledButtonThemeData(
        style: FilledButton.styleFrom(
          minimumSize: const Size(64, 48),
          shape: RoundedRectangleBorder(borderRadius: radius),
          textStyle: text.labelLarge,
        ),
      ),
      outlinedButtonTheme: OutlinedButtonThemeData(
        style: OutlinedButton.styleFrom(
          minimumSize: const Size(64, 48),
          shape: RoundedRectangleBorder(borderRadius: radius),
          side: BorderSide(color: scheme.outline),
          textStyle: text.labelLarge,
        ),
      ),
      textButtonTheme: TextButtonThemeData(
        style: TextButton.styleFrom(
          minimumSize: const Size(48, 44),
          textStyle: text.labelLarge,
        ),
      ),
      chipTheme: ChipThemeData(
        shape: RoundedRectangleBorder(
          borderRadius: BorderRadius.circular(AppRadius.full),
        ),
        side: BorderSide(color: scheme.outlineVariant),
        labelStyle: text.labelMedium,
      ),
      navigationBarTheme: NavigationBarThemeData(
        backgroundColor: scheme.surface,
        surfaceTintColor: Colors.transparent,
        indicatorColor: scheme.primaryContainer,
        elevation: 0,
        height: 72,
        iconTheme: WidgetStateProperty.resolveWith((states) {
          final selected = states.contains(WidgetState.selected);
          return IconThemeData(
            size: 24,
            color: selected ? scheme.primary : scheme.onSurfaceVariant,
          );
        }),
        labelTextStyle: WidgetStateProperty.resolveWith((states) {
          final selected = states.contains(WidgetState.selected);
          return text.labelSmall?.copyWith(
            color: selected ? scheme.primary : scheme.onSurfaceVariant,
            fontWeight: selected ? FontWeight.w700 : FontWeight.w500,
          );
        }),
      ),
      bottomSheetTheme: BottomSheetThemeData(
        backgroundColor: scheme.surface,
        surfaceTintColor: Colors.transparent,
        showDragHandle: true,
        shape: const RoundedRectangleBorder(
          borderRadius: BorderRadius.vertical(
            top: Radius.circular(AppRadius.xl),
          ),
        ),
      ),
      dialogTheme: DialogThemeData(
        backgroundColor: scheme.surface,
        surfaceTintColor: Colors.transparent,
        shape: RoundedRectangleBorder(
          borderRadius: BorderRadius.circular(AppRadius.xl),
        ),
      ),
      snackBarTheme: const SnackBarThemeData(
        behavior: SnackBarBehavior.floating,
      ),
      tabBarTheme: TabBarThemeData(
        labelStyle: text.labelLarge,
        unselectedLabelStyle: text.labelLarge,
        dividerColor: scheme.outlineVariant,
      ),
    );
  }
}
