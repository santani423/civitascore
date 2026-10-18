import 'package:flutter/material.dart';

import '../theme/app_dimensions.dart';
import '../theme/app_palette.dart';

export '../theme/app_palette.dart' show StatusTone, AppPaletteX;

/// Small pill showing a status with a semantic tone.
class StatusChip extends StatelessWidget {
  const StatusChip({
    super.key,
    required this.label,
    this.tone = StatusTone.neutral,
    this.icon,
    this.dense = false,
  });
  final String label;
  final StatusTone tone;
  final IconData? icon;
  final bool dense;

  @override
  Widget build(BuildContext context) {
    final (fg, bg) = context.toneColors(tone);
    return Container(
      padding: EdgeInsets.symmetric(
        horizontal: dense ? 8 : 10,
        vertical: dense ? 2 : 4,
      ),
      decoration: BoxDecoration(
        color: bg,
        borderRadius: BorderRadius.circular(AppRadius.full),
      ),
      child: Row(
        mainAxisSize: MainAxisSize.min,
        children: [
          if (icon != null) ...[
            Icon(icon, size: 14, color: fg),
            const SizedBox(width: 4),
          ],
          Flexible(
            child: Text(
              label,
              overflow: TextOverflow.ellipsis,
              style: Theme.of(
                context,
              ).textTheme.labelMedium?.copyWith(color: fg),
            ),
          ),
        ],
      ),
    );
  }
}

/// Section title with optional trailing action.
class SectionHeader extends StatelessWidget {
  const SectionHeader({
    super.key,
    required this.title,
    this.action,
    this.onAction,
    this.padding,
  });
  final String title;
  final String? action;
  final VoidCallback? onAction;
  final EdgeInsets? padding;

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding:
          padding ??
          const EdgeInsets.fromLTRB(
            AppSpacing.lg,
            AppSpacing.xl,
            AppSpacing.lg,
            AppSpacing.sm,
          ),
      child: Row(
        children: [
          Expanded(
            child: Text(title, style: Theme.of(context).textTheme.titleMedium),
          ),
          if (action != null && onAction != null)
            TextButton(onPressed: onAction, child: Text(action!)),
        ],
      ),
    );
  }
}

/// Label/value row used in detail screens.
class InfoRow extends StatelessWidget {
  const InfoRow({
    super.key,
    required this.label,
    required this.value,
    this.trailing,
    this.valueStyle,
  });
  final String label;
  final String value;
  final Widget? trailing;
  final TextStyle? valueStyle;

  @override
  Widget build(BuildContext context) {
    final text = Theme.of(context).textTheme;
    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 6),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Expanded(
            flex: 2,
            child: Text(
              label,
              style: text.bodyMedium?.copyWith(
                color: context.palette.mutedText,
              ),
            ),
          ),
          const SizedBox(width: AppSpacing.md),
          Expanded(
            flex: 3,
            child: Text(
              value,
              style:
                  valueStyle ??
                  text.bodyMedium?.copyWith(fontWeight: FontWeight.w500),
            ),
          ),
          ?trailing,
        ],
      ),
    );
  }
}

/// Bordered card with consistent padding; tappable when [onTap] is set.
class AppSurfaceCard extends StatelessWidget {
  const AppSurfaceCard({
    super.key,
    required this.child,
    this.onTap,
    this.padding,
    this.color,
  });
  final Widget child;
  final VoidCallback? onTap;
  final EdgeInsets? padding;
  final Color? color;

  @override
  Widget build(BuildContext context) {
    return Card(
      color: color,
      clipBehavior: Clip.antiAlias,
      child: InkWell(
        onTap: onTap,
        child: Padding(
          padding: padding ?? const EdgeInsets.all(AppSpacing.lg),
          child: child,
        ),
      ),
    );
  }
}

