import 'package:equatable/equatable.dart';

import '../../domain/constants/data_fetching_status.dart';

/// A "fetch a list once, filter it client-side" state — the §5 shape, shared by
/// the pickers that load a reference list and hold nothing else
/// ([StylistCubit], [CatalogCubit]-style lookups).
///
/// Distinct from `PaginatedListState`, which cursors through server pages.
class ListFetchState<T> extends Equatable {
  const ListFetchState({
    this.status = DataFetchStatus.idle,
    this.items = const [],
    this.errorMessage,
  });

  final DataFetchStatus status;
  final List<T> items;
  final String? errorMessage;

  bool get loading => status == DataFetchStatus.waiting;

  /// The fetch has completed at least once — the cache is warm.
  bool get loaded => status == DataFetchStatus.success;

  ListFetchState<T> copyWith({
    DataFetchStatus? status,
    List<T>? items,
    String? errorMessage,
    bool clearError = false,
  }) =>
      ListFetchState<T>(
        status: status ?? this.status,
        items: items ?? this.items,
        errorMessage: clearError ? null : (errorMessage ?? this.errorMessage),
      );

  @override
  List<Object?> get props => [status, items, errorMessage];
}
