import 'dart:convert';
import 'dart:typed_data';

import 'package:civitasone/core/network/api_client.dart';
import 'package:dio/dio.dart';

/// Recorded request (for asserting payloads in repository tests).
class FakeRequest {
  FakeRequest(this.method, this.path, this.query, this.body, this.headers);
  final String method;
  final String path;
  final Map<String, dynamic> query;
  final Object? body;
  final Map<String, dynamic> headers;
}

typedef FakeHandler = FakeResponse Function(FakeRequest request);

class FakeResponse {
  const FakeResponse(this.status, this.body);
  final int status;
  final Object? body;

  /// Success envelope `{success, message, data, meta?}` like the backend.
  factory FakeResponse.ok(
    Object? data, {
    Map<String, dynamic>? meta,
    String? message,
  }) => FakeResponse(200, {
    'success': true,
    'message': message ?? 'OK',
    'data': data,
    'meta': meta ?? <dynamic>[],
  });

  factory FakeResponse.error(
    int status,
    String message, {
    Map<String, dynamic>? errors,
  }) => FakeResponse(status, {
    'success': false,
    'message': message,
    'errors': errors ?? <dynamic>[],
  });
}

/// In-memory HTTP adapter for tests ONLY (never used by the app). Routes are
/// matched by method and path; `{param}` segments match any value.
class FakeApi implements HttpClientAdapter {
  final _routes = <(String, RegExp), FakeHandler>{};
  final requests = <FakeRequest>[];

  void on(String method, String pathPattern, FakeHandler handler) {
    final regex = RegExp(
      '^${pathPattern.replaceAllMapped(RegExp(r'\{[^}]+\}'), (_) => '[^/]+')}\$',
    );
    _routes[(method.toUpperCase(), regex)] = handler;
  }

  void get(String path, Object? data, {Map<String, dynamic>? meta}) =>
      on('GET', path, (_) => FakeResponse.ok(data, meta: meta));

  ApiClient client() {
    final dio = Dio(BaseOptions(baseUrl: 'https://campus.test/api/v1'));
    dio.httpClientAdapter = this;
    return ApiClient(dio);
  }

  @override
  Future<ResponseBody> fetch(
    RequestOptions options,
    Stream<Uint8List>? requestStream,
    Future<void>? cancelFuture,
  ) async {
    final path = options.uri.path.replaceFirst('/api/v1', '');
    Object? body = options.data;
    if (body is FormData) {
      body = {
        for (final f in body.fields) f.key: f.value,
        for (final f in body.files) f.key: f.value.filename,
      };
    }
    final request = FakeRequest(
      options.method,
      path,
      options.uri.queryParameters,
      body,
      options.headers,
    );
    requests.add(request);
    for (final entry in _routes.entries) {
      if (entry.key.$1 == options.method && entry.key.$2.hasMatch(path)) {
        final response = entry.value(request);
        return ResponseBody.fromString(
          jsonEncode(response.body),
          response.status,
          headers: {
            Headers.contentTypeHeader: [Headers.jsonContentType],
          },
        );
      }
    }
    return ResponseBody.fromString(
      jsonEncode({
        'success': false,
        'message': 'No fake route for ${options.method} $path',
      }),
      404,
      headers: {
        Headers.contentTypeHeader: [Headers.jsonContentType],
      },
    );
  }

  @override
  void close({bool force = false}) {}
}
