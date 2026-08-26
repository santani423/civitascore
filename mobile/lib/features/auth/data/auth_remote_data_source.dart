import 'package:dio/dio.dart';

import '../../../core/network/api_failure.dart';
import 'models/auth_user_model.dart';
import 'models/user_device_model.dart';
import 'models/user_session_model.dart';

class AuthRemoteDataSource {
  AuthRemoteDataSource(this._dio);

  final Dio _dio;

  Future<String> login({
    required String email,
    required String password,
  }) async {
    try {
      final response = await _dio.post(
        '/login',
        data: {'email': email, 'password': password},
      );
      final data = response.data['data'] as Map<String, dynamic>;
      return data['token'] as String;
    } on DioException catch (e) {
      throw ApiFailure.fromDioException(e);
    }
  }

  Future<AuthUserModel> me() async {
    try {
      final response = await _dio.get('/me');
      return AuthUserModel.fromMeJson(
        response.data['data'] as Map<String, dynamic>,
      );
    } on DioException catch (e) {
      throw ApiFailure.fromDioException(e);
    }
  }

  Future<void> logout() async {
    try {
      await _dio.post('/logout');
    } on DioException catch (e) {
      throw ApiFailure.fromDioException(e);
    }
  }

  Future<void> logoutAll() async {
    try {
      await _dio.post('/logout-all');
    } on DioException catch (e) {
      throw ApiFailure.fromDioException(e);
    }
  }

  Future<void> forgotPassword({required String email}) async {
    try {
      await _dio.post('/forgot-password', data: {'email': email});
    } on DioException catch (e) {
      throw ApiFailure.fromDioException(e);
    }
  }

  Future<void> resetPassword({
    required String token,
    required String email,
    required String password,
    required String passwordConfirmation,
  }) async {
    try {
      await _dio.post(
        '/reset-password',
        data: {
          'token': token,
          'email': email,
          'password': password,
          'password_confirmation': passwordConfirmation,
        },
      );
    } on DioException catch (e) {
      throw ApiFailure.fromDioException(e);
    }
  }

  Future<List<UserSessionModel>> sessions() async {
    try {
      final response = await _dio.get('/sessions');
      return (response.data['data'] as List)
          .map(
            (json) => UserSessionModel.fromJson(json as Map<String, dynamic>),
          )
          .toList();
    } on DioException catch (e) {
      throw ApiFailure.fromDioException(e);
    }
  }

  Future<void> revokeSession(String id) async {
    try {
      await _dio.delete('/sessions/$id');
    } on DioException catch (e) {
      throw ApiFailure.fromDioException(e);
    }
  }

  Future<List<UserDeviceModel>> devices() async {
    try {
      final response = await _dio.get('/devices');
      return (response.data['data'] as List)
          .map((json) => UserDeviceModel.fromJson(json as Map<String, dynamic>))
          .toList();
    } on DioException catch (e) {
      throw ApiFailure.fromDioException(e);
    }
  }
}
