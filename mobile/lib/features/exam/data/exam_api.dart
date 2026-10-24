import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../core/network/api_client.dart';
import '../../../core/network/api_envelope.dart';
import '../../../core/network/api_failure.dart';
import '../domain/attempt_report.dart';
import '../domain/exam.dart';
import '../domain/exam_monitoring.dart';
import '../domain/exam_question.dart';
import '../domain/grade_range.dart';
import '../domain/own_class.dart';
import '../domain/question_bank_item.dart';

final examApiProvider = Provider<ExamApi>(
  (ref) => ExamApi(ref.watch(apiClientProvider)),
);

/// Endpoints dosen untuk ujian, soal, bank soal, pemantauan & rekap
/// (Modules/Academic/Routes/api.php).
class ExamApi {
  ExamApi(this._api);
  final ApiClient _api;

  // Kelas milik dosen ------------------------------------------------------

  /// Kelas ajar milik dosen: `GET /lecturer-profile` -> id, lalu
  /// `GET /class-sections?filter[lecturer_id]=`. Pengguna tanpa profil dosen
  /// dan tanpa `classes.update` mendapat daftar kosong; dengan `classes.update`
  /// (admin) semua kelas.
  Future<List<OwnClass>> ownClasses({required bool canSeeAll}) async {
    String? lecturerId;
    try {
      lecturerId = await _api.get<String?>(
        '/lecturer-profile',
        cache: true,
        decode: (d) => asString(asMap(d)['id']),
      );
    } on ApiFailure catch (e) {
      final missing =
          e.kind == ApiFailureKind.notFound ||
          e.kind == ApiFailureKind.forbidden;
      if (!missing) rethrow;
    }
    if (lecturerId == null && !canSeeAll) return const [];

    final result = <OwnClass>[];
    var page = 1;
    while (page <= 20) {
      final p = await _api.getPage<OwnClass>(
        '/class-sections',
        query: {
          'per_page': 100,
          'page': page,
          'filter[lecturer_id]': ?lecturerId,
        },
        item: OwnClass.fromJson,
        cache: true,
      );
      result.addAll(p.items);
      if (!p.hasNextPage) break;
      page++;
    }
    return result;
  }

  // Ujian -------------------------------------------------------------------

  Future<Paginated<Exam>> listExams({int page = 1, bool? isPublished}) =>
      _api.getPage<Exam>(
        '/exams',
        query: {
          'page': page,
          'per_page': 20,
          if (isPublished != null) 'filter[is_published]': isPublished ? 1 : 0,
        },
        item: Exam.fromJson,
        cache: true,
      );

  Future<Exam> getExam(String id) => _api.get<Exam>(
    '/exams/$id',
    cache: true,
    decode: (d) => Exam.fromJson(asMap(d)),
  );

  Future<Exam> createExam(Map<String, dynamic> payload) => _api.post<Exam>(
    '/exams',
    data: payload,
    decode: (d) => Exam.fromJson(asMap(d)),
  );

  Future<Exam> updateExam(String id, Map<String, dynamic> payload) =>
      _api.put<Exam>(
        '/exams/$id',
        data: payload,
        decode: (d) => Exam.fromJson(asMap(d)),
      );

  Future<void> deleteExam(String id) =>
      _api.delete<void>('/exams/$id', decode: (_) {});

  Future<Exam> publish(String id) => _api.patch<Exam>(
    '/exams/$id/publish',
    decode: (d) => Exam.fromJson(asMap(d)),
  );

  Future<Exam> generateAccessLink(String id) => _api.patch<Exam>(
    '/exams/$id/access-link',
    decode: (d) => Exam.fromJson(asMap(d)),
  );

  // Soal ujian -------------------------------------------------------------

  Future<List<ExamQuestion>> listQuestions(String examId) =>
      _api.getList<ExamQuestion>(
        '/exams/$examId/questions',
        item: ExamQuestion.fromJson,
      );

  Future<ExamQuestion> addQuestion(
    String examId, {
    required String questionText,
    required double points,
    required List<OptionDraft> options,
  }) => _api.post<ExamQuestion>(
    '/exams/$examId/questions',
    data: {
      'question_text': questionText,
      'points': points,
      'options': optionsPayload(options),
    },
    decode: (d) => ExamQuestion.fromJson(asMap(d)),
  );

  Future<ExamQuestion> updateQuestion(
    String examQuestionId, {
    String? questionText,
    double? points,
    bool? isSelected,
    List<OptionDraft>? options,
  }) => _api.put<ExamQuestion>(
    '/exam-questions/$examQuestionId',
    data: {
      'question_text': ?questionText,
      'points': ?points,
      'is_selected': ?isSelected,
      if (options != null) 'options': optionsPayload(options),
    },
    decode: (d) => ExamQuestion.fromJson(asMap(d)),
  );

