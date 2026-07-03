import 'package:flutter/material.dart';

import 'palette.dart';

/// Preset-independent colours. The app's *themeable* colours live on
/// [AstraPalette] (read via `context.astra`); [ColorManager] only holds the few
/// constants that are the same across every preset (status colours, pure
/// black/white) so widgets never reach for raw `Color(0xFF…)` literals.
class ColorManager {
  ColorManager._();

  static const Color success = AstraPalette.success;
  static const Color danger = AstraPalette.danger;
  static const Color white = Colors.white;
  static const Color black = Colors.black;
  static const Color transparent = Colors.transparent;
}
