import 'package:flutter/widgets.dart';

import '../core/storage.dart';
import '../theme/palette.dart';

/// Appearance brightness, chosen independently of the colour preset.
///   • [light]  — always the light variant
///   • [dark]   — always the dark variant
///   • [system] — follow the OS setting (and update live when it changes)
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
/// The applied [palette] combines the chosen preset (hue/skin) with the resolved
/// brightness, so changing either re-skins the whole app instantly.
class ThemeController extends ChangeNotifier with WidgetsBindingObserver {
  ThemeController(this._storage) {
    _base = AstraPresets.byId(_storage.presetId);
    _mode = AstraModeX.fromId(_storage.themeMode);
    WidgetsBinding.instance.addObserver(this);
  }

  final Storage _storage;
  late AstraPalette _base;
  late AstraMode _mode;

  /// The user-chosen preset (hue/skin), independent of brightness.
  AstraPalette get preset => _base;

  AstraMode get mode => _mode;

  /// Whether the effective theme reads as dark right now.
  bool get isDark => switch (_mode) {
        AstraMode.light => false,
        AstraMode.dark => true,
        AstraMode.system =>
          WidgetsBinding.instance.platformDispatcher.platformBrightness == Brightness.dark,
      };

  /// The palette actually applied app-wide: the preset resolved to the current
  /// brightness.
  AstraPalette get palette => isDark ? _base.dark : _base;

  Future<void> setPreset(AstraPalette p) async {
    if (p.id == _base.id) return;
    _base = p;
    notifyListeners();
    await _storage.setPresetId(p.id);
  }

  Future<void> setMode(AstraMode m) async {
    if (m == _mode) return;
    _mode = m;
    notifyListeners();
    await _storage.setThemeMode(m.id);
  }

  /// When following the system and the OS flips light/dark, re-skin live.
  @override
  void didChangePlatformBrightness() {
    if (_mode == AstraMode.system) notifyListeners();
    super.didChangePlatformBrightness();
  }

  @override
  void dispose() {
    WidgetsBinding.instance.removeObserver(this);
    super.dispose();
  }
}
