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
    _http.activeBranchId = _storage.branchId;
    load();
  }

  final Completer<void> _resolved = Completer<void>();

  /// Completes once the branch is settled.
  ///
  /// Every stock figure the API returns is scoped by `branch_id`, and without
  /// one the server sums across every shop — which, with the negative counts a
  /// live catalogue accumulates, makes stocked products report as sold out. So
  /// catalog reads wait for this rather than racing it.
  Future<void> get ready => _resolved.future;

  HttpService get _http => serviceLocator<HttpService>();
  CatalogRepository get _repo => serviceLocator<CatalogRepository>();
  LocalStorageService get _storage => serviceLocator<LocalStorageService>();

  /// Screens scoped to a branch listen here and reload, so switching shop
  /// updates what is already on screen rather than only the next request.
  final StreamController<int> _changed = StreamController<int>.broadcast();
  Stream<int> get onBranchChanged => _changed.stream;

  int? get selectedId => state.selected?.id ?? _http.activeBranchId;

  Future<void> load() async {
    emit(state.copyWith(status: DataFetchStatus.waiting, clearError: true));
    try {
      final rows = await _repo.branches();
      Branch? pick;
      if (rows.isNotEmpty) {
        final target = _storage.branchId;
        pick = rows.firstWhere((b) => b.id == target, orElse: () => rows.first);
        _http.activeBranchId = pick.id;
        await _storage.setBranchId(pick.id);
      }
      emit(state.copyWith(
        status: DataFetchStatus.success,
        branches: rows,
        selected: pick,
      ));
    } on ApiException catch (e) {
      emit(state.copyWith(status: DataFetchStatus.failed, errorMessage: e.message));
    } finally {
      // Released even on failure: a catalogue with unscoped stock still beats a
      // screen that never loads because the branch list 500'd.
      if (!_resolved.isCompleted) _resolved.complete();
    }
  }

  Future<void> select(Branch branch) async {
    if (branch.id == state.selected?.id) return;
    _http.activeBranchId = branch.id;
    await _storage.setBranchId(branch.id);
    emit(state.copyWith(selected: branch));
    _changed.add(branch.id);
  }

  @override
  Future<void> close() {
    _changed.close();
    return super.close();
  }
}