  Future<void> deleteQuestion(String examQuestionId) =>
      _api.delete<void>('/exam-questions/$examQuestionId', decode: (_) {});

  Future<List<ExamQuestion>> applyBank(
    String examId,
    List<String> bankItemIds,
  ) => _api.post<List<ExamQuestion>>(
    '/exams/$examId/questions/apply-bank',
    data: {'question_bank_item_ids': bankItemIds},
    decode: (d) => asMapList(d).map(ExamQuestion.fromJson).toList(),
  );

  // Bank soal ----------------------------------------------------------------

  Future<Paginated<QuestionBankItem>> listBank({
    int page = 1,
    String? search,
    String? courseId,
  }) => _api.getPage<QuestionBankItem>(
    '/question-bank',
    query: {
      'page': page,
      'per_page': 20,
      if (search != null && search.isNotEmpty) 'search': search,
      'filter[course_id]': ?courseId,
    },
    item: QuestionBankItem.fromJson,
    cache: true,
  );

  Future<QuestionBankItem> createBankItem({
    String? courseId,
    required String questionText,
    required double points,
    required List<OptionDraft> options,
  }) => _api.post<QuestionBankItem>(
    '/question-bank',
    data: {
      'course_id': courseId,
      'question_text': questionText,
      'points': points,
      'options': optionsPayload(options),
    },
    decode: (d) => QuestionBankItem.fromJson(asMap(d)),
  );

  Future<QuestionBankItem> updateBankItem(
    String id, {
    String? courseId,
    required String questionText,
    required double points,
    required List<OptionDraft> options,
  }) => _api.put<QuestionBankItem>(
    '/question-bank/$id',
    data: {
      'course_id': courseId,
      'question_text': questionText,
      'points': points,
      'options': optionsPayload(options),
    },
    decode: (d) => QuestionBankItem.fromJson(asMap(d)),
  );

  Future<void> deleteBankItem(String id) =>
      _api.delete<void>('/question-bank/$id', decode: (_) {});

  // Pemantauan & rekap -----------------------------------------------------

  Future<List<ExamParticipant>> participants(String examId) async {
    final result = <ExamParticipant>[];
    var page = 1;
    while (page <= 20) {
      final p = await _api.getPage<ExamParticipant>(
        '/exams/$examId/participants',
        query: {'per_page': 100, 'page': page},
        item: ExamParticipant.fromJson,
      );
      result.addAll(p.items);
      if (!p.hasNextPage) break;
      page++;
    }
    return result;
  }

  Future<List<ExamViolation>> recentViolations(
    String examId, {
    DateTime? since,
  }) => _api.getList<ExamViolation>(
    '/exams/$examId/violations/recent',
    query: {if (since != null) 'since': since.toUtc().toIso8601String()},
    item: ExamViolation.fromJson,
  );

  Future<AttemptViolations> attemptViolations(String attemptId) =>
      _api.get<AttemptViolations>(
        '/exam-attempts/$attemptId/violations',
        decode: (d) => AttemptViolations.fromJson(asMap(d)),
      );

  /// "Detail Report" satu percobaan — waktu, jawaban per soal, pelanggaran,
  /// linimasa. Tanpa cache: dipantau langsung selama peserta mengerjakan.
  Future<AttemptReport> attemptReport(String attemptId) =>
      _api.get<AttemptReport>(
        '/exam-attempts/$attemptId/report',
        decode: (d) => AttemptReport.fromJson(asMap(d)),
      );

  static String attemptReportPdfPath(String attemptId) =>
      '/exam-attempts/$attemptId/report/pdf';

  Future<ExamRecap> recap(String examId) => _api.get<ExamRecap>(
    '/exams/$examId/recap',
    cache: true,
    decode: (d) => ExamRecap.fromJson(asMap(d)),
  );

  // Rentang nilai ----------------------------------------------------------

  Future<List<GradeRange>> gradeRanges(String examId) =>
      _api.getList<GradeRange>(
        '/exams/$examId/grade-ranges',
        item: GradeRange.fromJson,
      );

  Future<List<GradeRange>> saveGradeRanges(
    String examId,
    List<GradeRange> ranges,
  ) => _api.put<List<GradeRange>>(
    '/exams/$examId/grade-ranges',
    data: {'ranges': ranges.map((r) => r.toJson()).toList()},
    decode: (d) => asMapList(d).map(GradeRange.fromJson).toList(),
  );
}
