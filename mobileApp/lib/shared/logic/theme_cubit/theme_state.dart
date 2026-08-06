part of 'theme_cubit.dart';

/// State for [ThemeCubit] — the §5 shape.
///
/// [platformIsDark] is part of the state rather than read live, because in
/// `AstraMode.system` the OS brightness is a real input to [palette]: with an
/// `Equatable` state, an emit that did not change any field would not rebuild,
/// so the brightness has to be a field for a system-theme flip to take effect.
class ThemeState extends Equatable {
  const ThemeState({
    required this.preset,
    required this.mode,
    required this.typeface,
    required this.platformIsDark,
  });

  final AstraPalette preset;
  final AstraMode mode;
  final AstraTypeface typeface;
  final bool platformIsDark;

  bool get isDark => switch (mode) {
        AstraMode.light => false,
        AstraMode.dark => true,
        AstraMode.system => platformIsDark,
      };

  /// The preset resolved against the effective brightness — what the app skins from.
  AstraPalette get palette => isDark ? preset.dark : preset;

  ThemeState copyWith({
    AstraPalette? preset,
    AstraMode? mode,
    AstraTypeface? typeface,
    bool? platformIsDark,
  }) =>
      ThemeState(
        preset: preset ?? this.preset,
        mode: mode ?? this.mode,
        typeface: typeface ?? this.typeface,
        platformIsDark: platformIsDark ?? this.platformIsDark,
      );

  // Presets and typefaces are identified by id — comparing the palette objects
  // themselves would depend on their identity.
  @override
  List<Object?> get props => [preset.id, mode, typeface.id, platformIsDark];
}
