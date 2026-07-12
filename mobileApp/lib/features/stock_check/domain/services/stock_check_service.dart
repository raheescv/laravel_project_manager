import 'package:invo/shared/api/end_points.dart';
import 'package:invo/shared/domain/constants/global_variables.dart';
import 'package:invo/shared/utils/router/http_utils/http_service.dart';

import '../models/stock_check_models.dart';
import '../repository/stock_check_repository.dart';

class StockCheckService implements StockCheckRepository {
  HttpService get _http => serviceLocator<HttpService>();

  @override
  Future<StockCheckListPage> list({String? status, String? search, int page = 1}) async {
    final data = await _http.get(EndPoints.stockCheck, query: {
      'page': page,
      if (status != null && status.isNotEmpty) 'status': status,
      if (search != null && search.isNotEmpty) 'search': search,
    });
    return StockCheckListPage.fromJson(Map<String, dynamic>.from(data));
  }

  @override
  Future<StockCheckCreateResult> create({
    required int branchId,
    required String date,
    required String title,
    String? description,
  }) async {
    final data = await _http.post(EndPoints.stockCheck, body: {
      'branch_id': branchId,
      'date': date,
      'title': title,
      if (description != null && description.isNotEmpty) 'description': description,
    });
    return StockCheckCreateResult.fromJson(Map<String, dynamic>.from(data));
  }

  @override
  Future<StockCheckDetail> show(int id) async {
    final data = await _http.get(EndPoints.stockCheckById(id));
    return StockCheckDetail.fromJson(Map<String, dynamic>.from(data));
  }

  @override
  Future<StockCheckItemsPage> items(
    int id, {
    String? status,
    String? search,
    String? differenceCondition,
    int page = 1,
  }) async {
    final data = await _http.get(EndPoints.stockCheckItems(id), query: {
      'page': page,
      'per_page': 20,
      if (status != null && status.isNotEmpty) 'status': status,
      if (search != null && search.isNotEmpty) 'search': search,
      if (differenceCondition != null && differenceCondition.isNotEmpty)
        'difference_condition': differenceCondition,
    });
    return StockCheckItemsPage.fromJson(Map<String, dynamic>.from(data));
  }

  @override
  Future<ScanResult> scan(int id, String barcode) async {
    final data = await _http.post(EndPoints.stockCheckScan(id), body: {'barcode': barcode});
    return ScanResult.fromJson(Map<String, dynamic>.from(data));
  }

  @override
  Future<void> saveCounts(int id, List<Map<String, dynamic>> items) async {
    await _http.put(EndPoints.stockCheckById(id), body: {'items': items});
  }
}
