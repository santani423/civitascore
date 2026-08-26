import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../domain/entities/exam_question.dart';
import 'exam_providers.dart';

/// Mengelola bank soal satu ujian (`examId`). `family` karena tiap layar
/// bank soal terikat pada satu ujian spesifik.
class QuestionBankController
    extends FamilyAsyncNotifier<List<ExamQuestion>, String> {
  @override
  Future<List<ExamQuestion>> build(String examId) =>
      ref.watch(examRepositoryProvider).listQuestions(examId);

  Future<void> refresh() async {
    state = const AsyncLoading();
    state = await AsyncValue.guard(
      () => ref.read(examRepositoryProvider).listQuestions(arg),
    );
  }

  Future<void> addQuestion({
    required String questionText,
    required double points,
    required List<({String optionText, bool isCorrect})> options,
  }) async {
    await ref
        .read(examRepositoryProvider)
        .addQuestion(
          arg,
          questionText: questionText,
          points: points,
          options: options,
        );
    await refresh();
  }

  Future<void> updateQuestion(
    String examQuestionId, {
    String? questionText,
    double? points,
    bool? isSelected,
    List<({String optionText, bool isCorrect})>? options,
  }) async {
    await ref
        .read(examRepositoryProvider)
        .updateQuestion(
          examQuestionId,
          questionText: questionText,
          points: points,
          isSelected: isSelected,
          options: options,
        );
    await refresh();
  }

  Future<void> deleteQuestion(String examQuestionId) async {
    await ref.read(examRepositoryProvider).deleteQuestion(examQuestionId);
    await refresh();
  }

  /// Jumlah soal yang ditandai `is_selected` — dipakai validasi mode manual
  /// (spec §4 Mode C / §18).
  int get manuallySelectedCount =>
      state.valueOrNull?.where((q) => q.isSelected).length ?? 0;
}

final questionBankControllerProvider =
    AsyncNotifierProvider.family<
      QuestionBankController,
      List<ExamQuestion>,
      String
    >(QuestionBankController.new);
