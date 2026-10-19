import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import 'package:qr_flutter/qr_flutter.dart';
import 'package:share_plus/share_plus.dart';

import '../../../../core/l10n/core_strings.dart';
import '../../../../core/network/api_client.dart';
import '../../../../core/network/api_failure.dart';
import '../../../../core/permission/permission_gate.dart';
import '../../../../core/router/route_paths.dart';
import '../../../../core/theme/app_dimensions.dart';
import '../../../../core/theme/app_theme.dart';
import '../../../../core/utils/file_actions.dart';
import '../../../../core/utils/formatters.dart';
import '../../../../core/widgets/app_feedback.dart';
import '../../../../core/widgets/ui_kit.dart';
import '../../data/exam_api.dart';
import '../../domain/exam.dart';
import '../../domain/exam_validation.dart';
import '../../l10n/exam_strings.dart';
import '../providers.dart';

/// Tautan akses publik ujian: `https://<host portal>/exam/{token}`. Host
/// diturunkan dari base URL API (tanpa path `/api/...`).
String examAccessUrl(String apiBaseUrl, String token) {
  final uri = Uri.tryParse(apiBaseUrl);
  final origin = (uri == null || uri.host.isEmpty)
      ? ''
      : '${uri.scheme}://${uri.authority}';
  return '$origin/exam/$token';
}

/// Ringkasan ujian: status, jadwal, pengaturan, aksi (publish, ubah, soal,
/// preview, unduh naskah, hapus) dan tautan akses + QR.
class OverviewTab extends ConsumerStatefulWidget {
  const OverviewTab({super.key, required this.exam});

  final Exam exam;

  @override
  ConsumerState<OverviewTab> createState() => _OverviewTabState();
}

class _OverviewTabState extends ConsumerState<OverviewTab> {
  bool _busy = false;
  String? _publishError;

  Exam get exam => widget.exam;

