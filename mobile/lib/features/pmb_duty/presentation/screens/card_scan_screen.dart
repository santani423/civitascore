import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:mobile_scanner/mobile_scanner.dart';

import '../../../../core/l10n/core_strings.dart';
import '../../../../core/theme/app_dimensions.dart';
import '../../../../core/widgets/app_feedback.dart';
import '../../../../core/widgets/state_views.dart';
import '../../../../core/widgets/ui_kit.dart';
import '../../domain/pmb_duty_models.dart';
import '../../l10n/pmb_duty_strings.dart';

/// Checks a participant in from a card token; returns the success text.
typedef CardTokenHandler = Future<String> Function(String token);

/// Continuous exam-card scanner: stays open so a proctor can check in a
/// queue of participants; the last result is shown under the preview.
class CardScanScreen extends StatefulWidget {
  const CardScanScreen({super.key, required this.onToken});
  final CardTokenHandler onToken;

  @override
  State<CardScanScreen> createState() => _CardScanScreenState();
}

class _CardScanScreenState extends State<CardScanScreen> {
  final _controller = MobileScannerController(
    formats: const [BarcodeFormat.qrCode],
  );
  bool _busy = false;
  String? _lastToken;
  DateTime? _lastAt;
  ({String text, bool error})? _result;

  /// Cards checked in successfully during this scan session. A card left in
  /// view must not be re-submitted (each call rewrites checked_in_at and adds
  /// an audit entry).
  final _handled = <String>{};

  @override
  void dispose() {
    _controller.dispose();
    super.dispose();
  }

  void _onDetect(BarcodeCapture capture) {
    String? raw;
    for (final b in capture.barcodes) {
      if (b.rawValue != null && b.rawValue!.isNotEmpty) {
        raw = b.rawValue!.trim();
        break;
      }
    }
    if (raw == null || _busy || _handled.contains(raw)) return;
    // The camera reports the same code many times per second; a failed card
    // may be retried 3 s after its previous response.
    if (raw == _lastToken &&
        _lastAt != null &&
        DateTime.now().difference(_lastAt!) < const Duration(seconds: 3)) {
      return;
    }
    _submit(raw);
  }

  Future<void> _submit(String token) async {
    if (_busy) return;
    final s = context.pmbDuty;
    final t = token.trim();
    _lastToken = t;
    if (!looksLikeCardToken(t)) {
      _lastAt = DateTime.now();
      setState(() => _result = (text: s.invalidQr, error: true));
      return;
    }
    setState(() => _busy = true);
    try {
      final text = await widget.onToken(t);
      _handled.add(t);
      HapticFeedback.mediumImpact();
      if (mounted) setState(() => _result = (text: text, error: false));
    } catch (e) {
      HapticFeedback.heavyImpact();
      if (mounted) {
        setState(() => _result = (text: context.l10n.errorFor(e), error: true));
      }
    } finally {
      _lastAt = DateTime.now();
      if (mounted) setState(() => _busy = false);
    }
  }

  Future<void> _manual() async {
    final s = context.pmbDuty;
    final token = await promptText(
      context,
      title: s.enterCode,
      label: s.cardToken,
      confirmLabel: context.l10n.submit,
      maxLines: 2,
      maxLength: 500,
    );
    if (token != null && token.trim().isNotEmpty) await _submit(token);
  }

  @override
  Widget build(BuildContext context) {
    final s = context.pmbDuty;
    final scheme = Theme.of(context).colorScheme;
    final result = _result;
    return Scaffold(
      appBar: AppBar(
        title: Text(s.scannerTitle),
        actions: [
          ValueListenableBuilder<MobileScannerState>(
            valueListenable: _controller,
            builder: (_, state, _) => state.torchState == TorchState.unavailable
                ? const SizedBox.shrink()
                : IconButton(
                    tooltip: s.torch,
                    onPressed: _controller.toggleTorch,
                    icon: Icon(
                      state.torchState == TorchState.on
                          ? Icons.flash_on
                          : Icons.flash_off,
                    ),
                  ),
          ),
          IconButton(
            tooltip: s.enterCode,
            onPressed: _busy ? null : _manual,
            icon: const Icon(Icons.keyboard_outlined),
          ),
        ],
      ),
      body: Column(
        children: [
          Expanded(
            child: Stack(
              fit: StackFit.expand,
              children: [
                MobileScanner(
                  controller: _controller,
                  onDetect: _onDetect,
                  errorBuilder: (context, _) => ColoredBox(
                    color: scheme.surface,
                    child: MessageView(
                      icon: Icons.no_photography_outlined,
                      tone: StatusTone.warning,
                      title: s.cameraError,
                      action: FilledButton.icon(
                        onPressed: _manual,
                        icon: const Icon(Icons.keyboard_outlined),
                        label: Text(s.enterCode),
                      ),
                    ),
                  ),
                ),
                // Target frame only over a working camera, not over the
                // error message and its manual-entry button.
                ValueListenableBuilder<MobileScannerState>(
                  valueListenable: _controller,
                  builder: (_, state, _) => state.error != null
                      ? const SizedBox.shrink()
                      : IgnorePointer(
                          child: Center(
                            child: Container(
                              width: 240,
                              height: 240,
                              decoration: BoxDecoration(
                                border: Border.all(
                                  color: scheme.primary,
                                  width: 3,
                                ),
                                borderRadius: BorderRadius.circular(
                                  AppRadius.lg,
                                ),
                              ),
                            ),
                          ),
                        ),
                ),
                if (_busy) const Center(child: CircularProgressIndicator()),
              ],
            ),
          ),
          SafeArea(
            top: false,
            minimum: const EdgeInsets.all(AppSpacing.lg),
            child: Column(
              mainAxisSize: MainAxisSize.min,
              children: [
                if (result != null)
                  Padding(
                    padding: const EdgeInsets.only(bottom: AppSpacing.sm),
                    child: AppBanner(
                      message: result.text,
                      tone: result.error
                          ? StatusTone.danger
                          : StatusTone.success,
                      icon: result.error
                          ? Icons.error_outline
                          : Icons.check_circle_outline,
                    ),
                  ),
                Text(
                  s.scannerHint,
                  textAlign: TextAlign.center,
                  style: Theme.of(context).textTheme.bodySmall,
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }
}
