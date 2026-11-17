part of 'paginated_list_cubit.dart';

/// Immutable state for [PaginatedListCubit] — the §5 shape: `Equatable`,
/// `copyWith`, and a [DataFetchStatus] rather than loose booleans.
class PaginatedListState extends Equatable {
  const PaginatedListState({
    this.status = DataFetchStatus.idle,
    this.items = const [],
    this.page = 1,
    this.lastPage = 1,
    this.total = 0,
    this.totalPaid = 0,
    this.summary = const {},
    this.loadingMore = false,
    this.errorMessage,
  });

  final DataFetchStatus status;
  final List<Map<String, dynamic>> items;
  final int page;
  final int lastPage;

  /// Matching records server-side, which is what the result line reports — not
  /// `items.length`, which is only what has been scrolled into view.
  final int total;
  final double totalPaid;

  /// Extra whole-set figures — see [PageResult.summary].
  final Map<String, num> summary;

  /// A further page is being appended. Distinct from [isLoading], which covers
  /// the first page and blanks the list.
  final bool loadingMore;
  final String? errorMessage;

  bool get isLoading => status == DataFetchStatus.waiting;
  bool get hasFailed => status == DataFetchStatus.failed;
  bool get hasMore => page < lastPage;

  /// Loaded successfully but with nothing to show — the empty state, as opposed
  /// to "not loaded yet".
  bool get isEmptyResult =>
      items.isEmpty && (status == DataFetchStatus.success || status == DataFetchStatus.failed);

  PaginatedListState copyWith({
    DataFetchStatus? status,
    List<Map<String, dynamic>>? items,
    int? page,
    int? lastPage,
    int? total,
    double? totalPaid,
    Map<String, num>? summary,
    bool? loadingMore,
    String? errorMessage,
    bool clearError = false,
  }) =>
      PaginatedListState(
        status: status ?? this.status,
        items: items ?? this.items,
        page: page ?? this.page,
        lastPage: lastPage ?? this.lastPage,
        total: total ?? this.total,
        totalPaid: totalPaid ?? this.totalPaid,
        summary: summary ?? this.summary,
        loadingMore: loadingMore ?? this.loadingMore,
        errorMessage: clearError ? null : (errorMessage ?? this.errorMessage),
      );

  @override
  List<Object?> get props =>
      [status, items, page, lastPage, total, totalPaid, summary, loadingMore, errorMessage];
}
