import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';

import '../l10n/core_strings.dart';
import '../router/route_paths.dart';
import '../theme/app_dimensions.dart';
import '../widgets/state_views.dart';
import '../widgets/ui_kit.dart';
import 'pending_features.dart';

/// Detail page of a feature awaiting backend: explains why it is not
/// available and lists the endpoints it needs. No sample data is shown.
class PendingFeatureScreen extends StatelessWidget {
  const PendingFeatureScreen({super.key, required this.featureKey});
  final String featureKey;

  @override
  Widget build(BuildContext context) {
    final s = context.l10n;
    final feature = pendingFeatureByKey(featureKey);
    if (feature == null) {
      return Scaffold(appBar: AppBar(), body: const EmptyView());
    }
    final text = Theme.of(context).textTheme;
    return Scaffold(
      appBar: AppBar(title: Text(feature.title(context))),
      body: ListView(
        padding: const EdgeInsets.all(AppSpacing.lg),
        children: [
          MessageView(
            icon: feature.icon,
            tone: StatusTone.accent,
            title: s.pendingBackendTitle,
            message: feature.description(context),
            action: StatusChip(
              label: s.pendingBackendBadge,
              tone: StatusTone.accent,
              icon: Icons.hourglass_top_rounded,
            ),
          ),
          const SizedBox(height: AppSpacing.lg),
          Text(s.pendingBackendBody, style: text.bodyMedium),
          const SizedBox(height: AppSpacing.md),
          for (final e in feature.endpoints)
            Padding(
              padding: const EdgeInsets.only(bottom: 6),
              child: SelectableText(
                e,
                style: text.bodySmall?.copyWith(
                  fontFamily: 'monospace',
                  color: Theme.of(context).colorScheme.onSurface,
                ),
              ),
            ),
          const SizedBox(height: AppSpacing.lg),
          Text(s.documentationSource, style: text.titleSmall),
          const SizedBox(height: AppSpacing.sm),
          for (final src in feature.sources)
            Text('• $src', style: text.bodySmall),
          const SizedBox(height: AppSpacing.sm),
          Text(feature.matrixIds.join(' · '), style: text.labelSmall),
        ],
      ),
    );
  }
}

/// List of pending features for an audience (shown from hub/account menus).
class PendingFeaturesScreen extends StatelessWidget {
  const PendingFeaturesScreen({super.key, required this.audience});
  final PendingAudience audience;

  @override
  Widget build(BuildContext context) {
    final s = context.l10n;
    final items = kPendingFeatures
        .where((f) => f.audience.contains(audience))
        .toList();
    return Scaffold(
      appBar: AppBar(title: Text(s.pendingFeaturesTitle)),
      body: ListView.separated(
        padding: const EdgeInsets.symmetric(vertical: AppSpacing.sm),
        itemCount: items.length,
        separatorBuilder: (_, _) => const Divider(indent: 72),
        itemBuilder: (context, i) => PendingFeatureTile(feature: items[i]),
      ),
    );
  }
}

class PendingFeatureTile extends StatelessWidget {
  const PendingFeatureTile({super.key, required this.feature});
  final PendingFeature feature;

  @override
  Widget build(BuildContext context) {
    return MenuTile(
      icon: feature.icon,
      title: feature.title(context),
      subtitle: feature.description(context),
      badge: context.l10n.pendingBackendBadge,
      onTap: () => context.push(RoutePaths.pendingFeature(feature.key)),
    );
  }
}
