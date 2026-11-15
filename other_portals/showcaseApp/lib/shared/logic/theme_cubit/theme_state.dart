part of 'theme_cubit.dart';

class ThemeSettings extends Equatable {
  const ThemeSettings({
    this.mode = ThemeMode.system,
    this.light = ThemePreset.aurora,
    this.dark = ThemePreset.aurora,
    this.textScale = 1,
    this.typeface = TypePreset.jost,
    this.sizeColumns = ThemeCubit.defaultSizeColumns,
    this.productColumns = ThemeCubit.defaultProductColumns,
    this.idleMinutes = ThemeCubit.defaultIdleMinutes,
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

  /// How many chips a row of the size run holds.
  ///
  /// A number rather than a rule, because the right answer is the shop's: the
  /// same 11" tablet is a counter-top display in one branch and a handheld in
  /// another, and the size run is the screen everyone starts on.
  final int sizeColumns;

  /// How many product tiles a row of the results grid holds.
  ///
  /// This was read off the painted width, and the width could not answer it:
  /// the panel and a handheld report much the same number, and the tile that
  /// suits one is wrong on the other. Appearance asks instead.
  final int productColumns;

  /// Minutes of nobody touching the panel before it returns to the start and
  /// forgets the last customer.
  final int idleMinutes;

  ThemeSettings copyWith({
    ThemeMode? mode,
    ThemePreset? light,
    ThemePreset? dark,
    double? textScale,
    TypePreset? typeface,
    int? sizeColumns,
    int? productColumns,
    int? idleMinutes,
  }) =>
      ThemeSettings(
        mode: mode ?? this.mode,
        light: light ?? this.light,
        dark: dark ?? this.dark,
        textScale: textScale ?? this.textScale,
        typeface: typeface ?? this.typeface,
        sizeColumns: sizeColumns ?? this.sizeColumns,
        productColumns: productColumns ?? this.productColumns,
        idleMinutes: idleMinutes ?? this.idleMinutes,
      );

  @override
  List<Object?> get props => [
        mode,
        light,
        dark,
        textScale,
        typeface,
        sizeColumns,
        productColumns,
        idleMinutes,
      ];
}
