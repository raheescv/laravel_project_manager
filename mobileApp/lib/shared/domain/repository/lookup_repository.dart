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

  /// [type] narrows the list to categories that hold at least one product of
  /// that type ('product' / 'service'); null returns every visible category.
  Future<List<Category>> categories({String? type});

  Future<List<Branch>> branches();

  Future<List<Customer>> customers({String? mobile, String? search});

  Future<List<Employee>> employees({String? search, int? branchId});

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
