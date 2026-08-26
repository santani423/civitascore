import 'exam_option.dart';

class ExamQuestion {
  const ExamQuestion({
    required this.id,
    required this.examId,
    required this.questionText,
    required this.points,
    required this.orderIndex,
    required this.isSelected,
    required this.options,
  });

  final String id;
  final String examId;
  final String questionText;
  final double points;
  final int orderIndex;

  /// Dipakai hanya saat [QuestionSelectionMode.manual] — menandai soal ini
  /// termasuk dalam set tetap yang diterima setiap peserta (spec §4 Mode C).
  final bool isSelected;
  final List<ExamOption> options;
}
