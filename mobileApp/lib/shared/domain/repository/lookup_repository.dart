import 'dart:typed_data';

import '../models/index.dart';

/// Reference data used across features (catalog, branches, customers,
/// employees, payment methods, currencies) plus the shared settings sync.
/// Lives in `shared/` because several features and the app-wide Branch/
/// Currency cubits all depend on it. Read-only except [savePrintSettings].
abstract class LookupRepository {
  Future<Paginated<Product>> products({
    String? search,
    int? mainCategoryId,
    String? type,
    int page,
    int perPage,
  });

  Future<Product?> productByBarcode(String barcode);

  /// One page of `/products` left as the server's own JSON, for the offline
  /// snapshot to store verbatim. Parsing to [Product] here and re-encoding would
  /// drop everything the model doesn't carry — the category id the local filter
  /// needs among it — so the raw maps go to disk instead.
  Future<({List<Map<String, dynamic>> rows, int currentPage, int lastPage})> productsRaw({
    String? type,
    int page,
    int perPage,
  });

  /// [type] narrows the list to categories that hold at least one product of
  /// that type ('product' / 'service'); null returns every visible category.
  Future<List<Category>> categories({String? type});

  Future<List<Branch>> branches();

  Future<List<Customer>> customers({String? mobile, String? search});

  /// One page of customers, with the pagination the caller needs to keep going.
  ///
  /// Separate from [customers] because that one backs the search-as-you-type client
  /// sheet, where a small page is the right answer and a full download would fire on
  /// every keystroke. This is for the offline snapshot, which needs *all* of them:
  /// a till that has cached 20 of a shop's 2,000 customers will create a duplicate
  /// account for almost every returning client it sells to offline.
  Future<Paginated<Customer>> customersPage({int page, int perPage, String? search});

  Future<List<Employee>> employees({String? search, int? branchId});

  /// One page of staff, for the same reason as [customersPage].
  Future<Paginated<Employee>> employeesPage({int page, int perPage, int? branchId});

  Future<List<PaymentMethod>> paymentMethods();

  Future<({String? baseCode, List<Currency> currencies})> currencies();

  Future<({double? defaultQuantity, bool? tipEnabled, String? defaultProductType, RemotePrintConfig? print})> saleSettings();

  /// Company logo bytes (png/jpg/svg) for the receipt header; cached by the
  /// print cubit keyed on `RemotePrintConfig.logoVersion`.
  Future<Uint8List> logo();

  /// Saves print options back to the shared web Sale Configuration (partial
  /// [body] of the same keys GET /settings/sale returns under `print`).
  /// Returns the server's fresh print block. Needs `configuration.settings`.
  Future<RemotePrintConfig?> savePrintSettings(Map<String, dynamic> body);
}
