import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import 'package:url_launcher/url_launcher.dart';

import '../../../../core/l10n/core_strings.dart';
import '../../../../core/router/route_paths.dart';
import '../../../../core/theme/app_dimensions.dart';
import '../../../../core/utils/formatters.dart';
import '../../../../core/widgets/app_feedback.dart';
import '../../../../core/widgets/state_views.dart';
import '../../../../core/widgets/ui_kit.dart';
import '../../../auth/presentation/controllers/auth_controller.dart';
import '../../data/pmb_duty_api.dart';
import '../../domain/pmb_duty_models.dart';
import '../../l10n/pmb_duty_strings.dart';
import '../providers.dart';
import '../widgets/duty_widgets.dart';
import 'card_scan_screen.dart';

/// MOB-OTH-02 — session detail and participant attendance (check-in by
/// scanning the exam card or by picking a participant).
class DutySessionScreen extends ConsumerStatefulWidget {
  const DutySessionScreen({super.key, required this.sessionId});
  final String sessionId;

  @override
  ConsumerState<DutySessionScreen> createState() => _DutySessionScreenState();
}

class _DutySessionScreenState extends ConsumerState<DutySessionScreen> {
  // Owned here so the field keeps its text when the list is rebuilt
  // (refresh, retry) and stays in sync with the filter.
  final _search = TextEditingController();
  String _query = '';

  @override
  void dispose() {
    _search.dispose();
    super.dispose();
  }

  DutySessionNotifier get _notifier =>
      ref.read(dutySessionProvider(widget.sessionId).notifier);

  String _markedText(SessionParticipant p) => context.pmbDuty.marked(
    p.displayName,
    context.pmbDuty.attendanceLabel(p.attendance),
  );

  Future<void> _openScanner() => Navigator.of(context).push<void>(
    MaterialPageRoute(
      builder: (_) => CardScanScreen(
        onToken: (token) async =>
            _markedText(await _notifier.checkIn(token: token)),
      ),
    ),
  );

  Future<void> _enterCode() async {
    final s = context.pmbDuty;
    final token = await promptText(
      context,
      title: s.enterCode,
      label: s.cardToken,
      confirmLabel: context.l10n.submit,
      maxLines: 2,
      maxLength: 500,
    );
    if (token == null || token.trim().isEmpty || !mounted) return;
    if (!looksLikeCardToken(token)) {
      showAppSnack(context, s.invalidQr, isError: true);
      return;
    }
    try {
      final p = await _notifier.checkIn(token: token.trim());
      if (mounted) showAppSnack(context, _markedText(p));
    } catch (e) {
      if (mounted) showErrorSnack(context, e);
    }
  }

