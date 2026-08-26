/// Opsi jawaban satu soal pilihan ganda. `id` adalah identitas yang stabil
/// di sisi backend — dipakai untuk memetakan jawaban benar walau urutan
/// tampilnya diacak per percobaan peserta (lihat docs/EXAM_RANDOMIZATION.md).
class ExamOption {
  const ExamOption({
    required this.id,
    required this.optionText,
    required this.isCorrect,
    required this.orderIndex,
  });

  final String id;
  final String optionText;

  /// Hanya terisi bermakna pada tampilan dosen/admin (ExamQuestionModel) —
  /// tidak pernah dikirim backend untuk tampilan peserta ujian.
  final bool isCorrect;
  final int orderIndex;

  ExamOption copyWith({String? optionText, bool? isCorrect}) => ExamOption(
    id: id,
    optionText: optionText ?? this.optionText,
    isCorrect: isCorrect ?? this.isCorrect,
    orderIndex: orderIndex,
  );
}
