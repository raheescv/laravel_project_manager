part of 'pos_settings_cubit.dart';

/// How New Sale draws the category rail pinned above the product grid.
///
/// Categories carry a photo, so the rail can be anything from a text chip to a
/// photo tile — but it is *pinned*, so every point it grows is a point of
/// catalog the till never gets back. That trade is the whole choice, which is
/// why [railHeight] is part of the option rather than buried in the widget.
///
/// A category with no photo falls back to a tinted initial, so a shop halfway
/// through photographing its catalog still gets a rail with no gaps in it.
enum CategoryDisplay {
  nameOnly('name_only', 'Name only', 'Text chips — the shortest rail', 36),
  avatar('avatar', 'Name with photo', 'A round photo inside each chip', 42),
  card('card', 'Photo card', 'A large photo with the name beneath it', 84),
  tile('tile', 'Photo tile', 'A wide photo with the name over it', 66);

  const CategoryDisplay(this.key, this.label, this.blurb, this.railHeight);

  /// Stored in local storage — never rename a value, older installs hold them.
  final String key;
  final String label;
  final String blurb;

  /// Height of the rail itself, in logical pixels (its padding is on top).
  final double railHeight;

  bool get showsImage => this != CategoryDisplay.nameOnly;

  static CategoryDisplay fromKey(String? k) => CategoryDisplay.values
      .firstWhere((d) => d.key == k, orElse: () => CategoryDisplay.nameOnly);
}

/// State for [PosSettingsCubit] — every value is device-local (§5 shape).
class PosSettingsState extends Equatable {
  const PosSettingsState({
    this.lockAfterSale = true,
    this.gridColumns = defaultGridColumns,
    this.askClientOnNewSale = true,
    this.showTip = true,
    this.startScreen = StartScreen.home,
    this.categoryDisplay = CategoryDisplay.nameOnly,
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

  /// How the New Sale category rail is drawn. Text chips by default — that is
  /// the rail this screen has always had, and the one that costs the catalog
  /// the least room.
  final CategoryDisplay categoryDisplay;

  PosSettingsState copyWith({
    bool? lockAfterSale,
    int? gridColumns,
    bool? askClientOnNewSale,
    bool? showTip,
    StartScreen? startScreen,
    CategoryDisplay? categoryDisplay,
  }) =>
      PosSettingsState(
        lockAfterSale: lockAfterSale ?? this.lockAfterSale,
        gridColumns: gridColumns ?? this.gridColumns,
        askClientOnNewSale: askClientOnNewSale ?? this.askClientOnNewSale,
        showTip: showTip ?? this.showTip,
        startScreen: startScreen ?? this.startScreen,
        categoryDisplay: categoryDisplay ?? this.categoryDisplay,
      );

  @override
  List<Object?> get props => [
        lockAfterSale,
        gridColumns,
        askClientOnNewSale,
        showTip,
        startScreen,
        categoryDisplay,
      ];
}
