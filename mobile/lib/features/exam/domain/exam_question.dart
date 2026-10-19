import '../../../core/network/api_envelope.dart';

/// Opsi jawaban satu soal pilihan ganda. `id` stabil di backend — dipakai
/// memetakan jawaban benar walau urutan tampil diacak per percobaan
/// (docs/EXAM_RANDOMIZATION.md). `isCorrect` hanya bermakna untuk dosen.
class ExamOption {
  const ExamOption({
    required this.id,
    required this.optionText,
    required this.isCorrect,
    required this.orderIndex,
  });

  factory ExamOption.fromJson(Map<String, dynamic> json) => ExamOption(
    id: asString(json['id']) ?? '',
    optionText: asString(json['option_text']) ?? '',
    isCorrect: asBool(json['is_correct']),
    orderIndex: asInt(json['order_index']) ?? 0,
  );

  final String id;
  final String optionText;
  final bool isCorrect;
  final int orderIndex;
}

/// Draft opsi yang dikirim ke backend (create/update soal & bank soal).
typedef OptionDraft = ({String optionText, bool isCorrect});

List<Map<String, dynamic>> optionsPayload(List<OptionDraft> options) => options
    .map((o) => {'option_text': o.optionText, 'is_correct': o.isCorrect})
    .toList();

/// `ExamQuestionResource`.
class ExamQuestion {
  const ExamQuestion({
    required this.id,
    required this.examId,
    this.questionBankItemId,
    required this.questionText,
    required this.points,
    required this.orderIndex,
    required this.isSelected,
    required this.options,
  });

  factory ExamQuestion.fromJson(Map<String, dynamic> json) => ExamQuestion(
    id: asString(json['id']) ?? '',
    examId: asString(json['exam_id']) ?? '',
    questionBankItemId: asString(json['question_bank_item_id']),
    questionText: asString(json['question_text']) ?? '',
    points: asDouble(json['points']) ?? 1,
    orderIndex: asInt(json['order_index']) ?? 0,
    isSelected: asBool(json['is_selected'], fallback: true),
    options: asMapList(json['options']).map(ExamOption.fromJson).toList(),
  );

  final String id;
  final String examId;
  final String? questionBankItemId;
  final String questionText;
  final double points;
  final int orderIndex;

  /// Dipakai pada mode manual — menandai soal termasuk set tetap peserta.
  final bool isSelected;
  final List<ExamOption> options;
}
