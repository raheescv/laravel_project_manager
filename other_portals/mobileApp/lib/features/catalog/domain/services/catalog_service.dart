import 'dart:typed_data';

import 'package:invo/shared/api/end_points.dart';
import 'package:invo/shared/domain/constants/global_variables.dart';
import 'package:invo/shared/domain/helpers/formatters.dart';
import 'package:invo/shared/logic/paginated_list_cubit/paginated_list_cubit.dart';
import 'package:invo/shared/utils/router/http_utils/http_service.dart';

import '../models/catalog_models.dart';
import '../repository/catalog_repository.dart';

class CatalogService implements CatalogRepository {
  HttpService get _http => serviceLocator<HttpService>();

  Map<String, dynamic> _map(dynamic data) => Map<String, dynamic>.from(data as Map);

  @override
  Future<PageResult> list(CatalogType type, CatalogQuery query, {int page = 1}) async {
    final data = _map(await _http.get(EndPoints.catalog(type.key), query: query.toQuery(page)));
    final pagination = _map(data['pagination'] ?? const {});
    final summary = _map(data['summary'] ?? const {});
    return PageResult(
      rows: [
        for (final r in (data['data'] as List<dynamic>? ?? const []))
          if (r is Map) Map<String, dynamic>.from(r),
      ],
      currentPage: asNum(pagination['current_page']).toInt().clamp(1, 1 << 30),
      lastPage: asNum(pagination['last_page']).toInt().clamp(1, 1 << 30),
      total: asNum(pagination['total']).toInt(),
      summary: {for (final e in summary.entries) e.key: asNum(e.value)},
    );
  }

  @override
  Future<CatalogOptions> options() async => CatalogOptions.fromJson(_map(await _http.get(EndPoints.catalogOptions)));

  @override
  Future<CatalogItem> item(CatalogType type, int id) async =>
      CatalogItem.fromJson(_map(await _http.get(EndPoints.catalogItem(type.key, id))));

  @override
  Future<CatalogItem> save(CatalogType type, Map<String, dynamic> payload, {int? id}) async {
    final data = id == null
        ? await _http.post(EndPoints.catalog(type.key), body: payload)
        : await _http.put(EndPoints.catalogItem(type.key, id), body: payload);
    return CatalogItem.fromJson(_map(data));
  }

  @override
  Future<void> delete(CatalogType type, int id) => _http.delete(EndPoints.catalogItem(type.key, id));

  @override
  Future<CatalogItem> addImage(CatalogType type, int id, Uint8List bytes, String filename) async =>
      CatalogItem.fromJson(_map(await _http.postFileBytes(EndPoints.catalogImages(type.key, id),
          field: 'image', bytes: bytes, filename: filename)));

  @override
  Future<CatalogItem> removeImage(CatalogType type, int id, int imageId) async =>
      CatalogItem.fromJson(_map(await _http.delete(EndPoints.catalogImage(type.key, id, imageId))));

  @override
  Future<ProductConfig> productConfig() async => ProductConfig.fromJson(_map(await _http.get(EndPoints.productSettings)));

  @override
  Future<ProductConfig> updateProductConfig(ProductConfig config) async =>
      ProductConfig.fromJson(_map(await _http.put(EndPoints.productSettings, body: config.toJson())));
}
