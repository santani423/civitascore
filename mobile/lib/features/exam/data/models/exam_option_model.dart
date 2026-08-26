import '../../domain/entities/exam_option.dart';

class ExamOptionModel extends ExamOption {
  const ExamOptionModel({
    required super.id,
    required super.optionText,
    required super.isCorrect,
    required super.orderIndex,
  });

  factory ExamOptionModel.fromJson(Map<String, dynamic> json) =>
      ExamOptionModel(
        id: json['id'] as String,
        optionText: json['option_text'] as String,
        isCorrect: json['is_correct'] as bool? ?? false,
        orderIndex: json['order_index'] as int? ?? 0,
      );
}
