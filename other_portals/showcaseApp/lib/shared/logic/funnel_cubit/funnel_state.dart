part of 'funnel_cubit.dart';

/// Where the customer is in `size → brand → results`.
///
/// Size leads because it is the one answer a customer always has and the one
/// that eliminates the most: most of the catalogue does not exist in their
/// size. Brand follows, and both are skippable — plenty of people know the
/// brand but not the size, or the reverse.
enum FunnelStep { size, brand, results }

class FunnelState extends Equatable {
  const FunnelState({
    this.sizesStatus = DataFetchStatus.idle,
    this.brandsStatus = DataFetchStatus.idle,
    this.sizes = const [],
    this.brands = const [],
    this.chosenSizes = const [],
    this.brand,
    this.inStockOnly = true,
    this.errorMessage,
  });

  final DataFetchStatus sizesStatus;
  final DataFetchStatus brandsStatus;

  final List<SizeOption> sizes;
  final List<BrandOption> brands;

  /// The sizes the customer has picked, in the order they appear in the run.
  /// Empty means every size — nothing narrowed yet, or the step skipped.
  ///
  /// A list because people shop for more than one foot: a parent buying for
  /// two children, or someone between sizes who wants to see both.
  final List<String> chosenSizes;

  bool isChosen(String size) => chosenSizes.contains(size);

  /// The chosen sizes as one short line — "44 · 43 · 42", and past [max] the
  /// rest as a count ("44 · 43 · 42 +2") so a crumb never runs off the bar.
  String chosenSizesLabel({int max = 3}) {
    if (chosenSizes.length <= max) return chosenSizes.join(' · ');
    return '${chosenSizes.take(max).join(' · ')} +${chosenSizes.length - max}';
  }
  final BrandOption? brand;

  /// "Only what is on the shelf here", carried across every step rather than
  /// living in the results filter panel. A customer who has said they want
  /// stock does not mean it for one screen — and a size run or a brand list
  /// counting things the shop cannot sell today sends them to an empty rail.
  final bool inStockOnly;

  final String? errorMessage;

  /// The size run, as one list.
  ///
  /// The server groups sizes into young and adult, but a customer looking for
  /// their size does not think in those terms — they scan for a number, and
  /// splitting the run in two means scanning twice and knowing which half to
  /// scan. So the groups are merged and sorted as a single descending run.
  ///
  /// With [inStockOnly] on, sizes with nothing on the shelf are dropped rather
  /// than struck through: the customer has asked to be shown only what they can
  /// walk out with, and a greyed-out chip is still an offer.
  List<SizeOption> get visibleSizes {
    final rows = sizes.where((s) => !inStockOnly || s.inStock).toList()
      ..sort(_bySizeDescending);
    return List.unmodifiable(rows);
  }

  /// Numeric sizes first, largest to smallest, then the lettered ones. Mixing
  /// "44.5" and "XL" in one comparison has no natural answer, so the two kinds
  /// are kept apart rather than interleaved arbitrarily.
  static int _bySizeDescending(SizeOption a, SizeOption b) =>
      compareSizeLabels(a.size, b.size);

  /// [_bySizeDescending] on bare labels, so the chosen sizes can be kept in
  /// the same order as the run they were picked from.
  static int compareSizeLabels(String a, String b) {
    final x = double.tryParse(a);
    final y = double.tryParse(b);
    if (x != null && y != null) return y.compareTo(x);
    if (x != null) return -1;
    if (y != null) return 1;
    return a.compareTo(b);
  }

  /// The furthest step the customer has reached — drives the breadcrumbs.
  FunnelStep get step {
    if (chosenSizes.isEmpty) return FunnelStep.size;
    if (brand == null) return FunnelStep.brand;
    return FunnelStep.results;
  }

  FunnelState copyWith({
    DataFetchStatus? sizesStatus,
    DataFetchStatus? brandsStatus,
    List<SizeOption>? sizes,
    List<BrandOption>? brands,
    List<String>? chosenSizes,
    BrandOption? brand,
    bool? inStockOnly,
    String? errorMessage,
    bool clearError = false,
    bool clearSizes = false,
    bool clearBrand = false,
  }) =>
      FunnelState(
        sizesStatus: sizesStatus ?? this.sizesStatus,
        brandsStatus: brandsStatus ?? this.brandsStatus,
        sizes: sizes ?? this.sizes,
        brands: brands ?? this.brands,
        chosenSizes: clearSizes ? const [] : (chosenSizes ?? this.chosenSizes),
        brand: clearBrand ? null : (brand ?? this.brand),
        inStockOnly: inStockOnly ?? this.inStockOnly,
        errorMessage: clearError ? null : (errorMessage ?? this.errorMessage),
      );

  @override
  List<Object?> get props => [
        sizesStatus,
        brandsStatus,
        sizes,
        brands,
        chosenSizes,
        brand,
        inStockOnly,
        errorMessage,
      ];
}
