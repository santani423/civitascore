import '../../domain/entities/exam_question.dart';
import 'exam_option_model.dart';

class ExamQuestionModel extends ExamQuestion {
  const ExamQuestionModel({
    required super.id,
    required super.examId,
    required super.questionText,
    required super.points,
    required super.orderIndex,
    required super.isSelected,
    required super.options,
  });

  factory ExamQuestionModel.fromJson(Map<String, dynamic> json) =>
      ExamQuestionModel(
        id: json['id'] as String,
        examId: json['exam_id'] as String? ?? '',
        questionText: json['question_text'] as String,
        points: double.tryParse('${json['points']}') ?? 1,
        orderIndex: json['order_index'] as int? ?? 0,
        isSelected: json['is_selected'] as bool? ?? true,
        options: (json['options'] as List? ?? [])
            .map(
              (json) => ExamOptionModel.fromJson(json as Map<String, dynamic>),
            )
            .toList(),
      );
}
