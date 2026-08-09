import 'dart:typed_data';

import '../../api/end_points.dart';
import '../../utils/router/http_utils/http_service.dart';
import '../constants/global_variables.dart';
import '../helpers/formatters.dart';
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
    final data = await _http.get(EndPoints.products, auth: false, query: {
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
  Future<({List<Map<String, dynamic>> rows, int currentPage, int lastPage})> productsRaw({
    String? type,
    int page = 1,
    // The server validates `per_page` at max:100 and 422s above it.
    int perPage = 100,
  }) async {
    final data = await _http.get(EndPoints.products, auth: false, query: {
      if (type != null && type.isNotEmpty) 'type': type,
      'in_stock_only': false,
      'per_page': perPage,
      'page': page,
    });
    final list = (data is Map ? data['data'] : data) as List? ?? const [];
    final pag = (data is Map ? data['pagination'] : null) as Map? ?? const {};
    return (
      rows: list.map((e) => Map<String, dynamic>.from(e as Map)).toList(),
      currentPage: asNum(pag['current_page'] ?? page).toInt(),
      lastPage: asNum(pag['last_page'] ?? 1).toInt(),
    );
  }

  @override
  Future<Product?> productByBarcode(String barcode) async {
    final data = await _http.get(EndPoints.products, auth: false, query: {
      'barcode': barcode,
      'in_stock_only': false,
      'per_page': 1,
    });
    final p = Paginated.from(data, Product.fromJson);
    return p.items.isEmpty ? null : p.items.first;
  }

  @override
  Future<List<Category>> categories({String? type}) async {
    final data = await _http.get(EndPoints.categories, auth: false, query: {
      if (type != null) 'type': type,
    });
    return ((data as List?) ?? const [])
        .map((e) => Category.fromJson(Map<String, dynamic>.from(e)))
        .toList();
  }

  @override
  Future<List<Branch>> branches() async {
    final data = await _http.get(EndPoints.branches, auth: false);
    return ((data as List?) ?? const [])
        .map((e) => Branch.fromJson(Map<String, dynamic>.from(e)))
        .toList();
  }

  @override
  Future<List<Customer>> customers({String? mobile, String? search}) async {
    final data = await _http.get(EndPoints.customers, query: {
      if (mobile != null && mobile.isNotEmpty) 'mobile': mobile,
      if (search != null && search.isNotEmpty) 'search': search,
      'per_page': 20,
    });
    return Paginated.from(data, Customer.fromJson).items;
  }

  @override
  Future<Paginated<Customer>> customersPage({
    int page = 1,
    // The server validates `per_page` at max:100 and 422s above it.
    int perPage = 100,
    String? search,
  }) async {
    final data = await _http.get(EndPoints.customers, query: {
      if (search != null && search.isNotEmpty) 'search': search,
      'per_page': perPage,
      'page': page,
    });
    return Paginated.from(data, Customer.fromJson);
  }

  @override
  Future<List<Employee>> employees({String? search, int? branchId}) async {
    final data = await _http.get(EndPoints.employees, query: {
      if (search != null && search.isNotEmpty) 'search': search,
      if (branchId != null) 'branch_id': branchId,
      'per_page': 100,
    });
    return Paginated.from(data, Employee.fromJson).items;
  }

  @override
  Future<Paginated<Employee>> employeesPage({
    int page = 1,
    int perPage = 100,
    int? branchId,
  }) async {
    final data = await _http.get(EndPoints.employees, query: {
      if (branchId != null) 'branch_id': branchId,
      'per_page': perPage,
      'page': page,
    });
    return Paginated.from(data, Employee.fromJson);
  }

  @override
  Future<List<PaymentMethod>> paymentMethods() async {
    final data = await _http.get(EndPoints.paymentMethods);
    return ((data as List?) ?? const [])
        .map((e) => PaymentMethod.fromJson(Map<String, dynamic>.from(e)))
        .toList();
  }

  @override
  Future<({String? baseCode, List<Currency> currencies})> currencies() async {
    final data = await _http.get(EndPoints.currencies);
    final map = Map<String, dynamic>.from(data as Map);
    final list = ((map['currencies'] as List?) ?? const [])
        .map((e) => Currency.fromJson(Map<String, dynamic>.from(e)))
        .toList();
    return (baseCode: map['base_currency_code']?.toString(), currencies: list);
  }

  @override
  Future<({double? defaultQuantity, bool? tipEnabled, String? defaultProductType, RemotePrintConfig? print})> saleSettings() async {
    final data = await _http.get(EndPoints.saleSettings);
    final map = Map<String, dynamic>.from(data as Map);
    return (
      defaultQuantity: double.tryParse(map['default_quantity']?.toString() ?? ''),
      tipEnabled: map['tip_enabled'] is bool ? map['tip_enabled'] as bool : null,
      defaultProductType: map['default_product_type']?.toString(),
      print: map['print'] is Map ? RemotePrintConfig.fromJson(Map<String, dynamic>.from(map['print'] as Map)) : null,
    );
  }

  @override
  Future<Uint8List> logo() => _http.getBytes(EndPoints.logo);

  @override
  Future<RemotePrintConfig?> savePrintSettings(Map<String, dynamic> body) async {
    final data = await _http.put(EndPoints.printSettings, body: body);
    final map = Map<String, dynamic>.from(data as Map);
    return map['print'] is Map ? RemotePrintConfig.fromJson(Map<String, dynamic>.from(map['print'] as Map)) : null;
  }
}
