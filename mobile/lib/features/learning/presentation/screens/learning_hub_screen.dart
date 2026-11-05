import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import '../../../../core/pending/pending_feature_screen.dart';
import '../../../../core/pending/pending_features.dart';
import '../../../../core/router/route_paths.dart';
import '../../../../core/widgets/notification_bell.dart';
import '../../../../core/widgets/service_card.dart';
import '../../../../core/widgets/state_views.dart';
import '../../../../core/widgets/ui_kit.dart';
import '../../domain/learning_models.dart';
import '../../l10n/learning_strings.dart';
import '../providers.dart';
import '../widgets/learning_widgets.dart';

class LearningHubScreen extends ConsumerWidget {
  const LearningHubScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final s = context.learningL10n;
    final courses = ref.watch(coursesProvider);
    final pending = ref.watch(assignmentsProvider('not_submitted'));
    Future<void> refresh() async {
      ref.invalidate(coursesProvider);
      ref.invalidate(assignmentsProvider);
      await ref
          .read(coursesProvider.future)
          .catchError((_) => const CoursesOverview());
    }

    return Scaffold(
      appBar: AppBar(
        title: Text(s.hubTitle),
        actions: const [NotificationBellButton()],
      ),
      body: LearningBody(
        child: RefreshIndicator(
          onRefresh: refresh,
          child: ListView(
            physics: const AlwaysScrollableScrollPhysics(),
            padding: const EdgeInsets.only(bottom: 24),
            children: [
              ServiceCard(
                icon: Icons.menu_book_outlined,
                title: s.shortcutMaterials,
                subtitle: s.shortcutMaterialsSub,
                onTap: () => context.push(RoutePaths.studentMaterials),
              ),
              ServiceCard(
                icon: Icons.assignment_outlined,
                title: s.shortcutAssignments,
                subtitle: s.shortcutAssignmentsSub,
                badge:
                    pending.valueOrNull == null ||
                        pending.valueOrNull!.assignments.isEmpty
                    ? null
                    : '${pending.valueOrNull!.assignments.length}',
                onTap: () => context.push(RoutePaths.studentAssignments),
              ),
              ServiceCard(
                icon: Icons.quiz_outlined,
                title: s.shortcutExams,
                subtitle: s.shortcutExamsSub,
                onTap: () => context.push(RoutePaths.studentExams),
              ),
              SectionHeader(
                title: s.dueSoon,
                action: s.seeAll,
                onAction: () => context.push(RoutePaths.studentAssignments),
              ),
              _DueList(
                value: pending,
                onRetry: () => ref.invalidate(assignmentsProvider),
              ),
              SectionHeader(
                title: s.myCourses,
                action: s.seeAll,
                onAction: () => context.push(RoutePaths.studentCourses),
              ),
              AsyncValueView<CoursesOverview>(
                value: courses,
                onRetry: () => ref.invalidate(coursesProvider),
                loading: const SizedBox(
                  height: 220,
                  child: SkeletonList(itemCount: 2, itemHeight: 100),
                ),
                isEmpty: (d) => d.courses.isEmpty,
                empty: EmptyView(
                  icon: Icons.school_outlined,
                  title: s.noCourses,
                  message: s.noCoursesBody,
                ),
                data: (d) => Column(
                  children: [for (final c in d.courses) CourseCard(course: c)],
                ),
              ),
              const SizedBox(height: 8),
              PendingFeatureTile(feature: pendingFeatureByKey('lms_extras')!),
            ],
          ),
        ),
      ),
    );
  }
}

class _DueList extends StatelessWidget {
  const _DueList({required this.value, required this.onRetry});
  final AsyncValue<AssignmentsOverview> value;
  final VoidCallback onRetry;

  @override
  Widget build(BuildContext context) {
    final s = context.learningL10n;
    return AsyncValueView<AssignmentsOverview>(
      value: value,
      onRetry: onRetry,
      loading: const SizedBox(
        height: 160,
        child: SkeletonList(itemCount: 2, itemHeight: 70),
      ),
      data: (d) {
        final due = d.assignments.where((a) => a.canSubmit).take(5).toList();
        if (due.isEmpty) {
          return Padding(
            padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
            child: Text(
              s.noDueAssignments,
              style: Theme.of(context).textTheme.bodyMedium,
            ),
          );
        }
        return Column(
          children: [for (final a in due) AssignmentTile(assignment: a)],
        );
      },
    );
  }
}
