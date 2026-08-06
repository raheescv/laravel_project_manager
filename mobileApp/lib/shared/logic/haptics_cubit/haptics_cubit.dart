import 'package:flutter_bloc/flutter_bloc.dart';

import 'package:invo/shared/domain/constants/global_variables.dart';
import 'package:invo/shared/utils/components/haptics.dart';
import 'package:invo/shared/utils/local_storage/local_storage_service.dart';

/// Owns the app-wide haptic-feedback preference (Settings → Haptics) and keeps
/// the static [Haptics.enabled] flag — read on every tap by [HapticTapDetector]
/// — in sync. Constructed at boot so the flag is correct from the first frame.
///
/// State is the preference itself, so a rebuild only happens when it actually
/// changes (§5).
class HapticsCubit extends Cubit<bool> {
  HapticsCubit() : super(serviceLocator<LocalStorageService>().hapticsEnabled ?? true) {
    Haptics.enabled = state;
  }

  LocalStorageService get _storage => serviceLocator<LocalStorageService>();

  bool get enabled => state;

  Future<void> setEnabled(bool v) async {
    if (v == state) return;
    Haptics.enabled = v;
    emit(v);
    await _storage.setHapticsEnabled(v);
  }

  Future<void> toggle() => setEnabled(!state);
}
