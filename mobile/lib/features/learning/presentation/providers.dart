import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../data/learning_api.dart';
import '../domain/learning_models.dart';

final coursesProvider = FutureProvider.autoDispose<CoursesOverview>(
  (ref) => ref.watch(learningApiProvider).courses(),
);

final courseDetailProvider = FutureProvider.autoDispose
    .family<CourseDetail, String>(
      (ref, id) => ref.watch(learningApiProvider).course(id),
    );

final materialsProvider = FutureProvider.autoDispose<MaterialsOverview>(
  (ref) => ref.watch(learningApiProvider).materials(),
);

/// Family key: status filter (`all`, `not_submitted`, ...).
final assignmentsProvider = FutureProvider.autoDispose
    .family<AssignmentsOverview, String>(
      (ref, status) =>
          ref.watch(learningApiProvider).assignments(status: status),
    );

final assignmentProvider = FutureProvider.autoDispose
    .family<AssignmentItem, String>(
      (ref, id) => ref.watch(learningApiProvider).assignment(id),
    );

/// Re-fetch everything that depends on assignment state.
void invalidateAssignments(WidgetRef ref, {String? id}) {
  ref.invalidate(assignmentsProvider);
  ref.invalidate(coursesProvider);
  ref.invalidate(courseDetailProvider);
  if (id != null) ref.invalidate(assignmentProvider(id));
}