  Future<void> _changeAttendance(SessionParticipant p) async {
    final s = context.pmbDuty;
    final picked = await showModalBottomSheet<PmbAttendance>(
      context: context,
      showDragHandle: true,
      builder: (ctx) => SafeArea(
        child: SingleChildScrollView(
          child: Column(
            mainAxisSize: MainAxisSize.min,
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              ListTile(
                title: Text(p.displayName),
                subtitle: Text('${p.number} · ${s.changeAttendance}'),
              ),
              for (final a in PmbAttendance.values)
                ListTile(
                  key: ValueKey('attendance:${a.value}'),
                  leading: Icon(
                    a == p.attendance
                        ? Icons.radio_button_checked
                        : Icons.radio_button_unchecked,
                  ),
                  title: Text(s.attendanceLabel(a)),
                  trailing: Icon(
                    Icons.circle,
                    size: 12,
                    color: context.toneColors(attendanceTone(a)).$1,
                  ),
                  onTap: () => Navigator.pop(ctx, a),
                ),
            ],
          ),
        ),
      ),
    );
    if (picked == null || picked == p.attendance || !mounted) return;
    // Absent/disqualified change the applicant's selection status and
    // notify them (ExamService::checkIn), so ask first.
    if (picked == PmbAttendance.absent ||
        picked == PmbAttendance.disqualified) {
      final ok = await confirmAction(
        context,
        title: s.impactTitle,
        message: s.impactBody(p.displayName, s.attendanceLabel(picked)),
        destructive: picked == PmbAttendance.disqualified,
      );
      if (!ok || !mounted) return;
    }
    try {
      final updated = await _notifier.checkIn(
        participantId: p.id,
        status: picked,
      );
      if (mounted) showAppSnack(context, _markedText(updated));
    } catch (e) {
      if (mounted) showErrorSnack(context, e);
    }
  }

  Future<void> _openAttendanceSheet() async {
    final s = context.pmbDuty;
    showAppSnack(context, context.l10n.downloading);
    try {
      final uri = await ref
          .read(pmbDutyApiProvider)
          .attendanceSheetUrl(widget.sessionId);
      final ok =
          uri != null &&
          await launchUrl(uri, mode: LaunchMode.externalApplication);
      if (!ok && mounted) {
        showAppSnack(context, s.openLinkFailed, isError: true);
      }
    } catch (e) {
      if (mounted) showErrorSnack(context, e);
    }
  }

  @override
  Widget build(BuildContext context) {
    final s = context.pmbDuty;
    final value = ref.watch(dutySessionProvider(widget.sessionId));
    final user = ref.watch(currentUserProvider);
    final detail = value.valueOrNull;
    final closed =
        detail != null &&
        attendanceClosedAt(detail.endsAt, DateTime.now()) &&
        !(user?.hasPermission('pmb_scores.import') ?? false);

    return Scaffold(
      appBar: AppBar(
        title: Text(
          detail == null ? s.moduleTitle : s.sessionTitle(detail.code),
        ),
        actions: [
          if (detail != null && !closed)
            IconButton(
              tooltip: s.enterCode,
              onPressed: _enterCode,
              icon: const Icon(Icons.keyboard_outlined),
            ),
          if (detail != null)
            IconButton(
              tooltip: s.attendanceSheet,
              onPressed: _openAttendanceSheet,
              icon: const Icon(Icons.picture_as_pdf_outlined),
            ),
        ],
      ),
      floatingActionButton: detail != null && !closed
          ? FloatingActionButton.extended(
              onPressed: _openScanner,
              icon: const Icon(Icons.qr_code_scanner),
              label: Text(s.scanQr),
            )
          : null,
      body: AsyncValueView<SessionDetail>(
        value: value,
        onRetry: () => ref.invalidate(dutySessionProvider(widget.sessionId)),
        data: (d) {
          final roles = d.rolesOf(user?.id ?? '');
          final visible = d.participants
              .where((p) => p.matches(_query))
              .toList();
          return RefreshIndicator(
            onRefresh: _notifier.refresh,
            child: DutyBody(
              child: ListView(
                physics: const AlwaysScrollableScrollPhysics(),
                padding: const EdgeInsets.fromLTRB(
                  AppSpacing.lg,
                  AppSpacing.lg,
                  AppSpacing.lg,
                  96,
                ),
                children: [
                  _InfoCard(detail: d, roles: roles),
                  if (closed)
                    Padding(
                      padding: const EdgeInsets.only(top: AppSpacing.md),
                      child: AppBanner(
                        message: s.attendanceClosed,
                        tone: StatusTone.warning,
                      ),
                    ),
                  if (canScoreWith(roles))
                    Padding(
                      padding: const EdgeInsets.only(top: AppSpacing.md),
                      child: MenuTile(
                        icon: Icons.edit_note_outlined,
                        title: s.inputScores,
                        subtitle: d.scoresLockedAt == null
                            ? null
                            : s.scoresLocked,
                        onTap: () => context.push(
                          RoutePaths.lecturerPmbSessionScores(d.id),
                        ),
                      ),
                    ),
                  const SizedBox(height: AppSpacing.md),
                  Wrap(
                    spacing: AppSpacing.xs,
                    runSpacing: AppSpacing.xs,
                    children: [
                      for (final a in PmbAttendance.values)
                        StatusChip(
                          dense: true,
                          tone: attendanceTone(a),
                          label: '${s.attendanceLabel(a)} ${d.count(a)}',
                        ),
                    ],
                  ),
                  const SizedBox(height: AppSpacing.md),
                  TextField(
                    decoration: InputDecoration(
                      prefixIcon: const Icon(Icons.search),
                      hintText: s.searchHint,
                      isDense: true,
                    ),
                    controller: _search,
                    onChanged: (v) => setState(() => _query = v),
                  ),
                  const SizedBox(height: AppSpacing.md),
                  if (d.participants.isEmpty)
                    EmptyView(
                      title: s.noParticipantsTitle,
                      icon: Icons.groups_outlined,
                    )
                  else if (visible.isEmpty)
                    EmptyView(title: s.noMatchTitle, icon: Icons.search_off)
                  else
                    for (final p in visible)
                      Padding(
                        padding: const EdgeInsets.only(bottom: AppSpacing.sm),
                        child: _ParticipantTile(
                          participant: p,
                          onTap: closed ? null : () => _changeAttendance(p),
                        ),
                      ),
                  const SizedBox(height: AppSpacing.sm),
                  Text(
                    s.privacyNote,
                    style: Theme.of(context).textTheme.bodySmall,
                  ),
                ],
              ),
            ),
          );
        },
      ),
    );
  }
}

