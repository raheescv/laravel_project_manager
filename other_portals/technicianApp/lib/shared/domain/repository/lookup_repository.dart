import '../models/index.dart';

/// Read-only reference data used across features (catalog, branches, customers,
/// employees, payment methods, currencies). Lives in `shared/` because several
/// features and the app-wide Branch/Currency cubits all depend on it.
abstract class LookupRepository {
  Future<Paginated<Product>> products({
    String? search,
    int? mainCategoryId,
    String? type,
    int page,
    int perPage,
  });

  Future<Product?> productByBarcode(String barcode);

  Future<List<Category>> categories();

  Future<List<Branch>> branches();

  Future<List<Customer>> customers({String? mobile, String? search});

  Future<List<Employee>> employees({String? search, int? branchId});

  Future<List<PaymentMethod>> paymentMethods();

  Future<({String? baseCode, List<Currency> currencies})> currencies();

  Future<double?> defaultQuantity();
}
