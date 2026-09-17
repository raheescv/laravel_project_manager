import 'dart:async';

import 'package:invo/shared/domain/constants/global_variables.dart';
import 'package:invo/shared/domain/models/index.dart';
import 'package:invo/shared/domain/repository/lookup_repository.dart';
import 'package:equatable/equatable.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import 'package:invo/shared/domain/constants/data_fetching_status.dart';
import 'package:invo/shared/utils/local_storage/local_storage_service.dart';
import 'package:invo/shared/utils/router/http_utils/common_exception.dart';
import 'package:invo/shared/utils/router/http_utils/http_service.dart';

part 'branch_state.dart';

/// Holds the [Branch] the user is operating as and persists the choice. The
/// selected branch id is pushed onto [HttpService.activeBranchId] so every
/// request carries `branch_id` app-wide.
class BranchCubit extends Cubit<BranchState> {
  BranchCubit({this.userBranchId, List<Branch> userBranches = const []})
      : _assigned = userBranches,
        super(const BranchState()) {
    // Apply the persisted (or the user's home) branch immediately so requests
    // made before the branch list returns already carry branch_id.
    //
    // [lastBranchId] sits in the middle for the launch that has no network: the
    // offline catalog, the lookups and the queued sales are all keyed by branch,
    // so resolving a different one than the snapshot was written under reads as
    // an empty till — no products, no staff, "nothing is stored yet". That is
    // the likely case whenever the branch was resolved rather than picked (an
    // account with no home branch falls back to the first branch the server
    // lists, which [branchId] deliberately does not record).
    _http.activeBranchId = _storage.branchId ?? _storage.lastBranchId ?? userBranchId;
    _load();
  }

  final int? userBranchId;

  /// The signed-in user's own branches, from their sign-in. When known the app
  /// offers exactly these — nothing to fetch, so an offline launch has them too.
  /// Empty for a session cached by a build that predates them, which keeps the
  /// server's branch list.
  List<Branch> _assigned;

  HttpService get _http => serviceLocator<HttpService>();
  LookupRepository get _repo => serviceLocator<LookupRepository>();
  LocalStorageService get _storage => serviceLocator<LocalStorageService>();

  // Read facade over `state`, so existing `branch.branches` / `.loading`
  // call sites keep working while the data itself is immutable.
  List<Branch> get branches => state.branches;
  bool get loading => state.loading;
  String? get error => state.errorMessage;

  // Broadcasts the newly-selected branch id whenever the active branch actually
  // changes. Branch-scoped screens/cubits (dashboard, reports, sales & returns
  // lists, sale catalog) listen to this and reload so every screen reflects the
  // new branch's data — not just the requests made after the switch.
  final StreamController<int> _branchChanged = StreamController<int>.broadcast();
  Stream<int> get onBranchChanged => _branchChanged.stream;

  Branch? get selected => state.selected;
  int? get selectedId => state.selected?.id ?? _http.activeBranchId;

  Future<void> _load() async {
    if (_assigned.isNotEmpty) return _adopt(_assigned);
    emit(state.copyWith(status: DataFetchStatus.waiting, clearError: true));
    try {
      final rows = await _repo.branches();
      // A sign-in can hand over the user's own branches while this was in
      // flight — the server's full list must not land on top of them.
      if (_assigned.isNotEmpty) return;
      await _adopt(rows);
    } on ApiException catch (e) {
      if (_assigned.isNotEmpty) return;
      emit(state.copyWith(status: DataFetchStatus.failed, errorMessage: e.message));
    } catch (_) {
      if (_assigned.isNotEmpty) return;
      emit(state.copyWith(
          status: DataFetchStatus.failed, errorMessage: 'Could not load branches.'));
    }
  }

  /// Lands on the explicit pick, else the last branch used, else the home
  /// branch — whichever is actually in [rows] — or the first of them.
  Future<void> _adopt(List<Branch> rows) async {
    Branch? pick = state.selected;
    if (rows.isNotEmpty) {
      pick = _find(rows, _storage.branchId) ??
          _find(rows, _storage.lastBranchId) ??
          _find(rows, userBranchId) ??
          rows.first;
      _http.activeBranchId = pick.id;
    }
    emit(state.copyWith(
        status: DataFetchStatus.success, branches: rows, selected: pick));
    // Remember what we actually landed on, so the next launch resolves the
    // same branch with no network to ask.
    if (pick != null) await _storage.setLastBranchId(pick.id);
  }

  static Branch? _find(List<Branch> rows, int? id) {
    for (final b in rows) {
      if (b.id == id) return b;
    }
    return null;
  }

  Future<void> refreshBranches() => _load();

  /// A sign-in — or another cashier taking over the till: work from that
  /// user's own branches. Stays on the current branch when they work there too
  /// (no catalog reload), else lands on their home branch. A user with more
  /// than one is then asked which, on the branch picker.
  Future<void> applyUser(ApiUser user) async {
    final home = int.tryParse(user.branchId ?? '');
    if (user.branches.isEmpty) return applyUserDefault(home);
    _assigned = user.branches;
    final previous = selectedId;
    final pick = _find(_assigned, previous) ?? _find(_assigned, home) ?? _assigned.first;
    _http.activeBranchId = pick.id;
    emit(state.copyWith(
        status: DataFetchStatus.success, branches: _assigned, selected: pick, clearError: true));
    await _storage.setLastBranchId(pick.id);
    if (pick.id != previous) _branchChanged.add(pick.id);
  }

  Future<void> applyUserDefault(int? homeBranchId) async {
    if (_storage.branchId != null) return; // respect an explicit pick
    if (branches.isEmpty) await _load();
    if (homeBranchId == null) return;
    final match = state.branches.where((b) => b.id == homeBranchId);
    if (match.isEmpty) return;
    _http.activeBranchId = match.first.id;
    await _storage.setLastBranchId(match.first.id);
    emit(state.copyWith(selected: match.first));
  }

  Future<void> setBranch(Branch b) async {
    final changed = state.selected?.id != b.id;
    if (changed) {
      _http.activeBranchId = b.id;
      emit(state.copyWith(selected: b));
    }
    // Saved even when unchanged: picking the branch already in use still has to
    // replace an earlier explicit pick — possibly another cashier's — or the
    // next launch would resolve that one.
    await _storage.setBranchId(b.id);
    await _storage.setLastBranchId(b.id);
    // Fan out to every branch-scoped screen/cubit so they reload for this branch.
    if (changed) _branchChanged.add(b.id);
  }

  @override
  Future<void> close() {
    _branchChanged.close();
    return super.close();
  }
}
