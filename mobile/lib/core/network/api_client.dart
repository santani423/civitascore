import 'dart:convert';
import 'dart:io';

import 'package:dio/dio.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:hive_ce/hive_ce.dart';
import 'package:path_provider/path_provider.dart';

import 'api_envelope.dart';
import 'api_failure.dart';
import 'dio_client.dart';

/// Overridden in bootstrap with the opened encrypted cache box. Tests may
/// leave it `null` to disable caching.
final responseCacheProvider = Provider<Box<String>?>((ref) => null);

final apiClientProvider = Provider<ApiClient>((ref) {
  return ApiClient(ref.watch(dioProvider), ref.watch(responseCacheProvider));
});

/// Thin wrapper over [Dio] that understands the Civitas One envelope
/// `{success, message, data, meta}`, maps errors to [ApiFailure], and
/// optionally serves the last good response from an encrypted local cache
/// when the device is offline (read-only data only).
class ApiClient {
  ApiClient(this._dio, [this._cache]);

  final Dio _dio;
  final Box<String>? _cache;

  Dio get dio => _dio;

  /// GET returning the decoded `data` field.
  Future<T> get<T>(
    String path, {
    Map<String, dynamic>? query,
    required T Function(dynamic data) decode,
    bool cache = false,
  }) async {
    final body = await _getBody(path, query: query, cache: cache);
    return decode(body['data']);
  }

  /// GET of a paginated collection (`data: [...]`, `meta: {...}`).
  Future<Paginated<T>> getPage<T>(
    String path, {
    Map<String, dynamic>? query,
    required T Function(Map<String, dynamic> json) item,
    bool cache = false,
  }) async {
    final body = await _getBody(path, query: query, cache: cache);
    final data = body['data'];
    final list = data is Map ? data['data'] : data;
    final meta = body['meta'] is Map
        ? body['meta']
        : (data is Map ? data : null);
    return Paginated(
      items: asMapList(list).map(item).toList(),
      meta: PageMeta.fromJson(meta),
    );
  }

  /// GET of a non-paginated list (`data: [...]`).
  Future<List<T>> getList<T>(
    String path, {
    Map<String, dynamic>? query,
    required T Function(Map<String, dynamic> json) item,
    bool cache = false,
  }) async {
    final body = await _getBody(path, query: query, cache: cache);
    final data = body['data'];
    final list = data is Map ? data['data'] : data;
    return asMapList(list).map(item).toList();
  }

  Future<T> post<T>(
    String path, {
    Object? data,
    Map<String, dynamic>? query,
    Map<String, String>? headers,
    T Function(dynamic data)? decode,
  }) => _send(
    'POST',
    path,
    data: data,
    query: query,
    headers: headers,
    decode: decode,
  );

  Future<T> put<T>(
    String path, {
    Object? data,
    Map<String, String>? headers,
    T Function(dynamic data)? decode,
  }) => _send('PUT', path, data: data, headers: headers, decode: decode);

  Future<T> patch<T>(
    String path, {
    Object? data,
    Map<String, String>? headers,
    T Function(dynamic data)? decode,
  }) => _send('PATCH', path, data: data, headers: headers, decode: decode);

  Future<T> delete<T>(
    String path, {
    Object? data,
    T Function(dynamic data)? decode,
  }) => _send('DELETE', path, data: data, decode: decode);

  /// Returns the backend `message` of a mutation (for success snackbars).
  Future<String?> postForMessage(
    String path, {
    Object? data,
    Map<String, String>? headers,
  }) async {
    final body = await _request('POST', path, data: data, headers: headers);
    return asString(body['message']);
  }

  /// Uploads a file to `POST /file-uploads` (multipart field `file`) and
  /// returns the created file id, used by domain endpoints (payment proof,
  /// assignment attachment, leave attachment…).
  Future<String> uploadFile(
    String filePath, {
    String? filename,
    Map<String, dynamic>? fields,
    void Function(int sent, int total)? onProgress,
  }) async {
    final form = FormData.fromMap({
      ...?fields,
      'file': await MultipartFile.fromFile(filePath, filename: filename),
    });
    try {
      final response = await _dio.post(
        '/file-uploads',
        data: form,
        onSendProgress: onProgress,
      );
      final data = asMap(asMap(response.data)['data']);
      final id = asString(data['id']);
      if (id == null) {
        throw const ApiFailure(kind: ApiFailureKind.unknown);
      }
      return id;
    } on DioException catch (e) {
      throw ApiFailure.fromDioException(e);
    }
  }

  /// Downloads an authenticated binary (PDF receipts, KHS, transcript…) into
  /// the app's temporary directory and returns the local file. PDFs require
  /// the Bearer header, so they cannot be opened by URL in a browser.
  Future<File> download(
    String path, {
    required String filename,
    Map<String, dynamic>? query,
    void Function(int received, int total)? onProgress,
  }) async {
    final dir = await getTemporaryDirectory();
    final safe = filename.replaceAll(RegExp(r'[^A-Za-z0-9._-]'), '_');
    final file = File('${dir.path}/$safe');
    try {
      await _dio.download(
        path,
        file.path,
        queryParameters: query,
        onReceiveProgress: onProgress,
        options: Options(
          headers: {'Accept': 'application/pdf, application/json'},
        ),
      );
      return file;
    } on DioException catch (e) {
      // Error bodies of downloads arrive as bytes; decode the JSON envelope.
      final data = e.response?.data;
      if (data is List<int>) {
        try {
          final decoded = jsonDecode(utf8.decode(data));
          throw ApiFailure.fromResponse(e.response?.statusCode, decoded);
        } on FormatException {
          // fall through
        }
      }
      throw ApiFailure.fromDioException(e);
    }
  }

  /// Removes every cached response (called on logout / account switch).
  Future<void> clearCache() async => _cache?.clear();

  // -------------------------------------------------------------------------

  Future<Map<String, dynamic>> _getBody(
    String path, {
    Map<String, dynamic>? query,
    required bool cache,
  }) async {
    final key = _cacheKey(path, query);
    try {
      final body = await _request('GET', path, query: query);
      if (cache && _cache != null) {
        await _cache.put(key, jsonEncode(body));
      }
      return body;
    } on ApiFailure catch (failure) {
      if (cache && failure.isOffline && _cache != null) {
        final cached = _cache.get(key);
        if (cached != null) return asMap(jsonDecode(cached));
      }
      rethrow;
    }
  }

  Future<T> _send<T>(
    String method,
    String path, {
    Object? data,
    Map<String, dynamic>? query,
    Map<String, String>? headers,
    T Function(dynamic data)? decode,
  }) async {
    final body = await _request(
      method,
      path,
      data: data,
      query: query,
      headers: headers,
    );
    if (decode != null) return decode(body['data']);
    return body['data'] as T;
  }

  Future<Map<String, dynamic>> _request(
    String method,
    String path, {
    Object? data,
    Map<String, dynamic>? query,
    Map<String, String>? headers,
  }) async {
    try {
      final response = await _dio.request<Object?>(
        path,
        data: data,
        queryParameters: query,
        options: Options(method: method, headers: headers),
      );
      final body = response.data;
      if (body is Map) return asMap(body);
      return <String, dynamic>{'data': body};
    } on DioException catch (e) {
      throw ApiFailure.fromDioException(e);
    }
  }

  static String _cacheKey(String path, Map<String, dynamic>? query) {
    if (query == null || query.isEmpty) return path;
    final keys = query.keys.toList()..sort();
    return '$path?${keys.map((k) => '$k=${query[k]}').join('&')}';
  }
}
