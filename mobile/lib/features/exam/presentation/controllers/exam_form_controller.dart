import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../domain/entities/question_selection_mode.dart';
import 'exam_providers.dart';

/// Draft konfigurasi ujian yang sedang diedit di wizard (spec §3/§22).
/// `examId == null` berarti ujian baru — belum dikirim ke backend.
class ExamFormState {
  const ExamFormState({
    this.examId,
    this.classSectionId = '',
    this.title = '',
    this.durationMinutes = 60,
    this.questionsPerParticipant = 1,
    this.selectionMode = QuestionSelectionMode.all,
    this.randomizeQuestions = false,
    this.randomizeOptions = false,
    this.allowBackNavigation = true,
    this.showResultAfterSubmission = true,
    this.maxAttempts = 1,
    this.isPublished = false,
    this.questionPoolSize = 0,
  });

  final String? examId;
  final String classSectionId;
  final String title;
  final int durationMinutes;
  final int questionsPerParticipant;
  final QuestionSelectionMode selectionMode;
  final bool randomizeQuestions;
  final bool randomizeOptions;
  final bool allowBackNavigation;
  final bool showResultAfterSubmission;
  final int maxAttempts;
  final bool isPublished;
  final int questionPoolSize;

  ExamFormState copyWith({
    String? examId,
    String? classSectionId,
    String? title,
    int? durationMinutes,
    int? questionsPerParticipant,
    QuestionSelectionMode? selectionMode,
    bool? randomizeQuestions,
    bool? randomizeOptions,
    bool? allowBackNavigation,
    bool? showResultAfterSubmission,
    int? maxAttempts,
    bool? isPublished,
    int? questionPoolSize,
  }) => ExamFormState(
    examId: examId ?? this.examId,
    classSectionId: classSectionId ?? this.classSectionId,
    title: title ?? this.title,
    durationMinutes: durationMinutes ?? this.durationMinutes,
    questionsPerParticipant:
        questionsPerParticipant ?? this.questionsPerParticipant,
    selectionMode: selectionMode ?? this.selectionMode,
    randomizeQuestions: randomizeQuestions ?? this.randomizeQuestions,
    randomizeOptions: randomizeOptions ?? this.randomizeOptions,
    allowBackNavigation: allowBackNavigation ?? this.allowBackNavigation,
    showResultAfterSubmission:
        showResultAfterSubmission ?? this.showResultAfterSubmission,
    maxAttempts: maxAttempts ?? this.maxAttempts,
    isPublished: isPublished ?? this.isPublished,
    questionPoolSize: questionPoolSize ?? this.questionPoolSize,
  );
}

class ExamFormController extends FamilyAsyncNotifier<ExamFormState, String?> {
  @override
  Future<ExamFormState> build(String? examId) async {
    if (examId == null) return const ExamFormState();

    final exam = await ref.watch(examRepositoryProvider).getExam(examId);

    return ExamFormState(
      examId: exam.id,
      classSectionId: exam.classSectionId,
      title: exam.title,
      durationMinutes: exam.durationMinutes,
      questionsPerParticipant: exam.questionsPerParticipant,
      selectionMode: exam.questionSelectionMode,
      randomizeQuestions: exam.randomizeQuestions,
      randomizeOptions: exam.randomizeOptions,
      allowBackNavigation: exam.allowBackNavigation,
      showResultAfterSubmission: exam.showResultAfterSubmission,
      maxAttempts: exam.maxAttempts,
      isPublished: exam.isPublished,
      questionPoolSize: exam.questionPoolSize,
    );
  }

  void setClassSectionId(String value) =>
      _update((s) => s.copyWith(classSectionId: value));

  void setTitle(String value) => _update((s) => s.copyWith(title: value));

  void setDurationMinutes(int value) =>
      _update((s) => s.copyWith(durationMinutes: value));

  void setQuestionsPerParticipant(int value) =>
      _update((s) => s.copyWith(questionsPerParticipant: value));

  void setSelectionMode(QuestionSelectionMode value) =>
      _update((s) => s.copyWith(selectionMode: value));

  void setRandomizeQuestions(bool value) =>
      _update((s) => s.copyWith(randomizeQuestions: value));

  void setRandomizeOptions(bool value) =>
      _update((s) => s.copyWith(randomizeOptions: value));

  void setAllowBackNavigation(bool value) =>
      _update((s) => s.copyWith(allowBackNavigation: value));

  void setShowResultAfterSubmission(bool value) =>
      _update((s) => s.copyWith(showResultAfterSubmission: value));

  void setMaxAttempts(int value) =>
      _update((s) => s.copyWith(maxAttempts: value));

  void _update(ExamFormState Function(ExamFormState current) updater) {
    final current = state.valueOrNull;
    if (current == null) return;
    state = AsyncData(updater(current));
  }

  /// Create kalau draft belum punya `examId`, update kalau sudah — mengembalikan id ujian.
  Future<String> save() async {
    final current = state.requireValue;
    final repo = ref.read(examRepositoryProvider);

    if (current.examId == null) {
      final created = await repo.createExam(
        classSectionId: current.classSectionId,
        title: current.title,
        durationMinutes: current.durationMinutes,
        questionsPerParticipant: current.questionsPerParticipant,
        questionSelectionMode: current.selectionMode,
        randomizeQuestions: current.randomizeQuestions,
        randomizeOptions: current.randomizeOptions,
        allowBackNavigation: current.allowBackNavigation,
        showResultAfterSubmission: current.showResultAfterSubmission,
        maxAttempts: current.maxAttempts,
      );
      state = AsyncData(
        current.copyWith(
          examId: created.id,
          questionPoolSize: created.questionPoolSize,
        ),
      );
      return created.id;
    }

    final updated = await repo.updateExam(
      current.examId!,
      title: current.title,
      durationMinutes: current.durationMinutes,
      questionsPerParticipant: current.questionsPerParticipant,
      questionSelectionMode: current.selectionMode,
      randomizeQuestions: current.randomizeQuestions,
      randomizeOptions: current.randomizeOptions,
      allowBackNavigation: current.allowBackNavigation,
      showResultAfterSubmission: current.showResultAfterSubmission,
      maxAttempts: current.maxAttempts,
    );
    state = AsyncData(
      current.copyWith(questionPoolSize: updated.questionPoolSize),
    );
    return updated.id;
  }

  Future<void> publish() async {
    final current = state.requireValue;
    if (current.examId == null) return;

    final published = await ref
        .read(examRepositoryProvider)
        .publishExam(current.examId!);
    state = AsyncData(
      current.copyWith(
        isPublished: published.isPublished,
        questionPoolSize: published.questionPoolSize,
      ),
    );
  }
}

final examFormControllerProvider =
    AsyncNotifierProvider.family<ExamFormController, ExamFormState, String?>(
      ExamFormController.new,
    );
