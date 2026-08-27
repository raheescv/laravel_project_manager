part of 'theme_cubit.dart';

class ThemeSettings extends Equatable {
  const ThemeSettings({
    this.mode = ThemeMode.system,
    this.light = ThemePreset.aurora,
    this.dark = ThemePreset.aurora,
  });

  final ThemeMode mode;

  /// The preset dressing each mode. Which one is on screen depends on [mode]
  /// and, under `system`, on what the device is doing.
  final ThemePreset light;
  final ThemePreset dark;

  ThemeSettings copyWith({ThemeMode? mode, ThemePreset? light, ThemePreset? dark}) =>
      ThemeSettings(
        mode: mode ?? this.mode,
        light: light ?? this.light,
        dark: dark ?? this.dark,
      );

  @override
  List<Object?> get props => [mode, light, dark];
}
