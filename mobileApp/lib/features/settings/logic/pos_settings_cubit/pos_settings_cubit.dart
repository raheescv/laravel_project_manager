import 'dart:async';

import 'package:equatable/equatable.dart';
import 'package:flutter_bloc/flutter_bloc.dart';

import 'package:invo/features/auth/logic/auth_cubit/auth_cubit.dart';
import 'package:invo/shared/domain/constants/global_variables.dart';
import 'package:invo/shared/domain/models/product_sort.dart';
import 'package:invo/shared/domain/repository/lookup_repository.dart';
import 'package:invo/shared/utils/local_storage/local_storage_service.dart';
import 'package:invo/shared/utils/router/routes.dart';

part 'pos_settings_state.dart';

/// Device-local point-of-sale preferences — where the app lands after sign-in,
/// how the till behaves once a ticket is charged, and how densely it lays the
/// catalog out. Not synced: a shared counter terminal and a manager's own phone
/// want different answers. The one exception is [productSort] — the catalog
/// order is the business's, so it syncs from (and saves to) the web.
/// [startScreen] is also per user: it follows whoever is signed in.
class PosSettingsCubit extends Cubit<PosSettingsState> {
  PosSettingsCubit() : super(_initialState()) {
    _userId = _currentUserId();
    if (serviceLocator.isRegistered<AuthCubit>()) {
      _authSub = serviceLocator<AuthCubit>().stream.listen((auth) => _followUser(auth.user?.id));
    }
  }

  StreamSubscription<AuthState>? _authSub;
  String? _userId;

  static String? _currentUserId() =>
      serviceLocator.isRegistered<AuthCubit>() ? serviceLocator<AuthCubit>().user?.id : null;

  /// A different account signed in (or unlocked the till): show its own start
  /// screen in Settings.
  void _followUser(String? userId) {
    if (userId == _userId) return;
    _userId = userId;
    emit(state.copyWith(startScreen: StartScreen.fromKey(_storage.startScreenFor(userId))));
  }

  @override
  Future<void> close() async {
    await _authSub?.cancel();
    return super.close();
  }

  /// Built before `super`, so it cannot touch instance members.
  static PosSettingsState _initialState() {
    final storage = serviceLocator<LocalStorageService>();
    return PosSettingsState(
      lockAfterSale: storage.posLockAfterSale ?? true,
      askBranchOnUnlock: storage.posAskBranchOnUnlock ?? true,
      gridColumns: _sanitize(storage.posGridColumns),
      askClientOnNewSale: storage.posAskClient ?? true,
      showTip: storage.posShowTip ?? true,
      startScreen: StartScreen.fromKey(storage.startScreenFor(_currentUserId())),
      categoryDisplay: CategoryDisplay.fromKey(storage.posCategoryDisplay),
      productSort: storage.productSort,
    );
  }

  /// Anything the store hands back that isn't an offered option (an older
  /// build that still offered two-up, a hand-edited pref) falls back to the
  /// three-up default.
  static int _sanitize(int? v) =>
      PosSettingsState.gridColumnOptions.contains(v)
          ? v!
          : PosSettingsState.defaultGridColumns;

  LocalStorageService get _storage => serviceLocator<LocalStorageService>();

  /// Shared-till mode: lock the terminal after every completed sale, so no
  /// ticket can be rung under the last cashier's name on a counter nobody
  /// locked. The session itself survives — see `AuthCubit.lock`.
  bool get lockAfterSale => state.lockAfterSale;

  /// Whether an unlock asks a multi-branch user for the branch again. Read by
  /// `AuthCubit` straight from storage, so it applies from the next unlock.
  bool get askBranchOnUnlock => state.askBranchOnUnlock;

  /// Product tiles across the New Sale grid — exactly this many, on a phone
  /// and a tablet alike.
  int get gridColumns => state.gridColumns;

