import 'package:civitasone/features/exam/domain/entities/question_selection_mode.dart';
import 'package:civitasone/features/exam/domain/exam_validation.dart';
import 'package:flutter_test/flutter_test.dart';

void main() {
  group('ExamValidation.participantCountError', () {
    test('rejects zero questions per participant', () {
      expect(
        ExamValidation.participantCountError(0),
        'Jumlah soal harus lebih dari 0.',
      );
    });

    test('accepts a positive count', () {
      expect(ExamValidation.participantCountError(10), isNull);
    });
  });

  group('ExamValidation.poolSizeError', () {
    test('rejects an empty pool', () {
      expect(
        ExamValidation.poolSizeError(0),
        'Question pool masih kosong. Tambahkan soal terlebih dahulu.',
      );
    });

    test('accepts a non-empty pool', () {
      expect(ExamValidation.poolSizeError(5), isNull);
    });
  });

  group('ExamValidation.participantExceedsPoolError', () {
    test(
      'rejects when participants exceed the pool (scenario: pool=40, per-participant=50)',
      () {
        expect(
          ExamValidation.participantExceedsPoolError(
            questionsPerParticipant: 50,
            questionPoolSize: 40,
          ),
          'Jumlah soal per peserta tidak boleh melebihi jumlah soal dalam question pool.',
        );
      },
    );

    test(
      'accepts when participants equal the pool size (all questions delivered)',
      () {
        expect(
          ExamValidation.participantExceedsPoolError(
            questionsPerParticipant: 50,
            questionPoolSize: 50,
          ),
          isNull,
        );
      },
    );
  });

  group('ExamValidation.manualSelectionError', () {
    test(
      'rejects insufficient manual selection (scenario: need 50, selected 45)',
      () {
        expect(
          ExamValidation.manualSelectionError(
            questionsPerParticipant: 50,
            selectedCount: 45,
          ),
          'Soal belum mencukupi. Anda membutuhkan 50 soal, tetapi baru memilih 45 soal.',
        );
      },
    );

    test('accepts when selection meets the requirement', () {
      expect(
        ExamValidation.manualSelectionError(
          questionsPerParticipant: 50,
          selectedCount: 50,
        ),
        isNull,
      );
    });
  });

  group('ExamValidation.canPublish', () {
    test('is false when the pool is empty', () {
      expect(
        ExamValidation.canPublish(
          questionPoolSize: 0,
          questionsPerParticipant: 10,
          selectionMode: QuestionSelectionMode.random,
          manuallySelectedCount: 0,
        ),
        isFalse,
      );
    });

    test('is false for manual mode with an insufficient selection', () {
      expect(
        ExamValidation.canPublish(
          questionPoolSize: 100,
          questionsPerParticipant: 50,
          selectionMode: QuestionSelectionMode.manual,
          manuallySelectedCount: 45,
        ),
        isFalse,
      );
    });

    test(
      'is true for a valid random-mode configuration (scenario: pool=100, per-participant=50)',
      () {
        expect(
          ExamValidation.canPublish(
            questionPoolSize: 100,
            questionsPerParticipant: 50,
            selectionMode: QuestionSelectionMode.random,
            manuallySelectedCount: 0,
          ),
          isTrue,
        );
      },
    );

    test(
      'is true when all questions are delivered (pool == per-participant)',
      () {
        expect(
          ExamValidation.canPublish(
            questionPoolSize: 50,
            questionsPerParticipant: 50,
            selectionMode: QuestionSelectionMode.all,
            manuallySelectedCount: 0,
          ),
          isTrue,
        );
      },
    );
  });

  group('ExamValidation.distributionSummary', () {
    test('describes a random selection with both randomizations enabled', () {
      final summary = ExamValidation.distributionSummary(
        questionPoolSize: 100,
        questionsPerParticipant: 50,
        selectionMode: QuestionSelectionMode.random,
        randomizeQuestions: true,
        randomizeOptions: true,
      );

      expect(
        summary,
        contains('50 soal yang dipilih secara acak dari 100 soal'),
      );
      expect(summary, contains('Urutan soal dan pilihan jawaban akan diacak'));
    });

    test(
      'describes an "all questions" configuration without a randomization sentence',
      () {
        final summary = ExamValidation.distributionSummary(
          questionPoolSize: 50,
          questionsPerParticipant: 50,
          selectionMode: QuestionSelectionMode.all,
          randomizeQuestions: false,
          randomizeOptions: false,
        );

        expect(summary, contains('seluruh 50 soal yang tersedia'));
        expect(summary, isNot(contains('diacak')));
      },
    );
  });
}
