import 'package:equatable/equatable.dart';
import 'package:flutter_bloc/flutter_bloc.dart';

import 'package:invo/shared/domain/constants/global_variables.dart';
import 'package:invo/shared/utils/local_storage/local_storage_service.dart';

part 'pos_settings_state.dart';

/// Device-local point-of-sale preferences — how the till behaves once a ticket
/// is charged, and how densely it lays the catalog out. Not synced: a shared
/// counter terminal and a manager's own phone want different answers.
class PosSettingsCubit extends Cubit<PosSettingsState> {
  PosSettingsCubit() : super(_initialState());

  /// Built before `super`, so it cannot touch instance members.
  static PosSettingsState _initialState() {
    final storage = serviceLocator<LocalStorageService>();
    return PosSettingsState(
      lockAfterSale: storage.posLockAfterSale ?? true,
      gridColumns: _sanitize(storage.posGridColumns),
    );
  }

  /// Anything the store hands back that isn't an offered option (an older
  /// build, a hand-edited pref) falls back to the two-up default.
  static int _sanitize(int? v) =>
      PosSettingsState.gridColumnOptions.contains(v)
          ? v!
          : PosSettingsState.defaultGridColumns;

  LocalStorageService get _storage => serviceLocator<LocalStorageService>();

  /// Shared-till mode: lock the terminal after every completed sale, so no
  /// ticket can be rung under the last cashier's name on a counter nobody
  /// locked. The session itself survives — see `AuthCubit.lock`.
  bool get lockAfterSale => state.lockAfterSale;

  /// Product tiles across the New Sale grid on a phone. A wider screen still
  /// fits more — see `_productGrid`.
  int get gridColumns => state.gridColumns;

  Future<void> setLockAfterSale(bool v) async {
    if (v == state.lockAfterSale) return;
    emit(state.copyWith(lockAfterSale: v));
    await _storage.setPosLockAfterSale(v);
  }

  Future<void> toggleLockAfterSale() => setLockAfterSale(!state.lockAfterSale);

  Future<void> setGridColumns(int v) async {
    final cols = _sanitize(v);
    if (cols == state.gridColumns) return;
    emit(state.copyWith(gridColumns: cols));
    await _storage.setPosGridColumns(cols);
  }
}
