import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../core/network/api_envelope.dart';
import '../../../core/widgets/paged_list.dart';
import '../../auth/presentation/controllers/auth_controller.dart';
import '../data/exam_api.dart';
import '../domain/exam.dart';
import '../domain/exam_monitoring.dart';
import '../domain/exam_question.dart';
import '../domain/grade_range.dart';
import '../domain/own_class.dart';
import '../domain/question_bank_item.dart';

/// Kelas milik dosen (pemilih kelas + filter kepemilikan sisi klien, karena
/// server hanya membatasi bila flag academic.lecturer_ownership aktif).
final ownClassesProvider = FutureProvider.autoDispose<List<OwnClass>>((ref) {
  final user = ref.watch(currentUserProvider);
  return ref
      .watch(examApiProvider)
      .ownClasses(canSeeAll: user?.hasPermission('classes.update') ?? false);
});

/// Filter status publikasi daftar ujian: null = semua.
final examPublishedFilterProvider = StateProvider.autoDispose<bool?>(
  (ref) => null,
);

final examListProvider =
    AsyncNotifierProvider.autoDispose<ExamListNotifier, PagedState<Exam>>(
      ExamListNotifier.new,
    );

class ExamListNotifier extends PagedNotifier<Exam> {
  @override
  Future<PagedState<Exam>> build() {
    // Dependensi dibaca di build agar daftar dimuat ulang saat filter berubah.
    ref.watch(examPublishedFilterProvider);
    ref.watch(ownClassesProvider);
    return super.build();
  }

  @override
  Future<Paginated<Exam>> fetchPage(int page) async {
    final filter = ref.read(examPublishedFilterProvider);
    final own = await ref.read(ownClassesProvider.future);
    final ownIds = own.map((c) => c.id).toSet();
    final result = await ref
        .read(examApiProvider)
        .listExams(page: page, isPublished: filter);
    return Paginated(
      items: result.items
          .where((e) => ownIds.contains(e.classSectionId))
          .toList(),
      meta: result.meta,
    );
  }
}

final examProvider = FutureProvider.autoDispose.family<Exam, String>(
  (ref, id) => ref.watch(examApiProvider).getExam(id),
);

final examQuestionsProvider = FutureProvider.autoDispose
    .family<List<ExamQuestion>, String>(
      (ref, examId) => ref.watch(examApiProvider).listQuestions(examId),
    );

final examParticipantsProvider = FutureProvider.autoDispose
    .family<List<ExamParticipant>, String>(
      (ref, examId) => ref.watch(examApiProvider).participants(examId),
    );

final examRecapProvider = FutureProvider.autoDispose.family<ExamRecap, String>(
  (ref, examId) => ref.watch(examApiProvider).recap(examId),
);

final gradeRangesProvider = FutureProvider.autoDispose
    .family<List<GradeRange>, String>(
      (ref, examId) => ref.watch(examApiProvider).gradeRanges(examId),
    );

final attemptViolationsProvider = FutureProvider.autoDispose
    .family<AttemptViolations, String>(
      (ref, attemptId) =>
          ref.watch(examApiProvider).attemptViolations(attemptId),
    );

// Bank soal ------------------------------------------------------------------

class BankQuery {
  const BankQuery({this.search = '', this.courseId});
  final String search;
  final String? courseId;

  BankQuery copyWith({
    String? search,
    String? courseId,
    bool clearCourse = false,
  }) => BankQuery(
    search: search ?? this.search,
    courseId: clearCourse ? null : (courseId ?? this.courseId),
  );
}

final bankQueryProvider = StateProvider.autoDispose<BankQuery>(
  (ref) => const BankQuery(),
);

final bankListProvider =
    AsyncNotifierProvider.autoDispose<
      BankListNotifier,
      PagedState<QuestionBankItem>
    >(BankListNotifier.new);

class BankListNotifier extends PagedNotifier<QuestionBankItem> {
  @override
  Future<PagedState<QuestionBankItem>> build() {
    ref.watch(bankQueryProvider);
    return super.build();
  }

  @override
  Future<Paginated<QuestionBankItem>> fetchPage(int page) {
    final q = ref.read(bankQueryProvider);
    return ref
        .read(examApiProvider)
        .listBank(page: page, search: q.search, courseId: q.courseId);
  }
}
