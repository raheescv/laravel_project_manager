import '../models/stock_check_models.dart';

abstract class StockCheckRepository {
  Future<StockCheckListPage> list({String? status, String? search, int page});

  Future<StockCheckCreateResult> create({
    required int branchId,
    required String date,
    required String title,
    String? description,
  });

  Future<StockCheckDetail> show(int id);

  Future<StockCheckItemsPage> items(
    int id, {
    String? status,
    String? search,
    String? differenceCondition,
    int page,
  });

  Future<ScanResult> scan(int id, String barcode);

  /// Bulk-save counted quantities + per-item status. Completed items reconcile
  /// real inventory server-side.
  Future<void> saveCounts(int id, List<Map<String, dynamic>> items);
}
