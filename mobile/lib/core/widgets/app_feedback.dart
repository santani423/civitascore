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
}) => showDialog<String>(
  context: context,
  builder: (_) => _PromptTextDialog(
    title: title,
    label: label,
    confirmLabel: confirmLabel,
    required: required,
    maxLines: maxLines,
    maxLength: maxLength,
  ),
);

/// Owns its controller so it is disposed only after the dialog's exit
/// animation (disposing right after `showDialog` returns throws).
class _PromptTextDialog extends StatefulWidget {
  const _PromptTextDialog({
    required this.title,
    required this.label,
    required this.confirmLabel,
    required this.required,
    required this.maxLines,
    required this.maxLength,
  });

  final String title;
  final String? label;
  final String? confirmLabel;
  final bool required;
  final int maxLines;
  final int? maxLength;

  @override
  State<_PromptTextDialog> createState() => _PromptTextDialogState();
}

class _PromptTextDialogState extends State<_PromptTextDialog> {
  final _controller = TextEditingController();
  final _formKey = GlobalKey<FormState>();

  @override
  void dispose() {
    _controller.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final s = context.l10n;
    return AlertDialog(
      title: Text(widget.title),
      content: Form(
        key: _formKey,
        child: TextFormField(
          controller: _controller,
          autofocus: true,
          maxLines: widget.maxLines,
          maxLength: widget.maxLength,
          decoration: InputDecoration(labelText: widget.label),
          validator: (v) => widget.required && (v == null || v.trim().isEmpty)
              ? s.validationRequired
              : null,
        ),
      ),
      actions: [
        TextButton(
          onPressed: () => Navigator.pop(context),
          child: Text(s.cancel),
        ),
        FilledButton(
          onPressed: () {
            if (_formKey.currentState!.validate()) {
              Navigator.pop(context, _controller.text.trim());
            }
          },
          child: Text(widget.confirmLabel ?? s.submit),
        ),
      ],
    );
  }
}
