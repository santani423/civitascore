import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import '../../../../core/l10n/core_strings.dart';
import '../../../../core/pending/pending_feature_screen.dart';
import '../../../../core/pending/pending_features.dart';
import '../../../../core/router/route_paths.dart';
import '../../../../core/theme/app_dimensions.dart';
import '../../../../core/widgets/notification_bell.dart';
import '../../../../core/widgets/state_views.dart';
import '../../../../core/widgets/ui_kit.dart';
import '../../domain/teaching_models.dart';
import '../../l10n/teaching_strings.dart';
import '../providers.dart';
import '../widgets/common.dart';

const _pendingKeys = [
  'teaching_journal',
  'grade_components',
  'edom_reports',
  'thesis_supervision',
];

class TeachingHomeScreen extends ConsumerWidget {
  const TeachingHomeScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final t = context.tr;
    final classes = ref.watch(teachingClassesProvider);
    Future<void> refresh() async {
      ref.invalidate(teachingClassesProvider);
      try {
        await ref.read(teachingClassesProvider.future);
      } catch (_) {}
    }

    return Scaffold(
      appBar: AppBar(
        title: Text(t.homeTitle),
        actions: const [NotificationBellButton()],
      ),
      body: WideBody(
        child: RefreshIndicator(
          onRefresh: refresh,
          child: ListView(
            physics: const AlwaysScrollableScrollPhysics(),
            padding: const EdgeInsets.symmetric(vertical: AppSpacing.md),
            children: [
              MenuTile(
                icon: Icons.quiz_outlined,
                title: t.examsTile,
                subtitle: t.examsSubtitle,
                onTap: () => context.push(RoutePaths.lecturerExams),
              ),
              MenuTile(
                icon: Icons.library_books_outlined,
                title: t.questionBankTile,
                subtitle: t.questionBankSubtitle,
                onTap: () => context.push(RoutePaths.lecturerQuestionBank),
              ),
              SectionHeader(title: t.myClasses),
              classes.when(
                skipLoadingOnRefresh: true,
                loading: () => const SizedBox(
                  height: 280,
                  child: SkeletonList(itemCount: 3, itemHeight: 110),
                ),
                error: (e, _) => SizedBox(
                  height: 320,
                  child: ErrorView(error: e, onRetry: refresh),
                ),
                data: (list) => list.isEmpty
                    ? SizedBox(
                        height: 320,
                        child: EmptyView(
                          icon: Icons.class_outlined,
                          title: t.noClassesTitle,
                          message: t.noClassesBody,
                        ),
                      )
                    : Column(
                        children: [
                          for (final c in list)
                            Padding(
                              padding: const EdgeInsets.symmetric(
                                horizontal: AppSpacing.lg,
                                vertical: 2,
                              ),
                              child: TeachingClassCard(cls: c),
                            ),
                        ],
                      ),
              ),
              SectionHeader(title: t.moreFeatures),
              for (final k in _pendingKeys)
                if (pendingFeatureByKey(k) != null)
                  PendingFeatureTile(feature: pendingFeatureByKey(k)!),
            ],
          ),
        ),
      ),
    );
  }
}

class TeachingClassCard extends StatelessWidget {
  const TeachingClassCard({super.key, required this.cls});
  final TeachingClass cls;

  @override
  Widget build(BuildContext context) {
    final t = context.tr;
    final text = Theme.of(context).textTheme;
    return AppSurfaceCard(
      onTap: () => context.push(RoutePaths.lecturerClass(cls.id)),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(
            cls.courseName,
            style: text.titleMedium,
            maxLines: 2,
            overflow: TextOverflow.ellipsis,
          ),
          const SizedBox(height: 2),
          Text(
            '${cls.courseCode} · ${cls.classCode} · ${context.l10n.sks(cls.credits)}',
            style: text.bodySmall,
            maxLines: 1,
            overflow: TextOverflow.ellipsis,
          ),
          const SizedBox(height: AppSpacing.sm),
          SlotLines(slots: cls.schedules, emptyText: t.noScheduleYet),
          const SizedBox(height: AppSpacing.sm),
          Wrap(
            spacing: AppSpacing.sm,
            runSpacing: 4,
            children: [
              StatusChip(
                label: t.students(cls.enrolledCount),
                icon: Icons.groups_outlined,
                dense: true,
              ),
              StatusChip(
                label: t.materialsCount(cls.materialsCount),
                dense: true,
              ),
              StatusChip(
                label: t.assignmentsCount(cls.assignmentsCount),
                dense: true,
              ),
            ],
          ),
        ],
      ),
    );
  }
}
