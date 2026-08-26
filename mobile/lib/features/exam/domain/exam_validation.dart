import 'entities/question_selection_mode.dart';

/// Aturan validasi konfigurasi ujian (spec §2/§8/§18) — sengaja diduplikasi
/// murni sebagai pengecekan sisi-klien untuk umpan balik instan; backend
/// tetap satu-satunya sumber kebenaran dan menjalankan pengecekan yang sama
/// saat publish (lihat ExamService::publish di backend).
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

  /// Kumpulan seluruh pesan error yang berlaku untuk konfigurasi saat ini,
  /// dipakai layar wizard untuk menampilkan status & menonaktifkan tombol
  /// Publish (spec §18 — "Publish button disabled until valid").
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

  /// Ringkasan distribusi soal berbahasa Indonesia untuk layar preview
  /// (spec §11/§20) — dibangun dari konfigurasi yang sama dengan validasi di
  /// atas supaya tidak ada dua sumber kebenaran untuk teks yang sama.
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
