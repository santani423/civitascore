import 'package:dio/dio.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../config/env.dart';
import '../l10n/locale_controller.dart';
import '../session/session_signals.dart';
import '../storage/secure_storage.dart';
import '../storage/storage_keys.dart';

final dioProvider = Provider<Dio>((ref) => buildDio(ref));

Dio buildDio(Ref ref) {
  final dio = Dio(
    BaseOptions(
      baseUrl: Env.apiBaseUrl,
      connectTimeout: const Duration(seconds: 15),
      receiveTimeout: const Duration(seconds: 30),
      sendTimeout: const Duration(seconds: 60),
      headers: {'Accept': 'application/json', 'X-Device-Platform': 'android'},
    ),
  );

  dio.interceptors.add(
    InterceptorsWrapper(
      onRequest: (options, handler) async {
        final storage = ref.read(secureStorageProvider);
        final token = await storage.read(key: StorageKeys.authToken);
        if (token != null && !options.headers.containsKey('Authorization')) {
          options.headers['Authorization'] = 'Bearer $token';
        }
        // Tenant: explicit build-time id first, else the id learned from
        // GET /tenant/profile after login. Without it the backend falls back
        // to the user's default university membership.
        final universityId = Env.universityId.isNotEmpty
            ? Env.universityId
            : await storage.read(key: StorageKeys.selectedUniversityId);
        if (universityId != null && universityId.isNotEmpty) {
          options.headers['X-University-ID'] = universityId;
        }
        // The backend does not localize yet; sent for forward compatibility.
        options.headers['Accept-Language'] = ref
            .read(localeControllerProvider)
            .code;
        handler.next(options);
      },
      onError: (DioException error, handler) {
        final hadToken = error.requestOptions.headers.containsKey(
          'Authorization',
        );
        if (error.response?.statusCode == 401 && hadToken) {
          ref.read(unauthorizedSignalProvider.notifier).state++;
        }
        handler.next(error);
      },
    ),
  );

  return dio;
}
