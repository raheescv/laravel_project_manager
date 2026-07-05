import 'dart:async';

import 'package:invo/shared/domain/constants/global_variables.dart';
import 'package:invo/shared/domain/models/index.dart';
import 'package:invo/shared/domain/repository/lookup_repository.dart';
import 'package:invo/shared/logic/base/holder_cubit.dart';
import 'package:invo/shared/utils/local_storage/local_storage_service.dart';
import 'package:invo/shared/utils/router/http_utils/common_exception.dart';
import 'package:invo/shared/utils/router/http_utils/http_service.dart';

/// Holds the [Branch] the user is operating as and persists the choice. The
/// selected branch id is pushed onto [HttpService.activeBranchId] so every
/// request carries `branch_id` app-wide.
class BranchCubit extends HolderCubit {
  BranchCubit({this.userBranchId}) {
    // Apply the persisted (or the user's home) branch immediately so requests
    // made before the branch list returns already carry branch_id.
    _http.activeBranchId = _storage.branchId ?? userBranchId;
    _load();
  }

  final int? userBranchId;

  HttpService get _http => serviceLocator<HttpService>();
  LookupRepository get _repo => serviceLocator<LookupRepository>();
  LocalStorageService get _storage => serviceLocator<LocalStorageService>();

  List<Branch> branches = const [];
  Branch? _selected;
  bool loading = false;
  String? error;

  // Broadcasts the newly-selected branch id whenever the active branch actually
  // changes. Branch-scoped screens/cubits (dashboard, reports, sales & returns
  // lists, sale catalog) listen to this and reload so every screen reflects the
  // new branch's data — not just the requests made after the switch.
  final StreamController<int> _branchChanged = StreamController<int>.broadcast();
  Stream<int> get onBranchChanged => _branchChanged.stream;

  Branch? get selected => _selected;
  int? get selectedId => _selected?.id ?? _http.activeBranchId;

  Future<void> _load() async {
    loading = true;
    error = null;
    refresh();
    try {
      branches = await _repo.branches();
      if (branches.isNotEmpty) {
        final targetId = _storage.branchId ?? userBranchId;
        _selected = branches.firstWhere(
          (b) => b.id == targetId,
          orElse: () => branches.first,
        );
        _http.activeBranchId = _selected!.id;
      }
    } on ApiException catch (e) {
      error = e.message;
    } catch (_) {
      error = 'Could not load branches.';
    }
    loading = false;
    refresh();
  }

  Future<void> refreshBranches() => _load();

  Future<void> applyUserDefault(int? homeBranchId) async {
    if (_storage.branchId != null) return; // respect an explicit pick
    if (branches.isEmpty) await _load();
    if (homeBranchId == null) return;
    final match = branches.where((b) => b.id == homeBranchId);
    if (match.isEmpty) return;
    _selected = match.first;
    _http.activeBranchId = _selected!.id;
    refresh();
  }

  Future<void> setBranch(Branch b) async {
    if (_selected?.id == b.id) return;
    _selected = b;
    _http.activeBranchId = b.id;
    refresh();
    await _storage.setBranchId(b.id);
    // Fan out to every branch-scoped screen/cubit so they reload for this branch.
    _branchChanged.add(b.id);
  }

  @override
  Future<void> close() {
    _branchChanged.close();
    return super.close();
  }
}
