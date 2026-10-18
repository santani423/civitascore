import 'dart:io';

import 'package:dio/dio.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../core/network/api_client.dart';
import '../../../core/network/api_envelope.dart';
import '../../../core/network/api_failure.dart';
import '../domain/exam_models.dart';

/// An attempt plus the offset between the server clock and this device
/// (`server - device`), taken from the HTTP `Date` header (1 s resolution).
class AttemptSnapshot {
  const AttemptSnapshot(this.attempt, [this.serverOffset = Duration.zero]);
  final ExamAttempt attempt;
  final Duration serverOffset;
}

class ExamStudentApi {
  ExamStudentApi(this._api);
  final ApiClient _api;

  Future<List<StudentExam>> exams() =>
      _api.getList('/student/exams', item: StudentExam.fromJson, cache: true);

  Future<StudentExam> exam(String id) => _api.get(
    '/student/exams/$id',
    decode: (d) => StudentExam.fromJson(asMap(d)),
    cache: true,
  );

  Future<ExamAttempt> start(String examId) => _api.post(
    '/student/exams/$examId/start',
    decode: (d) => ExamAttempt.fromJson(asMap(d)),
  );

  /// Not cached: an exam in progress must always reflect the server.
  Future<AttemptSnapshot> attempt(String id) async {
    try {
      final response = await _api.dio.get<Object?>(
        '/student/exam-attempts/$id',
      );
      final body = asMap(response.data);
      Duration offset = Duration.zero;
      final date = response.headers.value('date');
      if (date != null) {
        try {
          offset = HttpDate.parse(date).difference(DateTime.now().toUtc());
        } on Exception {
          offset = Duration.zero;
        }
      }
      return AttemptSnapshot(ExamAttempt.fromJson(asMap(body['data'])), offset);
    } on DioException catch (e) {
      throw ApiFailure.fromDioException(e);
    }
  }

  Future<ExamAttempt> answer(
    String attemptId, {
    required String questionId,
    required String? optionId,
  }) => _api.put(
    '/student/exam-attempts/$attemptId/answer',
    data: {'exam_question_id': questionId, 'exam_question_option_id': optionId},
    decode: (d) => ExamAttempt.fromJson(asMap(d)),
  );

  Future<ExamAttempt> submit(String attemptId) => _api.patch(
    '/student/exam-attempts/$attemptId/submit',
    decode: (d) => ExamAttempt.fromJson(asMap(d)),
  );

  Future<ExamAttempt> violation(
    String attemptId,
    ExamViolationType type, {
    Map<String, dynamic>? metadata,
  }) => _api.post(
    '/student/exam-attempts/$attemptId/violations',
    data: {'violation_type': type.wire, 'metadata': ?metadata},
    decode: (d) => ExamAttempt.fromJson(asMap(d)),
  );

  Future<ExamResult> result(String attemptId) => _api.get(
    '/student/exam-attempts/$attemptId/result',
    decode: (d) => ExamResult.fromJson(asMap(d)),
  );

  static String resultPdfPath(String attemptId) =>
      '/student/exam-attempts/$attemptId/result/pdf';
}

final examStudentApiProvider = Provider<ExamStudentApi>(
  (ref) => ExamStudentApi(ref.watch(apiClientProvider)),
);
