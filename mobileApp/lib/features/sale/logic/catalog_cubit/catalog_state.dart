part of 'catalog_cubit.dart';

/// State for [CatalogCubit] — the §5 shape.
class CatalogState extends Equatable {
  const CatalogState({
    this.status = DataFetchStatus.idle,
    this.products = const [],
    this.categories = const [],
    this.selectedCategoryId,
    this.selectedType,
    this.search = '',
    this.page = 1,
    this.lastPage = 1,
    this.loadingMore = false,
    this.errorMessage,
  });

  final DataFetchStatus status;
  final List<Product> products;
  final List<Category> categories;
  final int? selectedCategoryId;

  /// POS Product/Service filter: null = All Types, `product`, `service`.
  final String? selectedType;
  final String search;
  final int page;
  final int lastPage;
  final bool loadingMore;
  final String? errorMessage;

  bool get loading => status == DataFetchStatus.waiting;

  /// The catalog has been fetched at least once for the current type/branch.
  bool get loaded => status == DataFetchStatus.success;
  bool get hasMore => page < lastPage;
  bool get isEmpty => products.isEmpty;

  CatalogState copyWith({
    DataFetchStatus? status,
    List<Product>? products,
    List<Category>? categories,
    int? selectedCategoryId,
    String? selectedType,
    String? search,
    int? page,
    int? lastPage,
    bool? loadingMore,
    String? errorMessage,
    bool clearError = false,
    bool clearCategory = false,
    bool clearType = false,
  }) =>
      CatalogState(
        status: status ?? this.status,
        products: products ?? this.products,
        categories: categories ?? this.categories,
        selectedCategoryId:
            clearCategory ? null : (selectedCategoryId ?? this.selectedCategoryId),
        selectedType: clearType ? null : (selectedType ?? this.selectedType),
        search: search ?? this.search,
        page: page ?? this.page,
        lastPage: lastPage ?? this.lastPage,
        loadingMore: loadingMore ?? this.loadingMore,
        errorMessage: clearError ? null : (errorMessage ?? this.errorMessage),
      );

  @override
  List<Object?> get props => [
        status, products, categories, selectedCategoryId, selectedType,
        search, page, lastPage, loadingMore, errorMessage,
      ];
}
