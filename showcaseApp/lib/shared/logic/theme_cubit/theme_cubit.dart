import 'package:equatable/equatable.dart';
import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';

import '../../domain/constants/global_variables.dart';
import '../../utils/components/theme/theme_presets.dart';
import '../../utils/local_storage/local_storage_service.dart';

part 'theme_state.dart';

/// Light / dark / follow-the-device, and which palette dresses each — persisted
/// per tablet.
///
/// The two presets are held separately on purpose: a shop that wants paper by
/// day and obsidian after dark should not have to pick one compromise that is
/// wrong half the time. Aurora Glass is the default in both, and the mode
/// follows the device — so an untouched tablet goes light and dark with the
/// room it is standing in.
class ThemeCubit extends Cubit<ThemeSettings> {
  ThemeCubit() : super(const ThemeSettings()) {
    emit(ThemeSettings(
      mode: _decode(_storage.themeMode),
      light: ThemePreset.decode(_storage.lightPreset, _default),
      dark: ThemePreset.decode(_storage.darkPreset, _default),
    ));
  }

  /// What a tablet nobody has configured wears, in both modes.
  static const ThemePreset _default = ThemePreset.aurora;

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
    if (mode == state.mode) return;
    emit(state.copyWith(mode: mode));
    await _storage.setThemeMode(_encode(mode));
  }

  Future<void> setLight(ThemePreset preset) async {
    if (preset == state.light) return;
    emit(state.copyWith(light: preset));
    await _storage.setLightPreset(preset.name);
  }

  Future<void> setDark(ThemePreset preset) async {
    if (preset == state.dark) return;
    emit(state.copyWith(dark: preset));
    await _storage.setDarkPreset(preset.name);
  }

  /// Dress both modes in one direction — the common case, and two taps fewer.
  Future<void> setBoth(ThemePreset preset) async {
    await setLight(preset);
    await setDark(preset);
  }

  /// Cycles system → light → dark → system, for the single rail control.
  Future<void> cycle() => set(switch (state.mode) {
        ThemeMode.system => ThemeMode.light,
        ThemeMode.light => ThemeMode.dark,
        ThemeMode.dark => ThemeMode.system,
      });
}
