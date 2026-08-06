import 'package:flutter/widgets.dart';
import 'package:invo/shared/domain/constants/global_variables.dart';
import 'package:equatable/equatable.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import 'package:invo/shared/utils/components/theme/palette.dart';
import 'package:invo/shared/utils/components/theme/typeface.dart';
import 'package:invo/shared/utils/local_storage/local_storage_service.dart';

part 'theme_state.dart';

/// Appearance brightness, chosen independently of the colour preset.
enum AstraMode { light, dark, system }

extension AstraModeX on AstraMode {
  String get id => switch (this) {
        AstraMode.light => 'light',
        AstraMode.dark => 'dark',
        AstraMode.system => 'system',
      };

  String get label => switch (this) {
        AstraMode.light => 'Light',
        AstraMode.dark => 'Dark',
        AstraMode.system => 'System',
      };

  static AstraMode fromId(String? id) => switch (id) {
        'light' => AstraMode.light,
        'dark' => AstraMode.dark,
        _ => AstraMode.system,
      };
}

/// Holds the active colour preset, the appearance mode *and* the type pairing,
/// persisting all three. The applied [palette] combines the chosen preset with
/// the resolved brightness, so changing any of them re-skins the whole app
/// instantly.
class ThemeCubit extends Cubit<ThemeState> with WidgetsBindingObserver {
  ThemeCubit() : super(_initialState()) {
    // `ui()` / `serif()` have no BuildContext to read from, so the choice is
    // mirrored on the registry as soon as it is known.
    AstraTypefaces.current = state.typeface;
    WidgetsBinding.instance.addObserver(this);
  }

  /// Built before `super`, so it cannot touch instance members.
  static ThemeState _initialState() {
    final st = serviceLocator<LocalStorageService>();
    return ThemeState(
      preset: AstraPresets.byId(st.presetId),
      mode: AstraModeX.fromId(st.themeMode),
      typeface: AstraTypefaces.byId(st.typefaceId),
      platformIsDark: _platformIsDark(),
    );
  }

  static bool _platformIsDark() =>
      WidgetsBinding.instance.platformDispatcher.platformBrightness ==
      Brightness.dark;

  LocalStorageService get _storage => serviceLocator<LocalStorageService>();

  // Read facade over `state`.
  AstraPalette get preset => state.preset;
  AstraMode get mode => state.mode;
  AstraTypeface get typeface => state.typeface;
  bool get isDark => state.isDark;
  AstraPalette get palette => state.palette;

  Future<void> setPreset(AstraPalette p) async {
    if (p.id == state.preset.id) return;
    emit(state.copyWith(preset: p));
    await _storage.setPresetId(p.id);
  }

  Future<void> setTypeface(AstraTypeface t) async {
    if (t.id == state.typeface.id) return;
    AstraTypefaces.current = t;
    emit(state.copyWith(typeface: t));
    await _storage.setTypefaceId(t.id);
  }

  Future<void> setMode(AstraMode m) async {
    if (m == state.mode) return;
    emit(state.copyWith(mode: m));
    await _storage.setThemeMode(m.id);
  }

  @override
  void didChangePlatformBrightness() {
    // Re-skins the app when the OS flips and the user is on System.
    if (!isClosed) emit(state.copyWith(platformIsDark: _platformIsDark()));
    super.didChangePlatformBrightness();
  }

  @override
  Future<void> close() {
    WidgetsBinding.instance.removeObserver(this);
    return super.close();
  }
}
