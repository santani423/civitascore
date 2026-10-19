import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../core/network/api_client.dart';
import '../domain/home_models.dart';

class LecturerHomeApi {
  LecturerHomeApi(this._api);
  final ApiClient _api;

  /// `GET /teaching/classes` (current term by default).
  Future<List<TeachingClass>> classes() async {
    final page = await _api.getPage(
      '/teaching/classes',
      query: {'per_page': 100},
      item: TeachingClass.fromJson,
      cache: true,
    );
    return page.items;
  }

  /// `GET /krs-submissions?status=submitted`.
  Future<PendingKrsSummary> pendingKrs() async {
    final page = await _api.getPage(
      '/krs-submissions',
      query: {'status': 'submitted', 'per_page': 5},
      item: PendingKrs.fromJson,
      cache: true,
    );
    return PendingKrsSummary(
      total: page.meta.total == 0 ? page.items.length : page.meta.total,
      preview: page.items,
    );
  }
}

final lecturerHomeApiProvider = Provider<LecturerHomeApi>(
  (ref) => LecturerHomeApi(ref.watch(apiClientProvider)),
);
