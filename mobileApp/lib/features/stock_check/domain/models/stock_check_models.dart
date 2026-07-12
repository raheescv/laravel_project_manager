import 'package:invo/shared/domain/helpers/formatters.dart';

/// A stock check summary row on the list screen — carries the per-check
/// progress the API computes (counted / total items, variance, net difference).
class StockCheckSummary {
  StockCheckSummary({
    required this.id,
    required this.title,
    required this.date,
    required this.description,
    required this.status,
    required this.branchId,
    required this.branchName,
    required this.createdBy,
    required this.itemsTotal,
    required this.itemsCounted,
    required this.varianceCount,
    required this.netDifference,
    required this.progress,
  });

  final int id;
  final String title;
  final String date;
  final String description;
  final String status;
  final int branchId;
  final String branchName;
  final String createdBy;
  final int itemsTotal;
  final int itemsCounted;
  final int varianceCount;
  final double netDifference;
  final double progress;

  bool get isCompleted => status == 'completed';

  factory StockCheckSummary.fromJson(Map<String, dynamic> j) => StockCheckSummary(
        id: asNum(j['id']).toInt(),
        title: asStr(j['title']),
        date: asStr(j['date']),
        description: asStr(j['description']),
        status: asStr(j['status']),
        branchId: asNum(j['branch_id']).toInt(),
        branchName: asStr(j['branch_name']),
        createdBy: asStr(j['created_by']),
        itemsTotal: asNum(j['items_total']).toInt(),
        itemsCounted: asNum(j['items_counted']).toInt(),
        varianceCount: asNum(j['variance_count']).toInt(),
        netDifference: asNum(j['net_difference']).toDouble(),
        progress: asNum(j['progress']).toDouble(),
      );
}

/// A page of stock check summaries + pagination cursor.
class StockCheckListPage {
  StockCheckListPage({
    required this.rows,
    required this.currentPage,
    required this.lastPage,
    required this.total,
  });

  final List<StockCheckSummary> rows;
  final int currentPage;
  final int lastPage;
  final int total;

  factory StockCheckListPage.fromJson(Map<String, dynamic> j) {
    final data = (j['data'] as List? ?? const [])
        .map((e) => StockCheckSummary.fromJson(Map<String, dynamic>.from(e)))
        .toList();
    final pg = Map<String, dynamic>.from(j['pagination'] ?? const {});
    return StockCheckListPage(
      rows: data,
      currentPage: asNum(pg['current_page']).toInt() == 0 ? 1 : asNum(pg['current_page']).toInt(),
      lastPage: asNum(pg['last_page']).toInt() == 0 ? 1 : asNum(pg['last_page']).toInt(),
      total: asNum(pg['total']).toInt(),
    );
  }
}

/// The stock check header shown on the counting screen.
class StockCheckDetail {
  StockCheckDetail({
    required this.id,
    required this.title,
    required this.date,
    required this.status,
    required this.branchId,
    required this.branchName,
    this.itemsTotal = 0,
    this.itemsCounted = 0,
    this.varianceCount = 0,
    this.netDifference = 0,
    this.progress = 0,
  });

  final int id;
  final String title;
  final String date;
  final String status;
  final int branchId;
  final String branchName;
  final int itemsTotal;
  final int itemsCounted;
  final int varianceCount;
  final double netDifference;
  final double progress;

  factory StockCheckDetail.fromJson(Map<String, dynamic> j) => StockCheckDetail(
        id: asNum(j['id']).toInt(),
        title: asStr(j['title']),
        date: asStr(j['date']),
        status: asStr(j['status']),
        branchId: asNum(j['branch_id']).toInt(),
        branchName: asStr(j['branch_name']),
        itemsTotal: asNum(j['items_total']).toInt(),
        itemsCounted: asNum(j['items_counted']).toInt(),
        varianceCount: asNum(j['variance_count']).toInt(),
        netDifference: asNum(j['net_difference']).toDouble(),
        progress: asNum(j['progress']).toDouble(),
      );
}

/// One item to count. `physical` is the counted qty (editable), `recorded` the
/// system qty (read-only); `difference` = physical − recorded.
class StockCheckItem {
  StockCheckItem({
    required this.id,
    required this.productName,
    required this.productCode,
    required this.brandName,
    required this.categoryName,
    required this.barcode,
    required this.physical,
    required this.recorded,
    required this.status,
  });

  final int id;
  final String productName;
  final String productCode;
  final String brandName;
  final String categoryName;
  final String barcode;
  final double physical;
  final double recorded;
  final String status;

  double get difference => physical - recorded;
  bool get isCompleted => status == 'completed';

  factory StockCheckItem.fromJson(Map<String, dynamic> j) => StockCheckItem(
        id: asNum(j['id']).toInt(),
        productName: asStr(j['product_name']),
        productCode: asStr(j['product_code']),
        brandName: asStr(j['brand_name']),
        categoryName: asStr(j['category_name']),
        barcode: asStr(j['barcode']),
        physical: asNum(j['physical_quantity']).toDouble(),
        recorded: asNum(j['recorded_quantity']).toDouble(),
        status: asStr(j['status']).isEmpty ? 'pending' : asStr(j['status']),
      );

  StockCheckItem copyWith({double? physical, String? status}) => StockCheckItem(
        id: id,
        productName: productName,
        productCode: productCode,
        brandName: brandName,
        categoryName: categoryName,
        barcode: barcode,
        physical: physical ?? this.physical,
        recorded: recorded,
        status: status ?? this.status,
      );
}

/// A page of items + pagination cursor (the GetStockCheckItemsAction paginator).
class StockCheckItemsPage {
  StockCheckItemsPage({
    required this.rows,
    required this.currentPage,
    required this.lastPage,
    required this.total,
  });

  final List<StockCheckItem> rows;
  final int currentPage;
  final int lastPage;
  final int total;

  factory StockCheckItemsPage.fromJson(Map<String, dynamic> j) {
    final data = (j['data'] as List? ?? const [])
        .map((e) => StockCheckItem.fromJson(Map<String, dynamic>.from(e)))
        .toList();
    return StockCheckItemsPage(
      rows: data,
      currentPage: asNum(j['current_page']).toInt() == 0 ? 1 : asNum(j['current_page']).toInt(),
      lastPage: asNum(j['last_page']).toInt() == 0 ? 1 : asNum(j['last_page']).toInt(),
      total: asNum(j['total']).toInt(),
    );
  }
}

/// The result of a create — the new id + how many items were snapshotted.
class StockCheckCreateResult {
  StockCheckCreateResult({required this.id, required this.itemsCount});
  final int id;
  final int itemsCount;

  factory StockCheckCreateResult.fromJson(Map<String, dynamic> j) => StockCheckCreateResult(
        id: asNum(j['id']).toInt(),
        itemsCount: asNum(j['items_count']).toInt(),
      );
}

/// The enriched item returned after a barcode scan (physical was +1'd server-side).
class ScanResult {
  ScanResult({
    required this.id,
    required this.productName,
    required this.productCode,
    required this.physical,
    required this.recorded,
    required this.difference,
  });

  final int id;
  final String productName;
  final String productCode;
  final double physical;
  final double recorded;
  final double difference;

  factory ScanResult.fromJson(Map<String, dynamic> j) => ScanResult(
        id: asNum(j['id']).toInt(),
        productName: asStr(j['product_name']),
        productCode: asStr(j['product_code']),
        physical: asNum(j['physical_quantity']).toDouble(),
        recorded: asNum(j['recorded_quantity']).toDouble(),
        difference: asNum(j['difference']).toDouble(),
      );
}
