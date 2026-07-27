import 'package:invo/shared/domain/constants/global_variables.dart';
import 'package:invo/shared/logic/base/holder_cubit.dart';
import 'package:invo/shared/utils/local_storage/local_storage_service.dart';

/// Device-local point-of-sale flow preferences — how the till behaves once a
/// ticket is charged. Not synced: a shared counter terminal and a manager's
/// own phone want different answers.
class PosSettingsCubit extends HolderCubit {
  PosSettingsCubit() {
    _lockAfterSale = _storage.posLockAfterSale ?? false;
  }

  LocalStorageService get _storage => serviceLocator<LocalStorageService>();

  late bool _lockAfterSale;

  /// Shared-till mode: lock the terminal after every completed sale, so no
  /// ticket can be rung under the last cashier's name on a counter nobody
  /// locked. The session itself survives — see `AuthCubit.lock`.
  bool get lockAfterSale => _lockAfterSale;

  Future<void> setLockAfterSale(bool v) async {
    if (v == _lockAfterSale) return;
    _lockAfterSale = v;
    refresh();
    await _storage.setPosLockAfterSale(v);
  }

  Future<void> toggleLockAfterSale() => setLockAfterSale(!_lockAfterSale);
}
