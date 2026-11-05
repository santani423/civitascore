import 'package:flutter/material.dart';

import '../theme/app_dimensions.dart';
import 'ui_kit.dart';

/// Kartu layanan (mockup "Menu Utama"): ikon, judul, deskripsi, dan indikator
/// navigasi. Dipakai di Menu Utama dan hub modul agar satu pola visual berlaku
/// di seluruh aplikasi.
class ServiceCard extends StatelessWidget {
  const ServiceCard({
    super.key,
    required this.icon,
    required this.title,
    this.subtitle,
    this.onTap,
    this.tone,
    this.footer,
    this.trailing,
    this.badge,
  });

  final IconData icon;
  final String title;
  final String? subtitle;
  final VoidCallback? onTap;

  /// Warna ikon (mis. akademik = biru). Null → warna primer aplikasi.
  final StatusTone? tone;

  /// Baris tambahan di bawah deskripsi, mis. chip "Segera hadir".
  final Widget? footer;
  final Widget? trailing;

  /// Label status singkat di baris bawah, mis. "Menunggu backend".
  final String? badge;

  @override
  Widget build(BuildContext context) {
    final text = Theme.of(context).textTheme;
    final scheme = Theme.of(context).colorScheme;
    final (fg, bg) = tone == null
        ? (scheme.onPrimaryContainer, scheme.primaryContainer)
        : context.toneColors(tone!);
    return Padding(
      padding: const EdgeInsets.only(bottom: AppSpacing.sm),
      child: AppSurfaceCard(
        onTap: onTap,
        padding: const EdgeInsets.all(AppSpacing.md),
        child: Row(
          children: [
            Container(
              width: 44,
              height: 44,
              decoration: BoxDecoration(
                color: bg,
                borderRadius: BorderRadius.circular(AppRadius.md),
              ),
              child: Icon(icon, size: 22, color: fg),
            ),
            const SizedBox(width: AppSpacing.md),
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    title,
                    style: text.titleSmall,
                    maxLines: 2,
                    overflow: TextOverflow.ellipsis,
                  ),
                  if (subtitle != null)
                    Text(
                      subtitle!,
                      style: text.bodySmall,
                      maxLines: 2,
                      overflow: TextOverflow.ellipsis,
                    ),
                  // Badge di baris bawah, bukan di sisi kanan, agar label
                  // panjang tidak mendesak judul pada layar sempit.
                  if (badge != null || footer != null)
                    Padding(
                      padding: const EdgeInsets.only(top: AppSpacing.xs),
                      child: Wrap(
                        spacing: AppSpacing.sm,
                        runSpacing: AppSpacing.xs,
                        children: [
                          if (badge != null)
                            StatusChip(
                              label: badge!,
                              tone: StatusTone.accent,
                              dense: true,
                            ),
                          ?footer,
                        ],
                      ),
                    ),
                ],
              ),
            ),
            trailing ??
                Icon(
                  Icons.chevron_right_rounded,
                  color: scheme.onSurfaceVariant,
                ),
          ],
        ),
      ),
    );
  }
}
