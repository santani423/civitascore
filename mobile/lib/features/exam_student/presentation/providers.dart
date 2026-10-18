import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../data/exam_student_api.dart';
import '../domain/exam_models.dart';

final studentExamsProvider = FutureProvider.autoDispose<List<StudentExam>>(
  (ref) => ref.watch(examStudentApiProvider).exams(),
);

final studentExamProvider = FutureProvider.autoDispose
    .family<StudentExam, String>(
      (ref, id) => ref.watch(examStudentApiProvider).exam(id),
    );

final examResultProvider = FutureProvider.autoDispose
    .family<ExamResult, String>(
      (ref, id) => ref.watch(examStudentApiProvider).result(id),
    );

/// Initial attempt load for the taking screen (kept alive only while the
/// screen is mounted; state afterwards lives in the screen itself).
final examAttemptSnapshotProvider = FutureProvider.autoDispose
    .family<AttemptSnapshot, String>(
      (ref, id) => ref.watch(examStudentApiProvider).attempt(id),
    );