class _InfoCard extends StatelessWidget {
  const _InfoCard({required this.detail, required this.roles});
  final SessionDetail detail;
  final Set<PmbExaminerRole> roles;

  @override
  Widget build(BuildContext context) {
    final s = context.pmbDuty;
    final d = detail;
    return AppSurfaceCard(
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(
            d.scheduleTitle ?? s.sessionTitle(d.code),
            style: Theme.of(context).textTheme.titleMedium,
          ),
          const SizedBox(height: AppSpacing.sm),
          InfoRow(
            label: s.time,
            value: sessionTimeRange(context, d.startsAt, d.endsAt),
          ),
          InfoRow(label: s.mode, value: s.modeLabel(d.mode)),
          InfoRow(label: s.room, value: d.roomLabel ?? s.online),
          InfoRow(
            label: s.capacity,
            value: '${d.participants.length}/${d.capacity}',
          ),
          if (d.checkinOpensAt != null)
            InfoRow(
              label: s.checkinOpens,
              value: context.formatDateTime(d.checkinOpensAt),
            ),
          if (roles.isNotEmpty)
            InfoRow(
              label: s.myRole,
              value: [
                for (final r in PmbExaminerRole.values)
                  if (roles.contains(r)) s.roleLabel(r),
              ].join(', '),
            ),
          if (d.scoresLockedAt != null)
            Padding(
              padding: const EdgeInsets.only(top: AppSpacing.sm),
              child: StatusChip(
                dense: true,
                tone: StatusTone.warning,
                icon: Icons.lock_outline,
                label: s.scoresLocked,
              ),
            ),
        ],
      ),
    );
  }
}

class _ParticipantTile extends StatelessWidget {
  const _ParticipantTile({required this.participant, this.onTap});
  final SessionParticipant participant;
  final VoidCallback? onTap;

  @override
  Widget build(BuildContext context) {
    final s = context.pmbDuty;
    final p = participant;
    final text = Theme.of(context).textTheme;
    return AppSurfaceCard(
      onTap: onTap,
      padding: const EdgeInsets.all(AppSpacing.md),
      child: Row(
        children: [
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  p.displayName,
                  style: text.titleSmall,
                  maxLines: 2,
                  overflow: TextOverflow.ellipsis,
                ),
                Text(p.number, style: text.bodySmall),
                if (p.checkedInAt != null)
                  Text(
                    s.checkedInAt(context.formatTime(p.checkedInAt)),
                    style: text.bodySmall,
                  ),
              ],
            ),
          ),
          const SizedBox(width: AppSpacing.sm),
          StatusChip(
            dense: true,
            tone: attendanceTone(p.attendance),
            label: s.attendanceLabel(p.attendance),
          ),
        ],
      ),
    );
  }
}
