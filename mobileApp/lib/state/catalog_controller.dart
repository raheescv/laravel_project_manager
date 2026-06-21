import 'dart:async';

import 'package:flutter/foundation.dart' hide Category;

import '../core/api_client.dart';
import '../core/api_service.dart';
import '../models/models.dart';

/// Loads the sellable catalog (services + products) for the New Sale screen.
///
/// Results are **server-paginated**: search and the category filter are sent to
/// the API, the first page replaces the list, and [loadMore] appends the next
/// page for an infinite-scroll feel. Search is debounced so typing doesn't fire
/// a request per keystroke.
class CatalogController extends ChangeNotifier {
  CatalogController(this.service);
  final ApiService service;

  static const int _pageSize = 20;

  bool loading = false; // first-page (full-screen) load
  bool loadingMore = false; // appending the next page
  String? error;
  List<Product> products = [];
  List<Category> categories = [];
  int? selectedCategoryId; // null => All
  String search = '';

  int _page = 1;
  int _lastPage = 1;
  bool _loaded = false;
  int _reqId = 0; // guards against out-of-order / superseded responses
  Timer? _searchDebounce;

  bool get hasMore => _page < _lastPage;
  bool get isEmpty => products.isEmpty;

  Future<void> loadIfNeeded() async {
    if (_loaded) return;
    await load();
  }

  String? get _searchParam => search.trim().isEmpty ? null : search.trim();

  /// (Re)load from page 1 for the current search + category. Refreshes the
  /// category list on the very first load.
  Future<void> load() async {
    final req = ++_reqId; // any in-flight loadMore for an older query is voided
    loading = true;
    error = null;
    notifyListeners();
    try {
      final results = await Future.wait([
        service.products(
          search: _searchParam,
          mainCategoryId: selectedCategoryId,
          page: 1,
          perPage: _pageSize,
        ),
        if (!_loaded) service.categories(),
      ]);
      if (req != _reqId) return; // a newer query replaced this one
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
      notifyListeners();
    }
  }

  /// Fetch the next page and append it (infinite scroll). No-op while a load is
  /// already running or the last page has been reached.
  Future<void> loadMore() async {
    if (loadingMore || loading || !hasMore) return;
    final req = _reqId; // tie to the current query; bail if it changes
    loadingMore = true;
    notifyListeners();
    try {
      final paged = await service.products(
        search: _searchParam,
        mainCategoryId: selectedCategoryId,
        page: _page + 1,
        perPage: _pageSize,
      );
      if (req != _reqId) return; // query changed mid-flight — drop this page
      products = [...products, ...paged.items];
      _page = paged.currentPage;
      _lastPage = paged.lastPage;
    } catch (_) {
      // Keep what we have; the next scroll can retry the same page.
    }
    if (req == _reqId) {
      loadingMore = false;
      notifyListeners();
    }
  }

  /// Debounced search — reloads from page 1 once typing settles.
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

  Future<Product?> findByBarcode(String code) => service.productByBarcode(code);

  @override
  void dispose() {
    _searchDebounce?.cancel();
    super.dispose();
  }
}
