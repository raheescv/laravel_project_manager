part of 'funnel_cubit.dart';

/// Where the customer is in `category → size → brand → results`.
enum FunnelStep { category, size, brand, results }

class FunnelState extends Equatable {
  const FunnelState({
    this.categoriesStatus = DataFetchStatus.idle,
    this.sizesStatus = DataFetchStatus.idle,
    this.brandsStatus = DataFetchStatus.idle,
    this.categories = const [],
    this.sizes = const [],
    this.brands = const [],
    this.category,
    this.size,
    this.brand,
    this.inStockOnly = true,
    this.errorMessage,
  });

  final DataFetchStatus categoriesStatus;
  final DataFetchStatus sizesStatus;
  final DataFetchStatus brandsStatus;

  final List<CategoryOption> categories;
  final List<SizeOption> sizes;
  final List<BrandOption> brands;

  final CategoryOption? category;

  /// The chosen size label, or null when the step was skipped.
  final String? size;
  final BrandOption? brand;

  /// "Only what is on the shelf here", carried across every step rather than
  /// living in the results filter panel. A customer who has said they want
  /// stock does not mean it for one screen — and a size run or a brand list
  /// counting things the shop cannot sell today sends them to an empty rail.
  final bool inStockOnly;

  final String? errorMessage;

  List<SizeOption> get youngSizes =>
      sizes.where((s) => s.group == SizeGroup.young).toList(growable: false);

  List<SizeOption> get adultSizes =>
      sizes.where((s) => s.group == SizeGroup.adult).toList(growable: false);

  int get productsInSize => sizes
      .where((s) => s.size == size)
      .fold<int>(0, (sum, s) => sum + s.productCount);

  int get brandTotal => brands.fold<int>(0, (sum, b) => sum + b.productCount);

  /// The furthest step the customer has reached — drives the funnel column.
  FunnelStep get step {
    if (category == null) return FunnelStep.category;
    if (size == null) return FunnelStep.size;
    if (brand == null) return FunnelStep.brand;
    return FunnelStep.results;
  }

  FunnelState copyWith({
    DataFetchStatus? categoriesStatus,
    DataFetchStatus? sizesStatus,
    DataFetchStatus? brandsStatus,
    List<CategoryOption>? categories,
    List<SizeOption>? sizes,
    List<BrandOption>? brands,
    CategoryOption? category,
    String? size,
    BrandOption? brand,
    bool? inStockOnly,
    String? errorMessage,
    bool clearError = false,
    bool clearCategory = false,
    bool clearSize = false,
    bool clearBrand = false,
  }) =>
      FunnelState(
        categoriesStatus: categoriesStatus ?? this.categoriesStatus,
        sizesStatus: sizesStatus ?? this.sizesStatus,
        brandsStatus: brandsStatus ?? this.brandsStatus,
        categories: categories ?? this.categories,
        sizes: sizes ?? this.sizes,
        brands: brands ?? this.brands,
        category: clearCategory ? null : (category ?? this.category),
        size: clearSize ? null : (size ?? this.size),
        brand: clearBrand ? null : (brand ?? this.brand),
        inStockOnly: inStockOnly ?? this.inStockOnly,
        errorMessage: clearError ? null : (errorMessage ?? this.errorMessage),
      );

  @override
  List<Object?> get props => [
        categoriesStatus,
        sizesStatus,
        brandsStatus,
        categories,
        sizes,
        brands,
        category,
        size,
        brand,
        inStockOnly,
        errorMessage,
      ];
}
