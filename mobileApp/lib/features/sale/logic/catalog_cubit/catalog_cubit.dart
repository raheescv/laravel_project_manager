import 'dart:async';

import 'package:invo/shared/domain/constants/global_variables.dart';
import 'package:invo/shared/domain/models/index.dart';
import 'package:invo/shared/domain/repository/lookup_repository.dart';
import 'package:invo/shared/logic/base/holder_cubit.dart';
import 'package:invo/shared/logic/branch_cubit/branch_cubit.dart';
import 'package:invo/shared/utils/local_storage/local_storage_service.dart';
import 'package:invo/shared/utils/router/http_utils/common_exception.dart';

/// Loads the sellable catalog (services + products) for the New Sale screen.
/// Server-paginated with debounced search and infinite-scroll append.
class CatalogCubit extends HolderCubit {
  CatalogCubit() {
    // When the active branch changes, drop the previous branch's catalog and
    // refetch (if it had already loaded) so the New Sale screen never shows
    // another branch's stock. If it was never opened, the first open fetches
    // fresh under the new branch header anyway.
    _branchSub = serviceLocator<BranchCubit>().onBranchChanged.listen((_) {
      if (!_loaded) return;
      _loaded = false;
      load();
    });
  }

  StreamSubscription<int>? _branchSub;

  LookupRepository get _repo => serviceLocator<LookupRepository>();
  LocalStorageService get _storage => serviceLocator<LocalStorageService>();

  static const int _pageSize = 20;

  bool loading = false;
  bool loadingMore = false;
  String? error;
  List<Product> products = [];
  List<Category> categories = [];
  int? selectedCategoryId;
  String search = '';

  /// POS Product/Service filter: null = All Types, 'product', 'service'.
  /// Mirrors the web POS type selector and both the product grid and the
  /// category list are scoped to it.
  String? selectedType;

  int _page = 1;
  int _lastPage = 1;
  bool _loaded = false;
  bool _typeInitialised = false;
  bool _typeTouched = false;
  int _reqId = 0;
  Timer? _searchDebounce;

  bool get hasMore => _page < _lastPage;
  bool get isEmpty => products.isEmpty;

  /// Resolve the default type from the cached sale setting (Settings → Sale
  /// Configuration → Default Product Type). '' / missing = All Types.
  String? _resolveDefaultType() {
    final def = _storage.defaultProductType;
    return (def == 'product' || def == 'service') ? def : null;
  }

  /// Seed [selectedType] from config on the first load, before any fetch.
  void _initDefaultType() {
    if (_typeInitialised) return;
    _typeInitialised = true;
    selectedType = _resolveDefaultType();
  }

  /// Adopt the freshly-synced default type once sale settings arrive from the
  /// server — but never override a type the user has picked this session.
  void applyDefaultType() {
    if (_typeTouched) return;
    final resolved = _resolveDefaultType();
    _typeInitialised = true;
    if (resolved == selectedType) return;
    selectedType = resolved;
    selectedCategoryId = null;
    _loaded = false; // refetch categories under the new type
    load();
  }

  Future<void> loadIfNeeded() async {
    if (_loaded) return;
    await load();
  }

  String? get _searchParam => search.trim().isEmpty ? null : search.trim();

  Future<void> load() async {
    _initDefaultType();
    final req = ++_reqId;
    loading = true;
    error = null;
    refresh();
    try {
      final results = await Future.wait([
        _repo.products(
          search: _searchParam,
          mainCategoryId: selectedCategoryId,
          type: selectedType,
          page: 1,
          perPage: _pageSize,
        ),
        if (!_loaded) _repo.categories(type: selectedType),
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
        type: selectedType,
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

  /// Switch the Product/Service filter. Both the product grid and the category
  /// list are re-fetched under the new type, and the category selection resets
  /// (the previous category may not exist for the new type).
  void selectType(String? type) {
    if (type == selectedType) return;
    _typeTouched = true;
    _typeInitialised = true;
    selectedType = type;
    selectedCategoryId = null;
    _loaded = false; // refetch categories scoped to the new type
    load();
  }

  Future<Product?> findByBarcode(String code) => _repo.productByBarcode(code);

  @override
  Future<void> close() {
    _searchDebounce?.cancel();
    _branchSub?.cancel();
    return super.close();
  }
}
