import 'dart:async';

import 'package:invo/shared/domain/constants/global_variables.dart';
import 'package:invo/shared/domain/models/index.dart';
import 'package:invo/shared/domain/repository/lookup_repository.dart';
import 'package:equatable/equatable.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import 'package:invo/shared/domain/constants/data_fetching_status.dart';
import 'package:invo/shared/logic/branch_cubit/branch_cubit.dart';
import 'package:invo/shared/utils/local_storage/local_storage_service.dart';
import 'package:invo/shared/utils/router/http_utils/common_exception.dart';

part 'catalog_state.dart';

/// Loads the sellable catalog (services + products) for the New Sale screen.
/// Server-paginated with debounced search and infinite-scroll append.
class CatalogCubit extends Cubit<CatalogState> {
  CatalogCubit() : super(const CatalogState()) {
    // When the active branch changes, drop the previous branch's catalog and
    // refetch (if it had already loaded) so the New Sale screen never shows
    // another branch's stock. If it was never opened, the first open fetches
    // fresh under the new branch header anyway.
    _branchSub = serviceLocator<BranchCubit>().onBranchChanged.listen((_) {
      if (!state.loaded) return;
      _categoriesFor = null; // refetch categories under the new branch
      unawaited(load());
    });
  }

  StreamSubscription<int>? _branchSub;

  LookupRepository get _repo => serviceLocator<LookupRepository>();
  LocalStorageService get _storage => serviceLocator<LocalStorageService>();

  static const int _pageSize = 20;

  // Read facade over `state`.
  bool get loading => state.loading;
  bool get loadingMore => state.loadingMore;
  String? get error => state.errorMessage;
  List<Product> get products => state.products;
  List<Category> get categories => state.categories;
  int? get selectedCategoryId => state.selectedCategoryId;
  String get search => state.search;
  String? get selectedType => state.selectedType;
  bool get hasMore => state.hasMore;
  bool get isEmpty => state.isEmpty;

  /// The type the cached [CatalogState.categories] were fetched for — null
  /// means "no categories cached", which forces a refetch on the next load.
  String? _categoriesFor;
  bool _categoriesCached = false;

  bool _typeInitialised = false;
  bool _typeTouched = false;
  int _reqId = 0;
  Timer? _searchDebounce;

  /// Resolve the default type from the cached sale setting (Settings → Sale
  /// Configuration → Default Product Type). '' / missing = All Types.
  String? _resolveDefaultType() {
    final def = _storage.defaultProductType;
    return (def == 'product' || def == 'service') ? def : null;
  }

  /// Preferred initial type: the last type the user picked (remembered across
  /// tickets) wins; otherwise the Settings → Sale Configuration default.
  String? _resolveInitialType() {
    final last = _storage.saleType;
    if (last != null) {
      return (last == 'product' || last == 'service') ? last : null;
    }
    return _resolveDefaultType();
  }

  /// Seed [selectedType] on the first load, before any fetch.
  void _initDefaultType() {
    if (_typeInitialised) return;
    _typeInitialised = true;
    final t = _resolveInitialType();
    emit(state.copyWith(selectedType: t, clearType: t == null));
  }

  /// Adopt the freshly-synced default type once sale settings arrive from the
  /// server — but never override a type the user has picked this session, nor a
  /// remembered last-used type (that wins over the Settings default).
  void applyDefaultType() {
    if (_typeTouched) return;
    if (_storage.saleType != null) return;
    final resolved = _resolveDefaultType();
    _typeInitialised = true;
    if (resolved == state.selectedType) return;
    _categoriesCached = false; // refetch categories under the new type
    emit(state.copyWith(
        selectedType: resolved, clearType: resolved == null, clearCategory: true));
    unawaited(load());
  }

  Future<void> loadIfNeeded() async {
    if (state.loaded) return;
    await load();
  }

  String? get _searchParam =>
      state.search.trim().isEmpty ? null : state.search.trim();

  Future<void> load() async {
    _initDefaultType();
    final req = ++_reqId;
    final needCategories = !_categoriesCached || _categoriesFor != state.selectedType;
    emit(state.copyWith(status: DataFetchStatus.waiting, clearError: true));
    try {
      final results = await Future.wait([
        _repo.products(
          search: _searchParam,
          mainCategoryId: state.selectedCategoryId,
          type: state.selectedType,
          page: 1,
          perPage: _pageSize,
        ),
        if (needCategories) _repo.categories(type: state.selectedType),
      ]);
      if (req != _reqId) return;
      final paged = results[0] as Paginated<Product>;
      if (needCategories) {
        _categoriesCached = true;
        _categoriesFor = state.selectedType;
      }
      emit(state.copyWith(
        status: DataFetchStatus.success,
        products: paged.items,
        page: paged.currentPage,
        lastPage: paged.lastPage,
        categories: needCategories ? results[1] as List<Category> : null,
      ));
    } on ApiException catch (e) {
      if (req == _reqId) {
        emit(state.copyWith(status: DataFetchStatus.failed, errorMessage: e.message));
      }
    } catch (_) {
      if (req == _reqId) {
        emit(state.copyWith(
            status: DataFetchStatus.failed,
            errorMessage: 'Could not load the catalog.'));
      }
    }
  }

  Future<void> loadMore() async {
    if (state.loadingMore || state.loading || !state.hasMore) return;
    final req = _reqId;
    emit(state.copyWith(loadingMore: true));
    try {
      final paged = await _repo.products(
        search: _searchParam,
        mainCategoryId: state.selectedCategoryId,
        type: state.selectedType,
        page: state.page + 1,
        perPage: _pageSize,
      );
      if (req != _reqId) return;
      emit(state.copyWith(
        products: [...state.products, ...paged.items],
        page: paged.currentPage,
        lastPage: paged.lastPage,
      ));
    } catch (_) {
      // Keep what we have; the next scroll can retry the same page.
    } finally {
      // Always clear, even for a discarded page, or the guard on entry blocks
      // every later page for the life of the screen.
      if (!isClosed) emit(state.copyWith(loadingMore: false));
    }
  }

  void setSearch(String v) {
    emit(state.copyWith(search: v));
    _searchDebounce?.cancel();
    _searchDebounce = Timer(const Duration(milliseconds: 350), load);
  }

  void selectCategory(int? id) {
    if (id == state.selectedCategoryId) return;
    emit(state.copyWith(selectedCategoryId: id, clearCategory: id == null));
    unawaited(load());
  }

  /// Switch the Product/Service filter. Both the product grid and the category
  /// list are re-fetched under the new type, and the category selection resets
  /// (the previous category may not exist for the new type).
  void selectType(String? type) {
    if (type == state.selectedType) return;
    _typeTouched = true;
    _typeInitialised = true;
    unawaited(_storage.setSaleType(type ?? '')); // remember for the next ticket
    _categoriesCached = false; // refetch categories scoped to the new type
    emit(state.copyWith(
        selectedType: type, clearType: type == null, clearCategory: true));
    unawaited(load());
  }

  Future<Product?> findByBarcode(String code) => _repo.productByBarcode(code);

  @override
  Future<void> close() {
    _searchDebounce?.cancel();
    _branchSub?.cancel();
    return super.close();
  }
}
