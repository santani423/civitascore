import '../domain/entities/exam.dart';
import '../domain/entities/exam_question.dart';
import '../domain/entities/question_selection_mode.dart';
import '../domain/exam_repository.dart';
import 'exam_remote_data_source.dart';

class ExamRepositoryImpl implements ExamRepository {
  ExamRepositoryImpl(this._remote);

  final ExamRemoteDataSource _remote;

  @override
  Future<List<Exam>> listExams({String? classSectionId}) =>
      _remote.list(classSectionId: classSectionId);

  @override
  Future<Exam> getExam(String examId) => _remote.get(examId);

  @override
  Future<Exam> createExam({
    required String classSectionId,
    required String title,
    required int durationMinutes,
    required int questionsPerParticipant,
    required QuestionSelectionMode questionSelectionMode,
    required bool randomizeQuestions,
    required bool randomizeOptions,
    required bool allowBackNavigation,
    required bool showResultAfterSubmission,
    required int maxAttempts,
  }) => _remote.create({
    'class_section_id': classSectionId,
    'title': title,
    'duration_minutes': durationMinutes,
    'questions_per_participant': questionsPerParticipant,
    'question_selection_mode': questionSelectionMode.apiValue,
    'randomize_questions': randomizeQuestions,
    'randomize_options': randomizeOptions,
    'allow_back_navigation': allowBackNavigation,
    'show_result_after_submission': showResultAfterSubmission,
    'max_attempts': maxAttempts,
  });

  @override
  Future<Exam> updateExam(
    String examId, {
    String? title,
    int? durationMinutes,
    int? questionsPerParticipant,
    QuestionSelectionMode? questionSelectionMode,
    bool? randomizeQuestions,
    bool? randomizeOptions,
    bool? allowBackNavigation,
    bool? showResultAfterSubmission,
    int? maxAttempts,
  }) => _remote.update(examId, {
    'title': ?title,
    'duration_minutes': ?durationMinutes,
    'questions_per_participant': ?questionsPerParticipant,
    'question_selection_mode': ?questionSelectionMode?.apiValue,
    'randomize_questions': ?randomizeQuestions,
    'randomize_options': ?randomizeOptions,
    'allow_back_navigation': ?allowBackNavigation,
    'show_result_after_submission': ?showResultAfterSubmission,
    'max_attempts': ?maxAttempts,
  });

  @override
  Future<void> deleteExam(String examId) => _remote.delete(examId);

  @override
  Future<Exam> publishExam(String examId) => _remote.publish(examId);

  @override
  Future<List<ExamQuestion>> listQuestions(String examId) =>
      _remote.listQuestions(examId);

  @override
  Future<ExamQuestion> addQuestion(
    String examId, {
    required String questionText,
    required double points,
    required List<({String optionText, bool isCorrect})> options,
  }) => _remote.addQuestion(examId, {
    'question_text': questionText,
    'points': points,
    'options': options
        .map((o) => {'option_text': o.optionText, 'is_correct': o.isCorrect})
        .toList(),
  });

  @override
  Future<ExamQuestion> updateQuestion(
    String examQuestionId, {
    String? questionText,
    double? points,
    bool? isSelected,
    List<({String optionText, bool isCorrect})>? options,
  }) => _remote.updateQuestion(examQuestionId, {
    'question_text': ?questionText,
    'points': ?points,
    'is_selected': ?isSelected,
    'options': ?options
        ?.map((o) => {'option_text': o.optionText, 'is_correct': o.isCorrect})
        .toList(),
  });

  @override
  Future<void> deleteQuestion(String examQuestionId) =>
      _remote.deleteQuestion(examQuestionId);
}
