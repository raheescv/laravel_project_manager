import 'dart:typed_data';

import 'package:invo/features/catalog/domain/models/catalog_models.dart';
import 'package:invo/features/catalog/domain/repository/catalog_repository.dart';
import 'package:invo/shared/logic/paginated_list_cubit/paginated_list_cubit.dart';

/// In-memory Settings → Catalog backend: a few products and services, with
/// every list query and saved payload recorded for assertions.
class FakeCatalogRepository implements CatalogRepository {
  final List<CatalogQuery> queries = [];
  final List<Map<String, dynamic>> saved = [];
  ProductConfig config = const ProductConfig(barcodeTypedPerProduct: true, barcodePrefix: 'LS');

  static Map<String, dynamic> _row(int id, String type, String name, {String status = 'active', bool fav = false}) => {
        'id': id,
        'type': type,
        'name': name,
        'name_arabic': 'اسم',
        'code': 'C-$id',
        'barcode': 'LS000$id',
        'department': 'Beauty',
        'main_category': 'Makeup',
        'sub_category': 'Lips',
        'unit': 'Pcs',
        'brand': 'Lumière',
        'size': '4g',
        'cost': 42.5,
        'mrp': 85,
        'priority': 3,
        'time': 45,
        'status': status,
        'is_selling': true,
        'is_favorite': fav,
        'thumbnail': '',
      };

  @override
  Future<PageResult> list(CatalogType type, CatalogQuery query, {int page = 1}) async {
    queries.add(query);
    final rows = [
      _row(124, type.key, 'Velvet Matte Lipstick With A Very Long Name', fav: true),
      _row(131, type.key, 'Argan Repair Shampoo'),
      _row(166, type.key, 'Silk Pillowcase', status: 'disabled'),
    ];
    return PageResult(rows: rows, total: rows.length, summary: const {'total': 3, 'selling': 2, 'disabled': 1, 'avg_time': 58});
  }

  @override
  Future<CatalogOptions> options() async => const CatalogOptions(
        departments: [CatalogOption(id: 1, name: 'Beauty')],
        mainCategories: [CatalogOption(id: 10, name: 'Makeup')],
        subCategories: [CatalogOption(id: 11, name: 'Lips', parentId: 10)],
        units: [CatalogOption(id: 5, name: 'Pcs')],
        brands: [CatalogOption(id: 7, name: 'Lumière')],
        defaultUnitId: 5,
        barcodeTypedPerProduct: true,
      );

  @override
  Future<CatalogItem> item(CatalogType type, int id) async => CatalogItem.fromJson({
        ..._row(id, type.key, 'Velvet Matte Lipstick'),
        'department_id': 1,
        'main_category_id': 10,
        'sub_category_id': 11,
        'unit_id': 5,
        'brand_id': 7,
        'tax': 5,
        'barcode_number': '000124',
        'images': const [],
      });

  @override
  Future<CatalogItem> save(CatalogType type, Map<String, dynamic> payload, {int? id}) async {
    saved.add(payload);
    return CatalogItem.fromJson({...payload, 'id': id ?? 900});
  }

  @override
  Future<void> delete(CatalogType type, int id) async {}

  @override
  Future<CatalogItem> addImage(CatalogType type, int id, Uint8List bytes, String filename) => item(type, id);

  @override
  Future<CatalogItem> removeImage(CatalogType type, int id, int imageId) => item(type, id);

  @override
  Future<ProductConfig> productConfig() async => config;

  @override
  Future<ProductConfig> updateProductConfig(ProductConfig next) async => config = next;
}
