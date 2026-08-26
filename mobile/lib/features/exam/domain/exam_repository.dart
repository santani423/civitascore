import 'entities/exam.dart';
import 'entities/exam_question.dart';
import 'entities/question_selection_mode.dart';

abstract interface class ExamRepository {
  Future<List<Exam>> listExams({String? classSectionId});
  Future<Exam> getExam(String examId);
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
  });
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
  });
  Future<void> deleteExam(String examId);
  Future<Exam> publishExam(String examId);

  Future<List<ExamQuestion>> listQuestions(String examId);
  Future<ExamQuestion> addQuestion(
    String examId, {
    required String questionText,
    required double points,
    required List<({String optionText, bool isCorrect})> options,
  });
  Future<ExamQuestion> updateQuestion(
    String examQuestionId, {
    String? questionText,
    double? points,
    bool? isSelected,
    List<({String optionText, bool isCorrect})>? options,
  });
  Future<void> deleteQuestion(String examQuestionId);
}