  @override
  Widget build(BuildContext context) {
    final s = context.examL10n;
    final text = Theme.of(context).textTheme;
    final canUpdate = PermissionGate.allows(ref, 'exams.update');
    final canPublish = PermissionGate.allows(ref, 'exams.publish');
    final canDelete = PermissionGate.allows(ref, 'exams.delete');
    final questions = ref.watch(examQuestionsProvider(exam.id)).valueOrNull;
    final pool = questions?.length ?? exam.questionPoolSize;
    final issues = ExamValidation.issuesFor(
      questionPoolSize: pool,
      questionsPerParticipant: exam.questionsPerParticipant,
      selectionMode: exam.questionSelectionMode,
      manuallySelectedCount: questions?.where((q) => q.isSelected).length ?? 0,
    );

    return ListView(
      padding: const EdgeInsets.all(AppSpacing.lg),
      children: [
        AppSurfaceCard(
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text(exam.title, style: text.titleMedium),
              const SizedBox(height: AppSpacing.sm),
              StatusChip(
                label: exam.isPublished ? s.published : s.draft,
                tone: exam.isPublished
                    ? StatusTone.success
                    : StatusTone.neutral,
              ),
              if (exam.publishedAt != null)
                InfoRow(
                  label: s.publishedAtLabel,
                  value: context.formatDateTime(exam.publishedAt),
                ),
              InfoRow(
                label: s.classRow,
                value: [?exam.courseName, ?exam.classCode].join(' • '),
              ),
            ],
          ),
        ),
        if (!exam.isPublished) ...[
          for (final issue in issues) ...[
            const SizedBox(height: AppSpacing.sm),
            AppBanner(message: s.issueText(issue), tone: StatusTone.danger),
          ],
          if (_publishError != null) ...[
            const SizedBox(height: AppSpacing.sm),
            AppBanner(
              title: s.publishErrorsTitle,
              message: _publishError!,
              tone: StatusTone.danger,
            ),
          ],
        ],
        SectionHeader(
          title: s.actionsSection,
          padding: const EdgeInsets.only(
            top: AppSpacing.lg,
            bottom: AppSpacing.xs,
          ),
        ),
        Wrap(
          spacing: AppSpacing.sm,
          runSpacing: AppSpacing.sm,
          children: [
            if (!exam.isPublished && canPublish)
              FilledButton.icon(
                icon: const Icon(Icons.publish),
                label: Text(s.publishExam),
                onPressed: _busy || issues.isNotEmpty ? null : _publish,
              ),
            if (canUpdate)
              OutlinedButton.icon(
                icon: const Icon(Icons.edit_outlined),
                label: Text(context.l10n.edit),
                onPressed: () =>
                    context.push(RoutePaths.lecturerExamEdit(exam.id)),
              ),
            OutlinedButton.icon(
              icon: const Icon(Icons.quiz_outlined),
              label: Text(s.questionsAction),
              onPressed: () =>
                  context.push(RoutePaths.lecturerExamQuestions(exam.id)),
            ),
            OutlinedButton.icon(
              icon: const Icon(Icons.visibility_outlined),
              label: Text(s.previewAsStudent),
              onPressed: () =>
                  context.push(RoutePaths.lecturerExamPreview(exam.id)),
            ),
            OutlinedButton.icon(
              icon: const Icon(Icons.picture_as_pdf_outlined),
              label: Text(s.downloadPaper),
              onPressed: () => _downloadPaper(withAnswers: false),
            ),
            if (canUpdate)
              OutlinedButton.icon(
                icon: const Icon(Icons.key_outlined),
                label: Text(s.downloadPaperKey),
                onPressed: () => _downloadPaper(withAnswers: true),
              ),
          ],
        ),
        SectionHeader(
          title: s.scheduleSection,
          padding: const EdgeInsets.only(
            top: AppSpacing.lg,
            bottom: AppSpacing.xs,
          ),
        ),
        AppSurfaceCard(
          child: Column(
            children: [
              InfoRow(
                label: s.scheduleStart,
                value: exam.startsAt == null
                    ? s.notSet
                    : context.formatDateTime(exam.startsAt),
              ),
              InfoRow(
                label: s.scheduleEnd,
                value: exam.endsAt == null
                    ? s.notSet
                    : context.formatDateTime(exam.endsAt),
              ),
              InfoRow(
                label: s.durationRow,
                value: s.durationMinutes(exam.durationMinutes),
              ),
            ],
          ),
        ),
        SectionHeader(
          title: s.settingsSection,
          padding: const EdgeInsets.only(
            top: AppSpacing.lg,
            bottom: AppSpacing.xs,
          ),
        ),
        AppSurfaceCard(
          child: Column(
            children: [
              InfoRow(label: s.poolRow, value: '$pool'),
              InfoRow(
                label: s.perParticipantRow,
                value: '${exam.questionsPerParticipant}',
              ),
              InfoRow(
                label: s.selectionRow,
                value: s.selectionModeLabel(exam.questionSelectionMode),
              ),
              InfoRow(
                label: s.shuffleQuestions,
                value: exam.randomizeQuestions ? s.shuffled : s.inOrder,
              ),
              InfoRow(
                label: s.shuffleOptions,
                value: exam.randomizeOptions ? s.shuffled : s.inOrder,
              ),
              InfoRow(label: s.attemptsRow, value: '${exam.maxAttempts}'),
              InfoRow(
                label: s.weightRow,
                value: exam.weightPercentage == null
                    ? '-'
                    : '${context.formatNumber(exam.weightPercentage)}%',
              ),
              InfoRow(
                label: s.backNavRow,
                value: exam.allowBackNavigation ? s.yes : s.no,
              ),
              InfoRow(
                label: s.showResultRow,
                value: exam.showResultAfterSubmission ? s.yes : s.no,
              ),
            ],
          ),
        ),
        if (canUpdate) ...[
          SectionHeader(
            title: s.accessLinkTitle,
            padding: const EdgeInsets.only(
              top: AppSpacing.lg,
              bottom: AppSpacing.xs,
            ),
          ),
          _AccessLinkCard(exam: exam),
        ],
        if (canDelete) ...[
          const SizedBox(height: AppSpacing.xl),
          TextButton.icon(
            style: TextButton.styleFrom(
              foregroundColor: Theme.of(context).colorScheme.error,
            ),
            icon: const Icon(Icons.delete_outline),
            label: Text(s.deleteExam),
            onPressed: _busy ? null : _delete,
          ),
        ],
        const SizedBox(height: AppSpacing.xl2),
      ],
    );
  }

  Future<void> _publish() async {
    final s = context.examL10n;
    setState(() {
      _busy = true;
      _publishError = null;
    });
    try {
      await ref.read(examApiProvider).publish(exam.id);
      ref.invalidate(examProvider(exam.id));
      ref.invalidate(examListProvider);
      if (mounted) showAppSnack(context, s.publishedOk);
    } on ApiFailure catch (e) {
      if (mounted) setState(() => _publishError = context.l10n.errorFor(e));
    } finally {
      if (mounted) setState(() => _busy = false);
    }
  }

  Future<void> _delete() async {
    final s = context.examL10n;
    final ok = await confirmAction(
      context,
      title: s.deleteExamTitle,
      message: s.deleteExamBody,
      confirmLabel: context.l10n.delete,
      destructive: true,
    );
    if (!ok || !mounted) return;
    setState(() => _busy = true);
    try {
      await ref.read(examApiProvider).deleteExam(exam.id);
      ref.invalidate(examListProvider);
      if (!mounted) return;
      showAppSnack(context, s.deleted);
      context.pop();
    } catch (e) {
      if (mounted) showErrorSnack(context, e);
    } finally {
      if (mounted) setState(() => _busy = false);
    }
  }

  Future<void> _downloadPaper({required bool withAnswers}) => downloadAndOpen(
    context,
    ref,
    path: '/exams/${exam.id}/download',
    query: {if (withAnswers) 'with_answers': 1},
    filename: 'naskah-ujian-${exam.id}${withAnswers ? '-kunci' : ''}.pdf',
  );
}

