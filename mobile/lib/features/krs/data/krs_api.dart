import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../core/network/api_client.dart';
import '../domain/krs_models.dart';

final krsApiProvider = Provider<KrsApi>((ref) => KrsApi(ref.watch(apiClientProvider)));

class KrsApi {
  KrsApi(this._api);
  final ApiClient _api;

  static KrsOverview _overview(dynamic d) =>
      KrsOverview.fromJson(d is Map<String, dynamic> ? d : <String, dynamic>{});

  Future<KrsOverview> overview() =>
      _api.get('/student/krs', decode: _overview, cache: true);

  Future<KrsOfferings> offerings() => _api.get(
    '/student/krs/offerings',
    cache: true,
    decode: (d) => KrsOfferings.fromJson(d is Map<String, dynamic> ? d : {}),
  );

  Future<List<KrsHistoryEntry>> history() => _api.getList(
    '/student/krs/history',
    item: KrsHistoryEntry.fromJson,
    cache: true,
  );

  Future<KrsOverview> addItem(String classSectionId) => _api.post(
    '/student/krs/items',
    data: {'class_section_id': classSectionId},
    decode: _overview,
  );

  Future<KrsOverview> removeItem(String krsItemId) =>
      _api.delete('/student/krs/items/$krsItemId', decode: _overview);

  Future<KrsOverview> submit() =>
      _api.post('/student/krs/submit', decode: _overview);

  Future<KrsOverview> cancel() =>
      _api.post('/student/krs/cancel', decode: _overview);
}
