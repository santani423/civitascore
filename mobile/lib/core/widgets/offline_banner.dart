import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../l10n/core_strings.dart';
import '../network/connectivity.dart';
import '../theme/app_palette.dart';

/// Thin banner shown above shell content while offline.
class OfflineBanner extends ConsumerWidget {
  const OfflineBanner({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final offline = ref.watch(isOfflineProvider).valueOrNull ?? false;
    final (fg, bg) = context.toneColors(StatusTone.warning);
    return AnimatedSize(
      duration: const Duration(milliseconds: 200),
      child: offline
          ? Material(
              color: bg,
              child: SafeArea(
                bottom: false,
                child: Padding(
                  padding: const EdgeInsets.symmetric(
                    horizontal: 16,
                    vertical: 6,
                  ),
                  child: Row(
                    children: [
                      Icon(Icons.wifi_off_rounded, size: 16, color: fg),
                      const SizedBox(width: 8),
                      Expanded(
                        child: Text(
                          context.l10n.offlineBanner,
                          style: Theme.of(
                            context,
                          ).textTheme.labelMedium?.copyWith(color: fg),
                        ),
                      ),
                    ],
                  ),
                ),
              ),
            )
          : const SizedBox(width: double.infinity),
    );
  }
}
