import 'package:invo/shared/domain/constants/global_variables.dart';
import 'package:invo/shared/logic/base/holder_cubit.dart';
import 'package:invo/shared/utils/components/haptics.dart';
import 'package:invo/shared/utils/local_storage/local_storage_service.dart';

/// Owns the app-wide haptic-feedback preference (Settings → Haptics) and keeps
/// the static [Haptics.enabled] flag — read on every tap by [HapticTapDetector]
/// — in sync. Constructed at boot so the flag is correct from the first frame.
class HapticsCubit extends HolderCubit {
  HapticsCubit() {
    _enabled = _storage.hapticsEnabled ?? true;
    Haptics.enabled = _enabled;
  }

  LocalStorageService get _storage => serviceLocator<LocalStorageService>();

  late bool _enabled;

  bool get enabled => _enabled;

  Future<void> setEnabled(bool v) async {
    if (v == _enabled) return;
    _enabled = v;
    Haptics.enabled = v;
    refresh();
    await _storage.setHapticsEnabled(v);
  }

  Future<void> toggle() => setEnabled(!_enabled);
}
