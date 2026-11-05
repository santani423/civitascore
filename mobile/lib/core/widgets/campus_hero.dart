import 'package:flutter/material.dart';

import '../theme/app_colors.dart';
import '../theme/app_dimensions.dart';

/// Tinggi minimum hero foto kampus di beranda (mahasiswa dan dosen). Hero
/// bertambah tinggi bila teks (skala font besar, nama panjang) membutuhkannya.
const kCampusHeroHeight = 260.0;

/// Hero beranda: foto fasad kampus dengan gradien navy, sapaan, nama, dan
/// chip identitas. Gradien membuat teks putih tetap terbaca di light maupun
/// dark mode. [bottomInset] menjaga teks tetap di atas kartu yang menumpang
/// di tepi bawah (mis. kartu semester mahasiswa).
class CampusHeroBanner extends StatelessWidget {
  const CampusHeroBanner({
    super.key,
    required this.greeting,
    required this.name,
    required this.welcome,
    this.details = const [],
    this.bottomInset = AppSpacing.lg,
  });

  /// Baris kecil di atas nama, mis. "Selamat pagi".
  final String greeting;
  final String name;
  final String welcome;

  /// Chip di bawah sambutan (NIM, prodi, peran).
  final List<String> details;
  final double bottomInset;

  @override
  Widget build(BuildContext context) {
    final text = Theme.of(context).textTheme;
    const fg = Colors.white;
    return ClipRRect(
      borderRadius: const BorderRadius.only(
        bottomLeft: Radius.circular(AppRadius.xl),
        bottomRight: Radius.circular(AppRadius.xl),
      ),
      // Stack mengikuti tinggi teks (non-positioned), dengan tinggi minimum
      // kCampusHeroHeight dan lebar penuh. Foto dan gradien mengisi area.
      child: ConstrainedBox(
        constraints: const BoxConstraints(
          minWidth: double.infinity,
          minHeight: kCampusHeroHeight,
        ),
        child: Stack(
          alignment: Alignment.bottomLeft,
          children: [
            Positioned.fill(
              child: Image.asset(
                'assets/images/kampus_fasad.webp',
                fit: BoxFit.cover,
                semanticLabel: 'Universitas Atma Jaya',
              ),
            ),
            Positioned.fill(
              child: DecoratedBox(
                decoration: BoxDecoration(
                  gradient: LinearGradient(
                    begin: Alignment.topCenter,
                    end: Alignment.bottomCenter,
                    colors: [
                      AppColorsLight.navy.withValues(alpha: 0.2),
                      AppColorsLight.navy.withValues(alpha: 0.92),
                    ],
                  ),
                ),
              ),
            ),
            Padding(
              padding: EdgeInsets.fromLTRB(
                AppSpacing.lg,
                AppSpacing.lg,
                AppSpacing.lg,
                bottomInset,
              ),
              child: Column(
                mainAxisSize: MainAxisSize.min,
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    greeting,
                    style: text.labelLarge?.copyWith(
                      color: fg.withValues(alpha: 0.9),
                    ),
                  ),
                  Text(
                    name,
                    style: text.headlineSmall?.copyWith(
                      color: fg,
                      fontWeight: FontWeight.w700,
                    ),
                    maxLines: 2,
                    overflow: TextOverflow.ellipsis,
                  ),
                  Text(
                    welcome,
                    style: text.bodyMedium?.copyWith(
                      color: fg.withValues(alpha: 0.9),
                    ),
                  ),
                  if (details.isNotEmpty)
                    Padding(
                      padding: const EdgeInsets.only(top: AppSpacing.sm),
                      child: Wrap(
                        spacing: AppSpacing.sm,
                        runSpacing: AppSpacing.sm,
                        children: [
                          for (final d in details)
                            DecoratedBox(
                              decoration: BoxDecoration(
                                color: Colors.white.withValues(alpha: 0.16),
                                borderRadius: BorderRadius.circular(
                                  AppRadius.full,
                                ),
                              ),
                              child: Padding(
                                padding: const EdgeInsets.symmetric(
                                  horizontal: AppSpacing.md,
                                  vertical: AppSpacing.xs,
                                ),
                                child: Text(
                                  d,
                                  style: text.labelMedium?.copyWith(color: fg),
                                ),
                              ),
                            ),
                        ],
                      ),
                    ),
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }
}
