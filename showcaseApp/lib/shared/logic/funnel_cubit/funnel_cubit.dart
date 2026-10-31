import 'dart:async';

import 'package:equatable/equatable.dart';
import 'package:flutter_bloc/flutter_bloc.dart';

import '../../domain/constants/data_fetching_status.dart';
import '../../domain/constants/global_variables.dart';
import '../../domain/models/index.dart';
import '../../domain/repository/catalog_repository.dart';
import '../../utils/router/http_utils/common_exception.dart';
import '../branch_cubit/branch_cubit.dart';
import '../connectivity_cubit/connectivity_cubit.dart';

part 'funnel_state.dart';

/// Owns the whole funnel: the choices made so far and the options for each step.
///
/// One cubit rather than two because the steps are not independent — choosing a
/// size re-scopes the brand counts, and the tablet layout shows both at once.
/// Splitting them would mean choreographing two cubits to stay consistent.
///
/// In `shared/logic` rather than under the catalog feature, because it is not
/// one feature's state — it is the visit's. It is provided in `app.dart` for the
/// life of the app beside the branch, theme and locale cubits; the top bar and
/// the breadcrumb strip read it on every screen; the product page and the search
/// screen scope themselves by it; and the idle timer clears it. While it lived
/// in `features/catalog` all six of those were reaching across a feature
/// boundary to get at it, two of them from `shared/` — which is the wrong
/// direction for a dependency, whatever the file happens to be called.
class FunnelCubit extends Cubit<FunnelState> {
  FunnelCubit() : super(const FunnelState()) {
    loadSizes();
    _branchSub = _branch.onBranchChanged.listen((_) => _reloadForBranch());
    _reconnectSub = _connectivity.onReconnected.listen((_) => _retryFailed());
  }

  CatalogRepository get _repo => serviceLocator<CatalogRepository>();
  BranchCubit get _branch => serviceLocator<BranchCubit>();
  ConnectivityCubit get _connectivity => serviceLocator<ConnectivityCubit>();

  StreamSubscription<int>? _branchSub;
  StreamSubscription<void>? _reconnectSub;

  /// Two taps on a size chip land before the first has loaded its brands.
  ///
  /// A plain "something is already running" guard gets this exactly wrong: the
  /// second tap is a *different* question, and dropping it leaves the brand
  /// step answering for the size the customer moved off. So the guard is keyed
  /// on the question — an identical one already in flight is dropped, a new one
  /// supersedes, and the superseded answer is discarded when it lands.
  /// Every answer in this cubit can land after the screen that asked for it is
  /// gone — a customer taps a size and immediately walks away from the kiosk,
  /// or taps Back while the brands are still loading. Emitting then throws, and
  /// the throw escapes into the zone rather than into the catch, so the guard
  /// belongs on the emit itself.
  void _set(FunnelState next) {
    if (!isClosed) emit(next);
  }

  String? _sizesInFlight;
  String? _brandsInFlight;
  int _sizesRequest = 0;
  int _brandsRequest = 0;

  /// Step 1's options. Every size in the catalogue — there is nothing chosen
  /// yet to narrow them by, and the per-size stock figure comes from the branch.
  Future<void> loadSizes() async {
    await _branch.ready;
    final key = '${_branch.selectedId}';
    if (_sizesInFlight == key) return;
    _sizesInFlight = key;
    final id = ++_sizesRequest;
    _set(state.copyWith(sizesStatus: DataFetchStatus.waiting, clearError: true));
    try {
      final rows = await _repo.sizes(branchId: _branch.selectedId);
      if (id != _sizesRequest) return;
      _set(_keepVisible(state.copyWith(sizesStatus: DataFetchStatus.success, sizes: rows)));
    } on ApiException catch (e) {
      if (id != _sizesRequest) return;
      _set(state.copyWith(sizesStatus: DataFetchStatus.failed, errorMessage: e.message));
    } catch (_) {
      if (id != _sizesRequest) return;
      _set(state.copyWith(sizesStatus: DataFetchStatus.failed));
    } finally {
      if (id == _sizesRequest) _sizesInFlight = null;
    }
  }

  /// Tap a size on or off. Local only — nothing is asked of the server until
  /// the customer says they are done picking ([confirmSizes]), so tapping
  /// through four plates is four redraws, not four brand fetches racing.
  ///
  /// Any change drops the brand behind it: those counts were taken for a
  /// different set of sizes and would send the customer to an empty grid.
  void toggleSize(String size) {
    final next = state.isChosen(size)
        ? state.chosenSizes.where((s) => s != size).toList()
        : (<String>[...state.chosenSizes, size]..sort(FunnelState.compareSizeLabels));
    _set(state.copyWith(
      chosenSizes: List.unmodifiable(next),
      clearBrand: true,
      brands: const [],
      clearError: true,
    ));
  }

