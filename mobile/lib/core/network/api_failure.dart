import 'package:dio/dio.dart';

/// Category of a failed request. The UI localizes transport-level kinds
/// (network/timeout/server/unknown); for business errors (4xx) it shows the
/// backend `message`, which the API only provides in Indonesian.
enum ApiFailureKind {
  network,
  timeout,
  unauthorized,
  forbidden,
  notFound,
  validation,
  conflict,
  rateLimited,
  server,
  unknown,
}

class ApiFailure implements Exception {
  const ApiFailure({
    required this.kind,
    this.status,
    this.message,
    this.code,
    this.fieldErrors = const {},
    this.details,
  });

  final ApiFailureKind kind;
  final int? status;

  /// Message from the backend envelope (`message`), if any.
  final String? message;

  /// Machine-readable business code (`errors.code`), e.g. `SCHEDULE_CONFLICT`,
  /// `ACADEMIC_BLOCKED`, `CHANNEL_NOT_CONNECTED`.
  final String? code;

  /// Laravel validation errors keyed by field (422).
  final Map<String, List<String>> fieldErrors;

  /// Raw `errors` payload for features that need extra data (e.g. blocks).
  final Map<String, dynamic>? details;

  bool get isOffline =>
      kind == ApiFailureKind.network || kind == ApiFailureKind.timeout;

  /// First validation message for [field], if present.
  String? fieldError(String field) {
    final list = fieldErrors[field];
    return (list == null || list.isEmpty) ? null : list.first;
  }

  /// Best human-readable detail: first field error, else backend message.
  String? get serverDetail {
    for (final list in fieldErrors.values) {
      if (list.isNotEmpty) return list.first;
    }
    return message;
  }

  factory ApiFailure.fromDioException(DioException e) {
    final response = e.response;
    if (response == null) {
      final kind = switch (e.type) {
        DioExceptionType.connectionTimeout ||
        DioExceptionType.sendTimeout ||
        DioExceptionType.receiveTimeout => ApiFailureKind.timeout,
        DioExceptionType.connectionError ||
        DioExceptionType.unknown => ApiFailureKind.network,
        _ => ApiFailureKind.unknown,
      };
      return ApiFailure(kind: kind);
    }
    return ApiFailure.fromResponse(response.statusCode, response.data);
  }

  factory ApiFailure.fromResponse(int? status, Object? body) {
    String? message;
    String? code;
    Map<String, dynamic>? details;
    final fieldErrors = <String, List<String>>{};

    if (body is Map) {
      final m = body['message'];
      if (m is String && m.isNotEmpty) message = m;
      final errors = body['errors'];
      // PHP serialises an empty array as `[]`, so `errors` may be a List.
      if (errors is Map) {
        details = errors.map((k, v) => MapEntry(k.toString(), v));
        errors.forEach((key, value) {
          if (key == 'code' && value is String) {
            code = value;
          } else if (value is List) {
            fieldErrors[key.toString()] = value.map((x) => '$x').toList();
          } else if (value is String) {
            fieldErrors[key.toString()] = [value];
          }
        });
      }
      final topCode = body['code'];
      if (code == null && topCode is String) code = topCode;
    }

    final kind = switch (status) {
      401 => ApiFailureKind.unauthorized,
      403 => ApiFailureKind.forbidden,
      404 => ApiFailureKind.notFound,
      409 => ApiFailureKind.conflict,
      422 => ApiFailureKind.validation,
      429 => ApiFailureKind.rateLimited,
      final s? when s >= 500 => ApiFailureKind.server,
      _ => ApiFailureKind.unknown,
    };

    return ApiFailure(
      kind: kind,
      status: status,
      message: message,
      code: code,
      fieldErrors: fieldErrors,
      details: details,
    );
  }

  @override
  String toString() =>
      'ApiFailure(kind: $kind, status: $status, code: $code, message: $message)';
}
