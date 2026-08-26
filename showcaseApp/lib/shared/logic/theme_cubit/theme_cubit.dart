import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';

import '../../domain/constants/global_variables.dart';
import '../../utils/local_storage/local_storage_service.dart';

/// Light / dark / follow-the-device, persisted per tablet.
///
/// Pearl is designed in both, and a shop floor changes light through the day —
/// so this is a real setting, not a developer toggle.
class ThemeCubit extends Cubit<ThemeMode> {
  ThemeCubit() : super(ThemeMode.system) {
    emit(_decode(_storage.themeMode));
  }

  LocalStorageService get _storage => serviceLocator<LocalStorageService>();

  static ThemeMode _decode(String raw) => switch (raw) {
        'light' => ThemeMode.light,
        'dark' => ThemeMode.dark,
        _ => ThemeMode.system,
      };

  static String _encode(ThemeMode mode) => switch (mode) {
        ThemeMode.light => 'light',
        ThemeMode.dark => 'dark',
        ThemeMode.system => 'system',
      };

  Future<void> set(ThemeMode mode) async {
    if (mode == state) return;
    emit(mode);
    await _storage.setThemeMode(_encode(mode));
  }

  /// Cycles system → light → dark → system, for the single top-bar control.
  Future<void> cycle() => set(switch (state) {
        ThemeMode.system => ThemeMode.light,
        ThemeMode.light => ThemeMode.dark,
        ThemeMode.dark => ThemeMode.system,
      });
}
