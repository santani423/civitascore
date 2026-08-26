class ListQuery {
  const ListQuery({
    this.page,
    this.perPage,
    this.search,
    this.filter = const {},
    this.sort,
  });

  final int? page;
  final int? perPage;
  final String? search;
  final Map<String, String> filter;
  final String? sort;

  ListQuery copyWith({
    int? page,
    int? perPage,
    String? search,
    Map<String, String>? filter,
    String? sort,
  }) {
    return ListQuery(
      page: page ?? this.page,
      perPage: perPage ?? this.perPage,
      search: search ?? this.search,
      filter: filter ?? this.filter,
      sort: sort ?? this.sort,
    );
  }
}
