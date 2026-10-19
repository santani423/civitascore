import '../../../core/network/api_envelope.dart';
import 'exam_question.dart';

/// `QuestionBankItemResource`.
class QuestionBankItem {
  const QuestionBankItem({
    required this.id,
    this.courseId,
    this.courseName,
    required this.questionText,
    required this.points,
    required this.options,
    required this.usageCount,
  });

  factory QuestionBankItem.fromJson(Map<String, dynamic> json) =>
      QuestionBankItem(
        id: asString(json['id']) ?? '',
        courseId: asString(json['course_id']),
        courseName: asString(json['course_name']),
        questionText: asString(json['question_text']) ?? '',
        points: asDouble(json['points']) ?? 1,
        options: asMapList(json['options']).map(ExamOption.fromJson).toList(),
        usageCount: asInt(json['usage_count']) ?? 0,
      );

  final String id;
  final String? courseId;
  final String? courseName;
  final String questionText;
  final double points;
  final List<ExamOption> options;
  final int usageCount;
}
