import 'package:flutter/material.dart';

import '../l10n/core_strings.dart';

void showAppSnack(
  BuildContext context,
  String message, {
  bool isError = false,
}) {
  final scheme = Theme.of(context).colorScheme;
  ScaffoldMessenger.of(context)
    ..hideCurrentSnackBar()
    ..showSnackBar(
      SnackBar(
        content: Text(message),
        backgroundColor: isError ? scheme.error : null,
        showCloseIcon: isError,
      ),
    );
}

/// Shows the localized error for [error] (ApiFailure aware).
void showErrorSnack(BuildContext context, Object error) =>
    showAppSnack(context, context.l10n.errorFor(error), isError: true);

/// Confirmation dialog; returns true when confirmed.
Future<bool> confirmAction(
  BuildContext context, {
  required String title,
  String? message,
  String? confirmLabel,
  bool destructive = false,
}) async {
  final s = context.l10n;
  final scheme = Theme.of(context).colorScheme;
  final result = await showDialog<bool>(
    context: context,
    builder: (ctx) => AlertDialog(
      title: Text(title),
      content: message == null ? null : Text(message),
      actions: [
        TextButton(
          onPressed: () => Navigator.pop(ctx, false),
          child: Text(s.cancel),
        ),
        FilledButton(
          style: destructive
              ? FilledButton.styleFrom(
                  backgroundColor: scheme.error,
                  foregroundColor: scheme.onError,
                )
              : null,
          onPressed: () => Navigator.pop(ctx, true),
          child: Text(confirmLabel ?? s.confirm),
        ),
      ],
    ),
  );
  return result ?? false;
}

/// Prompts for a text (e.g. rejection note). Returns null when cancelled.
Future<String?> promptText(
  BuildContext context, {
  required String title,
  String? label,
  String? confirmLabel,
  bool required = true,
  int maxLines = 4,
  int? maxLength,
}) async {
  final s = context.l10n;
  final controller = TextEditingController();
  final formKey = GlobalKey<FormState>();
  final result = await showDialog<String>(
    context: context,
    builder: (ctx) => AlertDialog(
      title: Text(title),
      content: Form(
        key: formKey,
        child: TextFormField(
          controller: controller,
          autofocus: true,
          maxLines: maxLines,
          maxLength: maxLength,
          decoration: InputDecoration(labelText: label),
          validator: (v) => required && (v == null || v.trim().isEmpty)
              ? s.validationRequired
              : null,
        ),
      ),
      actions: [
        TextButton(onPressed: () => Navigator.pop(ctx), child: Text(s.cancel)),
        FilledButton(
          onPressed: () {
            if (formKey.currentState!.validate()) {
              Navigator.pop(ctx, controller.text.trim());
            }
          },
          child: Text(confirmLabel ?? s.submit),
        ),
      ],
    ),
  );
  controller.dispose();
  return result;
}
