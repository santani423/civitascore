import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../../core/widgets/state_views.dart';
import '../../domain/learning_models.dart';
import '../../l10n/learning_strings.dart';
import '../providers.dart';
import '../widgets/learning_widgets.dart';

class CoursesScreen extends ConsumerWidget {
  const CoursesScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final s = context.learningL10n;
    final value = ref.watch(coursesProvider);
    return Scaffold(
      appBar: AppBar(title: Text(s.coursesTitle)),
      body: LearningBody(
        child: RefreshIndicator(
          onRefresh: () async {
            ref.invalidate(coursesProvider);
            await ref
                .read(coursesProvider.future)
                .catchError((_) => const CoursesOverview());
          },
          child: AsyncValueView<CoursesOverview>(
            value: value,
            onRetry: () => ref.invalidate(coursesProvider),
            isEmpty: (d) => d.courses.isEmpty,
            empty: ListView(
              children: [
                EmptyView(
                  icon: Icons.school_outlined,
                  title: s.noCourses,
                  message: s.noCoursesBody,
                ),
              ],
            ),
            data: (d) => ListView(
              physics: const AlwaysScrollableScrollPhysics(),
              padding: const EdgeInsets.symmetric(vertical: 8),
              children: [
                if (d.term != null)
                  Padding(
                    padding: const EdgeInsets.fromLTRB(16, 4, 16, 8),
                    child: Text(
                      d.term!.label,
                      style: Theme.of(context).textTheme.titleSmall,
                    ),
                  ),
                for (final c in d.courses) CourseCard(course: c),
              ],
            ),
          ),
        ),
      ),
    );
  }
}
