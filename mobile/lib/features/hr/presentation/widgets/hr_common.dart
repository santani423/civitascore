import 'package:flutter/material.dart';

import '../../../../core/l10n/core_strings.dart';
import '../../../../core/network/api_failure.dart';
import '../../../../core/theme/app_dimensions.dart';
import '../../../../core/utils/file_actions.dart';
import '../../../../core/widgets/app_feedback.dart';
import '../../../../core/widgets/state_views.dart';
import '../../../../core/widgets/ui_kit.dart';
import '../../l10n/hr_strings.dart';

/// Constrains content on wide screens.
class HrBody extends StatelessWidget {
  const HrBody({super.key, required this.child});
  final Widget child;

  @override
  Widget build(BuildContext context) => Center(
    child: ConstrainedBox(
      constraints: const BoxConstraints(maxWidth: 720),
      child: child,
    ),
  );
}

bool isNotLinked(Object error) =>
    error is ApiFailure && error.kind == ApiFailureKind.notFound;

/// Explanatory empty state for a 404 caused by a missing employee/lecturer
/// link (known backend gap), instead of a generic error.
class NotLinkedView extends StatelessWidget {
  const NotLinkedView({
    super.key,
    required this.title,
    required this.body,
    this.serverMessage,
    this.onRetry,
  });
  final String title;
  final String body;
  final String? serverMessage;
  final VoidCallback? onRetry;

  @override
  Widget build(BuildContext context) => MessageView(
    icon: Icons.link_off_outlined,
    tone: StatusTone.warning,
    title: title,
    message: serverMessage == null ? body : '$body\n\n$serverMessage',
    action: onRetry == null
        ? null
        : OutlinedButton(onPressed: onRetry, child: Text(context.l10n.retry)),
  );
}

/// Two-step upload field: picks a file, uploads it right away and keeps the
/// returned file id.
class AttachmentField extends StatefulWidget {
  const AttachmentField({
    super.key,
    required this.upload,
    required this.fileId,
    required this.onChanged,
    this.helper,
    this.errorText,
  });
  final Future<String> Function(String path) upload;
  final String? fileId;
  final ValueChanged<String?> onChanged;
  final String? helper;
  final String? errorText;

  @override
  State<AttachmentField> createState() => _AttachmentFieldState();
}

class _AttachmentFieldState extends State<AttachmentField> {
  bool _busy = false;
  String? _name;

  Future<void> _pick() async {
    final picked = await pickUpload(context);
    if (picked == null || !mounted) return;
    setState(() => _busy = true);
    try {
      final id = await widget.upload(picked.path);
      if (!mounted) return;
      setState(() => _name = picked.name);
      widget.onChanged(id);
    } catch (e) {
      if (mounted) showErrorSnack(context, e);
    } finally {
      if (mounted) setState(() => _busy = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    final h = context.hrL10n;
    final scheme = Theme.of(context).colorScheme;
    final text = Theme.of(context).textTheme;
    final has = widget.fileId != null;
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text(h.attachment, style: text.labelLarge),
        const SizedBox(height: AppSpacing.sm),
        Row(
          children: [
            Expanded(
              child: has
                  ? Row(
                      children: [
                        Icon(Icons.check_circle_outline, color: scheme.primary),
                        const SizedBox(width: AppSpacing.sm),
                        Expanded(
                          child: Text(
                            _name ?? h.attachmentUploaded,
                            maxLines: 1,
                            overflow: TextOverflow.ellipsis,
                          ),
                        ),
                      ],
                    )
                  : OutlinedButton.icon(
                      onPressed: _busy ? null : _pick,
                      icon: _busy
                          ? const SizedBox(
                              width: 16,
                              height: 16,
                              child: CircularProgressIndicator(strokeWidth: 2),
                            )
                          : const Icon(Icons.attach_file),
                      label: Text(_busy ? h.uploading : h.uploadAttachment),
                    ),
            ),
            if (has)
              IconButton(
                tooltip: h.removeAttachment,
                onPressed: () {
                  setState(() => _name = null);
                  widget.onChanged(null);
                },
                icon: const Icon(Icons.close),
              ),
          ],
        ),
        if (widget.helper != null)
          Padding(
            padding: const EdgeInsets.only(top: AppSpacing.xs),
            child: Text(
              widget.helper!,
              style: text.bodySmall?.copyWith(color: scheme.onSurfaceVariant),
            ),
          ),
        if (widget.errorText != null)
          Padding(
            padding: const EdgeInsets.only(top: AppSpacing.xs),
            child: Text(
              widget.errorText!,
              style: text.bodySmall?.copyWith(color: scheme.error),
            ),
          ),
      ],
    );
  }
}
