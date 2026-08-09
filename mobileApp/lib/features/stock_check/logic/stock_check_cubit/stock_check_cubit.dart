import 'package:equatable/equatable.dart';
import 'package:flutter_bloc/flutter_bloc.dart';

import 'package:invo/shared/domain/constants/data_fetching_status.dart';
import 'package:invo/shared/domain/constants/global_variables.dart';
import 'package:invo/shared/utils/components/app_strings.dart';
import 'package:invo/shared/utils/router/http_utils/common_exception.dart';
import 'package:invo/shared/utils/router/http_utils/reachability.dart';

import '../../domain/models/stock_check_models.dart';
import '../../domain/repository/stock_check_repository.dart';

part 'stock_check_state.dart';

/// Owns [StockCheckRepository] for the stock-check screens.
///
/// The three screens each held their own
/// `StockCheckRepository get _repo => serviceLocator<StockCheckRepository>()`
/// and repeated the same try/catch around every call. §10's flow is
/// Widget → Cubit → Repository; this is the missing middle for a feature that
/// had no `logic/` layer at all.
///
/// Read operations return null on failure and leave the reason in
/// [StockCheckState.errorMessage], so screens branch on the result instead of
/// re-implementing error handling.
class StockCheckCubit extends Cubit<StockCheckState> {
  StockCheckCubit({StockCheckRepository? repository})
      : _repo = repository ?? serviceLocator<StockCheckRepository>(),
        super(const StockCheckState());

  final StockCheckRepository _repo;

  Future<T?> _run<T>(Future<T> Function() action) async {
    emit(state.copyWith(status: DataFetchStatus.waiting, clearError: true));
    try {
      final result = await action();
      emit(state.copyWith(status: DataFetchStatus.success));
      return result;
    } on ApiException catch (e) {
      emit(state.copyWith(status: DataFetchStatus.failed, errorMessage: e.message));
      return null;
    } catch (e) {
      emit(state.copyWith(
          status: DataFetchStatus.failed,
          errorMessage: networkErrorMessage(e, AppStrings.somethingWentWrong)));
      return null;
    }
  }

  Future<StockCheckListPage?> list({String? status, String? search, int page = 1}) =>
      _run(() => _repo.list(status: status, search: search, page: page));

  Future<StockCheckCreateResult?> create({
    required int branchId,
    required String date,
    required String title,
    String? description,
  }) =>
      _run(() => _repo.create(
            branchId: branchId,
            date: date,
            title: title,
            description: description,
          ));

  Future<StockCheckDetail?> show(int id) => _run(() => _repo.show(id));

  Future<StockCheckItemsPage?> items(
    int id, {
    String? status,
    String? search,
    String? differenceCondition,
    int page = 1,
  }) =>
      _run(() => _repo.items(id,
          status: status,
          search: search,
          differenceCondition: differenceCondition,
          page: page));

  Future<ScanResult?> scan(int id, String barcode) => _run(() => _repo.scan(id, barcode));

  /// True when the counts were persisted. Completed items reconcile inventory
  /// server-side, so a false here means the working copy is still unsaved.
  Future<bool> saveCounts(int id, List<Map<String, dynamic>> items) async {
    await _run<void>(() => _repo.saveCounts(id, items));
    return state.status == DataFetchStatus.success;
  }

  /// True when the count's own status was moved. The reason for a false is on
  /// [StockCheckState.errorMessage].
  Future<bool> updateStatus(int id, String status) async {
    await _run<void>(() => _repo.updateStatus(id, status));
    return state.status == DataFetchStatus.success;
  }
}
