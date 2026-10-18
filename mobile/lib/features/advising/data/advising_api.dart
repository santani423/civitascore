import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../core/network/api_client.dart';
import '../../../core/network/api_envelope.dart';
import '../domain/krs_submission.dart';

/// KRS approval endpoints for academic advisors (`krs_advising.*`).
class AdvisingApi {
  AdvisingApi(this._api);
  final ApiClient _api;

  Future<Paginated<KrsSubmission>> list({
    String status = 'submitted',
    String? search,
    int page = 1,
    int perPage = 20,
  }) => _api.getPage(
    '/krs-submissions',
    query: {
      'status': status,
      if (search != null && search.trim().isNotEmpty) 'search': search.trim(),
      'page': page,
      'per_page': perPage,
    },
    item: KrsSubmission.fromJson,
    cache: true,
  );

  Future<KrsSubmission> detail(String id) => _api.get(
    '/krs-submissions/$id',
    decode: (d) => KrsSubmission.fromJson(asMap(d)),
    cache: true,
  );

  Future<String?> approve(String id, {String? note}) => _api.postForMessage(
    '/krs-submissions/$id/approve',
    data: {if (note != null && note.isNotEmpty) 'note': note},
  );

  Future<String?> reject(String id, {required String note}) =>
      _api.postForMessage('/krs-submissions/$id/reject', data: {'note': note});
}

final advisingApiProvider = Provider<AdvisingApi>(
  (ref) => AdvisingApi(ref.watch(apiClientProvider)),
);
