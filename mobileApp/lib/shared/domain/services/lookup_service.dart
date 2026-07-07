import '../../utils/router/http_utils/http_service.dart';
import '../constants/global_variables.dart';
import '../models/index.dart';
import '../repository/lookup_repository.dart';

/// Concrete [LookupRepository] backed by [HttpService].
class LookupService implements LookupRepository {
  HttpService get _http => serviceLocator<HttpService>();

  @override
  Future<Paginated<Product>> products({
    String? search,
    int? mainCategoryId,
    String? type,
    int page = 1,
    int perPage = 50,
  }) async {
    final data = await _http.get('/products', auth: false, query: {
      if (search != null && search.isNotEmpty) 'search': search,
      if (mainCategoryId != null) 'main_category_id': mainCategoryId,
      if (type != null) 'type': type,
      'in_stock_only': false,
      'per_page': perPage,
      'page': page,
    });
    return Paginated.from(data, Product.fromJson);
  }

  @override
  Future<Product?> productByBarcode(String barcode) async {
    final data = await _http.get('/products', auth: false, query: {
      'barcode': barcode,
      'in_stock_only': false,
      'per_page': 1,
    });
    final p = Paginated.from(data, Product.fromJson);
    return p.items.isEmpty ? null : p.items.first;
  }

  @override
  Future<List<Category>> categories() async {
    final data = await _http.get('/categories', auth: false);
    return ((data as List?) ?? const [])
        .map((e) => Category.fromJson(Map<String, dynamic>.from(e)))
        .toList();
  }

  @override
  Future<List<Branch>> branches() async {
    final data = await _http.get('/branches', auth: false);
    return ((data as List?) ?? const [])
        .map((e) => Branch.fromJson(Map<String, dynamic>.from(e)))
        .toList();
  }

  @override
  Future<List<Customer>> customers({String? mobile, String? search}) async {
    final data = await _http.get('/customers', query: {
      if (mobile != null && mobile.isNotEmpty) 'mobile': mobile,
      if (search != null && search.isNotEmpty) 'search': search,
      'per_page': 20,
    });
    return Paginated.from(data, Customer.fromJson).items;
  }

  @override
  Future<List<Employee>> employees({String? search, int? branchId}) async {
    final data = await _http.get('/employees', query: {
      if (search != null && search.isNotEmpty) 'search': search,
      if (branchId != null) 'branch_id': branchId,
      'per_page': 100,
    });
    return Paginated.from(data, Employee.fromJson).items;
  }

  @override
  Future<List<PaymentMethod>> paymentMethods() async {
    final data = await _http.get('/payment-methods');
    return ((data as List?) ?? const [])
        .map((e) => PaymentMethod.fromJson(Map<String, dynamic>.from(e)))
        .toList();
  }

  @override
  Future<({String? baseCode, List<Currency> currencies})> currencies() async {
    final data = await _http.get('/settings/currencies');
    final map = Map<String, dynamic>.from(data as Map);
    final list = ((map['currencies'] as List?) ?? const [])
        .map((e) => Currency.fromJson(Map<String, dynamic>.from(e)))
        .toList();
    return (baseCode: map['base_currency_code']?.toString(), currencies: list);
  }

  @override
  Future<({double? defaultQuantity, bool? tipEnabled})> saleSettings() async {
    final data = await _http.get('/settings/sale');
    final map = Map<String, dynamic>.from(data as Map);
    return (
      defaultQuantity: double.tryParse(map['default_quantity']?.toString() ?? ''),
      tipEnabled: map['tip_enabled'] is bool ? map['tip_enabled'] as bool : null,
    );
  }
}