class _AccessLinkCard extends ConsumerStatefulWidget {
  const _AccessLinkCard({required this.exam});
  final Exam exam;

  @override
  ConsumerState<_AccessLinkCard> createState() => _AccessLinkCardState();
}

class _AccessLinkCardState extends ConsumerState<_AccessLinkCard> {
  bool _busy = false;

  @override
  Widget build(BuildContext context) {
    final s = context.examL10n;
    final text = Theme.of(context).textTheme;
    final exam = widget.exam;
    final baseUrl = ref.watch(apiClientProvider).dio.options.baseUrl;
    final url = exam.hasAccessLink
        ? examAccessUrl(baseUrl, exam.accessToken!)
        : null;
    // QR selalu gelap di atas terang agar terbaca pemindai di mode gelap.
    final qrScheme = AppTheme.light().colorScheme;

    return AppSurfaceCard(
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          if (url == null)
            Text(s.accessLinkNone, style: text.bodyMedium)
          else ...[
            StatusChip(
              label: s.accessLinkActive,
              tone: StatusTone.success,
              dense: true,
            ),
            const SizedBox(height: AppSpacing.sm),
            SelectableText(url, style: text.bodyMedium),
            const SizedBox(height: AppSpacing.md),
            Center(
              child: Semantics(
                label: s.qrSemantics,
                image: true,
                child: Container(
                  padding: const EdgeInsets.all(AppSpacing.sm),
                  color: qrScheme.surface,
                  child: QrImageView(
                    data: url,
                    size: 180,
                    backgroundColor: qrScheme.surface,
                    eyeStyle: QrEyeStyle(
                      eyeShape: QrEyeShape.square,
                      color: qrScheme.onSurface,
                    ),
                    dataModuleStyle: QrDataModuleStyle(
                      dataModuleShape: QrDataModuleShape.square,
                      color: qrScheme.onSurface,
                    ),
                  ),
                ),
              ),
            ),
            const SizedBox(height: AppSpacing.sm),
            Wrap(
              spacing: AppSpacing.sm,
              children: [
                OutlinedButton.icon(
                  icon: const Icon(Icons.copy),
                  label: Text(s.copyLink),
                  onPressed: () async {
                    await Clipboard.setData(ClipboardData(text: url));
                    if (context.mounted) showAppSnack(context, s.linkCopied);
                  },
                ),
                OutlinedButton.icon(
                  icon: const Icon(Icons.share),
                  label: Text(s.shareLink),
                  onPressed: () => SharePlus.instance.share(
                    ShareParams(text: s.shareMessage(exam.title, url)),
                  ),
                ),
              ],
            ),
          ],
          const SizedBox(height: AppSpacing.sm),
          FilledButton.tonalIcon(
            icon: const Icon(Icons.link),
            label: Text(url == null ? s.generateLink : s.regenerateLink),
            onPressed: _busy ? null : () => _generate(regenerate: url != null),
          ),
        ],
      ),
    );
  }

  Future<void> _generate({required bool regenerate}) async {
    final s = context.examL10n;
    if (regenerate) {
      final ok = await confirmAction(
        context,
        title: s.regenerateTitle,
        message: s.regenerateBody,
        destructive: true,
      );
      if (!ok || !mounted) return;
    }
    setState(() => _busy = true);
    try {
      await ref.read(examApiProvider).generateAccessLink(widget.exam.id);
      ref.invalidate(examProvider(widget.exam.id));
    } catch (e) {
      if (mounted) showErrorSnack(context, e);
    } finally {
      if (mounted) setState(() => _busy = false);
    }
  }
}
