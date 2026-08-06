import 'package:flutter_bloc/flutter_bloc.dart';

import 'package:invo/shared/domain/constants/global_variables.dart';
import 'package:invo/shared/utils/local_storage/local_storage_service.dart';

/// Device-local point-of-sale flow preferences — how the till behaves once a
/// ticket is charged. Not synced: a shared counter terminal and a manager's
/// own phone want different answers.
///
/// State is the preference itself, so watchers rebuild only on a real change (§5).
class PosSettingsCubit extends Cubit<bool> {
  PosSettingsCubit()
      : super(serviceLocator<LocalStorageService>().posLockAfterSale ?? false);

  LocalStorageService get _storage => serviceLocator<LocalStorageService>();

  /// Shared-till mode: lock the terminal after every completed sale, so no
  /// ticket can be rung under the last cashier's name on a counter nobody
  /// locked. The session itself survives — see `AuthCubit.lock`.
  bool get lockAfterSale => state;

  Future<void> setLockAfterSale(bool v) async {
    if (v == state) return;
    emit(v);
    await _storage.setPosLockAfterSale(v);
  }

  Future<void> toggleLockAfterSale() => setLockAfterSale(!state);
}
