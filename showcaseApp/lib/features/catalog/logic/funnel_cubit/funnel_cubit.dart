import 'package:equatable/equatable.dart';
import 'package:flutter_bloc/flutter_bloc.dart';

import '../../../../shared/domain/constants/data_fetching_status.dart';
import '../../../../shared/domain/constants/global_variables.dart';
import '../../../../shared/domain/models/index.dart';
import '../../../../shared/domain/repository/catalog_repository.dart';
import '../../../../shared/logic/branch_cubit/branch_cubit.dart';
import '../../../../shared/utils/router/http_utils/common_exception.dart';

part 'funnel_state.dart';

/// Owns the whole funnel: the choices made so far and the options for each step.
///
/// One cubit rather than three because the steps are not independent — choosing
/// a size re-scopes the categories, choosing a category re-scopes the brand
/// counts, and the tablet layout shows all of it at once. Splitting them would
/// mean choreographing three cubits to stay consistent.
class FunnelCubit extends Cubit<FunnelState> {
  FunnelCubit() : super(const FunnelState()) {
    loadSizes();
    _branch.onBranchChanged.listen((_) => _reloadForBranch());
  }

  CatalogRepository get _repo => serviceLocator<CatalogRepository>();
  BranchCubit get _branch => serviceLocator<BranchCubit>();

  /// Step 1's options. Every size in the catalogue — there is nothing chosen
  /// yet to narrow them by, and the per-size stock figure comes from the branch.
  Future<void> loadSizes() async {
    emit(state.copyWith(sizesStatus: DataFetchStatus.waiting, clearError: true));
    try {
      await _branch.ready;
      final rows = await _repo.sizes(branchId: _branch.selectedId);
      emit(state.copyWith(sizesStatus: DataFetchStatus.success, sizes: rows));
    } on ApiException catch (e) {
      emit(state.copyWith(sizesStatus: DataFetchStatus.failed, errorMessage: e.message));
    }
  }

  /// Choosing a size drops everything downstream of it — the category counts
  /// were taken in a different size and would send the customer to an empty
  /// grid.
  Future<void> chooseSize(String size) async {
    emit(state.copyWith(
      size: size,
      clearCategory: true,
      clearBrand: true,
      categories: const [],
      brands: const [],
      clearError: true,
    ));
    await loadCategories();
  }

  /// Skipping a step is a first-class action, not a hidden link: plenty of
  /// customers know the brand but not the size, or the reverse.
  Future<void> skipSize() async {
    emit(state.copyWith(
      clearSize: true,
      clearCategory: true,
      clearBrand: true,
      categories: const [],
      brands: const [],
    ));
    await loadCategories();
  }

  Future<void> loadCategories() async {
    emit(state.copyWith(categoriesStatus: DataFetchStatus.waiting));
    try {
      await _branch.ready;
      final rows = await _repo.categories(
        size: state.size,
        branchId: _branch.selectedId,
        inStockOnly: state.inStockOnly,
      );
      emit(state.copyWith(
        categoriesStatus: DataFetchStatus.success,
        categories: rows,
      ));
    } on ApiException catch (e) {
      emit(state.copyWith(
        categoriesStatus: DataFetchStatus.failed,
        errorMessage: e.message,
      ));
    }
  }

  Future<void> chooseCategory(CategoryOption category) async {
    emit(state.copyWith(category: category, clearBrand: true, brands: const []));
    await loadBrands();
  }

  /// The category step is skippable too — "everything in my size".
  Future<void> skipCategory() async {
    emit(state.copyWith(clearCategory: true, clearBrand: true, brands: const []));
    await loadBrands();
  }

  Future<void> loadBrands() async {
    emit(state.copyWith(brandsStatus: DataFetchStatus.waiting));
    try {
      final rows = await _repo.brands(
        mainCategoryId: state.category?.id,
        size: state.size,
        inStockOnly: state.inStockOnly,
      );
      emit(state.copyWith(brandsStatus: DataFetchStatus.success, brands: rows));
    } on ApiException catch (e) {
      emit(state.copyWith(brandsStatus: DataFetchStatus.failed, errorMessage: e.message));
    }
  }

  /// Flip "in stock at this store" for the whole funnel.
  ///
  /// The category and brand counts are scoped server-side and have to be
  /// refetched. The size chips already carry their own stock figure, so the
  /// size run only needs redrawing — [FunnelState] drops the empty ones.
  Future<void> setInStockOnly(bool value) async {
    if (value == state.inStockOnly) return;
    emit(state.copyWith(inStockOnly: value));
    if (state.categories.isNotEmpty) await loadCategories();
    if (state.brands.isNotEmpty) await loadBrands();
  }

  void chooseBrand(BrandOption brand) => emit(state.copyWith(brand: brand));

  void skipBrand() => emit(state.copyWith(clearBrand: true));

  /// Re-entering an earlier step keeps that step's answer and everything before
  /// it, and drops only what came after — going back must never be destructive.
  Future<void> backTo(FunnelStep step) async {
    switch (step) {
      case FunnelStep.size:
        emit(state.copyWith(
          clearSize: true,
          clearCategory: true,
          clearBrand: true,
          categories: const [],
          brands: const [],
        ));
        if (state.sizes.isEmpty) await loadSizes();
      case FunnelStep.category:
        emit(state.copyWith(clearCategory: true, clearBrand: true, brands: const []));
        if (state.categories.isEmpty) await loadCategories();
      case FunnelStep.brand:
        emit(state.copyWith(clearBrand: true));
        if (state.brands.isEmpty) await loadBrands();
      case FunnelStep.results:
        break;
    }
  }

  /// Every count on screen is branch-relative once "in stock" is on, so a
  /// branch switch re-asks for whichever steps have already been loaded.
  Future<void> _reloadForBranch() async {
    await loadSizes();
    if (state.categories.isNotEmpty) await loadCategories();
    if (state.brands.isNotEmpty) await loadBrands();
  }
}
