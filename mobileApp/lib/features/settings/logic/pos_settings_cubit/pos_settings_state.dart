part of 'pos_settings_cubit.dart';

/// State for [PosSettingsCubit] — every value is device-local (§5 shape).
class PosSettingsState extends Equatable {
  const PosSettingsState({
    this.lockAfterSale = true,
    this.gridColumns = defaultGridColumns,
    this.askClientOnNewSale = true,
    this.showTip = true,
    this.startScreen = StartScreen.home,
  });

  /// Tile counts a till can choose between for the New Sale catalog grid.
  /// Three and four suit a catalog without photos, where the tile is mostly
  /// name and price and two-up just wastes the screen.
  static const List<int> gridColumnOptions = [2, 3, 4];
  static const int defaultGridColumns = 2;

  final bool lockAfterSale;
  final int gridColumns;

  /// Whether New Sale opens the client form on a fresh ticket. On by default —
  /// that is how the screen has always behaved.
  final bool askClientOnNewSale;

  /// Whether this device offers the tip row at Review & Pay. On by default, and
  /// only ever a veto — the web's "Enable Tip" still has the final say, so this
  /// being true does not make the row appear where the business turned it off.
  final bool showTip;

  /// Where sign-in — and an unlock, which is the same landing — puts the user.
  final StartScreen startScreen;

  PosSettingsState copyWith({
    bool? lockAfterSale,
    int? gridColumns,
    bool? askClientOnNewSale,
    bool? showTip,
    StartScreen? startScreen,
  }) =>
      PosSettingsState(
        lockAfterSale: lockAfterSale ?? this.lockAfterSale,
        gridColumns: gridColumns ?? this.gridColumns,
        askClientOnNewSale: askClientOnNewSale ?? this.askClientOnNewSale,
        showTip: showTip ?? this.showTip,
        startScreen: startScreen ?? this.startScreen,
      );

  @override
  List<Object?> get props =>
      [lockAfterSale, gridColumns, askClientOnNewSale, showTip, startScreen];
}
