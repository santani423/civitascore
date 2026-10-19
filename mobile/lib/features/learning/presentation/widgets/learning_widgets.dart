import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import 'package:url_launcher/url_launcher.dart';

import '../../../../core/router/route_paths.dart';
import '../../../../core/theme/app_dimensions.dart';
import '../../../../core/utils/file_actions.dart';
import '../../../../core/utils/formatters.dart';
import '../../../../core/widgets/app_feedback.dart';
import '../../../../core/widgets/ui_kit.dart';
import '../../domain/learning_models.dart';
import '../../l10n/learning_strings.dart';

/// Constrains content on wide screens.
class LearningBody extends StatelessWidget {
  const LearningBody({super.key, required this.child});
  final Widget child;
  @override
  Widget build(BuildContext context) => Center(
    child: ConstrainedBox(
      constraints: const BoxConstraints(maxWidth: 720),
      child: child,
    ),
  );
}

StatusTone assignmentTone(AssignmentStatus? s) => switch (s) {
  AssignmentStatus.graded => StatusTone.success,
  AssignmentStatus.submitted => StatusTone.info,
  AssignmentStatus.late => StatusTone.warning,
  AssignmentStatus.missed => StatusTone.danger,
  _ => StatusTone.neutral,
};

IconData materialIcon(LmsMaterialType t) => switch (t) {
  LmsMaterialType.file => Icons.description_outlined,
  LmsMaterialType.link => Icons.link,
  LmsMaterialType.video => Icons.play_circle_outline,
  LmsMaterialType.text => Icons.article_outlined,
};

class CourseCard extends StatelessWidget {
  const CourseCard({super.key, required this.course});
  final CourseSummary course;

  @override
  Widget build(BuildContext context) {
    final s = context.learningL10n;
    final theme = Theme.of(context);
    final info = course.info;
    return Padding(
      padding: const EdgeInsets.symmetric(
        horizontal: AppSpacing.lg,
        vertical: AppSpacing.xs,
      ),
      child: AppSurfaceCard(
        onTap: () => context.push(RoutePaths.studentCourse(info.id)),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(
              info.courseName,
              style: theme.textTheme.titleMedium,
              maxLines: 2,
              overflow: TextOverflow.ellipsis,
            ),
            const SizedBox(height: 2),
            Text(
              [
                info.courseCode,
                if (info.classCode.isNotEmpty) info.classCode,
                if (info.credits != null) '${info.credits} ${s.credits}',
              ].join(' • '),
              style: theme.textTheme.bodySmall,
            ),
            if (info.lecturerName != null)
              Text(
                info.lecturerName!,
                style: theme.textTheme.bodySmall,
                maxLines: 1,
                overflow: TextOverflow.ellipsis,
              ),
            const SizedBox(height: AppSpacing.sm),
            Wrap(
              spacing: AppSpacing.sm,
              runSpacing: AppSpacing.xs,
              children: [
                StatusChip(
                  label: s.materialsCount(course.materialsCount),
                  icon: Icons.menu_book_outlined,
                  dense: true,
                ),
                StatusChip(
                  label: s.assignmentsCount(course.assignmentsCount),
                  icon: Icons.assignment_outlined,
                  dense: true,
                ),
                if (course.pendingAssignmentsCount > 0)
                  StatusChip(
                    label: s.pendingCount(course.pendingAssignmentsCount),
                    tone: StatusTone.warning,
                    dense: true,
                  ),
              ],
            ),
            if (course.attendance.totalMeetings > 0) ...[
              const SizedBox(height: AppSpacing.sm),
              Text(
                s.attendanceSummary(
                  course.attendance.present,
                  course.attendance.totalMeetings,
                ),
                style: theme.textTheme.bodySmall,
              ),
            ],
          ],
        ),
      ),
    );
  }
}

class AssignmentTile extends StatelessWidget {
  const AssignmentTile({super.key, required this.assignment});
  final AssignmentItem assignment;

