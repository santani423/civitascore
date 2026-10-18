import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../l10n/app_language.dart';
import '../l10n/locale_controller.dart';

/// Globe icon that switches the UI language (ID / EN / 中文).
class LanguageMenuButton extends ConsumerWidget {
  const LanguageMenuButton({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final current = ref.watch(localeControllerProvider);
    return PopupMenuButton<AppLanguage>(
      tooltip: pick(context, id: 'Bahasa', en: 'Language', zh: '语言'),
      icon: const Icon(Icons.language),
      initialValue: current,
      onSelected: (l) =>
          ref.read(localeControllerProvider.notifier).setLanguage(l),
      itemBuilder: (_) => [
        for (final l in AppLanguage.values)
          CheckedPopupMenuItem(
            value: l,
            checked: l == current,
            child: Text(l.nativeName),
          ),
      ],
    );
  }
}
