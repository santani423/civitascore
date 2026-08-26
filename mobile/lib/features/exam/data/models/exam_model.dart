import '../../domain/entities/exam.dart';
import '../../domain/entities/question_selection_mode.dart';

class ExamModel extends Exam {
  const ExamModel({
    required super.id,
    required super.classSectionId,
    super.classCode,
    super.courseName,
    required super.title,
    required super.durationMinutes,
    required super.questionPoolSize,
    required super.questionsPerParticipant,
    required super.questionSelectionMode,
    required super.randomizeQuestions,
    required super.randomizeOptions,
    required super.allowBackNavigation,
    required super.showResultAfterSubmission,
    required super.maxAttempts,
    required super.isPublished,
    super.publishedAt,
  });

  factory ExamModel.fromJson(Map<String, dynamic> json) => ExamModel(
    id: json['id'] as String,
    classSectionId: json['class_section_id'] as String,
    classCode: json['class_code'] as String?,
    courseName: json['course_name'] as String?,
    title: json['title'] as String,
    durationMinutes: json['duration_minutes'] as int,
    questionPoolSize: json['question_pool_size'] as int? ?? 0,
    questionsPerParticipant: json['questions_per_participant'] as int,
    questionSelectionMode: QuestionSelectionMode.fromApiValue(
      json['question_selection_mode'] as String,
    ),
    randomizeQuestions: json['randomize_questions'] as bool? ?? false,
    randomizeOptions: json['randomize_options'] as bool? ?? false,
    allowBackNavigation: json['allow_back_navigation'] as bool? ?? true,
    showResultAfterSubmission:
        json['show_result_after_submission'] as bool? ?? true,
    maxAttempts: json['max_attempts'] as int? ?? 1,
    isPublished: json['is_published'] as bool? ?? false,
    publishedAt: json['published_at'] != null
        ? DateTime.parse(json['published_at'] as String)
        : null,
  );
}
