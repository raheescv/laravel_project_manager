import '../models/index.dart';

/// Everything the showcase reads. One repository because the whole app is one
/// read path — the funnel, the product page and the store list all come from
/// the same public catalog API.
abstract class CatalogRepository {
  Future<List<CategoryOption>> categories();

  /// Both size runs for a category, already split into young / adult.
  Future<List<SizeOption>> sizes({int? mainCategoryId, int? brandId, int? branchId});

  Future<List<BrandOption>> brands({int? mainCategoryId, String? size, bool inStockOnly});

  Future<List<ColorOption>> colors();

  Future<Paginated<Product>> products({
    int? mainCategoryId,
    int? brandId,
    String? size,
    String? color,
    String? search,
    double? minPrice,
    double? maxPrice,
    bool inStockOnly,
    String sortBy,
    String sortDirection,
    int page,
    int perPage,
  });

  /// Full detail — the only place `images360`, `related_sizes` and the
  /// per-branch stock exist.
  Future<Product> product(int id);

  Future<Product> productByBarcode(String barcode);

  /// Same main category and brand, current product removed.
  ///
  /// There is no `/products/{id}/related` endpoint; this is composed from the
  /// list endpoint's own filters, which is why it lives behind the repository
  /// rather than in a screen.
  Future<List<Product>> related(Product product, {int limit});

  Future<List<Branch>> branches();

  Future<Branding> branding();
}
