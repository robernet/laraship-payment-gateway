/// Wraps a list endpoint's `{ "data": [...], "meta": {...} }` response.
///
/// Plain (no codegen) so it stays generic-friendly. Pass the item parser:
///   final page = await api.getList('/products', Product.fromJson);
class Paginated<T> {
  const Paginated({
    required this.data,
    required this.currentPage,
    required this.perPage,
    required this.total,
    required this.lastPage,
  });

  final List<T> data;
  final int currentPage;
  final int perPage;
  final int total;
  final int lastPage;

  bool get hasMore => currentPage < lastPage;

  factory Paginated.fromJson(
    Map<String, dynamic> json,
    T Function(Map<String, dynamic>) fromItem,
  ) {
    final items = (json['data'] as List? ?? const [])
        .whereType<Map<String, dynamic>>()
        .map(fromItem)
        .toList();
    final meta = (json['meta'] as Map<String, dynamic>?) ?? const {};
    return Paginated<T>(
      data: items,
      currentPage: (meta['current_page'] as num?)?.toInt() ?? 1,
      perPage: (meta['per_page'] as num?)?.toInt() ?? items.length,
      total: (meta['total'] as num?)?.toInt() ?? items.length,
      lastPage: (meta['last_page'] as num?)?.toInt() ?? 1,
    );
  }
}
