class ApiPaginationMeta {
  const ApiPaginationMeta({
    required this.currentPage,
    required this.perPage,
    required this.total,
    required this.lastPage,
  });

  factory ApiPaginationMeta.fromJson(Map<String, dynamic> json) =>
      ApiPaginationMeta(
        currentPage: json['current_page'] as int? ?? 1,
        perPage: json['per_page'] as int? ?? 15,
        total: json['total'] as int? ?? 0,
        lastPage: json['last_page'] as int? ?? 1,
      );

  final int currentPage;
  final int perPage;
  final int total;
  final int lastPage;

  bool get hasNextPage => currentPage < lastPage;
}

class ApiSuccessResponse<T> {
  const ApiSuccessResponse({required this.data, this.message, this.meta});

  final T data;
  final String? message;
  final ApiPaginationMeta? meta;

  static ApiSuccessResponse<T> fromJson<T>(
    Map<String, dynamic> json,
    T Function(dynamic json) fromData,
  ) {
    return ApiSuccessResponse<T>(
      data: fromData(json['data']),
      message: json['message'] as String?,
      meta: json['meta'] != null
          ? ApiPaginationMeta.fromJson(json['meta'] as Map<String, dynamic>)
          : null,
    );
  }
}