/// Highlighted figure (IPK, SKS, outstanding amount…).
class StatTile extends StatelessWidget {
  const StatTile({
    super.key,
    required this.label,
    required this.value,
    this.icon,
    this.tone = StatusTone.primary,
    this.onTap,
  });
  final String label;
  final String value;
  final IconData? icon;
  final StatusTone tone;
  final VoidCallback? onTap;

  @override
  Widget build(BuildContext context) {
    final (fg, bg) = context.toneColors(tone);
    final text = Theme.of(context).textTheme;
    return AppSurfaceCard(
      onTap: onTap,
      padding: const EdgeInsets.all(AppSpacing.md),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          if (icon != null)
            Container(
              padding: const EdgeInsets.all(6),
              decoration: BoxDecoration(
                color: bg,
                borderRadius: BorderRadius.circular(AppRadius.sm),
              ),
              child: Icon(icon, size: 18, color: fg),
            ),
          if (icon != null) const SizedBox(height: AppSpacing.sm),
          Text(
            value,
            style: text.titleLarge,
            maxLines: 1,
            overflow: TextOverflow.ellipsis,
          ),
          const SizedBox(height: 2),
          Text(
            label,
            style: text.bodySmall,
            maxLines: 2,
            overflow: TextOverflow.ellipsis,
          ),
        ],
      ),
    );
  }
}

/// Tinted banner for warnings/info (cekal, deadlines, offline).
class AppBanner extends StatelessWidget {
  const AppBanner({
    super.key,
    required this.message,
    this.title,
    this.tone = StatusTone.info,
    this.icon,
    this.action,
  });
  final String message;
  final String? title;
  final StatusTone tone;
  final IconData? icon;
  final Widget? action;

  @override
  Widget build(BuildContext context) {
    final (fg, bg) = context.toneColors(tone);
    final text = Theme.of(context).textTheme;
    final defaultIcon = switch (tone) {
      StatusTone.success => Icons.check_circle_outline,
      StatusTone.warning => Icons.warning_amber_rounded,
      StatusTone.danger => Icons.error_outline,
      _ => Icons.info_outline,
    };
    return Container(
      width: double.infinity,
      padding: const EdgeInsets.all(AppSpacing.md),
      decoration: BoxDecoration(
        color: bg,
        borderRadius: BorderRadius.circular(AppRadius.lg),
      ),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Icon(icon ?? defaultIcon, color: fg, size: 20),
          const SizedBox(width: AppSpacing.md),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                if (title != null)
                  Text(title!, style: text.titleSmall?.copyWith(color: fg)),
                Text(message, style: text.bodyMedium?.copyWith(color: fg)),
                if (action != null)
                  Padding(
                    padding: const EdgeInsets.only(top: AppSpacing.sm),
                    child: action,
                  ),
              ],
            ),
          ),
        ],
      ),
    );
  }
}

/// Menu entry used by hub screens (Akademik, Kuliah, Akun…).
class MenuTile extends StatelessWidget {
  const MenuTile({
    super.key,
    required this.icon,
    required this.title,
    this.subtitle,
    this.onTap,
    this.trailing,
    this.badge,
  });
  final IconData icon;
  final String title;
  final String? subtitle;
  final VoidCallback? onTap;
  final Widget? trailing;
  final String? badge;

  @override
  Widget build(BuildContext context) {
    final scheme = Theme.of(context).colorScheme;
    return ListTile(
      onTap: onTap,
      leading: Container(
        padding: const EdgeInsets.all(8),
        decoration: BoxDecoration(
          color: scheme.primaryContainer,
          borderRadius: BorderRadius.circular(AppRadius.sm),
        ),
        child: Icon(icon, color: scheme.onPrimaryContainer, size: 20),
      ),
      title: Text(title),
      subtitle: subtitle == null ? null : Text(subtitle!),
      trailing:
          trailing ??
          Row(
            mainAxisSize: MainAxisSize.min,
            children: [
              if (badge != null)
                StatusChip(label: badge!, tone: StatusTone.accent, dense: true),
              const SizedBox(width: 4),
              const Icon(Icons.chevron_right),
            ],
          ),
    );
  }
}
