import 'dart:typed_data';

import 'package:invo/features/admin/domain/repository/admin_repository.dart';
import 'package:invo/features/auth/domain/repository/auth_repository.dart';
import 'package:invo/features/sale/domain/repository/sale_repository.dart';
import 'package:invo/shared/domain/models/index.dart';

/// A [SaleRepository] that returns a finished sale for createSale (so the
/// Charge → Invoice navigation can be exercised) and records the payload sent.
class FakeSaleRepository implements SaleRepository {
  Map<String, dynamic>? lastPayload;

  Sale _finishedSale() => Sale.fromJson({
        'id': '9001',
        'invoice_no': 'INV-9001',
        'date': '2026-06-14',
        'status': 'completed',
        'branch': 'Downtown',
        'customer': {'name': 'Walk-in', 'mobile': ''},
        'items': [
          {'name': 'Signature Cut', 'type': 'service', 'employee': 'Maya', 'quantity': 1, 'unit_price': 45, 'discount': 0, 'total': 45},
        ],
        'payments': [
          {'method': 'Cash', 'amount': 45}
        ],
        'summary': {'gross_amount': 45, 'item_discount': 0, 'other_discount': 0, 'tax_amount': 0, 'grand_total': 45, 'paid': 45, 'balance': 0},
        'created_by': 'Maya',
      });

  @override
  Future<Sale> createSale(Map<String, dynamic> payload) async {
    lastPayload = payload;
    return _finishedSale();
  }

  @override
  Future<Sale> updateSale(String id, Map<String, dynamic> payload) async {
    lastPayload = payload;
    return _finishedSale();
  }

  @override
  Future<SalesPage> sales({
    String? status,
    String? search,
    int? paymentMethodId,
    String? fromDate,
    String? toDate,
    String sortBy = 'date',
    String sortDirection = 'desc',
    bool mineOnly = false,
    int page = 1,
    int perPage = 30,
  }) async =>
      SalesPage(
        rows: [
          {'id': '1', 'invoice_no': '#1042', 'customer_name': 'Walk-in', 'date': '2026-06-14', 'paid': 248.4, 'status': 'completed'},
          {'id': '2', 'invoice_no': '#1041', 'customer_name': 'A. Rivera', 'date': '2026-06-13', 'paid': 92.0, 'status': 'completed'},
        ],
        total: 2,
        totalPaid: 340.4,
        currentPage: 1,
        lastPage: 1,
      );

  @override
  Future<Sale> saleById(String id) async => _finishedSale();

  @override
  Future<Uint8List> saleReceiptPdf(String id) async => Uint8List(0);
}

/// A no-op [AuthRepository]; the widget tests never actually log in through it.
class FakeAuthRepository implements AuthRepository {
  @override
  Future<({String token, ApiUser user})> login(String pin) async => throw UnimplementedError();

  @override
  Future<({String token, ApiUser user})> loginCredential(String username, String password) async =>
      throw UnimplementedError();

  @override
  Future<void> logout() async {}

  @override
  Future<void> changePin(String current, String next) async {}

  @override
  Future<void> changePassword(String current, String next) async {}
}

/// An [AdminRepository] returning canned dashboard / report data so the admin
/// screens render with realistic content offline.
class FakeAdminRepository implements AdminRepository {
  @override
  Future<DashboardData> dashboard({int? branchId}) async => DashboardData(
        today: [
          Metric(title: "Today's Sales", value: 4200, type: 'currency'),
          Metric(title: "Today's Bills", value: 18, type: 'count'),
        ],
        inventory: [
          Metric(title: 'Active Employees', value: 6, type: 'count'),
          Metric(title: 'Customers', value: 540, type: 'count'),
        ],
        business: [
          Metric(title: 'weekly sales', value: 21000, type: 'currency', percentage: '12%'),
          Metric(title: 'Monthly sales', value: 86000, type: 'currency', percentage: '-4%'),
        ],
      );

  @override
  Future<Map<String, dynamic>> report({
    required String type,
    String? startDate,
    String? endDate,
    int? page,
    int? perPage,
    String? sort,
    String? productType,
  }) async {
    if (type == 'employeewise') {
      return {
        'rows': [
          {'employee_name': 'Maya Chen', 'bills_count': 94, 'items_count': 120, 'revenue': 2540},
          {'employee_name': 'Liam Ortiz', 'bills_count': 78, 'items_count': 90, 'revenue': 2110},
        ],
        'summary': {'total_revenue': 4650},
        'pagination': {'current_page': 1, 'last_page': 1, 'total': 2},
      };
    }
    if (type == 'itemwise') {
      return {
        'rows': [
          {'item_name': 'Signature Cut', 'quantity': 40, 'total': 1800, 'bills_count': 40},
          {'item_name': 'Balayage', 'quantity': 18, 'total': 3240, 'bills_count': 18},
        ],
        'summary': {'total_amount': 5040, 'total_quantity': 58},
        'pagination': {'current_page': 1, 'last_page': 1, 'total': 2},
      };
    }
    if (type == 'overview') {
      return {};
    }
    // billwise
    return {
      'rows': [
        {'invoice_no': '#1042', 'customer': 'Walk-in', 'date': '2026-06-14', 'paid': 248.4},
        {'invoice_no': '#1041', 'customer': 'A. Rivera', 'date': '2026-06-13', 'paid': 92.0},
      ],
      'summary': {'total_revenue': 340.4},
      'pagination': {'current_page': 1, 'last_page': 1, 'total': 2},
    };
  }

  @override
  Future<DaySessionToggleResult> toggleDay(String dateTime) async =>
      DaySessionToggleResult(message: 'ok', status: 'open', session: null);
}
