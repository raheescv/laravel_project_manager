import 'dart:typed_data';

import 'package:invo/shared/logic/paginated_list_cubit/paginated_list_cubit.dart';

import '../models/catalog_models.dart';

/// Settings → Catalog: products and services, managed from the app.
abstract class CatalogRepository {
  /// One page of the table. Rows stay maps — every cell is a lookup by its
  /// column key — with the KPI counts in [PageResult.summary].
  Future<PageResult> list(CatalogType type, CatalogQuery query, {int page = 1});

  Future<CatalogOptions> options();

  Future<CatalogItem> item(CatalogType type, int id);

  /// Create (no [id]) or update.
  Future<CatalogItem> save(CatalogType type, Map<String, dynamic> payload, {int? id});

  Future<void> delete(CatalogType type, int id);

  Future<CatalogItem> addImage(CatalogType type, int id, Uint8List bytes, String filename);

  Future<CatalogItem> removeImage(CatalogType type, int id, int imageId);

  Future<ProductConfig> productConfig();

  Future<ProductConfig> updateProductConfig(ProductConfig config);
}
