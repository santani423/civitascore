import '../../core/network/api_envelope.dart';

class PaginatedResult<T> {
  const PaginatedResult({required this.data, required this.meta});

  final List<T> data;
  final ApiPaginationMeta meta;
}
