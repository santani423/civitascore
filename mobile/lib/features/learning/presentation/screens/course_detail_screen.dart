import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import '../../../../core/router/route_paths.dart';
import '../../../../core/theme/app_dimensions.dart';
import '../../../../core/utils/formatters.dart';
import '../../../../core/widgets/state_views.dart';
import '../../../../core/widgets/ui_kit.dart';
import '../../domain/learning_models.dart';
import '../../l10n/learning_strings.dart';
import '../providers.dart';
import '../widgets/learning_widgets.dart';

class CourseDetailScreen extends ConsumerWidget {
  const CourseDetailScreen({super.key, required this.classSectionId});
  final String classSectionId;

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final s = context.learningL10n;
    final value = ref.watch(courseDetailProvider(classSectionId));
    return Scaffold(
      appBar: AppBar(title: Text(s.courseDetailTitle)),
      body: LearningBody(
        child: RefreshIndicator(
          onRefresh: () async {
            ref.invalidate(courseDetailProvider(classSectionId));
            await ref
                .read(courseDetailProvider(classSectionId).future)
                .catchError((_) => const CourseDetail(info: _emptyInfo));
          },
          child: AsyncValueView<CourseDetail>(
            value: value,
            onRetry: () => ref.invalidate(courseDetailProvider(classSectionId)),
            data: (d) => _Body(detail: d),
          ),
        ),
      ),
    );
  }
}

const _emptyInfo = ClassInfo(
  id: '',
  classCode: '',
  courseCode: '',
  courseName: '',
);

class _Body extends StatelessWidget {
  const _Body({required this.detail});
  final CourseDetail detail;

  @override
  Widget build(BuildContext context) {
    final s = context.learningL10n;
    final theme = Theme.of(context);
    final info = detail.info;
    final att = detail.attendance;
    return ListView(
      physics: const AlwaysScrollableScrollPhysics(),
      padding: const EdgeInsets.only(bottom: 24),
      children: [
        Padding(
          padding: const EdgeInsets.all(AppSpacing.lg),
          child: AppSurfaceCard(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(info.courseName, style: theme.textTheme.titleLarge),
                const SizedBox(height: AppSpacing.sm),
                InfoRow(
                  label: s.classCode,
                  value: '${info.courseCode} ${info.classCode}'.trim(),
                ),
                if (info.credits != null)
                  InfoRow(label: s.credits, value: '${info.credits}'),
                if (detail.term != null)
                  InfoRow(label: s.term, value: detail.term!.label),
                if (info.lecturerName != null)
                  InfoRow(label: s.lecturer, value: info.lecturerName!),
                for (final sc in info.schedules)
                  InfoRow(label: s.schedule, value: sc.text),
                if (att.totalMeetings > 0)
                  InfoRow(
                    label: s.attendance,
                    value:
                        s.attendanceSummary(att.present, att.totalMeetings) +
                        (att.percentage == null ? '' : ' (${att.percentage}%)'),
                  ),
              ],
            ),
          ),
        ),
        SectionHeader(title: s.materialsSection),
        if (detail.meetings.every((m) => m.materials.isEmpty))
          Padding(
            padding: const EdgeInsets.symmetric(horizontal: 16),
            child: Text(s.noMaterialsBody),
          )
        else
          for (final m in detail.meetings.where((m) => m.materials.isNotEmpty))
            ExpansionTile(
              title: Text(m.label),
              initiallyExpanded: detail.meetings.length == 1,
              children: [
                for (final mat in m.materials) MaterialTile(material: mat),
              ],
            ),
        SectionHeader(title: s.assignmentsSection),
        if (detail.assignments.isEmpty)
          Padding(
            padding: const EdgeInsets.symmetric(horizontal: 16),
            child: Text(s.noAssignmentsBody),
          )
        else
          for (final a in detail.assignments) AssignmentTile(assignment: a),
        if (detail.exams.isNotEmpty) ...[
          SectionHeader(title: s.examsSection),
          for (final e in detail.exams)
            MenuTile(
              icon: Icons.quiz_outlined,
              title: e.title,
              subtitle: context.formatDateTime(e.startsAt),
              onTap: () => context.push(RoutePaths.studentExam(e.id)),
            ),
        ],
      ],
    );
  }
}
