import 'package:equatable/equatable.dart';
import 'package:flutter_bloc/flutter_bloc.dart';

import 'package:invo/shared/domain/constants/global_variables.dart';
import 'package:invo/shared/utils/local_storage/local_storage_service.dart';
import 'package:invo/shared/utils/router/routes.dart';

part 'pos_settings_state.dart';

/// Device-local point-of-sale preferences — where the app lands after sign-in,
/// how the till behaves once a ticket is charged, and how densely it lays the
/// catalog out. Not synced: a shared counter terminal and a manager's own phone
/// want different answers.
class PosSettingsCubit extends Cubit<PosSettingsState> {
  PosSettingsCubit() : super(_initialState());

  /// Built before `super`, so it cannot touch instance members.
  static PosSettingsState _initialState() {
    final storage = serviceLocator<LocalStorageService>();
    return PosSettingsState(
      lockAfterSale: storage.posLockAfterSale ?? true,
      gridColumns: _sanitize(storage.posGridColumns),
      askClientOnNewSale: storage.posAskClient ?? true,
      showTip: storage.posShowTip ?? true,
      startScreen: StartScreen.fromKey(storage.posStartScreen),
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

  /// Whether New Sale opens the client form before the catalog, on a ticket
  /// that is still an empty walk-in. Off is for a counter serving a queue, where
  /// a form between the cashier and the products costs a tap on every sale.
  bool get askClientOnNewSale => state.askClientOnNewSale;

  /// Whether this device offers the tip row at Review & Pay. A veto only — the
  /// web's "Enable Tip" is still the business's answer, and `CartCubit.tipEnabled`
  /// requires both.
  bool get showTip => state.showTip;

  /// The screen a fresh sign-in (or an unlock) lands on. Read by the router's
  /// redirect, which still has the last word on whether the account may open it.
  StartScreen get startScreen => state.startScreen;

  Future<void> setLockAfterSale(bool v) async {
    if (v == state.lockAfterSale) return;
    emit(state.copyWith(lockAfterSale: v));
    await _storage.setPosLockAfterSale(v);
  }

  Future<void> toggleLockAfterSale() => setLockAfterSale(!state.lockAfterSale);

  Future<void> setAskClientOnNewSale(bool v) async {
    if (v == state.askClientOnNewSale) return;
    emit(state.copyWith(askClientOnNewSale: v));
    await _storage.setPosAskClient(v);
  }

  Future<void> toggleAskClientOnNewSale() =>
      setAskClientOnNewSale(!state.askClientOnNewSale);

  Future<void> setShowTip(bool v) async {
    if (v == state.showTip) return;
    emit(state.copyWith(showTip: v));
    await _storage.setPosShowTip(v);
  }

  Future<void> toggleShowTip() => setShowTip(!state.showTip);

  Future<void> setStartScreen(StartScreen v) async {
    if (v == state.startScreen) return;
    emit(state.copyWith(startScreen: v));
    await _storage.setPosStartScreen(v.key);
  }

  Future<void> setGridColumns(int v) async {
    final cols = _sanitize(v);
    if (cols == state.gridColumns) return;
    emit(state.copyWith(gridColumns: cols));
    await _storage.setPosGridColumns(cols);
  }
}
