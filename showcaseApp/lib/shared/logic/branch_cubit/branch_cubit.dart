import 'dart:async';

import 'package:equatable/equatable.dart';
import 'package:flutter_bloc/flutter_bloc.dart';

import '../../domain/constants/data_fetching_status.dart';
import '../../domain/constants/global_variables.dart';
import '../../domain/models/index.dart';
import '../../domain/repository/catalog_repository.dart';
import '../../utils/local_storage/local_storage_service.dart';
import '../../utils/router/http_utils/common_exception.dart';
import '../../utils/router/http_utils/http_service.dart';

part 'branch_state.dart';

/// The shop this tablet stands in. Everything stock-related is scoped to it:
/// the size chips, the availability list, the "in stock only" filter.
///
/// The saved id is pushed onto [HttpService] before the branch list even
/// returns, so the first catalog request already carries `branch_id` and the
/// opening screen never shows another shop's stock.
class BranchCubit extends Cubit<BranchState> {
  BranchCubit() : super(const BranchState()) {
    final saved = _storage.branchId;
    // `allBranches` is a choice, so it survives a restart like any other. The
    // http layer takes null for it, which is what makes the server answer for
    // the whole chain.
    _http.activeBranchId = saved == allBranches ? null : saved;
    load();
  }

  /// Stored in place of a branch id to mean "every shop". Zero is never a real
  /// id, and a sentinel beats a second flag that can disagree with the first.
  static const int allBranches = 0;

  final Completer<void> _resolved = Completer<void>();

  /// Completes once the branch is settled.
  ///
  /// Every stock figure the API returns is scoped by `branch_id`, and without
  /// one the server sums across every shop — which, with the negative counts a
  /// live catalogue accumulates, makes stocked products report as sold out. So
  /// catalog reads wait for this rather than racing it.
  Future<void> get ready => _resolved.future;

  /// The list can land after this cubit is gone — only in tests, since the app
  /// holds it for its whole life, but a teardown that crashes is a red suite
  /// nobody trusts.
  void _set(BranchState next) {
    if (!isClosed) emit(next);
  }

  HttpService get _http => serviceLocator<HttpService>();
  CatalogRepository get _repo => serviceLocator<CatalogRepository>();
  LocalStorageService get _storage => serviceLocator<LocalStorageService>();

  /// Screens scoped to a branch listen here and reload, so switching shop
  /// updates what is already on screen rather than only the next request.
  final StreamController<int> _changed = StreamController<int>.broadcast();
  Stream<int> get onBranchChanged => _changed.stream;

  /// The branch every request is scoped to, or null for the whole chain.
  int? get selectedId => state.showingAll ? null : (state.selected?.id ?? _http.activeBranchId);

  Future<void> load() async {
    _set(state.copyWith(status: DataFetchStatus.waiting, clearError: true));
    try {
      final rows = await _repo.branches();
      final target = _storage.branchId;
      final all = target == allBranches;
      Branch? pick;
      if (rows.isNotEmpty && !all) {
        pick = rows.firstWhere((b) => b.id == target, orElse: () => rows.first);
        _http.activeBranchId = pick.id;
        await _storage.setBranchId(pick.id);
      }
      _set(state.copyWith(
        status: DataFetchStatus.success,
        branches: rows,
        selected: pick,
        showingAll: all,
      ));
    } on ApiException catch (e) {
      _set(state.copyWith(status: DataFetchStatus.failed, errorMessage: e.message));
    } catch (_) {
      // Anything the transport did not think to type — a malformed body, a cast
      // that did not hold. Uncaught it escapes a fire-and-forget `load()` and
      // leaves the store pill on a spinner nothing will ever resolve.
      _set(state.copyWith(status: DataFetchStatus.failed));
    } finally {
      // Released even on failure: a catalogue with unscoped stock still beats a
      // screen that never loads because the branch list 500'd.
      if (!_resolved.isCompleted) _resolved.complete();
    }
  }

  Future<void> select(Branch branch) async {
    if (!state.showingAll && branch.id == state.selected?.id) return;
    _http.activeBranchId = branch.id;
    await _storage.setBranchId(branch.id);
    _set(state.copyWith(selected: branch, showingAll: false));
    _changed.add(branch.id);
  }

  /// Look at the whole chain rather than one shop.
  ///
  /// Stock then comes back summed across every branch. That is the honest
  /// answer to "does the company have this", and it is a different question
  /// from "can I hand it to you now" — which is why the availability strip
  /// still breaks it down by shop.
  Future<void> selectAll() async {
    if (state.showingAll) return;
    _http.activeBranchId = null;
    await _storage.setBranchId(allBranches);
    _set(state.copyWith(showingAll: true, clearSelected: true));
    _changed.add(allBranches);
  }

  @override
  Future<void> close() {
    _changed.close();
    return super.close();
  }
}
