import 'question_selection_mode.dart';

class Exam {
  const Exam({
    required this.id,
    required this.classSectionId,
    this.classCode,
    this.courseName,
    required this.title,
    required this.durationMinutes,
    required this.questionPoolSize,
    required this.questionsPerParticipant,
    required this.questionSelectionMode,
    required this.randomizeQuestions,
    required this.randomizeOptions,
    required this.allowBackNavigation,
    required this.showResultAfterSubmission,
    required this.maxAttempts,
    required this.isPublished,
    this.publishedAt,
  });

  final String id;
  final String classSectionId;
  final String? classCode;
  final String? courseName;
  final String title;
  final int durationMinutes;
  final int questionPoolSize;
  final int questionsPerParticipant;
  final QuestionSelectionMode questionSelectionMode;
  final bool randomizeQuestions;
  final bool randomizeOptions;
  final bool allowBackNavigation;
  final bool showResultAfterSubmission;
  final int maxAttempts;
  final bool isPublished;
  final DateTime? publishedAt;
}
