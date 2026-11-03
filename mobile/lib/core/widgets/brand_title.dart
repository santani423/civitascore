import 'package:flutter/material.dart';

import '../theme/app_dimensions.dart';

/// Judul AppBar beranda: logo Universitas Atma Jaya dan wordmark One Atma.
/// Logo ditaruh di atas ubin putih tipis agar warna crest tetap terbaca di
/// light maupun dark mode.
class BrandAppBarTitle extends StatelessWidget {
  const BrandAppBarTitle({super.key});

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    return Row(
      mainAxisSize: MainAxisSize.min,
      children: [
        Container(
          padding: const EdgeInsets.symmetric(
            horizontal: AppSpacing.xs,
            vertical: AppSpacing.xs,
          ),
          decoration: BoxDecoration(
            color: Colors.white,
            borderRadius: BorderRadius.circular(AppRadius.sm),
            border: Border.all(color: theme.colorScheme.outlineVariant),
          ),
          child: Image.asset(
            'assets/images/logo_atma_jaya.png',
            height: 28,
            semanticLabel: 'Universitas Atma Jaya',
          ),
        ),
        const SizedBox(width: AppSpacing.sm),
        Text('One Atma', style: theme.textTheme.titleLarge),
      ],
    );
  }
}
