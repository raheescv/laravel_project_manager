import 'package:flutter/widgets.dart';
import 'package:invo/shared/domain/constants/global_variables.dart';
import 'package:invo/shared/logic/base/holder_cubit.dart';
import 'package:invo/shared/utils/components/theme/palette.dart';
import 'package:invo/shared/utils/local_storage/local_storage_service.dart';

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

/// Holds the active colour preset *and* the appearance mode, persisting both.
/// The applied [palette] combines the chosen preset with the resolved
/// brightness, so changing either re-skins the whole app instantly.
class ThemeCubit extends HolderCubit with WidgetsBindingObserver {
  ThemeCubit() {
    _base = AstraPresets.byId(_storage.presetId);
    _mode = AstraModeX.fromId(_storage.themeMode);
    WidgetsBinding.instance.addObserver(this);
  }

  LocalStorageService get _storage => serviceLocator<LocalStorageService>();
  late AstraPalette _base;
  late AstraMode _mode;

  AstraPalette get preset => _base;
  AstraMode get mode => _mode;

  bool get isDark => switch (_mode) {
        AstraMode.light => false,
        AstraMode.dark => true,
        AstraMode.system =>
          WidgetsBinding.instance.platformDispatcher.platformBrightness ==
              Brightness.dark,
      };

  AstraPalette get palette => isDark ? _base.dark : _base;

  Future<void> setPreset(AstraPalette p) async {
    if (p.id == _base.id) return;
    _base = p;
    refresh();
    await _storage.setPresetId(p.id);
  }

  Future<void> setMode(AstraMode m) async {
    if (m == _mode) return;
    _mode = m;
    refresh();
    await _storage.setThemeMode(m.id);
  }

  @override
  void didChangePlatformBrightness() {
    if (_mode == AstraMode.system) refresh();
    super.didChangePlatformBrightness();
  }

  @override
  Future<void> close() {
    WidgetsBinding.instance.removeObserver(this);
    return super.close();
  }
}
