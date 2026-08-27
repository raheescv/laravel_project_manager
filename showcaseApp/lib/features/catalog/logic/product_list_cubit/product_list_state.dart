part of 'product_list_cubit.dart';

/// Everything narrowing the list. Held as one value so a change is one emit and
/// the "N filters active" count has a single source.
class ProductFilters extends Equatable {
  const ProductFilters({
    this.mainCategoryId,
    this.brandId,
    this.size,
    this.color,
    this.search,
    this.minPrice,
    this.maxPrice,
    this.inStockOnly = false,
    this.spinOnly = false,
    this.sortBy = 'name',
    this.sortDirection = 'asc',
  });

  final int? mainCategoryId;
  final int? brandId;
  final String? size;
  final String? color;
  final String? search;
  final double? minPrice;
  final double? maxPrice;
  final bool inStockOnly;

  /// Client-side: the API has no "has spin frames" filter, and `images360` is
  /// not on the list payload, so this narrows what came back rather than what
  /// was asked for. It is labelled as a refinement in the UI for that reason.
  final bool spinOnly;

  final String sortBy;
  final String sortDirection;

  /// What the filter chip counts — the funnel's own choices are shown as
  /// breadcrumbs and are deliberately excluded.
  int get activeCount => [
        mainCategoryId != null,
        color != null && color!.isNotEmpty,
        minPrice != null || maxPrice != null,
        inStockOnly,
        spinOnly,
      ].where((e) => e).length;

  ProductFilters copyWith({
    int? mainCategoryId,
    int? brandId,
    String? size,
    String? color,
    String? search,
    double? minPrice,
    double? maxPrice,
    bool? inStockOnly,
    bool? spinOnly,
    String? sortBy,
    String? sortDirection,
    bool clearBrand = false,
    bool clearCategory = false,
    bool clearSize = false,
    bool clearColor = false,
    bool clearSearch = false,
    bool clearPrice = false,
  }) =>
      ProductFilters(
        mainCategoryId: clearCategory ? null : (mainCategoryId ?? this.mainCategoryId),
        brandId: clearBrand ? null : (brandId ?? this.brandId),
        size: clearSize ? null : (size ?? this.size),
        color: clearColor ? null : (color ?? this.color),
        search: clearSearch ? null : (search ?? this.search),
        minPrice: clearPrice ? null : (minPrice ?? this.minPrice),
        maxPrice: clearPrice ? null : (maxPrice ?? this.maxPrice),
        inStockOnly: inStockOnly ?? this.inStockOnly,
        spinOnly: spinOnly ?? this.spinOnly,
        sortBy: sortBy ?? this.sortBy,
        sortDirection: sortDirection ?? this.sortDirection,
      );

  @override
  List<Object?> get props => [
        mainCategoryId,
        brandId,
        size,
        color,
        search,
        minPrice,
        maxPrice,
        inStockOnly,
        spinOnly,
        sortBy,
        sortDirection,
      ];
}

class ProductListState extends Equatable {
  const ProductListState({
    this.status = DataFetchStatus.idle,
    this.filters = const ProductFilters(),
    this.items = const [],
    this.colors = const [],
    this.categories = const [],
    this.page = 1,
    this.lastPage = 1,
    this.total = 0,
    this.hasMore = false,
    this.loadingMore = false,
    this.errorMessage,
  });

  final DataFetchStatus status;
  final ProductFilters filters;
  final List<Product> items;
  final List<ColorOption> colors;

  /// Departments to filter by. Since the funnel dropped its category step,
  /// this is the only place a customer can narrow by department — and the
  /// counts are scoped to the size they already chose.
  final List<CategoryOption> categories;
  final int page;
  final int lastPage;
  final int total;
  final bool hasMore;
  final bool loadingMore;
  final String? errorMessage;

  /// [total] is the server's count for the query; when a client-side refinement
  /// is on it no longer describes what is on screen.
  bool get totalIsExact => !filters.spinOnly;

  ProductListState copyWith({
    DataFetchStatus? status,
    ProductFilters? filters,
    List<Product>? items,
    List<ColorOption>? colors,
    List<CategoryOption>? categories,
    int? page,
    int? lastPage,
    int? total,
    bool? hasMore,
    bool? loadingMore,
    String? errorMessage,
    bool clearError = false,
  }) =>
      ProductListState(
        status: status ?? this.status,
        filters: filters ?? this.filters,
        items: items ?? this.items,
        colors: colors ?? this.colors,
        categories: categories ?? this.categories,
        page: page ?? this.page,
        lastPage: lastPage ?? this.lastPage,
        total: total ?? this.total,
        hasMore: hasMore ?? this.hasMore,
        loadingMore: loadingMore ?? this.loadingMore,
        errorMessage: clearError ? null : (errorMessage ?? this.errorMessage),
      );

  @override
  List<Object?> get props => [
        status,
        filters,
        items,
        colors,
        categories,
        page,
        lastPage,
        total,
        hasMore,
        loadingMore,
        errorMessage,
      ];
}
