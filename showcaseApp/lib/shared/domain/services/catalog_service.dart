import '../../api/end_points.dart';
import '../../utils/router/http_utils/http_service.dart';
import '../constants/global_variables.dart';
import '../helpers/formatters.dart';
import '../models/index.dart';
import '../../utils/router/http_utils/common_exception.dart';
import '../repository/catalog_repository.dart';

/// Concrete [CatalogRepository] over [HttpService].
class CatalogService implements CatalogRepository {
  HttpService get _http => serviceLocator<HttpService>();

  @override
  Future<List<CategoryOption>> categories({
    String? size,
    int? branchId,
    bool inStockOnly = true,
  }) async {
    // Scoped the same way /brands and /sizes are: the funnel asks for a size
    // first, so a category's count has to mean "in that size", not "in total".
    final data = await _http.get(EndPoints.categories, query: {
      if (size != null && size.isNotEmpty) 'size': size,
      if (branchId != null) 'branch_id': branchId,
      'available_products_only': inStockOnly,
    });
    return asMapList(data).map(CategoryOption.fromJson).toList(growable: false);
  }

  @override
  Future<List<SizeOption>> sizes({int? mainCategoryId, int? brandId, int? branchId}) async {
    final data = await _http.get(EndPoints.sizes, query: {
      if (mainCategoryId != null) 'main_category_id': mainCategoryId,
      if (brandId != null) 'brand_id': brandId,
      if (branchId != null) 'branch_id': branchId,
    });

    if (data is List) {
      // Oldest shape: a flat array with no grouping at all.
      return asMapList(data)
          .map((e) => SizeOption.fromJson(e, SizeGroup.adult))
          .toList(growable: false);
    }

    final map = data is Map ? Map<String, dynamic>.from(data) : const <String, dynamic>{};
    // `kids_sizes` / `other_sizes` are the older aliases the server still emits.
    final young = asMapList(map['young_sizes'] ?? map['kids_sizes']);
    final adult = asMapList(map['adult_sizes'] ?? map['other_sizes']);
    return [
      ...young.map((e) => SizeOption.fromJson(e, SizeGroup.young)),
      ...adult.map((e) => SizeOption.fromJson(e, SizeGroup.adult)),
    ];
  }

  @override
  Future<List<BrandOption>> brands({
    int? mainCategoryId,
    String? size,
    bool inStockOnly = true,
  }) async {
    final data = await _http.get(EndPoints.brands, query: {
      if (mainCategoryId != null) 'main_category_id': mainCategoryId,
      if (size != null && size.isNotEmpty) 'size': size,
      'available_products_only': inStockOnly,
    });
    return asMapList(data).map(BrandOption.fromJson).toList(growable: false);
  }

  @override
  Future<List<ColorOption>> colors() async {
    final data = await _http.get(EndPoints.colors);
    return asMapList(data)
        .map(ColorOption.fromJson)
        .where((c) => c.color.isNotEmpty)
        .toList(growable: false);
  }

  @override
  Future<Paginated<Product>> products({
    int? mainCategoryId,
    int? brandId,
    String? size,
    String? color,
    String? search,
    double? minPrice,
    double? maxPrice,
    bool inStockOnly = false,
    bool has360 = false,
    String sortBy = 'name',
    String sortDirection = 'asc',
    int page = 1,
    int perPage = 24,
  }) async {
    final data = await _http.get(EndPoints.products, query: {
      if (mainCategoryId != null) 'main_category_id': mainCategoryId,
      if (brandId != null) 'brand_id': brandId,
      if (size != null && size.isNotEmpty) 'size': size,
      if (color != null && color.isNotEmpty) 'color': color,
      if (search != null && search.isNotEmpty) 'search': search,
      if (minPrice != null) 'min_price': minPrice,
      if (maxPrice != null) 'max_price': maxPrice,
      'in_stock_only': inStockOnly,
      // Only sent when it is on: a server that predates this filter ignores an
      // unknown parameter, so the grid comes back unfiltered rather than empty
      // — and sending `false` would put a second key in the count cache for
      // every query that never asked the question.
      if (has360) 'has_360': true,
      'sort_by': sortBy,
      'sort_direction': sortDirection,
      'type': 'product',
      'page': page,
      // The server validates per_page at max:100 and 422s above it.
      'per_page': perPage.clamp(1, 100),
    });
    return Paginated.from(data, Product.fromJson);
  }

  @override
  Future<Product> product(int id) async {
    return _asProduct(await _http.get(EndPoints.productById(id)));
  }

  /// A 200 carrying `data: null` is a real answer from this API — a product
  /// that resolved to nothing. Casting it straight to a Map threw a TypeError
  /// that no caller catches, which left the page spinning instead of saying
  /// the product could not be loaded.
  static Product _asProduct(dynamic data) {
    if (data is! Map) throw ApiException('This product could not be read.');
    return Product.fromJson(Map<String, dynamic>.from(data));
  }

  @override
  Future<List<Product>> related(
    Product product, {
    int limit = 12,
    bool inStockOnly = true,
  }) async {
    final page = await products(
      mainCategoryId: product.mainCategory?.id,
      brandId: product.brand?.id,
      inStockOnly: inStockOnly,
      perPage: limit + 1,
    );
    final rows = page.items.where((p) => p.id != product.id).toList();
    if (rows.length > limit) return rows.sublist(0, limit);

    // A brand with only one style in the category would otherwise show an empty
    // rail, so widen to the category before giving up.
    if (rows.length < 4 && product.mainCategory != null) {
      final wider = await products(
        mainCategoryId: product.mainCategory?.id,
        inStockOnly: inStockOnly,
        perPage: limit + 1,
      );
      final seen = rows.map((p) => p.id).toSet()..add(product.id);
      for (final p in wider.items) {
        if (rows.length >= limit) break;
        if (seen.add(p.id)) rows.add(p);
      }
    }
    return rows;
  }

  @override
  Future<List<Branch>> branches() async {
    final data = await _http.get(EndPoints.branches);
    return asMapList(data).map(Branch.fromJson).toList(growable: false);
  }

  @override
  Future<Branding> branding() async {
    final data = await _http.get(EndPoints.branding);
    return Branding.fromJson(
      data is Map ? Map<String, dynamic>.from(data) : const <String, dynamic>{},
    );
  }
}
