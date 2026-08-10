part of 'pos_settings_cubit.dart';

/// State for [PosSettingsCubit] — every value is device-local (§5 shape).
class PosSettingsState extends Equatable {
  const PosSettingsState({
    this.lockAfterSale = false,
    this.gridColumns = defaultGridColumns,
  });

  /// Tile counts a till can choose between for the New Sale catalog grid.
  /// Three and four suit a catalog without photos, where the tile is mostly
  /// name and price and two-up just wastes the screen.
  static const List<int> gridColumnOptions = [2, 3, 4];
  static const int defaultGridColumns = 2;

  final bool lockAfterSale;
  final int gridColumns;

  PosSettingsState copyWith({bool? lockAfterSale, int? gridColumns}) =>
      PosSettingsState(
        lockAfterSale: lockAfterSale ?? this.lockAfterSale,
        gridColumns: gridColumns ?? this.gridColumns,
      );

  @override
  List<Object?> get props => [lockAfterSale, gridColumns];
}
