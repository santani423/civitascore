import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:shimmer/shimmer.dart';

import '../l10n/core_strings.dart';
import '../network/api_failure.dart';
import '../theme/app_dimensions.dart';
import '../theme/app_palette.dart';

/// Centered illustration-like message used for empty/error/info states.
class MessageView extends StatelessWidget {
  const MessageView({
    super.key,
    required this.icon,
    required this.title,
    this.message,
    this.action,
    this.tone = StatusTone.neutral,
  });

  final IconData icon;
  final String title;
  final String? message;
  final Widget? action;
  final StatusTone tone;

  @override
  Widget build(BuildContext context) {
    final (fg, bg) = context.toneColors(tone);
    final text = Theme.of(context).textTheme;
    return Center(
      child: SingleChildScrollView(
        padding: const EdgeInsets.all(AppSpacing.xl2),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            Container(
              padding: const EdgeInsets.all(18),
              decoration: BoxDecoration(color: bg, shape: BoxShape.circle),
              child: Icon(icon, size: 36, color: fg),
            ),
            const SizedBox(height: AppSpacing.lg),
            Text(title, style: text.titleMedium, textAlign: TextAlign.center),
            if (message != null) ...[
              const SizedBox(height: AppSpacing.sm),
              Text(
                message!,
                style: text.bodyMedium?.copyWith(
                  color: context.palette.mutedText,
                ),
                textAlign: TextAlign.center,
              ),
            ],
            if (action != null) ...[
              const SizedBox(height: AppSpacing.lg),
              action!,
            ],
          ],
        ),
      ),
    );
  }
}

class EmptyView extends StatelessWidget {
  const EmptyView({
    super.key,
    this.title,
    this.message,
    this.icon = Icons.inbox_outlined,
    this.action,
  });
  final String? title;
  final String? message;
  final IconData icon;
  final Widget? action;

  @override
  Widget build(BuildContext context) => MessageView(
    icon: icon,
    title: title ?? context.l10n.emptyTitle,
    message: message ?? context.l10n.emptyBody,
    action: action,
  );
}

class ErrorView extends StatelessWidget {
  const ErrorView({super.key, required this.error, this.onRetry});
  final Object error;
  final VoidCallback? onRetry;

  @override
  Widget build(BuildContext context) {
    final s = context.l10n;
    final offline = error is ApiFailure && (error as ApiFailure).isOffline;
    final forbidden =
        error is ApiFailure &&
        (error as ApiFailure).kind == ApiFailureKind.forbidden;
    return MessageView(
      icon: offline
          ? Icons.wifi_off_rounded
          : (forbidden ? Icons.lock_outline : Icons.error_outline_rounded),
      tone: forbidden ? StatusTone.warning : StatusTone.danger,
      title: forbidden ? s.accessDeniedTitle : s.errorTitle,
      message: s.errorFor(error),
      action: onRetry == null
          ? null
          : OutlinedButton.icon(
              onPressed: onRetry,
              icon: const Icon(Icons.refresh),
              label: Text(s.retry),
            ),
    );
  }
}

/// Shimmer placeholder list shown while the first page loads.
class SkeletonList extends StatelessWidget {
  const SkeletonList({
    super.key,
    this.itemCount = 6,
    this.itemHeight = 76,
    this.padding,
  });
  final int itemCount;
  final double itemHeight;
  final EdgeInsets? padding;

  @override
  Widget build(BuildContext context) {
    final p = context.palette;
    return Shimmer.fromColors(
      baseColor: p.subtleSurface,
      highlightColor: Theme.of(context).colorScheme.surface,
      child: ListView.separated(
        physics: const NeverScrollableScrollPhysics(),
        padding: padding ?? const EdgeInsets.all(AppSpacing.lg),
        itemCount: itemCount,
        separatorBuilder: (_, _) => const SizedBox(height: AppSpacing.md),
        itemBuilder: (_, _) => Container(
          height: itemHeight,
          decoration: BoxDecoration(
            color: p.subtleSurface,
            borderRadius: BorderRadius.circular(AppRadius.lg),
          ),
        ),
      ),
    );
  }
}

/// Single shimmering block for a card that is still loading. Use it inside
/// the card's own frame so the layout does not jump when data arrives.
class SkeletonBox extends StatelessWidget {
  const SkeletonBox({super.key, required this.height, this.width});
  final double height;
  final double? width;

  @override
  Widget build(BuildContext context) {
    final p = context.palette;
    return Shimmer.fromColors(
      baseColor: p.subtleSurface,
      highlightColor: Theme.of(context).colorScheme.surface,
      child: Container(
        height: height,
        width: width,
        decoration: BoxDecoration(
          color: p.subtleSurface,
          borderRadius: BorderRadius.circular(AppRadius.md),
        ),
      ),
    );
  }
}

/// Renders an [AsyncValue] with consistent loading / error / data states.
/// Keeps showing previous data while refreshing.
class AsyncValueView<T> extends StatelessWidget {
  const AsyncValueView({
    super.key,
    required this.value,
    required this.data,
    this.onRetry,
    this.loading,
    this.isEmpty,
    this.empty,
  });

  final AsyncValue<T> value;
  final Widget Function(T data) data;
  final VoidCallback? onRetry;
  final Widget? loading;
  final bool Function(T data)? isEmpty;
  final Widget? empty;

  @override
  Widget build(BuildContext context) {
    return value.when(
      skipLoadingOnRefresh: true,
      skipLoadingOnReload: true,
      data: (d) =>
          (isEmpty?.call(d) ?? false) ? (empty ?? const EmptyView()) : data(d),
      loading: () => loading ?? const SkeletonList(),
      error: (e, _) => ErrorView(error: e, onRetry: onRetry),
    );
  }
}
