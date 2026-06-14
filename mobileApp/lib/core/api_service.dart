import '../models/models.dart';
import 'api_client.dart';

/// Typed wrappers over the Laravel `api/v1` endpoints (see mobileApp/BUILD_PROMPT.md).
class ApiService {
  ApiService(this.client);
  final ApiClient client;

  // ---- Auth ----
  /// Unified login — the backend `/login` handles both methods. PIN is the
  /// default; pass username/password with method=password for credential login.
  Future<({String token, ApiUser user})> login(String pin) =>
      _login({'method': 'pin', 'pin': pin});

  Future<({String token, ApiUser user})> loginCredential(String username, String password) =>
      _login({'method': 'password', 'username': username, 'password': password});

  Future<({String token, ApiUser user})> _login(Map<String, dynamic> body) async {
    final data = await client.post('/login', body: body, auth: false);
    final map = Map<String, dynamic>.from(data);
    return (
      token: map['token'].toString(),
      user: ApiUser.fromJson(Map<String, dynamic>.from(map['user'])),
    );
  }

  Future<void> logout() => client.post('/logout');

  Future<void> changePin(String current, String next) => client.post('/change-pin', body: {
        'current_pin': current,
        'new_pin': next,
        'new_pin_confirmation': next,
      });

  // ---- Catalog ----
  Future<Paginated<Product>> products({
    String? search,
    int? mainCategoryId,
    String? type,
    int page = 1,
    int perPage = 50,
  }) async {
    final data = await client.get('/products', auth: false, query: {
      if (search != null && search.isNotEmpty) 'search': search,
      if (mainCategoryId != null) 'main_category_id': mainCategoryId,
      if (type != null) 'type': type,
      'in_stock_only': false,
      'per_page': perPage,
      'page': page,
    });
    return Paginated.from(data, Product.fromJson);
  }

  Future<Product?> productByBarcode(String barcode) async {
    final data = await client.get('/products', auth: false, query: {
      'barcode': barcode,
      'in_stock_only': false,
      'per_page': 1,
    });
    final p = Paginated.from(data, Product.fromJson);
    return p.items.isEmpty ? null : p.items.first;
  }

  Future<List<Category>> categories() async {
    final data = await client.get('/categories', auth: false);
    return ((data as List?) ?? const [])
        .map((e) => Category.fromJson(Map<String, dynamic>.from(e)))
        .toList();
  }

  Future<List<Branch>> branches() async {
    final data = await client.get('/branches', auth: false);
    return ((data as List?) ?? const [])
        .map((e) => Branch.fromJson(Map<String, dynamic>.from(e)))
        .toList();
  }

  // ---- Customers ----
  Future<List<Customer>> customers({String? mobile, String? search}) async {
    final data = await client.get('/customers', query: {
      if (mobile != null && mobile.isNotEmpty) 'mobile': mobile,
      if (search != null && search.isNotEmpty) 'search': search,
      'per_page': 20,
    });
    final p = Paginated.from(data, Customer.fromJson);
    return p.items;
  }

  // ---- Employees (stylists) ----
  /// Active staff who can be assigned to a sale / line as the stylist.
  Future<List<Employee>> employees({String? search, int? branchId}) async {
    final data = await client.get('/employees', query: {
      if (search != null && search.isNotEmpty) 'search': search,
      if (branchId != null) 'branch_id': branchId,
      'per_page': 100,
    });
    return Paginated.from(data, Employee.fromJson).items;
  }

  // ---- Payment methods ----
  /// The payment-method accounts configured for the business (custom-payment selector).
  Future<List<PaymentMethod>> paymentMethods() async {
    final data = await client.get('/payment-methods');
    return ((data as List?) ?? const [])
        .map((e) => PaymentMethod.fromJson(Map<String, dynamic>.from(e)))
        .toList();
  }

  // ---- Sales ----
  Future<Sale> createSale(Map<String, dynamic> payload) async {
    final data = await client.post('/sale', body: payload);
    return Sale.fromJson(Map<String, dynamic>.from(data));
  }

  Future<List<Map<String, dynamic>>> sales({String? status, bool mineOnly = false}) async {
    final data = await client.get('/sale', query: {
      if (status != null) 'status': status,
      'mine_only': mineOnly,
      'per_page': 30,
    });
    final list = (data is Map ? data['data'] : data) as List? ?? const [];
    return list.map((e) => Map<String, dynamic>.from(e)).toList();
  }

  Future<Sale> saleById(String id) async {
    final data = await client.get('/sale/$id');
    return Sale.fromJson(Map<String, dynamic>.from(data));
  }

  // ---- Admin ----
  Future<DashboardData> dashboard({int? branchId}) async {
    final data = await client.get('/admin/dashboard', query: {
      if (branchId != null) 'branch_id': branchId,
    });
    return DashboardData.fromJson(Map<String, dynamic>.from(data));
  }

  Future<Map<String, dynamic>> report({
    required String type, // billwise | employeewise
    String? startDate,
    String? endDate,
  }) async {
    final data = await client.get('/admin/reports', query: {
      'type': type,
      if (startDate != null) 'startDate': startDate,
      if (endDate != null) 'endDate': endDate,
    });
    return Map<String, dynamic>.from(data);
  }

  Future<Map<String, dynamic>> toggleDay(String date) async {
    final data = await client.post('/admin/day-status', body: {'date': date});
    return Map<String, dynamic>.from(data);
  }
}
