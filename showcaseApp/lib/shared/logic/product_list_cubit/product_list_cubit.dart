import 'dart:async';

import 'package:equatable/equatable.dart';
import 'package:flutter_bloc/flutter_bloc.dart';

import '../../domain/constants/data_fetching_status.dart';
import '../../domain/constants/global_variables.dart';
import '../../domain/models/index.dart';
import '../../domain/repository/catalog_repository.dart';
import '../../utils/router/http_utils/common_exception.dart';
import '../branch_cubit/branch_cubit.dart';

part 'product_list_state.dart';

/// The results list: one page at a time, appended on scroll.
///
/// Created per list screen (not registered as a singleton) so two lists — the
/// funnel's results and a search — never share a cursor.
///
/// Two screens in two different features build one, which is why it sits in
/// `shared/logic` and not under either of them: search reaching into the catalog
/// feature for its list cubit was a feature importing another feature's
/// internals. Being shared is what the class was already for — the sentence
/// above says so.
class ProductListCubit extends Cubit<ProductListState> {
  ProductListCubit({ProductFilters filters = const ProductFilters()})
      : super(ProductListState(filters: filters)) {
    _branchSub = _branch.onBranchChanged.listen((_) => refresh());
  }

  static const int _perPage = 24;

  /// How long a page of results may take before the screen gives up on it.
  ///
  /// Shorter than the transport's own ceiling on purpose. This is a catalogue
  /// grid on a kiosk somebody is standing at: a spinner that runs for the
  /// twenty-five seconds the HTTP layer allows is indistinguishable from a
  /// broken app, and there is no way out of it because nothing has failed yet.
  /// Failing at ten gives them a message and a Try again.
  static const Duration _deadline = Duration(seconds: 10);

  CatalogRepository get _repo => serviceLocator<CatalogRepository>();
  BranchCubit get _branch => serviceLocator<BranchCubit>();

  StreamSubscription<int>? _branchSub;

  /// Guards against a stale page landing after the filters moved on and
  /// appending results that no longer match the query.
  int _requestId = 0;

  /// The question whose first page is currently in the air, if any.
  ///
  /// Screens call `load()` from more than one place — the provider that builds
  /// them, a filter change, a branch change — so the same query can be asked
  /// for twice in a frame. Keyed on the filters rather than on a bare "busy"
  /// flag: an identical request is a duplicate and is dropped, but a different
  /// one is the customer changing their mind and has to supersede. The store
  /// is part of the key even though it is not part of the filters — switching
  /// branches asks the same filters of a different shelf, and dropping that as
  /// a duplicate would leave the grid showing the previous store's stock.
  String? _inFlight;

  /// Every emit is guarded: the screen can be popped while its request is in
  /// the air, and a cubit that is closed throws rather than ignoring the emit.
  /// That threw out of the catch block too, so an abandoned screen took the
  /// error with it instead of the request quietly finishing.
  void _set(ProductListState next) {
    if (!isClosed) emit(next);
  }

  Future<void> load() async {
    final key = '${state.filters}|${_branch.selectedId}';
    if (_inFlight == key) return;
    _inFlight = key;
    final id = ++_requestId;
    _set(state.copyWith(status: DataFetchStatus.waiting, clearError: true));
    try {
      final page = await _fetch(1).timeout(_deadline, onTimeout: _tooSlow);
      if (id != _requestId) return;
      _set(state.copyWith(
        status: DataFetchStatus.success,
        items: page.items,
        page: page.currentPage,
        lastPage: page.lastPage,
        total: page.total,
        hasMore: page.hasMorePages,
        loadingMore: false,
      ));
    } on ApiException catch (e) {
      if (id != _requestId) return;
      _set(state.copyWith(status: DataFetchStatus.failed, errorMessage: e.message));
    } catch (_) {
      if (id != _requestId) return;
      _set(state.copyWith(status: DataFetchStatus.failed));
    } finally {
      if (id == _requestId) _inFlight = null;
    }
  }

  /// The store changed under a list that is already built. Part of the query
  /// key, so this supersedes whatever is running rather than being taken for a
  /// repeat of it.
  Future<void> refresh() => load();

  Future<void> loadMore() async {
    if (state.loadingMore || !state.hasMore || state.status.isWaiting) return;
    final id = _requestId;
    _set(state.copyWith(loadingMore: true));
    try {
      final page = await _fetch(state.page + 1).timeout(_deadline, onTimeout: _tooSlow);
      if (id != _requestId) return;
      _set(state.copyWith(
        items: [...state.items, ...page.items],
        page: page.currentPage,
        lastPage: page.lastPage,
        hasMore: page.hasMorePages,
        loadingMore: false,
      ));
    } on ApiException catch (e) {
      _set(state.copyWith(loadingMore: false, errorMessage: e.message));
    } catch (_) {
      // The page stays as it was; only the "loading more" spinner has to go,
      // or the grid keeps a footer that never resolves.
      _set(state.copyWith(loadingMore: false));
    } finally {
      // Including the superseded path, which returned early out of the try and
      // left the flag on — after which every later loadMore bailed at the
      // guard and infinite scroll was dead for the life of the screen.
      if (state.loadingMore) _set(state.copyWith(loadingMore: false));
    }
  }

  /// Reported as unreachable rather than as its own kind of error: from the
  /// customer's side a request that never answers and one that cannot be sent
  /// are the same thing, and the screen already knows how to say that.
  static Never _tooSlow() => throw OfflineException();

  Future<Paginated<Product>> _fetch(int page) async {
    await _branch.ready;
    final f = state.filters;
    return _repo.products(
      mainCategoryId: f.mainCategoryId,
      brandId: f.brandId,
      size: f.size,
      color: f.color,
      search: f.search,
      minPrice: f.minPrice,
      maxPrice: f.maxPrice,
      inStockOnly: f.inStockOnly,
      has360: f.spinOnly,
      sortBy: f.sortBy,
      sortDirection: f.sortDirection,
      page: page,
      perPage: _perPage,
    );
  }

  Future<void> apply(ProductFilters filters) async {
    if (filters == state.filters) return;
    _set(state.copyWith(filters: filters));
    await load();
  }

  /// The swatches for the filter sheet, fetched alongside the first page.
  ///
  /// The rows are held in a local before the emit, and that is the whole point:
  /// written as `state.copyWith(colors: await ...)` the receiver `state` is
  /// evaluated *before* the await, so a copy of the state as it was when this
  /// was called gets emitted whenever the colours land. That state is the empty
  /// `waiting` one the grid starts on — so a `/colors` that answered after
  /// `/products` put the loaded page back to a skeleton, and nothing was left
  /// running to emit again. The grid then span for good.
  Future<void> loadColors() async {
    if (state.colors.isNotEmpty) return;
    try {
      final rows = await _repo.colors();
      _set(state.copyWith(colors: rows));
    } on ApiException {
      // The filter sheet just shows no swatches; nothing else depends on this.
    }
  }

  /// The departments the filter panel offers.
  ///
  /// Scoped to the size and the stock rule already in force, so a department is
  /// never offered that would empty the grid — the same reason `/categories`
  /// takes those filters at all.
  Future<void> loadCategories() async {
    try {
      await _branch.ready;
      final rows = await _repo.categories(
        size: state.filters.size,
        branchId: _branch.selectedId,
        inStockOnly: state.filters.inStockOnly,
      );
      _set(state.copyWith(categories: rows));
    } on ApiException {
      // The panel simply shows no departments; the grid is unaffected.
    }
  }

  @override
  Future<void> close() {
    _branchSub?.cancel();
    return super.close();
  }
}
