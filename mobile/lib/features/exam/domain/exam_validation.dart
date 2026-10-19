import 'question_selection_mode.dart';

/// Jenis masalah konfigurasi ujian (spec §2/§8/§18). UI menerjemahkannya via
/// `ExamStrings.issueText`; pesan Indonesia di bawah dipertahankan untuk
/// pengecekan murni & tes.
enum ExamIssueType { participantCount, poolEmpty, exceedsPool, manualShort }

class ExamIssue {
  const ExamIssue(this.type, {this.required = 0, this.selected = 0});
  final ExamIssueType type;
  final int required;
  final int selected;
}

/// Aturan validasi konfigurasi ujian — pengecekan sisi-klien untuk umpan balik
/// instan; backend tetap sumber kebenaran (ExamService::publish).
abstract final class ExamValidation {
  static String? participantCountError(int questionsPerParticipant) {
    if (questionsPerParticipant <= 0) {
      return 'Jumlah soal harus lebih dari 0.';
    }
    return null;
  }

  static String? poolSizeError(int questionPoolSize) {
    if (questionPoolSize == 0) {
      return 'Question pool masih kosong. Tambahkan soal terlebih dahulu.';
    }
    return null;
  }

  static String? participantExceedsPoolError({
    required int questionsPerParticipant,
    required int questionPoolSize,
  }) {
    if (questionsPerParticipant > questionPoolSize) {
      return 'Jumlah soal per peserta tidak boleh melebihi jumlah soal dalam question pool.';
    }
    return null;
  }

  static String? manualSelectionError({
    required int questionsPerParticipant,
    required int selectedCount,
  }) {
    if (selectedCount < questionsPerParticipant) {
      return 'Soal belum mencukupi. Anda membutuhkan $questionsPerParticipant soal, '
          'tetapi baru memilih $selectedCount soal.';
    }
    return null;
  }

  /// Masalah terstruktur (untuk UI terlokalisasi).
  static List<ExamIssue> issuesFor({
    required int questionPoolSize,
    required int questionsPerParticipant,
    required QuestionSelectionMode selectionMode,
    required int manuallySelectedCount,
  }) {
    final issues = <ExamIssue>[];
    if (participantCountError(questionsPerParticipant) != null) {
      issues.add(const ExamIssue(ExamIssueType.participantCount));
    }
    if (poolSizeError(questionPoolSize) != null) {
      issues.add(const ExamIssue(ExamIssueType.poolEmpty));
    }
    if (participantExceedsPoolError(
          questionsPerParticipant: questionsPerParticipant,
          questionPoolSize: questionPoolSize,
        ) !=
        null) {
      issues.add(const ExamIssue(ExamIssueType.exceedsPool));
    }
    if (selectionMode == QuestionSelectionMode.manual &&
        manualSelectionError(
              questionsPerParticipant: questionsPerParticipant,
              selectedCount: manuallySelectedCount,
            ) !=
            null) {
      issues.add(
        ExamIssue(
          ExamIssueType.manualShort,
          required: questionsPerParticipant,
          selected: manuallySelectedCount,
        ),
      );
    }
    return issues;
  }

  static List<String> errorsFor({
    required int questionPoolSize,
    required int questionsPerParticipant,
    required QuestionSelectionMode selectionMode,
    required int manuallySelectedCount,
  }) {
    final errors = <String>[];

    final participantCount = participantCountError(questionsPerParticipant);
    if (participantCount != null) errors.add(participantCount);

    final poolSize = poolSizeError(questionPoolSize);
    if (poolSize != null) errors.add(poolSize);

    final exceedsPool = participantExceedsPoolError(
      questionsPerParticipant: questionsPerParticipant,
      questionPoolSize: questionPoolSize,
    );
    if (exceedsPool != null) errors.add(exceedsPool);

    if (selectionMode == QuestionSelectionMode.manual) {
      final manualError = manualSelectionError(
        questionsPerParticipant: questionsPerParticipant,
        selectedCount: manuallySelectedCount,
      );
      if (manualError != null) errors.add(manualError);
    }

    return errors;
  }

  static bool canPublish({
    required int questionPoolSize,
    required int questionsPerParticipant,
    required QuestionSelectionMode selectionMode,
    required int manuallySelectedCount,
  }) => errorsFor(
    questionPoolSize: questionPoolSize,
    questionsPerParticipant: questionsPerParticipant,
    selectionMode: selectionMode,
    manuallySelectedCount: manuallySelectedCount,
  ).isEmpty;

  /// Ringkasan distribusi soal berbahasa Indonesia (spec §11/§20). UI memakai
  /// `ExamStrings.distributionSummary` untuk versi terlokalisasi.
  static String distributionSummary({
    required int questionPoolSize,
    required int questionsPerParticipant,
    required QuestionSelectionMode selectionMode,
    required bool randomizeQuestions,
    required bool randomizeOptions,
  }) {
    final selectionText = switch (selectionMode) {
      QuestionSelectionMode.all =>
        'seluruh $questionPoolSize soal yang tersedia',
      QuestionSelectionMode.random =>
        '$questionsPerParticipant soal yang dipilih secara acak dari $questionPoolSize soal',
      QuestionSelectionMode.manual =>
        '$questionsPerParticipant soal yang telah dipilih secara manual',
    };

    final buffer = StringBuffer(
      'Setiap peserta akan mendapatkan $selectionText.',
    );

    if (randomizeQuestions || randomizeOptions) {
      final parts = [
        if (randomizeQuestions) 'urutan soal',
        if (randomizeOptions) 'pilihan jawaban',
      ].join(' dan ');
      buffer.write(
        ' ${parts[0].toUpperCase()}${parts.substring(1)} akan diacak.',
      );
    }

    return buffer.toString();
  }
}
