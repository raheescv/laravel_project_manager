import 'dart:async';

import 'package:invo/shared/domain/constants/global_variables.dart';
import 'package:invo/shared/domain/models/index.dart';
import 'package:invo/shared/domain/repository/lookup_repository.dart';
import 'package:invo/shared/logic/base/holder_cubit.dart';
import 'package:invo/shared/utils/router/http_utils/common_exception.dart';

/// Loads the sellable catalog (services + products) for the New Sale screen.
/// Server-paginated with debounced search and infinite-scroll append.
class CatalogCubit extends HolderCubit {
  CatalogCubit();

  LookupRepository get _repo => serviceLocator<LookupRepository>();

  static const int _pageSize = 20;

  bool loading = false;
  bool loadingMore = false;
  String? error;
  List<Product> products = [];
  List<Category> categories = [];
  int? selectedCategoryId;
  String search = '';

  int _page = 1;
  int _lastPage = 1;
  bool _loaded = false;
  int _reqId = 0;
  Timer? _searchDebounce;

  bool get hasMore => _page < _lastPage;
  bool get isEmpty => products.isEmpty;

  Future<void> loadIfNeeded() async {
    if (_loaded) return;
    await load();
  }

  String? get _searchParam => search.trim().isEmpty ? null : search.trim();

  Future<void> load() async {
    final req = ++_reqId;
    loading = true;
    error = null;
    refresh();
    try {
      final results = await Future.wait([
        _repo.products(
          search: _searchParam,
          mainCategoryId: selectedCategoryId,
          page: 1,
          perPage: _pageSize,
        ),
        if (!_loaded) _repo.categories(),
      ]);
      if (req != _reqId) return;
      final paged = results[0] as Paginated<Product>;
      products = paged.items;
      _page = paged.currentPage;
      _lastPage = paged.lastPage;
      if (!_loaded) categories = results[1] as List<Category>;
      _loaded = true;
    } on ApiException catch (e) {
      if (req == _reqId) error = e.message;
    } catch (_) {
      if (req == _reqId) error = 'Could not load the catalog.';
    }
    if (req == _reqId) {
      loading = false;
      refresh();
    }
  }

  Future<void> loadMore() async {
    if (loadingMore || loading || !hasMore) return;
    final req = _reqId;
    loadingMore = true;
    refresh();
    try {
      final paged = await _repo.products(
        search: _searchParam,
        mainCategoryId: selectedCategoryId,
        page: _page + 1,
        perPage: _pageSize,
      );
      if (req != _reqId) return;
      products = [...products, ...paged.items];
      _page = paged.currentPage;
      _lastPage = paged.lastPage;
    } catch (_) {
      // Keep what we have; the next scroll can retry the same page.
    }
    if (req == _reqId) {
      loadingMore = false;
      refresh();
    }
  }

  void setSearch(String v) {
    search = v;
    _searchDebounce?.cancel();
    _searchDebounce = Timer(const Duration(milliseconds: 350), load);
  }

  void selectCategory(int? id) {
    if (id == selectedCategoryId) return;
    selectedCategoryId = id;
    load();
  }

  Future<Product?> findByBarcode(String code) => _repo.productByBarcode(code);

  @override
  Future<void> close() {
    _searchDebounce?.cancel();
    return super.close();
  }
}
