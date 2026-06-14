import 'package:flutter/foundation.dart';

import '../core/storage.dart';
import '../theme/palette.dart';

/// Holds the active colour preset and persists the choice. Changing the preset
/// re-skins the whole app instantly (the Settings "Colour preset" behaviour).
class ThemeController extends ChangeNotifier {
  ThemeController(this._storage) {
    _palette = AstraPresets.byId(_storage.presetId);
  }

  final Storage _storage;
  late AstraPalette _palette;

  AstraPalette get palette => _palette;

  Future<void> setPreset(AstraPalette p) async {
    if (p.id == _palette.id) return;
    _palette = p;
    notifyListeners();
    await _storage.setPresetId(p.id);
  }
}
