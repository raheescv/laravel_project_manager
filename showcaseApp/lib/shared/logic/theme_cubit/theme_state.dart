part of 'theme_cubit.dart';

class ThemeSettings extends Equatable {
  const ThemeSettings({
    this.mode = ThemeMode.system,
    this.light = ThemePreset.aurora,
    this.dark = ThemePreset.aurora,
    this.textScale = 1,
    this.typeface = TypePreset.jost,
  });

  final ThemeMode mode;

  /// The preset dressing each mode. Which one is on screen depends on [mode]
  /// and, under `system`, on what the device is doing.
  final ThemePreset light;
  final ThemePreset dark;

  /// Multiplier on every text size in the app. A shop tablet is read at arm's
  /// length across a counter, which is further away than a phone in the hand.
  final double textScale;

  /// The typeface pairing every label is set in.
  final TypePreset typeface;

  ThemeSettings copyWith({
    ThemeMode? mode,
    ThemePreset? light,
    ThemePreset? dark,
    double? textScale,
    TypePreset? typeface,
  }) =>
      ThemeSettings(
        mode: mode ?? this.mode,
        light: light ?? this.light,
        dark: dark ?? this.dark,
        textScale: textScale ?? this.textScale,
        typeface: typeface ?? this.typeface,
      );

  @override
  List<Object?> get props => [mode, light, dark, textScale, typeface];
}
