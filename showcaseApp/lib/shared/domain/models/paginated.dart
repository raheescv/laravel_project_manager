import '../helpers/formatters.dart';

/// One page of a `/products` response, plus the pagination block the list needs
/// to decide whether to keep loading.
class Paginated<T> {
  const Paginated({
    required this.items,
    required this.currentPage,
    required this.lastPage,
    required this.total,
    required this.hasMorePages,
  });

  final List<T> items;
  final int currentPage;
  final int lastPage;
  final int total;
  final bool hasMorePages;

  static Paginated<T> from<T>(dynamic data, T Function(Map<String, dynamic>) parse) {
    final rows = asMapList(data is Map ? data['data'] : data);
    final pag = (data is Map ? data['pagination'] : null);
    final page = pag is Map ? pag : const {};
    final current = asInt(page['current_page'] ?? 1);
    final last = asInt(page['last_page'] ?? 1);
    return Paginated<T>(
      items: rows.map(parse).toList(growable: false),
      currentPage: current == 0 ? 1 : current,
      lastPage: last == 0 ? 1 : last,
      total: asInt(page['total'] ?? rows.length),
      // Derive rather than trust: `has_more_pages` is absent on the non-paginated
      // shapes this endpoint can return.
      hasMorePages: page['has_more_pages'] == null
          ? current < last
          : asBool(page['has_more_pages']),
    );
  }

  static Paginated<T> empty<T>() => Paginated<T>(
        items: const [],
        currentPage: 1,
        lastPage: 1,
        total: 0,
        hasMorePages: false,
      );
}
