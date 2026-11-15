import 'package:equatable/equatable.dart';
import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';

import '../../domain/constants/global_variables.dart';
import '../../utils/components/theme/pearl_theme.dart';
import '../../utils/components/theme/theme_presets.dart';
import '../../utils/components/theme/type_presets.dart';
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
    final typeface = TypePreset.decode(_storage.typeface, TypePreset.jost);
    PearlText.useType(typeface);
    emit(ThemeSettings(
      mode: _decode(_storage.themeMode),
      light: ThemePreset.decode(_storage.lightPreset, _default),
      dark: ThemePreset.decode(_storage.darkPreset, _default),
      textScale: _storage.textScale == 0 ? 1 : _storage.textScale,
      typeface: typeface,
      sizeColumns:
          _storage.sizeColumns == 0 ? defaultSizeColumns : _storage.sizeColumns,
      productColumns: _storage.productColumns == 0
          ? defaultProductColumns
          : _storage.productColumns,
      idleMinutes:
          _storage.idleMinutes == 0 ? defaultIdleMinutes : _storage.idleMinutes,
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

  /// The sizes offered, and the only sizes the layouts are checked at — the
  /// overflow tests take their largest case from the end of this list, so
  /// adding a step here widens what they cover rather than going untested.
  static const List<double> textScales = [1, 1.12, 1.25, 1.4];

  /// The counts offered for the size run, and what a tablet nobody has
  /// configured shows. Three because it is the one count that fits every
  /// screen the app runs on; a counter-top tablet will want more, which is
  /// what the setting is for.
  static const List<int> sizeColumnOptions = [3, 4, 5, 6];
  static const int defaultSizeColumns = 3;

  Future<void> setSizeColumns(int columns) async {
    if (columns == state.sizeColumns) return;
    emit(state.copyWith(sizeColumns: columns));
    await _storage.setSizeColumns(columns);
  }

  /// The counts offered for the results grid, and what an unconfigured tablet
  /// shows. Two because a customer is choosing a shoe from a photograph and a
  /// photograph a quarter of the panel wide is not one they can choose from —
  /// the same reason the width rule that used to decide this was wrong on the
  /// one screen the app runs on. A wide desk display can want five.
  static const List<int> productColumnOptions = [2, 3, 4, 5];
  static const int defaultProductColumns = 2;

  Future<void> setProductColumns(int columns) async {
    if (columns == state.productColumns) return;
    emit(state.copyWith(productColumns: columns));
    await _storage.setProductColumns(columns);
  }

  /// How long the panel waits before it belongs to the next customer.
  ///
  /// Typed rather than picked from a list: the right number is the shop's, and
  /// it depends on things no list can anticipate — how long the queue is, how
  /// far the tablet is from the till, whether staff use it between customers.
  /// A shop that wants seven minutes should be able to have seven.
  static const int defaultIdleMinutes = 10;

  /// Under a minute the panel resets while someone is still reading it; past
  /// two hours it has stopped being a reset at all. Typing outside the range
  /// is not refused — it is pulled to the nearest end, so the field always
  /// answers with something that works.
  static const int minIdleMinutes = 1;
  static const int maxIdleMinutes = 120;

  Future<void> setIdleMinutes(int minutes) async {
    final value = minutes.clamp(minIdleMinutes, maxIdleMinutes);
    if (value == state.idleMinutes) return;
    emit(state.copyWith(idleMinutes: value));
    await _storage.setIdleMinutes(value);
  }

  Future<void> setTypeface(TypePreset preset) async {
    if (preset == state.typeface) return;
    // Set before the emit, so the frame that rebuilds is already in the new
    // face — the styles are read statically and would otherwise lag by one.
    PearlText.useType(preset);
    emit(state.copyWith(typeface: preset));
    await _storage.setTypeface(preset.name);
  }

  Future<void> setTextScale(double value) async {
    if (value == state.textScale) return;
    emit(state.copyWith(textScale: value));
    await _storage.setTextScale(value);
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
