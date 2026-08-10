import 'package:equatable/equatable.dart';
import 'package:flutter_bloc/flutter_bloc.dart';

import '../../domain/constants/data_fetching_status.dart';
import '../../utils/components/app_strings.dart';
import '../../utils/router/http_utils/common_exception.dart';
import '../../utils/router/http_utils/reachability.dart';

part 'paginated_list_state.dart';

/// One page of rows plus its cursor, as the list endpoints return it.
/// Screens adapt their own `SalesPage` / `SaleReturnsPage` into this.
class PageResult {
  const PageResult({
    required this.rows,
    this.currentPage = 1,
    this.lastPage = 1,
    this.total = 0,
    this.totalPaid = 0,
  });

  final List<Map<String, dynamic>> rows;
  final int currentPage;
  final int lastPage;

  /// Total matching records server-side (not the number loaded so far).
  final int total;

  /// Summed `paid` across the whole filtered set, where the endpoint reports it.
  final double totalPaid;
}

typedef PageFetcher = Future<PageResult> Function(int page);

/// Infinite-scroll list state, shared by every "filter + paginate + append"
/// screen (Sales, Sales Returns, the return invoice picker).
///
/// Screens used to hand-roll this — five near-identical copies of `_load` /
/// `_loadMore` / `_reqId` / `_loadingMore`, which is how the same
/// stuck-pagination bug shipped in three of them at once. The rules that were
/// easy to get wrong live here now:
///
/// * A [load] supersedes anything in flight; a stale response is discarded via
///   the request id rather than clobbering newer rows.
/// * [loadMore] always clears its in-flight flag, including when its own
///   response is discarded — otherwise the entry guard blocks every later page
///   and infinite scroll dies for the life of the screen.
/// * A failed [loadMore] keeps the rows already shown; only a failed [load]
///   surfaces an error, because that is the one the user is waiting on.
class PaginatedListCubit extends Cubit<PaginatedListState> {
  PaginatedListCubit({
    required this.fetch,
    this.errorMessage = AppStrings.somethingWentWrong,
  }) : super(const PaginatedListState());

  /// Fetches one page. Screens close over their own filter state here.
  final PageFetcher fetch;

  /// Shown when the first page fails — screens pass their own wording.
  final String errorMessage;

  int _reqId = 0;

  /// (Re)load from page 1. Call after any filter change.
  Future<void> load() async {
    final req = ++_reqId;
    emit(state.copyWith(status: DataFetchStatus.waiting, clearError: true));
    try {
      final res = await fetch(1);
      if (isClosed || req != _reqId) return;
      emit(state.copyWith(
        status: DataFetchStatus.success,
        items: res.rows,
        page: res.currentPage,
        lastPage: res.lastPage,
        total: res.total,
        totalPaid: res.totalPaid,
        loadingMore: false,
      ));
    } on ApiException catch (e) {
      if (isClosed || req != _reqId) return;
      emit(state.copyWith(
        status: DataFetchStatus.failed,
        errorMessage: e.message,
        loadingMore: false,
      ));
    } catch (e) {
      if (isClosed || req != _reqId) return;
      emit(state.copyWith(
        status: DataFetchStatus.failed,
        // "Could not load sales" reads as a server problem. When the request
        // never left the device, say that instead — it is the one failure the
        // person holding it can do something about.
        errorMessage: networkErrorMessage(e, errorMessage),
        loadingMore: false,
      ));
    }
  }

  /// Append the next page. No-op while a load is running or the last page has
  /// been reached.
  Future<void> loadMore() async {
    if (state.loadingMore || state.isLoading || !state.hasMore) return;
    final req = _reqId; // tie to the current filters
    emit(state.copyWith(loadingMore: true));
    try {
      final res = await fetch(state.page + 1);
      if (isClosed || req != _reqId) return;
      emit(state.copyWith(
        items: [...state.items, ...res.rows],
        page: res.currentPage,
        lastPage: res.lastPage,
        total: res.total,
        totalPaid: res.totalPaid,
      ));
    } catch (_) {
      // Keep what we have; the next scroll retries the same page.
    } finally {
      // Must clear even when the response above was discarded, or the guard on
      // entry blocks every later page for the life of the screen.
      if (!isClosed) emit(state.copyWith(loadingMore: false));
    }
  }

  /// Drop a row that no longer exists (e.g. after a delete) without refetching.
  void removeWhere(bool Function(Map<String, dynamic> row) test) {
    final kept = state.items.where((r) => !test(r)).toList();
    if (kept.length != state.items.length) {
      emit(state.copyWith(items: kept, total: (state.total - 1).clamp(0, 1 << 31)));
    }
  }
}