  @override
  Widget build(BuildContext context) {
    final s = context.learningL10n;
    final theme = Theme.of(context);
    final a = assignment;
    return Padding(
      padding: const EdgeInsets.symmetric(
        horizontal: AppSpacing.lg,
        vertical: AppSpacing.xs,
      ),
      child: AppSurfaceCard(
        onTap: () => context.push(RoutePaths.studentAssignment(a.id)),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Expanded(
                  child: Text(
                    a.title,
                    style: theme.textTheme.titleSmall,
                    maxLines: 2,
                    overflow: TextOverflow.ellipsis,
                  ),
                ),
                const SizedBox(width: AppSpacing.sm),
                StatusChip(
                  label: s.statusLabel(a),
                  tone: assignmentTone(a.status),
                  dense: true,
                ),
              ],
            ),
            if (a.course != null)
              Text(
                a.course!.name,
                style: theme.textTheme.bodySmall,
                maxLines: 1,
                overflow: TextOverflow.ellipsis,
              ),
            const SizedBox(height: 2),
            Text(
              '${s.due}: ${context.formatDateTime(a.dueAt)}',
              style: theme.textTheme.bodySmall,
            ),
          ],
        ),
      ),
    );
  }
}

/// Opens a material by type: file (authenticated download), link/video
/// (external browser), text (in-app sheet).
Future<void> openMaterial(
  BuildContext context,
  WidgetRef ref,
  CourseMaterial m,
) async {
  final s = context.learningL10n;
  switch (m.type) {
    case LmsMaterialType.file:
      final f = m.file;
      if (f == null) {
        showAppSnack(context, s.noContent, isError: true);
        return;
      }
      await downloadAndOpen(
        context,
        ref,
        path: f.downloadPath,
        filename: f.name,
      );
    case LmsMaterialType.link:
    case LmsMaterialType.video:
      final uri = Uri.tryParse(m.url ?? '');
      if (uri == null || !(uri.scheme == 'http' || uri.scheme == 'https')) {
        showAppSnack(context, s.invalidLink, isError: true);
        return;
      }
      final ok = await launchUrl(uri, mode: LaunchMode.externalApplication);
      if (!ok && context.mounted) {
        showAppSnack(context, s.cannotOpenLink, isError: true);
      }
    case LmsMaterialType.text:
      await showModalBottomSheet<void>(
        context: context,
        isScrollControlled: true,
        showDragHandle: true,
        builder: (ctx) => DraggableScrollableSheet(
          expand: false,
          initialChildSize: 0.7,
          maxChildSize: 0.95,
          builder: (ctx, controller) => ListView(
            controller: controller,
            padding: const EdgeInsets.all(AppSpacing.lg),
            children: [
              Text(m.title, style: Theme.of(ctx).textTheme.titleLarge),
              const SizedBox(height: AppSpacing.md),
              SelectableText(
                (m.description ?? m.url ?? '').trim().isEmpty
                    ? s.noContent
                    : (m.description ?? m.url!),
              ),
            ],
          ),
        ),
      );
  }
}

class MaterialTile extends ConsumerWidget {
  const MaterialTile({super.key, required this.material});
  final CourseMaterial material;

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final s = context.learningL10n;
    final m = material;
    final meta = [
      s.materialTypeLabel(m.type),
      if (m.file != null && m.file!.sizeBytes != null)
        formatBytes(m.file!.sizeBytes),
    ].join(' • ');
    return MenuTile(
      icon: materialIcon(m.type),
      title: m.title,
      subtitle: (m.description != null && m.type != LmsMaterialType.text)
          ? '$meta\n${m.description}'
          : meta,
      trailing: Icon(
        m.type == LmsMaterialType.file
            ? Icons.download_outlined
            : m.type == LmsMaterialType.text
            ? Icons.chevron_right
            : Icons.open_in_new,
      ),
      onTap: () => openMaterial(context, ref, m),
    );
  }
}
