import '../../../core/network/api_envelope.dart';
import 'question_selection_mode.dart';

/// `ExamResource` (Modules\Academic\Resources\ExamResource).
class Exam {
  const Exam({
    required this.id,
    required this.classSectionId,
    this.classCode,
    this.courseName,
    required this.title,
    required this.durationMinutes,
    this.startsAt,
    this.endsAt,
    this.creatorName,
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
    this.accessToken,
    this.accessTokenGeneratedAt,
    this.weightPercentage,
  });

  factory Exam.fromJson(Map<String, dynamic> json) => Exam(
    id: asString(json['id']) ?? '',
    classSectionId: asString(json['class_section_id']) ?? '',
    classCode: asString(json['class_code']),
    courseName: asString(json['course_name']),
    title: asString(json['title']) ?? '',
    durationMinutes: asInt(json['duration_minutes']) ?? 0,
    startsAt: asDateTime(json['starts_at']),
    endsAt: asDateTime(json['ends_at']),
    creatorName: asString(json['creator_name']),
    questionPoolSize: asInt(json['question_pool_size']) ?? 0,
    questionsPerParticipant: asInt(json['questions_per_participant']) ?? 0,
    questionSelectionMode: QuestionSelectionMode.fromApiValue(
      asString(json['question_selection_mode']),
    ),
    randomizeQuestions: asBool(json['randomize_questions']),
    randomizeOptions: asBool(json['randomize_options']),
    allowBackNavigation: asBool(json['allow_back_navigation'], fallback: true),
    showResultAfterSubmission: asBool(
      json['show_result_after_submission'],
      fallback: true,
    ),
    maxAttempts: asInt(json['max_attempts']) ?? 1,
    isPublished: asBool(json['is_published']),
    publishedAt: asDateTime(json['published_at']),
    accessToken: asString(json['access_token']),
    accessTokenGeneratedAt: asDateTime(json['access_token_generated_at']),
    weightPercentage: asDouble(json['weight_percentage']),
  );

  final String id;
  final String classSectionId;
  final String? classCode;
  final String? courseName;
  final String title;
  final int durationMinutes;
  final DateTime? startsAt;
  final DateTime? endsAt;
  final String? creatorName;
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
  final String? accessToken;
  final DateTime? accessTokenGeneratedAt;

  /// Decimal string on the wire ("30.00"); null when not weighted.
  final double? weightPercentage;

  bool get hasAccessLink => accessToken != null && accessToken!.isNotEmpty;
}