  /// Drop any ticked size the run no longer shows — gone from this branch, or
  /// hidden by "in stock". A tick nobody can see still narrowing the grid is a
  /// filter the customer cannot find to undo.
  FunnelState _keepVisible(FunnelState next) {
    if (next.chosenSizes.isEmpty || next.sizes.isEmpty) return next;
    final shown = {for (final s in next.visibleSizes) s.size};
    final kept = next.chosenSizes.where(shown.contains).toList();
    if (kept.length == next.chosenSizes.length) return next;
    return next.copyWith(chosenSizes: List.unmodifiable(kept), clearBrand: true);
  }

  /// Untick every size without leaving the step.
  void clearSizes() => _set(state.copyWith(
        clearSizes: true,
        clearBrand: true,
        brands: const [],
      ));

  /// The picked sizes are the answer — fetch the brand step for them.
  Future<void> confirmSizes() => loadBrands();

  /// Choose exactly [sizes] and move on in one go.
  Future<void> chooseSizes(List<String> sizes) async {
    _set(state.copyWith(
      chosenSizes: List.unmodifiable(<String>[...sizes]..sort(FunnelState.compareSizeLabels)),
      clearBrand: true,
      brands: const [],
      clearError: true,
    ));
    await loadBrands();
  }

  Future<void> chooseSize(String size) => chooseSizes([size]);

  /// Skipping a step is a first-class action, not a hidden link: plenty of
  /// customers know the brand but not the size, or the reverse.
  Future<void> skipSize() async {
    _set(state.copyWith(clearSizes: true, clearBrand: true, brands: const []));
    await loadBrands();
  }

  Future<void> loadBrands() async {
    final key = '${state.chosenSizes.join(',')}|${state.inStockOnly}';
    if (_brandsInFlight == key) return;
    _brandsInFlight = key;
    final id = ++_brandsRequest;
    _set(state.copyWith(brandsStatus: DataFetchStatus.waiting));
    try {
      final rows = await _repo.brands(
        sizes: state.chosenSizes,
        inStockOnly: state.inStockOnly,
      );
      if (id != _brandsRequest) return;
      _set(state.copyWith(brandsStatus: DataFetchStatus.success, brands: rows));
    } on ApiException catch (e) {
      if (id != _brandsRequest) return;
      _set(state.copyWith(brandsStatus: DataFetchStatus.failed, errorMessage: e.message));
    } catch (_) {
      if (id != _brandsRequest) return;
      _set(state.copyWith(brandsStatus: DataFetchStatus.failed));
    } finally {
      if (id == _brandsRequest) _brandsInFlight = null;
    }
  }

  void chooseBrand(BrandOption brand) => _set(state.copyWith(brand: brand));

  void skipBrand() => _set(state.copyWith(clearBrand: true));

  /// Flip "in stock at this store" for the whole funnel.
  ///
  /// The brand counts are scoped server-side and have to be refetched. The size
  /// chips already carry their own stock figure, so the size run only needs
  /// redrawing — [FunnelState] drops the empty ones.
  Future<void> setInStockOnly(bool value) async {
    if (value == state.inStockOnly) return;
    _set(_keepVisible(state.copyWith(inStockOnly: value)));
    if (state.brands.isNotEmpty) await loadBrands();
  }

  /// Put the funnel back the way the next customer should find it.
  ///
  /// Called when the tablet has been left alone: the size and brand the last
  /// person chose are cleared and the stock filter goes back on, because the
  /// next person walking up has not asked for any of it. The branch is reset
  /// alongside this by whoever calls it — it is the one piece of the slate
  /// that does not live here.
  Future<void> resetForNextCustomer() async {
    _set(state.copyWith(
      clearSizes: true,
      clearBrand: true,
      brands: const [],
      inStockOnly: true,
      clearError: true,
    ));
    await loadSizes();
  }

  /// Re-entering an earlier step keeps that step's answer and everything before
  /// it, and drops only what came after — going back must never be destructive.
  ///
  /// The size step keeps its ticks: a customer who comes back has usually come
  /// to add a size or take one off, not to start the run again.
  Future<void> backTo(FunnelStep step) async {
    switch (step) {
      case FunnelStep.size:
        _set(state.copyWith(clearBrand: true, brands: const []));
        if (state.sizes.isEmpty) await loadSizes();
      case FunnelStep.brand:
        _set(state.copyWith(clearBrand: true));
        if (state.brands.isEmpty) await loadBrands();
      case FunnelStep.results:
        break;
    }
  }

  /// Every count on screen is branch-relative once "in stock" is on, so a
  /// branch switch re-asks for whichever steps have already been loaded.
  Future<void> _reloadForBranch() async {
    await loadSizes();
    if (state.brands.isNotEmpty) await loadBrands();
  }

  /// The server is back. Only the steps that failed are asked again — a step
  /// that loaded is what the customer is looking at, and reloading it would
  /// blank their screen for an answer they already have.
  Future<void> _retryFailed() async {
    if (state.sizesStatus.isFailed) await loadSizes();
    if (state.brandsStatus.isFailed) await loadBrands();
  }

  @override
  Future<void> close() {
    _branchSub?.cancel();
    _reconnectSub?.cancel();
    return super.close();
  }
}
