import 'package:equatable/equatable.dart';

import '../helpers/formatters.dart';

class Paginated<T> extends Equatable {
  const Paginated({required this.items, required this.currentPage, required this.lastPage, required this.total});
  final List<T> items;
  final int currentPage;
  final int lastPage;
  final int total;

  bool get hasMore => currentPage < lastPage;

  factory Paginated.from(dynamic data, T Function(Map<String, dynamic>) fromJson) {
    // The list endpoints return { data: [...], pagination: {...} }.
    final list = (data is Map ? data['data'] : data) as List? ?? const [];
    final pag = (data is Map ? data['pagination'] : null) as Map? ?? const {};
    return Paginated(
      items: list.map((e) => fromJson(Map<String, dynamic>.from(e))).toList(),
      currentPage: asNum(pag['current_page']).toInt(),
      lastPage: asNum(pag['last_page'] ?? 1).toInt(),
      total: asNum(pag['total'] ?? list.length).toInt(),
    );
  }


  @override
  List<Object?> get props => [
        items,
        currentPage,
        lastPage,
        total,
      ];
}
