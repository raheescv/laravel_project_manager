import 'dart:async';

import 'package:equatable/equatable.dart';
import 'package:flutter_bloc/flutter_bloc.dart';

import '../../../../shared/domain/constants/data_fetching_status.dart';
import '../../../../shared/domain/constants/global_variables.dart';
import '../../../../shared/domain/models/index.dart';
import '../../../../shared/domain/repository/catalog_repository.dart';
import '../../../../shared/logic/branch_cubit/branch_cubit.dart';
import '../../../../shared/utils/router/http_utils/common_exception.dart';

part 'product_list_state.dart';

/// The results list: one page at a time, appended on scroll.
///
/// Created per list screen (not registered as a singleton) so two lists — the
/// funnel's results and a search — never share a cursor.
class ProductListCubit extends Cubit<ProductListState> {
  ProductListCubit({ProductFilters filters = const ProductFilters()})
      : super(ProductListState(filters: filters)) {
    _branchSub = _branch.onBranchChanged.listen((_) => refresh());
  }

  static const int _perPage = 24;

  CatalogRepository get _repo => serviceLocator<CatalogRepository>();
  BranchCubit get _branch => serviceLocator<BranchCubit>();

  StreamSubscription<int>? _branchSub;

  /// Guards against a stale page landing after the filters moved on and
  /// appending results that no longer match the query.
  int _requestId = 0;

  Future<void> load() async {
    final id = ++_requestId;
    emit(state.copyWith(status: DataFetchStatus.waiting, clearError: true));
    try {
      final page = await _fetch(1);
      if (id != _requestId) return;
      emit(state.copyWith(
        status: DataFetchStatus.success,
        items: _refine(page.items),
        page: page.currentPage,
        lastPage: page.lastPage,
        total: page.total,
        hasMore: page.hasMorePages,
        loadingMore: false,
      ));
    } on ApiException catch (e) {
      if (id != _requestId) return;
      emit(state.copyWith(status: DataFetchStatus.failed, errorMessage: e.message));
    }
  }

  Future<void> refresh() => load();

  Future<void> loadMore() async {
    if (state.loadingMore || !state.hasMore || state.status.isWaiting) return;
    final id = _requestId;
    emit(state.copyWith(loadingMore: true));
    try {
      final page = await _fetch(state.page + 1);
      if (id != _requestId) return;
      emit(state.copyWith(
        items: [...state.items, ..._refine(page.items)],
        page: page.currentPage,
        lastPage: page.lastPage,
        hasMore: page.hasMorePages,
        loadingMore: false,
      ));
    } on ApiException catch (e) {
      if (id != _requestId) return;
      emit(state.copyWith(loadingMore: false, errorMessage: e.message));
    }
  }

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
      sortBy: f.sortBy,
      sortDirection: f.sortDirection,
      page: page,
      perPage: _perPage,
    );
  }

  /// The one refinement the API cannot do. Applied per page so paging still
  /// works — it just returns fewer rows than the server counted.
  List<Product> _refine(List<Product> rows) =>
      state.filters.spinOnly ? rows.where((p) => p.hasSpin).toList(growable: false) : rows;

  Future<void> apply(ProductFilters filters) async {
    if (filters == state.filters) return;
    emit(state.copyWith(filters: filters));
    await load();
  }

  Future<void> loadColors() async {
    if (state.colors.isNotEmpty) return;
    try {
      emit(state.copyWith(colors: await _repo.colors()));
    } on ApiException {
      // The filter sheet just shows no swatches; nothing else depends on this.
    }
  }

  @override
  Future<void> close() {
    _branchSub?.cancel();
    return super.close();
  }
}
