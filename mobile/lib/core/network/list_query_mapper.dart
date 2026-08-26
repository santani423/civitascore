import '../../shared/entities/list_query.dart';

/// Flattens [ListQuery] into query params the backend `ListQuery` contract
/// understands (`filter[col]=value`) — padanan `utils/listParams.ts` (`toQueryParams`).
Map<String, dynamic> toQueryParams(ListQuery query) {
  final params = <String, dynamic>{};

  if (query.page != null) params['page'] = query.page;
  if (query.perPage != null) params['per_page'] = query.perPage;
  if (query.search != null && query.search!.isNotEmpty) {
    params['search'] = query.search;
  }
  if (query.sort != null) params['sort'] = query.sort;

  for (final entry in query.filter.entries) {
    params['filter[${entry.key}]'] = entry.value;
  }

  return params;
}
