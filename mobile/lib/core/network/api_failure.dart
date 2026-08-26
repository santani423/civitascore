import 'package:dio/dio.dart';

class ApiFailure implements Exception {
  const ApiFailure({required this.status, required this.message, this.errors});

  final int? status;
  final String message;
  final Map<String, List<String>>? errors;

  factory ApiFailure.fromDioException(DioException e) {
    final data = e.response?.data;
    final message = data is Map ? data['message'] as String? : null;
    final rawErrors = data is Map ? data['errors'] as Map? : null;

    return ApiFailure(
      status: e.response?.statusCode,
      message: message ?? _fallbackMessage(e),
      errors: rawErrors?.map(
        (key, value) =>
            MapEntry(key as String, List<String>.from(value as List)),
      ),
    );
  }

  static String _fallbackMessage(DioException e) {
    if (e.type == DioExceptionType.connectionTimeout ||
        e.type == DioExceptionType.receiveTimeout ||
        e.type == DioExceptionType.sendTimeout) {
      return 'Waktu koneksi habis. Periksa jaringan Anda dan coba lagi.';
    }
    if (e.type == DioExceptionType.connectionError) {
      return 'Tidak dapat terhubung ke server. Periksa koneksi internet Anda.';
    }
    return 'Terjadi kesalahan. Silakan coba lagi.';
  }

  @override
  String toString() => 'ApiFailure(status: $status, message: $message)';
}
