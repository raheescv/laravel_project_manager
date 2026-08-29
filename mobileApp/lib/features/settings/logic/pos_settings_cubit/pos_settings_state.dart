part of 'pos_settings_cubit.dart';

/// State for [PosSettingsCubit] — every value is device-local (§5 shape).
class PosSettingsState extends Equatable {
  const PosSettingsState({
    this.lockAfterSale = true,
    this.gridColumns = defaultGridColumns,
    this.startScreen = StartScreen.home,
  });

  /// Tile counts a till can choose between for the New Sale catalog grid.
  /// Three and four suit a catalog without photos, where the tile is mostly
  /// name and price and two-up just wastes the screen.
  static const List<int> gridColumnOptions = [2, 3, 4];
  static const int defaultGridColumns = 2;

  final bool lockAfterSale;
  final int gridColumns;

  /// Where sign-in — and an unlock, which is the same landing — puts the user.
  final StartScreen startScreen;

  PosSettingsState copyWith({bool? lockAfterSale, int? gridColumns, StartScreen? startScreen}) =>
      PosSettingsState(
        lockAfterSale: lockAfterSale ?? this.lockAfterSale,
        gridColumns: gridColumns ?? this.gridColumns,
        startScreen: startScreen ?? this.startScreen,
      );

  @override
  List<Object?> get props => [lockAfterSale, gridColumns, startScreen];
}