  /// Whether New Sale opens the client form before the catalog, on a ticket
  /// that is still an empty walk-in. Off is for a counter serving a queue, where
  /// a form between the cashier and the products costs a tap on every sale.
  bool get askClientOnNewSale => state.askClientOnNewSale;

  /// Whether this device offers the tip row at Review & Pay. A veto only — the
  /// web's "Enable Tip" is still the business's answer, and `CartCubit.tipEnabled`
  /// requires both.
  bool get showTip => state.showTip;

  /// The screen the signed-in user lands on after a sign-in (or an unlock).
  StartScreen get startScreen => state.startScreen;

  /// [userId]'s start screen, straight from storage — what the router's
  /// redirect reads, so it never depends on this cubit having heard the auth
  /// change yet. The redirect still has the last word on whether the account
  /// may open it.
  StartScreen startScreenFor(String? userId) => StartScreen.fromKey(_storage.startScreenFor(userId));

  /// Whether [userId] has yet to pick a start screen on this device — the
  /// router asks them once, on their first sign-in here, before landing.
  bool needsStartScreenFor(String? userId) => !_storage.hasStartScreenFor(userId);

  /// How New Sale draws its category rail — text chips, or one of three photo
  /// layouts. See [CategoryDisplay].
  CategoryDisplay get categoryDisplay => state.categoryDisplay;

  /// How New Sale orders the catalog — the shared web setting.
  ProductSort get productSort => state.productSort;

  /// Adopt the order a sale-settings sync brought down. Null (an older
  /// server) keeps whatever is cached.
  Future<void> applyRemoteProductSort(ProductSort? v) async {
    if (v == null || v == state.productSort) return;
    emit(state.copyWith(productSort: v));
    await _storage.setProductSort(v);
  }

  /// Change the catalog order for every till: applied at once, then saved to
  /// the web Sale Configuration (needs `configuration.settings`). Rolls back
  /// and returns false when the server rejects it, so the UI can say so.
  Future<bool> setProductSort(ProductSort v) async {
    final current = state.productSort;
    if (v == current) return true;
    emit(state.copyWith(productSort: v));
    await _storage.setProductSort(v);
    try {
      final echo = await serviceLocator<LookupRepository>().saveProductSort(v);
      await applyRemoteProductSort(echo);
      return true;
    } catch (_) {
      emit(state.copyWith(productSort: current));
      await _storage.setProductSort(current);
      return false;
    }
  }

  Future<void> setLockAfterSale(bool v) async {
    if (v == state.lockAfterSale) return;
    emit(state.copyWith(lockAfterSale: v));
    await _storage.setPosLockAfterSale(v);
  }

  Future<void> toggleLockAfterSale() => setLockAfterSale(!state.lockAfterSale);

  Future<void> setAskBranchOnUnlock(bool v) async {
    if (v == state.askBranchOnUnlock) return;
    emit(state.copyWith(askBranchOnUnlock: v));
    await _storage.setPosAskBranchOnUnlock(v);
  }

  Future<void> toggleAskBranchOnUnlock() => setAskBranchOnUnlock(!state.askBranchOnUnlock);

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
    await _storage.setStartScreenFor(_userId, v.key);
  }

  /// The first-sign-in answer. Always written — even when it matches the
  /// fallback already showing — since writing it is what stops the question
  /// coming back.
  Future<void> chooseStartScreenFor(String? userId, StartScreen v) async {
    if (userId == _userId) emit(state.copyWith(startScreen: v));
    await _storage.setStartScreenFor(userId, v.key);
  }

  Future<void> setCategoryDisplay(CategoryDisplay v) async {
    if (v == state.categoryDisplay) return;
    emit(state.copyWith(categoryDisplay: v));
    await _storage.setPosCategoryDisplay(v.key);
  }

  Future<void> setGridColumns(int v) async {
    final cols = _sanitize(v);
    if (cols == state.gridColumns) return;
    emit(state.copyWith(gridColumns: cols));
    await _storage.setPosGridColumns(cols);
  }
}
