import 'package:dio/dio.dart';

import '../../../core/network/api_failure.dart';
import 'models/exam_model.dart';
import 'models/exam_question_model.dart';

class ExamRemoteDataSource {
  ExamRemoteDataSource(this._dio);

  final Dio _dio;

  Future<List<ExamModel>> list({String? classSectionId}) async {
    try {
      final response = await _dio.get(
        '/exams',
        queryParameters: {
          'filter[class_section_id]': ?classSectionId,
          'per_page': 100,
        },
      );
      return (response.data['data'] as List)
          .map((json) => ExamModel.fromJson(json as Map<String, dynamic>))
          .toList();
    } on DioException catch (e) {
      throw ApiFailure.fromDioException(e);
    }
  }

  Future<ExamModel> get(String examId) async {
    try {
      final response = await _dio.get('/exams/$examId');
      return ExamModel.fromJson(response.data['data'] as Map<String, dynamic>);
    } on DioException catch (e) {
      throw ApiFailure.fromDioException(e);
    }
  }

  Future<ExamModel> create(Map<String, dynamic> payload) async {
    try {
      final response = await _dio.post('/exams', data: payload);
      return ExamModel.fromJson(response.data['data'] as Map<String, dynamic>);
    } on DioException catch (e) {
      throw ApiFailure.fromDioException(e);
    }
  }

  Future<ExamModel> update(String examId, Map<String, dynamic> payload) async {
    try {
      final response = await _dio.put('/exams/$examId', data: payload);
      return ExamModel.fromJson(response.data['data'] as Map<String, dynamic>);
    } on DioException catch (e) {
      throw ApiFailure.fromDioException(e);
    }
  }

  Future<void> delete(String examId) async {
    try {
      await _dio.delete('/exams/$examId');
    } on DioException catch (e) {
      throw ApiFailure.fromDioException(e);
    }
  }

  Future<ExamModel> publish(String examId) async {
    try {
      final response = await _dio.patch('/exams/$examId/publish');
      return ExamModel.fromJson(response.data['data'] as Map<String, dynamic>);
    } on DioException catch (e) {
      throw ApiFailure.fromDioException(e);
    }
  }

  Future<List<ExamQuestionModel>> listQuestions(String examId) async {
    try {
      final response = await _dio.get('/exams/$examId/questions');
      return (response.data['data'] as List)
          .map(
            (json) => ExamQuestionModel.fromJson(json as Map<String, dynamic>),
          )
          .toList();
    } on DioException catch (e) {
      throw ApiFailure.fromDioException(e);
    }
  }

  Future<ExamQuestionModel> addQuestion(
    String examId,
    Map<String, dynamic> payload,
  ) async {
    try {
      final response = await _dio.post(
        '/exams/$examId/questions',
        data: payload,
      );
      return ExamQuestionModel.fromJson(
        response.data['data'] as Map<String, dynamic>,
      );
    } on DioException catch (e) {
      throw ApiFailure.fromDioException(e);
    }
  }

  Future<ExamQuestionModel> updateQuestion(
    String examQuestionId,
    Map<String, dynamic> payload,
  ) async {
    try {
      final response = await _dio.put(
        '/exam-questions/$examQuestionId',
        data: payload,
      );
      return ExamQuestionModel.fromJson(
        response.data['data'] as Map<String, dynamic>,
      );
    } on DioException catch (e) {
      throw ApiFailure.fromDioException(e);
    }
  }

  Future<void> deleteQuestion(String examQuestionId) async {
    try {
      await _dio.delete('/exam-questions/$examQuestionId');
    } on DioException catch (e) {
      throw ApiFailure.fromDioException(e);
    }
  }
}
