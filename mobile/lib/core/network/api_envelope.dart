/// Pagination metadata from the backend envelope
/// `meta{current_page, per_page, total, last_page}`.
class PageMeta {
  const PageMeta({
    required this.currentPage,
    required this.perPage,
    required this.total,
    required this.lastPage,
  });

  factory PageMeta.fromJson(Object? json) {
    final m = json is Map ? json : const {};
    return PageMeta(
      currentPage: asInt(m['current_page']) ?? 1,
      perPage: asInt(m['per_page']) ?? 15,
      total: asInt(m['total']) ?? 0,
      lastPage: asInt(m['last_page']) ?? 1,
    );
  }

  final int currentPage;
  final int perPage;
  final int total;
  final int lastPage;

  bool get hasNextPage => currentPage < lastPage;
}

class Paginated<T> {
  const Paginated({required this.items, required this.meta});

  final List<T> items;
  final PageMeta meta;

  bool get hasNextPage => meta.hasNextPage;
}

// ---------------------------------------------------------------------------
// Tolerant JSON readers. The API mixes ints/strings for numbers ("85.00"),
// serialises empty objects as `[]`, and may omit fields on older records.
// ---------------------------------------------------------------------------

Map<String, dynamic> asMap(Object? v) {
  if (v is Map<String, dynamic>) return v;
  if (v is Map) return v.map((k, value) => MapEntry(k.toString(), value));
  return <String, dynamic>{};
}

List<Map<String, dynamic>> asMapList(Object? v) {
  if (v is List) return v.whereType<Map>().map(asMap).toList();
  return const [];
}

List<String> asStringList(Object? v) {
  if (v is List) return v.where((e) => e != null).map((e) => '$e').toList();
  return const [];
}

String? asString(Object? v) {
  if (v == null) return null;
  if (v is String) return v;
  return '$v';
}

int? asInt(Object? v) {
  if (v == null) return null;
  if (v is int) return v;
  if (v is num) return v.toInt();
  if (v is String) return int.tryParse(v) ?? double.tryParse(v)?.toInt();
  return null;
}

double? asDouble(Object? v) {
  if (v == null) return null;
  if (v is num) return v.toDouble();
  if (v is String) return double.tryParse(v);
  return null;
}

bool asBool(Object? v, {bool fallback = false}) {
  if (v is bool) return v;
  if (v is num) return v != 0;
  if (v is String) return v == '1' || v.toLowerCase() == 'true';
  return fallback;
}

DateTime? asDateTime(Object? v) {
  if (v is! String || v.isEmpty) return null;
  return DateTime.tryParse(v)?.toLocal();
}
