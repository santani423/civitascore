import 'dart:async';

import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../../core/l10n/core_strings.dart';
import '../../../../core/theme/app_dimensions.dart';
import '../../../../core/utils/formatters.dart';
import '../../../../core/widgets/state_views.dart';
import '../../../../core/widgets/ui_kit.dart';
import '../../data/exam_api.dart';
import '../../domain/exam_monitoring.dart';
import '../../l10n/exam_strings.dart';
import '../providers.dart';
import 'participant_tile.dart';

/// Interval polling pemantauan (backend tidak punya push/WebSocket).
const kMonitorRefreshInterval = Duration(seconds: 15);

/// Pemantauan peserta: daftar peserta + pelanggaran terbaru, diperbarui
/// otomatis tiap ~15 detik selama tab terlihat (timer berhenti saat tab
/// dibuang atau aplikasi di latar belakang).
class MonitorTab extends ConsumerStatefulWidget {
  const MonitorTab({super.key, required this.examId});

  final String examId;

  @override
  ConsumerState<MonitorTab> createState() => _MonitorTabState();
}

class _MonitorTabState extends ConsumerState<MonitorTab>
    with WidgetsBindingObserver {
  Timer? _timer;
  DateTime? _since;
  List<ExamViolation> _alerts = const [];
  Object? _alertError;

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addObserver(this);
    _poll(initial: true);
    _startTimer();
  }

  void _startTimer() {
    _timer?.cancel();
    _timer = Timer.periodic(kMonitorRefreshInterval, (_) => _poll());
  }

  @override
  void didChangeAppLifecycleState(AppLifecycleState state) {
    if (state == AppLifecycleState.resumed) {
      _poll();
      _startTimer();
    } else {
      _timer?.cancel();
    }
  }

  @override
  void dispose() {
    WidgetsBinding.instance.removeObserver(this);
    _timer?.cancel();
    super.dispose();
  }

  Future<void> _poll({bool initial = false}) async {
    if (!initial) ref.invalidate(examParticipantsProvider(widget.examId));
    try {
      final fresh = await ref
          .read(examApiProvider)
          .recentViolations(widget.examId, since: _since);
      if (!mounted) return;
      setState(() {
        _alertError = null;
        if (fresh.isNotEmpty) {
          final last = fresh
              .map((v) => v.occurredAt)
              .whereType<DateTime>()
              .fold<DateTime?>(
                _since,
                (a, b) => a == null || b.isAfter(a) ? b : a,
              );
          _since = last;
          _alerts = [...fresh.reversed, ..._alerts].take(30).toList();
        }
      });
    } catch (e) {
      if (mounted) setState(() => _alertError = e);
    }
  }

  @override
  Widget build(BuildContext context) {
    final s = context.examL10n;
    final text = Theme.of(context).textTheme;
    final participants = ref.watch(examParticipantsProvider(widget.examId));

    return RefreshIndicator(
      onRefresh: () async {
        await _poll();
        await ref.read(examParticipantsProvider(widget.examId).future);
      },
      child: ListView(
        physics: const AlwaysScrollableScrollPhysics(),
        padding: const EdgeInsets.all(AppSpacing.lg),
        children: [
          Row(
            children: [
              Icon(Icons.sync, size: 16, color: context.palette.mutedText),
              const SizedBox(width: AppSpacing.xs),
              Expanded(
                child: Text(
                  s.autoRefresh,
                  style: text.bodySmall?.copyWith(
                    color: context.palette.mutedText,
                  ),
                ),
              ),
            ],
          ),
          SectionHeader(
            title: s.alertsTitle,
            padding: const EdgeInsets.only(
              top: AppSpacing.md,
              bottom: AppSpacing.sm,
            ),
          ),
          if (_alertError != null && _alerts.isEmpty)
            AppBanner(
              message: context.l10n.errorFor(_alertError!),
              tone: StatusTone.warning,
            )
          else if (_alerts.isEmpty)
            Text(s.noAlerts, style: text.bodyMedium)
          else
            for (final v in _alerts)
              Padding(
                padding: const EdgeInsets.only(bottom: AppSpacing.xs),
                child: AppBanner(
                  tone: StatusTone.danger,
                  icon: Icons.report_gmailerrorred_outlined,
                  message:
                      '${s.violationAlert(v)} - ${context.formatTime(v.occurredAt)}',
                ),
              ),
          SectionHeader(
            title: s.participantsTitle,
            padding: const EdgeInsets.only(
              top: AppSpacing.lg,
              bottom: AppSpacing.sm,
            ),
          ),
          participants.when(
            skipLoadingOnRefresh: true,
            skipLoadingOnReload: true,
            loading: () => const SizedBox(
              height: 200,
              child: Center(child: CircularProgressIndicator()),
            ),
            error: (e, _) => SizedBox(
              height: 260,
              child: ErrorView(
                error: e,
                onRetry: () =>
                    ref.invalidate(examParticipantsProvider(widget.examId)),
              ),
            ),
            data: (list) => list.isEmpty
                ? SizedBox(
                    height: 240,
                    child: EmptyView(
                      icon: Icons.groups_outlined,
                      title: s.noParticipants,
                    ),
                  )
                : Column(
                    children: [
                      for (final p in list)
                        Padding(
                          padding: const EdgeInsets.only(bottom: AppSpacing.sm),
                          child: ParticipantTile(participant: p),
                        ),
                    ],
                  ),
          ),
        ],
      ),
    );
  }
}
