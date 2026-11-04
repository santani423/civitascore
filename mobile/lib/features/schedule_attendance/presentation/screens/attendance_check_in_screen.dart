import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import 'package:mobile_scanner/mobile_scanner.dart';

import '../../../../core/l10n/core_strings.dart';
import '../../../../core/theme/app_dimensions.dart';
import '../../../../core/utils/formatters.dart';
import '../../../../core/widgets/app_feedback.dart';
import '../../../../core/widgets/state_views.dart';
import '../../../../core/widgets/ui_kit.dart';
import '../../data/schedule_attendance_api.dart';
import '../../domain/check_in_payload.dart';
import '../../domain/models.dart';
import '../../l10n/schedule_attendance_strings.dart';
import '../widgets/constrained_body.dart';

/// Presensi mandiri: memindai QR dosen lalu mengirim payload-nya ke server.
/// Setelah berhasil kamera berhenti, supaya kode yang masih terlihat di layar
/// tidak dikirim ulang.
class StudentAttendanceCheckInScreen extends ConsumerStatefulWidget {
  const StudentAttendanceCheckInScreen({super.key});

  @override
  ConsumerState<StudentAttendanceCheckInScreen> createState() =>
      _StudentAttendanceCheckInScreenState();
}

class _StudentAttendanceCheckInScreenState
    extends ConsumerState<StudentAttendanceCheckInScreen> {
  final _controller = MobileScannerController(
    formats: const [BarcodeFormat.qrCode],
  );
  bool _busy = false;
  String? _lastRaw;
  DateTime? _lastAt;
  String? _errorText;
  CheckInResult? _done;

  @override
  void dispose() {
    _controller.dispose();
    super.dispose();
  }

  void _onDetect(BarcodeCapture capture) {
    if (_busy || _done != null) return;

    final raw = capture.barcodes
        .map((barcode) => barcode.rawValue)
        .firstWhere(
          (value) => value != null && value.isNotEmpty,
          orElse: () => null,
        );
    if (raw == null) return;

    // Kamera melaporkan kode yang sama berkali-kali per detik; setelah satu
    // percobaan, tunggu 3 detik sebelum memproses kode yang sama lagi.
    if (raw == _lastRaw &&
        _lastAt != null &&
        DateTime.now().difference(_lastAt!) < const Duration(seconds: 3)) {
      return;
    }
    _lastRaw = raw;
    _lastAt = DateTime.now();
    _submit(raw);
  }

  Future<void> _submit(String raw) async {
    final payload = parseCheckInPayload(raw);
    if (payload == null) {
      setState(() => _errorText = context.sa.checkInInvalidQr);
      return;
    }

    setState(() {
      _busy = true;
      _errorText = null;
    });
    try {
      final result = await ref
          .read(scheduleAttendanceApiProvider)
          .checkInByQr(payload);
      HapticFeedback.mediumImpact();
      await _controller.stop();
      if (mounted) setState(() => _done = result);
    } catch (e) {
      HapticFeedback.heavyImpact();
      if (mounted) setState(() => _errorText = context.l10n.errorFor(e));
    } finally {
      if (mounted) setState(() => _busy = false);
    }
  }

  /// Jalur cadangan bila kamera tidak tersedia (mis. emulator); kode diketik lalu diproses seperti pindaian.
  Future<void> _manual() async {
    final s = context.sa;
    final code = await promptText(
      context,
      title: s.checkInEnterCode,
      label: s.checkInCodeLabel,
      confirmLabel: context.l10n.submit,
      maxLines: 2,
      maxLength: 120,
    );
    if (code != null && code.trim().isNotEmpty) await _submit(code);
  }

  @override
  Widget build(BuildContext context) {
    final s = context.sa;
    final done = _done;
    return Scaffold(
      appBar: AppBar(
        title: Text(s.checkInTitle),
        actions: [
          if (done == null)
            IconButton(
              tooltip: s.checkInEnterCode,
              onPressed: _busy ? null : _manual,
              icon: const Icon(Icons.keyboard_outlined),
            ),
        ],
      ),
      body: SafeArea(
        child: done == null
            ? _buildScanner(context)
            : ConstrainedBody(
                child: CheckInResultView(
                  result: done,
                  onDone: () => context.pop(),
                ),
              ),
      ),
    );
  }

  Widget _buildScanner(BuildContext context) {
    final s = context.sa;
    final scheme = Theme.of(context).colorScheme;
    return Column(
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
                    title: s.checkInCameraError,
                    action: FilledButton.icon(
                      onPressed: () => _controller.start(),
                      icon: const Icon(Icons.refresh),
                      label: Text(s.checkInScanAgain),
                    ),
                  ),
                ),
              ),
              // Bingkai sasaran hanya tampil di atas kamera yang berfungsi.
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
                              borderRadius: BorderRadius.circular(AppRadius.lg),
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
              if (_errorText != null)
                Padding(
                  padding: const EdgeInsets.only(bottom: AppSpacing.sm),
                  child: AppBanner(
                    message: _errorText!,
                    tone: StatusTone.danger,
                    icon: Icons.error_outline,
                  ),
                ),
              Text(
                s.checkInHint,
                textAlign: TextAlign.center,
                style: Theme.of(context).textTheme.bodySmall,
              ),
            ],
          ),
        ),
      ],
    );
  }
}

/// Hasil setelah presensi tercatat. Dipisah dari layar pemindai agar bisa diuji
/// tanpa kamera.
class CheckInResultView extends StatelessWidget {
  const CheckInResultView({
    super.key,
    required this.result,
    required this.onDone,
  });
  final CheckInResult result;
  final VoidCallback onDone;

  @override
  Widget build(BuildContext context) {
    final s = context.sa;
    final text = Theme.of(context).textTheme;
    final scheme = Theme.of(context).colorScheme;
    return ListView(
      padding: const EdgeInsets.all(AppSpacing.lg),
      children: [
        AppBanner(
          message: result.alreadyRecorded
              ? s.checkInAlreadyTitle
              : s.checkInSuccessTitle,
          tone: StatusTone.success,
          icon: Icons.check_circle_outline,
        ),
        const SizedBox(height: AppSpacing.md),
        AppSurfaceCard(
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text(result.courseName, style: text.titleMedium),
              Text(
                [?result.courseCode, ?result.classCode].join(' · '),
                style: text.bodySmall?.copyWith(color: scheme.onSurfaceVariant),
              ),
              const SizedBox(height: AppSpacing.sm),
              Text(s.meetingNo(result.meetingNumber), style: text.labelLarge),
              Text(
                context.formatDate(result.meetingDate),
                style: text.bodySmall,
              ),
              const SizedBox(height: AppSpacing.sm),
              StatusChip(
                label:
                    result.statusLabel ?? s.meetingStatus(result.status, null),
                tone: StatusTone.success,
                dense: true,
              ),
            ],
          ),
        ),
        const SizedBox(height: AppSpacing.lg),
        FilledButton(onPressed: onDone, child: Text(s.checkInDone)),
      ],
    );
  }
}
