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
/// a category invalidates the size run, choosing a size re-scopes the brand
/// counts, and the tablet layout shows all of it at once. Splitting them would
/// mean choreographing three cubits to stay consistent.
class FunnelCubit extends Cubit<FunnelState> {
  FunnelCubit() : super(const FunnelState()) {
    loadCategories();
    _branch.onBranchChanged.listen((_) => _reloadForBranch());
  }

  CatalogRepository get _repo => serviceLocator<CatalogRepository>();
  BranchCubit get _branch => serviceLocator<BranchCubit>();

  Future<void> loadCategories() async {
    emit(state.copyWith(categoriesStatus: DataFetchStatus.waiting, clearError: true));
    try {
      final rows = await _repo.categories();
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

  /// Choosing a category drops everything downstream of it — the old size run
  /// belongs to a different category and would silently filter the results.
  Future<void> chooseCategory(CategoryOption category) async {
    emit(state.copyWith(
      category: category,
      clearSize: true,
      clearBrand: true,
      sizes: const [],
      brands: const [],
      clearError: true,
    ));
    await loadSizes();
  }

  Future<void> loadSizes() async {
    final category = state.category;
    if (category == null) return;
    emit(state.copyWith(sizesStatus: DataFetchStatus.waiting));
    try {
      await _branch.ready;
      final rows = await _repo.sizes(
        mainCategoryId: category.id,
        branchId: _branch.selectedId,
      );
      emit(state.copyWith(sizesStatus: DataFetchStatus.success, sizes: rows));
    } on ApiException catch (e) {
      emit(state.copyWith(sizesStatus: DataFetchStatus.failed, errorMessage: e.message));
    }
  }

  Future<void> chooseSize(String size) async {
    emit(state.copyWith(size: size, clearBrand: true, brands: const []));
    await loadBrands();
  }

  /// Skipping a step is a first-class action, not a hidden link: plenty of
  /// customers know the brand but not the size, or the reverse.
  Future<void> skipSize() async {
    emit(state.copyWith(clearSize: true, clearBrand: true, brands: const []));
    await loadBrands();
  }

  Future<void> loadBrands() async {
    final category = state.category;
    if (category == null) return;
    emit(state.copyWith(brandsStatus: DataFetchStatus.waiting));
    try {
      final rows = await _repo.brands(mainCategoryId: category.id, size: state.size);
      emit(state.copyWith(brandsStatus: DataFetchStatus.success, brands: rows));
    } on ApiException catch (e) {
      emit(state.copyWith(brandsStatus: DataFetchStatus.failed, errorMessage: e.message));
    }
  }

  void chooseBrand(BrandOption brand) => emit(state.copyWith(brand: brand));

  void skipBrand() => emit(state.copyWith(clearBrand: true));

  /// Re-entering an earlier step keeps that step's answer and everything before
  /// it, and drops only what came after — going back must never be destructive.
  Future<void> backTo(FunnelStep step) async {
    switch (step) {
      case FunnelStep.category:
        emit(state.copyWith(
          clearCategory: true,
          clearSize: true,
          clearBrand: true,
          sizes: const [],
          brands: const [],
        ));
      case FunnelStep.size:
        emit(state.copyWith(clearSize: true, clearBrand: true, brands: const []));
        if (state.sizes.isEmpty) await loadSizes();
      case FunnelStep.brand:
        emit(state.copyWith(clearBrand: true));
        if (state.brands.isEmpty) await loadBrands();
      case FunnelStep.results:
        break;
    }
  }

  Future<void> _reloadForBranch() async {
    // Only the size run is branch-sensitive; categories and brands are not.
    if (state.category != null) await loadSizes();
  }
}
