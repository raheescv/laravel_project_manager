import '../models/models.dart';
import 'api_client.dart';
import 'formatters.dart';

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

  Future<void> changePassword(String current, String next) => client.post('/change-password', body: {
        'current_password': current,
        'new_password': next,
        'new_password_confirmation': next,
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

  Future<SalesPage> sales({
    String? status,
    int? paymentMethodId,
    String? fromDate,
    String? toDate,
    String sortBy = 'date', // date | invoice_no | paid | gross_amount | id
    String sortDirection = 'desc', // asc | desc
    bool mineOnly = false,
    int page = 1,
    int perPage = 30,
  }) async {
    final data = await client.get('/sale', query: {
      if (status != null) 'status': status,
      if (paymentMethodId != null) 'payment_method_id': paymentMethodId,
      if (fromDate != null) 'from_date': fromDate,
      if (toDate != null) 'to_date': toDate,
      'sort_by': sortBy,
      'sort_direction': sortDirection,
      'mine_only': mineOnly,
      'page': page,
      'per_page': perPage,
    });
    final list = (data is Map ? data['data'] : data) as List? ?? const [];
    final summary = (data is Map ? data['summary'] : null) as Map? ?? const {};
    final pag = (data is Map ? data['pagination'] : null) as Map? ?? const {};
    return SalesPage(
      rows: list.map((e) => Map<String, dynamic>.from(e)).toList(),
      total: asNum(summary['invoices'] ?? pag['total'] ?? list.length).toInt(),
      totalPaid: asNum(summary['total_paid']).toDouble(),
      currentPage: asNum(pag['current_page'] ?? 1).toInt(),
      lastPage: asNum(pag['last_page'] ?? 1).toInt(),
    );
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
    required String type, // billwise | employeewise | itemwise
    String? startDate,
    String? endDate,
    int? page,
    int? perPage,
    String? sort, // itemwise ranking: amount | quantity
  }) async {
    final data = await client.get('/admin/reports', query: {
      'type': type,
      if (startDate != null) 'startDate': startDate,
      if (endDate != null) 'endDate': endDate,
      if (page != null) 'page': page,
      if (perPage != null) 'per_page': perPage,
      if (sort != null) 'sort': sort,
    });
    return Map<String, dynamic>.from(data);
  }

  Future<Map<String, dynamic>> toggleDay(String date) async {
    final data = await client.post('/admin/day-status', body: {'date': date});
    return Map<String, dynamic>.from(data);
  }
}
