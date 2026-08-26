import 'package:dio/dio.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../features/auth/presentation/controllers/auth_controller.dart';
import '../config/env.dart';
import '../storage/secure_storage.dart';
import '../storage/storage_keys.dart';

final dioProvider = Provider<Dio>((ref) => buildDio(ref));

Dio buildDio(Ref ref) {
  final dio = Dio(
    BaseOptions(
      baseUrl: Env.apiBaseUrl,
      connectTimeout: const Duration(seconds: 15),
      headers: {'Accept': 'application/json'},
    ),
  );

  dio.interceptors.add(
    InterceptorsWrapper(
      onRequest: (options, handler) async {
        final token = await ref
            .read(secureStorageProvider)
            .read(key: StorageKeys.authToken);
        if (token != null) {
          options.headers['Authorization'] = 'Bearer $token';
        }
        handler.next(options);
      },
      onError: (DioException error, handler) async {
        if (error.response?.statusCode == 401) {
          await ref.read(authControllerProvider.notifier).clearSession();
        }
        handler.next(error);
      },
    ),
  );

  return dio;
}
