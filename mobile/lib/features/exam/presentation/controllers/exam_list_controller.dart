import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../domain/entities/exam.dart';
import 'exam_providers.dart';

class ExamListController extends AsyncNotifier<List<Exam>> {
  @override
  Future<List<Exam>> build() => ref.watch(examRepositoryProvider).listExams();

  Future<void> refresh() async {
    state = const AsyncLoading();
    state = await AsyncValue.guard(
      () => ref.read(examRepositoryProvider).listExams(),
    );
  }

  Future<void> deleteExam(String examId) async {
    await ref.read(examRepositoryProvider).deleteExam(examId);
    await refresh();
  }
}

final examListControllerProvider =
    AsyncNotifierProvider<ExamListController, List<Exam>>(
      ExamListController.new,
    );
