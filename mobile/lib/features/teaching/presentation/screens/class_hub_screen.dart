import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import '../../../../core/l10n/core_strings.dart';
import '../../../../core/router/route_paths.dart';
import '../../../../core/theme/app_dimensions.dart';
import '../../../../core/widgets/state_views.dart';
import '../../../../core/widgets/ui_kit.dart';
import '../../../auth/presentation/controllers/auth_controller.dart';
import '../../domain/teaching_models.dart';
import '../../l10n/teaching_strings.dart';
import '../providers.dart';
import '../widgets/common.dart';

class ClassHubScreen extends ConsumerWidget {
  const ClassHubScreen({super.key, required this.classSectionId});
  final String classSectionId;

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final t = context.tr;
    final detail = ref.watch(classDetailProvider(classSectionId));
    final user = ref.watch(currentUserProvider);
    bool can(String p) => user?.hasAnyPermission(p.split(',')) ?? false;
    return Scaffold(
      appBar: AppBar(title: Text(t.classTitle)),
      body: WideBody(
        child: AsyncValueView<ClassDetail>(
          value: detail,
          onRetry: () => ref.invalidate(classDetailProvider(classSectionId)),
          data: (d) => RefreshIndicator(
            onRefresh: () async {
              ref.invalidate(classDetailProvider(classSectionId));
              try {
                await ref.read(classDetailProvider(classSectionId).future);
              } catch (_) {}
            },
            child: ListView(
              physics: const AlwaysScrollableScrollPhysics(),
              padding: const EdgeInsets.symmetric(vertical: AppSpacing.md),
              children: [
                Padding(
                  padding: const EdgeInsets.symmetric(
                    horizontal: AppSpacing.lg,
                  ),
                  child: _InfoCard(d: d),
                ),
                SectionHeader(title: t.classManagement),
                if (can('krs.read,students.read'))
                  MenuTile(
                    icon: Icons.groups_outlined,
                    title: t.participants,
                    subtitle: t.participantsSubtitle,
                    onTap: () => context.push(
                      RoutePaths.lecturerClassStudents(classSectionId),
                    ),
                  ),
                if (can('course_materials.read'))
                  MenuTile(
                    icon: Icons.menu_book_outlined,
                    title: t.materials,
                    subtitle: t.materialsSubtitle,
                    onTap: () => context.push(
                      RoutePaths.lecturerClassMaterials(classSectionId),
                    ),
                  ),
                if (can('assignments.read'))
                  MenuTile(
                    icon: Icons.assignment_outlined,
                    title: t.assignments,
                    subtitle: t.assignmentsSubtitle,
                    onTap: () => context.push(
                      RoutePaths.lecturerClassAssignments(classSectionId),
                    ),
                  ),
                if (can('attendance.read,attendance.create'))
                  MenuTile(
                    icon: Icons.fact_check_outlined,
                    title: t.attendance,
                    subtitle: t.attendanceSubtitle,
                    onTap: () => context.push(
                      RoutePaths.lecturerClassAttendance(classSectionId),
                    ),
                  ),
                if (can('grades.read,grades.create,grades.update'))
                  MenuTile(
                    icon: Icons.grade_outlined,
                    title: t.grades,
                    subtitle: t.gradesSubtitle,
                    onTap: () => context.push(
                      RoutePaths.lecturerClassGrades(classSectionId),
                    ),
                  ),
              ],
            ),
          ),
        ),
      ),
    );
  }
}

class _InfoCard extends StatelessWidget {
  const _InfoCard({required this.d});
  final ClassDetail d;
  @override
  Widget build(BuildContext context) {
    final t = context.tr;
    final text = Theme.of(context).textTheme;
    return AppSurfaceCard(
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(d.courseName, style: text.titleLarge),
          const SizedBox(height: 2),
          Text(
            '${d.courseCode} · ${d.classCode} · ${context.l10n.sks(d.credits)}',
            style: text.bodyMedium,
          ),
          const SizedBox(height: AppSpacing.sm),
          if (d.termLabel != null)
            InfoRow(label: t.termLabel, value: d.termLabel!),
          if (d.studyProgram != null)
            InfoRow(label: t.studyProgram, value: d.studyProgram!),
          if (d.lecturerName != null)
            InfoRow(label: t.lecturer, value: d.lecturerName!),
          if (d.capacity != null)
            InfoRow(label: t.capacity, value: '${d.capacity}'),
          if (d.enrolledCount != null)
            InfoRow(label: t.enrolled, value: '${d.enrolledCount}'),
          InfoRow(label: t.schedule, value: ''),
          SlotLines(slots: d.schedules, emptyText: t.noScheduleYet),
        ],
      ),
    );
  }
}
