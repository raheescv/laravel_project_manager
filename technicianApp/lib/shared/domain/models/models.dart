import '../helpers/formatters.dart';

/// Authenticated user (Laravel AuthUserResource).
class ApiUser {
  ApiUser({
    required this.id,
    required this.name,
    required this.code,
    required this.email,
    required this.mobile,
    required this.isAdmin,
    required this.designation,
    required this.branchId,
    required this.daySessionStatus,
    required this.daySessionDate,
    this.daySessionOpenedAt = '',
    this.lastClosedSessionAt = '',
    this.permissions = const [],
  });

  final String id;
  final String name;
  final String code;
  final String email;
  final String mobile;
  final bool isAdmin;
  final String designation;
  final String? branchId;
  final String daySessionStatus; // open | closed
  final String daySessionDate;
  final String daySessionOpenedAt; // 'Y-m-d H:i:s' while a day is open, else ''
  final String lastClosedSessionAt; // 'Y-m-d H:i:s' of the most recent close, else ''
  final List<String> permissions; // Spatie permission slugs granted to this user

  bool get dayOpen => daySessionStatus == 'open';

  factory ApiUser.fromJson(Map<String, dynamic> j) => ApiUser(
        id: asStr(j['id']),
        name: asStr(j['name']),
        code: asStr(j['code']),
        email: asStr(j['email']),
        mobile: asStr(j['mobile']),
        isAdmin: j['is_admin'] == true,
        designation: asStr(j['designation']),
        branchId: j['branch_id']?.toString(),
        daySessionStatus: asStr(j['sale_day_session_status']),
        daySessionDate: asStr(j['sale_day_session_date']),
        daySessionOpenedAt: asStr(j['sale_day_session_opened_at']),
        lastClosedSessionAt: asStr(j['last_closed_session_at']),
        permissions: (j['permissions'] as List<dynamic>? ?? [])
            .map((e) => asStr(e))
            .toList(),
      );

  Map<String, dynamic> toJson() => {
        'id': id,
        'name': name,
        'code': code,
        'email': email,
        'mobile': mobile,
        'is_admin': isAdmin,
        'designation': designation,
        'branch_id': branchId,
        'sale_day_session_status': daySessionStatus,
        'sale_day_session_date': daySessionDate,
        'sale_day_session_opened_at': daySessionOpenedAt,
        'last_closed_session_at': lastClosedSessionAt,
        'permissions': permissions,
      };

  /// Returns a copy with the day-session fields replaced — used after a
  /// successful open/close so the profile row and dashboard pill update live.
  ApiUser copyWith({
    String? daySessionStatus,
    String? daySessionDate,
    String? daySessionOpenedAt,
    String? lastClosedSessionAt,
  }) =>
      ApiUser(
        id: id,
        name: name,
        code: code,
        email: email,
        mobile: mobile,
        isAdmin: isAdmin,
        designation: designation,
        branchId: branchId,
        daySessionStatus: daySessionStatus ?? this.daySessionStatus,
        daySessionDate: daySessionDate ?? this.daySessionDate,
        daySessionOpenedAt: daySessionOpenedAt ?? this.daySessionOpenedAt,
        lastClosedSessionAt: lastClosedSessionAt ?? this.lastClosedSessionAt,
        permissions: permissions,
      );

  String get initial => name.isNotEmpty ? name[0].toUpperCase() : '?';
}

/// One branch sale-day session (Laravel `DaySessionResource`). Returned by the
/// open/close toggle; amounts are nullable until a closing/expected value is set.
class DaySession {
  DaySession({
    required this.id,
    required this.branch,
    required this.status,
    required this.openedAt,
    required this.closedAt,
    required this.openedBy,
    required this.closedBy,
    required this.openingAmount,
    required this.closingAmount,
    required this.expectedAmount,
  });

  final String id;
  final String branch;
  final String status; // open | closed
  final String openedAt; // ISO datetime
  final String closedAt; // ISO datetime ('' while open)
  final String openedBy;
  final String closedBy;
  final double openingAmount;
  final double? closingAmount;
  final double? expectedAmount;

  bool get isOpen => status == 'open';

  factory DaySession.fromJson(Map<String, dynamic> j) => DaySession(
        id: asStr(j['id']),
        branch: asStr(j['branch']),
        status: asStr(j['status']),
        openedAt: asStr(j['opened_at']),
        closedAt: asStr(j['closed_at']),
        openedBy: asStr(j['opened_by']),
        closedBy: asStr(j['closed_by']),
        openingAmount: asNum(j['opening_amount']).toDouble(),
        closingAmount: j['closing_amount'] == null ? null : asNum(j['closing_amount']).toDouble(),
        expectedAmount: j['expected_amount'] == null ? null : asNum(j['expected_amount']).toDouble(),
      );
}

/// The envelope returned by `POST /admin/day-status` — a message, the resulting
/// `status`, and the affected [session].
class DaySessionToggleResult {
  DaySessionToggleResult({required this.message, required this.status, required this.session});
  final String message;
  final String status; // open | closed (the new state)
  final DaySession? session;

  bool get isOpen => status == 'open';

  factory DaySessionToggleResult.fromJson(Map<String, dynamic> j) => DaySessionToggleResult(
        message: asStr(j['message']),
        status: asStr(j['status']),
        session: j['session'] is Map
            ? DaySession.fromJson(Map<String, dynamic>.from(j['session']))
            : null,
      );
}

class Branch {
  Branch({required this.id, required this.name, required this.location, required this.code});
  final int id;
  final String name;
  final String location;
  final String code;

  factory Branch.fromJson(Map<String, dynamic> j) => Branch(
        id: asNum(j['id']).toInt(),
        name: asStr(j['name']),
        location: asStr(j['location'].toString().isEmpty ? j['name'] : j['location']),
        code: asStr(j['code']),
      );
}

class Category {
  Category({required this.id, required this.name, required this.productCount});
  final int id;
  final String name;
  final int productCount;

  factory Category.fromJson(Map<String, dynamic> j) => Category(
        id: asNum(j['id']).toInt(),
        name: asStr(j['name']),
        productCount: asNum(j['product_count']).toInt(),
      );
}

/// Product / service (Laravel ProductResource, trimmed to what the app uses).
class Product {
  Product({
    required this.id,
    required this.code,
    required this.name,
    required this.barcode,
    required this.mrp,
    required this.tax,
    required this.type,
    required this.categoryName,
    required this.duration,
    required this.totalStock,
    required this.thumbnail,
  });

  final int id;
  final String code;
  final String name;
  final String barcode;
  final double mrp;
  final double tax; // tax percentage applied to the line; mirrors products.tax
  final String type; // product | service
  final String categoryName;
  final String duration;
  final num totalStock;
  final String thumbnail;

  bool get isService => type == 'service';
  bool get hasImage => thumbnail.startsWith('http');

  factory Product.fromJson(Map<String, dynamic> j) {
    final main = j['main_category'];
    // Prefer the thumbnail; fall back to the first attached image's url.
    var thumb = asStr(j['thumbnail']);
    if (!thumb.startsWith('http')) {
      final images = j['images'];
      if (images is List && images.isNotEmpty && images.first is Map) {
        thumb = asStr((images.first as Map)['url']);
      }
    }
    return Product(
      id: asNum(j['id']).toInt(),
      code: asStr(j['code']),
      name: asStr(j['name']),
      barcode: asStr(j['barcode']),
      mrp: asNum(j['mrp']).toDouble(),
      tax: asNum(j['tax']).toDouble(),
      type: asStr(j['type']).isEmpty ? 'service' : asStr(j['type']),
      categoryName: main is Map ? asStr(main['name']) : 'Other',
      duration: asStr(j['time']),
      totalStock: asNum(j['total_stock']),
      thumbnail: thumb,
    );
  }
}

class Paginated<T> {
  Paginated({required this.items, required this.currentPage, required this.lastPage, required this.total});
  final List<T> items;
  final int currentPage;
  final int lastPage;
  final int total;

  bool get hasMore => currentPage < lastPage;

  factory Paginated.from(dynamic data, T Function(Map<String, dynamic>) fromJson) {
    // The list endpoints return { data: [...], pagination: {...} }.
    final list = (data is Map ? data['data'] : data) as List? ?? const [];
    final pag = (data is Map ? data['pagination'] : null) as Map? ?? const {};
    return Paginated(
      items: list.map((e) => fromJson(Map<String, dynamic>.from(e))).toList(),
      currentPage: asNum(pag['current_page']).toInt(),
      lastPage: asNum(pag['last_page'] ?? 1).toInt(),
      total: asNum(pag['total'] ?? list.length).toInt(),
    );
  }
}

/// A page of sales plus the full-set totals (count + sum of paid) so the Sales
/// screen can show an accurate "N invoices · total" line regardless of paging.
class SalesPage {
  SalesPage({
    required this.rows,
    required this.total,
    required this.totalPaid,
    this.currentPage = 1,
    this.lastPage = 1,
  });
  final List<Map<String, dynamic>> rows;
  final int total;
  final double totalPaid;
  final int currentPage;
  final int lastPage;

  bool get hasMore => currentPage < lastPage;
}

class Customer {
  Customer({required this.id, required this.name, required this.mobile});
  final int id;
  final String name;
  final String mobile;

  factory Customer.fromJson(Map<String, dynamic> j) => Customer(
        id: asNum(j['id']).toInt(),
        name: asStr(j['name']),
        mobile: asStr(j['mobile']),
      );
}

/// A staff member who can be assigned to a sale / line as the stylist.
/// Mirrors `GET /employees` (active users with type = employee).
class Employee {
  Employee({required this.id, required this.name, required this.code, required this.mobile, required this.designation});
  final int id;
  final String name;
  final String code;
  final String mobile;
  final String designation;

  factory Employee.fromJson(Map<String, dynamic> j) => Employee(
        id: asNum(j['id']).toInt(),
        name: asStr(j['name']),
        code: asStr(j['code']),
        mobile: asStr(j['mobile']),
        designation: asStr(j['designation']),
      );

  String get initial => name.isNotEmpty ? name[0].toUpperCase() : '?';
}

/// A configured payment-method account (Cash, Card, Bank, …) used by the
/// custom-payment selector. Mirrors `GET /payment-methods`.
class PaymentMethod {
  PaymentMethod({required this.id, required this.name});
  final int id;
  final String name;

  factory PaymentMethod.fromJson(Map<String, dynamic> j) => PaymentMethod(
        id: asNum(j['id']).toInt(),
        name: asStr(j['name']),
      );
}

// ---- Sale (SaleResource) ----

class SaleLine {
  SaleLine({
    required this.name,
    required this.nameArabic,
    required this.type,
    required this.employee,
    required this.quantity,
    required this.unitPrice,
    required this.discount,
    required this.total,
    this.itemId,
    this.productId,
    this.employeeId,
    this.code = '',
    this.tax = 0,
  });
  final String name;
  final String nameArabic;
  final String type;
  final String employee;
  final double quantity;
  final double unitPrice;
  final double discount;
  final double total;
  // Edit round-trip ids — present on the view/show payload so the line can be
  // re-sent on an update instead of being treated as a brand-new product.
  final int? itemId;
  final int? productId;
  final int? employeeId;
  final String code;
  final double tax;

  factory SaleLine.fromJson(Map<String, dynamic> j) => SaleLine(
        name: asStr(j['name']),
        nameArabic: asStr(j['name_arabic']),
        type: asStr(j['type']),
        employee: asStr(j['employee']),
        quantity: asNum(j['quantity']).toDouble(),
        unitPrice: asNum(j['unit_price']).toDouble(),
        discount: asNum(j['discount']).toDouble(),
        total: asNum(j['total']).toDouble(),
        itemId: j['id'] == null ? null : asNum(j['id']).toInt(),
        productId: j['product_id'] == null ? null : asNum(j['product_id']).toInt(),
        employeeId: j['employee_id'] == null ? null : asNum(j['employee_id']).toInt(),
        code: asStr(j['code']),
        tax: asNum(j['tax']).toDouble(),
      );
}

class SalePayment {
  SalePayment({required this.method, required this.amount, this.paymentId, this.paymentMethodId});
  final String method;
  final double amount;
  final int? paymentId;
  final int? paymentMethodId;
  factory SalePayment.fromJson(Map<String, dynamic> j) => SalePayment(
        method: asStr(j['method']),
        amount: asNum(j['amount']).toDouble(),
        paymentId: j['id'] == null ? null : asNum(j['id']).toInt(),
        paymentMethodId: j['payment_method_id'] == null ? null : asNum(j['payment_method_id']).toInt(),
      );
}

class Sale {
  Sale({
    required this.id,
    required this.invoiceNo,
    required this.date,
    required this.status,
    required this.branch,
    required this.customerName,
    required this.customerMobile,
    required this.lines,
    required this.payments,
    required this.grossAmount,
    required this.itemDiscount,
    required this.otherDiscount,
    required this.taxAmount,
    required this.tip,
    required this.grandTotal,
    required this.paid,
    required this.balance,
    required this.createdBy,
  });

  final String id;
  final String invoiceNo;
  final String date;
  final String status;
  final String branch;
  final String customerName;
  final String customerMobile;
  final List<SaleLine> lines;
  final List<SalePayment> payments;
  final double grossAmount;
  final double itemDiscount;
  final double otherDiscount;
  final double taxAmount;
  // Gratuity stored on the sale as an independent extra amount (not in grandTotal).
  final double tip;
  // Net payable on the ticket (gross − discounts + tax + freight ± round-off).
  final double grandTotal;
  final double paid;
  // Outstanding amount straight from the sale's `balance` column (grand_total − paid).
  final double balance;
  final String createdBy;

  double get discount => itemDiscount + otherDiscount;

  factory Sale.fromJson(Map<String, dynamic> j) {
    final customer = (j['customer'] as Map?) ?? const {};
    final summary = (j['summary'] as Map?) ?? const {};
    return Sale(
      id: asStr(j['id']),
      invoiceNo: asStr(j['invoice_no']),
      date: asStr(j['date']),
      status: asStr(j['status']),
      branch: asStr(j['branch']),
      customerName: asStr(customer['name']),
      customerMobile: asStr(customer['mobile']),
      lines: ((j['items'] as List?) ?? const [])
          .map((e) => SaleLine.fromJson(Map<String, dynamic>.from(e)))
          .toList(),
      payments: ((j['payments'] as List?) ?? const [])
          .map((e) => SalePayment.fromJson(Map<String, dynamic>.from(e)))
          .toList(),
      grossAmount: asNum(summary['gross_amount']).toDouble(),
      itemDiscount: asNum(summary['item_discount']).toDouble(),
      otherDiscount: asNum(summary['other_discount']).toDouble(),
      taxAmount: asNum(summary['tax_amount']).toDouble(),
      tip: asNum(summary['tip']).toDouble(),
      grandTotal: asNum(summary['grand_total']).toDouble(),
      paid: asNum(summary['paid']).toDouble(),
      balance: asNum(summary['balance']).toDouble(),
      createdBy: asStr(j['created_by']),
    );
  }
}

// ---- Sale Return (SaleReturnResource) ----

/// One line on a saved sale return — mirrors [SaleLine].
class SaleReturnLine {
  SaleReturnLine({
    required this.name,
    required this.nameArabic,
    required this.type,
    required this.employee,
    required this.quantity,
    required this.unitPrice,
    required this.discount,
    required this.total,
    this.itemId,
    this.saleItemId,
    this.productId,
    this.employeeId,
    this.tax = 0,
  });
  final String name;
  final String nameArabic;
  final String type;
  final String employee;
  final double quantity;
  final double unitPrice;
  final double discount;
  final double total;
  // Edit round-trip ids — itemId is the sale_return_item id; saleItemId is the
  // source sale line the return is capped against.
  final int? itemId;
  final int? saleItemId;
  final int? productId;
  final int? employeeId;
  final double tax;

  factory SaleReturnLine.fromJson(Map<String, dynamic> j) => SaleReturnLine(
        name: asStr(j['name']),
        nameArabic: asStr(j['name_arabic']),
        type: asStr(j['type']),
        employee: asStr(j['employee']),
        quantity: asNum(j['quantity']).toDouble(),
        unitPrice: asNum(j['unit_price']).toDouble(),
        discount: asNum(j['discount']).toDouble(),
        total: asNum(j['total']).toDouble(),
        itemId: j['id'] == null ? null : asNum(j['id']).toInt(),
        saleItemId: j['sale_item_id'] == null ? null : asNum(j['sale_item_id']).toInt(),
        productId: j['product_id'] == null ? null : asNum(j['product_id']).toInt(),
        employeeId: j['employee_id'] == null ? null : asNum(j['employee_id']).toInt(),
        tax: asNum(j['tax']).toDouble(),
      );
}

/// A refund payment on a saved sale return — mirrors [SalePayment].
class SaleReturnPayment {
  SaleReturnPayment({required this.method, required this.amount, this.paymentId, this.paymentMethodId});
  final String method;
  final double amount;
  final int? paymentId;
  final int? paymentMethodId;
  factory SaleReturnPayment.fromJson(Map<String, dynamic> j) => SaleReturnPayment(
        method: asStr(j['method']),
        amount: asNum(j['amount']).toDouble(),
        paymentId: j['id'] == null ? null : asNum(j['id']).toInt(),
        paymentMethodId: j['payment_method_id'] == null ? null : asNum(j['payment_method_id']).toInt(),
      );
}

/// A saved sale return (SaleReturnResource). Mirrors [Sale]; sale returns have
/// no invoice_no, so [referenceNo] carries the document number.
class SaleReturn {
  SaleReturn({
    required this.id,
    required this.referenceNo,
    required this.date,
    required this.status,
    required this.branch,
    required this.customerName,
    required this.customerMobile,
    required this.lines,
    required this.payments,
    required this.grossAmount,
    required this.itemDiscount,
    required this.otherDiscount,
    required this.taxAmount,
    required this.total,
    required this.grandTotal,
    required this.paid,
    required this.balance,
    required this.createdBy,
    this.saleId = '',
    this.accountId,
  });

  final String id;
  final String referenceNo;
  // The source sale this return was raised against, and the customer account —
  // both needed to re-seed the return draft when editing.
  final String saleId;
  final int? accountId;
  final String date;
  final String status;
  final String branch;
  final String customerName;
  final String customerMobile;
  final List<SaleReturnLine> lines;
  final List<SaleReturnPayment> payments;
  final double grossAmount;
  final double itemDiscount;
  final double otherDiscount;
  final double taxAmount;
  final double total;
  final double grandTotal;
  final double paid;
  // Outstanding refund still owed to the customer (grand_total − paid).
  final double balance;
  final String createdBy;

  double get discount => itemDiscount + otherDiscount;

  factory SaleReturn.fromJson(Map<String, dynamic> j) {
    final customer = (j['customer'] as Map?) ?? const {};
    final summary = (j['summary'] as Map?) ?? const {};
    return SaleReturn(
      id: asStr(j['id']),
      referenceNo: asStr(j['reference_no']),
      saleId: asStr(j['sale_id']),
      accountId: j['account_id'] == null ? null : asNum(j['account_id']).toInt(),
      date: asStr(j['date']),
      status: asStr(j['status']),
      branch: asStr(j['branch']),
      customerName: asStr(customer['name']),
      customerMobile: asStr(customer['mobile']),
      lines: ((j['items'] as List?) ?? const [])
          .map((e) => SaleReturnLine.fromJson(Map<String, dynamic>.from(e)))
          .toList(),
      payments: ((j['payments'] as List?) ?? const [])
          .map((e) => SaleReturnPayment.fromJson(Map<String, dynamic>.from(e)))
          .toList(),
      grossAmount: asNum(summary['gross_amount']).toDouble(),
      itemDiscount: asNum(summary['item_discount']).toDouble(),
      otherDiscount: asNum(summary['other_discount']).toDouble(),
      taxAmount: asNum(summary['tax_amount']).toDouble(),
      total: asNum(summary['total']).toDouble(),
      grandTotal: asNum(summary['grand_total']).toDouble(),
      paid: asNum(summary['paid']).toDouble(),
      balance: asNum(summary['balance']).toDouble(),
      createdBy: asStr(j['created_by']),
    );
  }
}

/// A page of sale returns plus the full-set totals — mirrors [SalesPage].
class SaleReturnsPage {
  SaleReturnsPage({
    required this.rows,
    required this.total,
    required this.totalPaid,
    this.currentPage = 1,
    this.lastPage = 1,
  });
  final List<Map<String, dynamic>> rows;
  final int total;
  final double totalPaid;
  final int currentPage;
  final int lastPage;

  bool get hasMore => currentPage < lastPage;
}

/// One returnable line of a sale (ReturnableSaleResource). Carries the source
/// `sale_item_id` and the remaining returnable quantity so the New Return screen
/// can cap each line's stepper.
class ReturnableSaleLine {
  ReturnableSaleLine({
    required this.saleItemId,
    required this.productId,
    required this.inventoryId,
    required this.unitId,
    required this.conversionFactor,
    required this.name,
    required this.nameArabic,
    required this.type,
    required this.employee,
    required this.employeeId,
    required this.unitPrice,
    required this.discount,
    required this.tax,
    required this.soldQuantity,
    required this.returnedQuantity,
    required this.returnableQuantity,
  });

  final int saleItemId;
  final int productId;
  final int inventoryId;
  final int unitId;
  final double conversionFactor;
  final String name;
  final String nameArabic;
  final String type;
  final String employee;
  final int? employeeId;
  final double unitPrice;
  final double discount;
  final double tax;
  final double soldQuantity;
  final double returnedQuantity;
  final double returnableQuantity;

  factory ReturnableSaleLine.fromJson(Map<String, dynamic> j) => ReturnableSaleLine(
        saleItemId: asNum(j['sale_item_id']).toInt(),
        productId: asNum(j['product_id']).toInt(),
        inventoryId: asNum(j['inventory_id']).toInt(),
        unitId: asNum(j['unit_id']).toInt(),
        conversionFactor: asNum(j['conversion_factor']).toDouble(),
        name: asStr(j['name']),
        nameArabic: asStr(j['name_arabic']),
        type: asStr(j['type']),
        employee: asStr(j['employee']),
        employeeId: j['employee_id'] == null ? null : asNum(j['employee_id']).toInt(),
        unitPrice: asNum(j['unit_price']).toDouble(),
        discount: asNum(j['discount']).toDouble(),
        tax: asNum(j['tax']).toDouble(),
        soldQuantity: asNum(j['sold_quantity']).toDouble(),
        returnedQuantity: asNum(j['returned_quantity']).toDouble(),
        returnableQuantity: asNum(j['returnable_quantity']).toDouble(),
      );
}

/// A sale presented for return (ReturnableSaleResource) — seeds the return draft.
class ReturnableSale {
  ReturnableSale({
    required this.saleId,
    required this.invoiceNo,
    required this.referenceNo,
    required this.date,
    required this.status,
    required this.branch,
    required this.accountId,
    required this.customerName,
    required this.customerMobile,
    required this.lines,
  });

  final String saleId;
  final String invoiceNo;
  final String referenceNo;
  final String date;
  final String status;
  final String branch;
  final int? accountId;
  final String customerName;
  final String customerMobile;
  final List<ReturnableSaleLine> lines;

  factory ReturnableSale.fromJson(Map<String, dynamic> j) {
    final customer = (j['customer'] as Map?) ?? const {};
    return ReturnableSale(
      saleId: asStr(j['sale_id']),
      invoiceNo: asStr(j['invoice_no']),
      referenceNo: asStr(j['reference_no']),
      date: asStr(j['date']),
      status: asStr(j['status']),
      branch: asStr(j['branch']),
      accountId: j['account_id'] == null ? null : asNum(j['account_id']).toInt(),
      customerName: asStr(customer['name']),
      customerMobile: asStr(customer['mobile']),
      lines: ((j['items'] as List?) ?? const [])
          .map((e) => ReturnableSaleLine.fromJson(Map<String, dynamic>.from(e)))
          .toList(),
    );
  }
}

/// A simple labelled metric (dashboard cards).
class Metric {
  Metric({required this.title, required this.value, required this.type, this.percentage});
  final String title;
  final dynamic value;
  final String type; // currency | count
  final String? percentage;

  String get display => type == 'currency' ? Money.of(asNum(value)) : asNum(value).toInt().toString();

  factory Metric.fromJson(Map<String, dynamic> j) => Metric(
        title: asStr(j['title']),
        value: j['value'],
        type: asStr(j['type']),
        percentage: j['percentage']?.toString(),
      );
}

class DashboardData {
  DashboardData({required this.today, required this.inventory, required this.business});
  final List<Metric> today;
  final List<Metric> inventory;
  final List<Metric> business;

  factory DashboardData.fromJson(Map<String, dynamic> j) => DashboardData(
        today: _list(j['todaySummary']),
        inventory: _list(j['inventoryOverview']),
        business: _list(j['bussinessOverview']),
      );

  static List<Metric> _list(dynamic v) => ((v as List?) ?? const [])
      .map((e) => Metric.fromJson(Map<String, dynamic>.from(e)))
      .toList();
}

// ---------------------------------------------------------------------------
// Sales Overview report (GET /admin/reports?type=overview) — mirrors the web
// OverviewReport: sales performance + payment overview + ranked breakdowns.
// ---------------------------------------------------------------------------

class SalesOverview {
  SalesOverview({
    required this.summary,
    required this.payments,
    required this.employees,
    required this.products,
  });

  final OverviewSummary summary;
  final OverviewPayments payments;
  final List<OverviewEmployee> employees;
  final List<OverviewProduct> products;

  factory SalesOverview.fromJson(Map<String, dynamic> j) => SalesOverview(
        summary: OverviewSummary.fromJson(Map<String, dynamic>.from(j['summary'] ?? const {})),
        payments: OverviewPayments.fromJson(Map<String, dynamic>.from(j['payments'] ?? const {})),
        employees: ((j['employees'] as List?) ?? const [])
            .map((e) => OverviewEmployee.fromJson(Map<String, dynamic>.from(e)))
            .toList(),
        products: ((j['products'] as List?) ?? const [])
            .map((e) => OverviewProduct.fromJson(Map<String, dynamic>.from(e)))
            .toList(),
      );
}

class OverviewSummary {
  OverviewSummary({
    required this.grossSales,
    required this.discount,
    required this.netSales,
    required this.totalSales,
    required this.totalItem,
    required this.productSale,
    required this.serviceSale,
    required this.noOfSales,
    required this.noOfSalesReturns,
    required this.successRate,
    required this.collectionRate,
  });

  final double grossSales;
  final double discount;
  final double netSales;
  final double totalSales;
  final double totalItem;
  final double productSale;
  final double serviceSale;
  final int noOfSales;
  final int noOfSalesReturns;
  final double successRate;
  final double collectionRate;

  factory OverviewSummary.fromJson(Map<String, dynamic> j) => OverviewSummary(
        grossSales: asNum(j['gross_sales']).toDouble(),
        discount: asNum(j['discount']).toDouble(),
        netSales: asNum(j['net_sales']).toDouble(),
        totalSales: asNum(j['total_sales']).toDouble(),
        totalItem: asNum(j['total_item']).toDouble(),
        productSale: asNum(j['product_sale']).toDouble(),
        serviceSale: asNum(j['service_sale']).toDouble(),
        noOfSales: asNum(j['no_of_sales']).toInt(),
        noOfSalesReturns: asNum(j['no_of_sales_returns']).toInt(),
        successRate: asNum(j['success_rate']).toDouble(),
        collectionRate: asNum(j['collection_rate']).toDouble(),
      );
}

class OverviewPayments {
  OverviewPayments({
    required this.salesTotal,
    required this.salesTransactions,
    required this.returnsTotal,
    required this.returnsTransactions,
    required this.netPayment,
    required this.totalTransactions,
    required this.credit,
    required this.methods,
    required this.chart,
  });

  final double salesTotal;
  final int salesTransactions;
  final double returnsTotal;
  final int returnsTransactions;
  final double netPayment;
  final int totalTransactions;
  final double credit;
  final List<PaymentMethodStat> methods;
  final List<PaymentChartSlice> chart;

  factory OverviewPayments.fromJson(Map<String, dynamic> j) => OverviewPayments(
        salesTotal: asNum(j['sales_total']).toDouble(),
        salesTransactions: asNum(j['sales_transactions']).toInt(),
        returnsTotal: asNum(j['returns_total']).toDouble(),
        returnsTransactions: asNum(j['returns_transactions']).toInt(),
        netPayment: asNum(j['net_payment']).toDouble(),
        totalTransactions: asNum(j['total_transactions']).toInt(),
        credit: asNum(j['credit']).toDouble(),
        methods: ((j['methods'] as List?) ?? const [])
            .map((e) => PaymentMethodStat.fromJson(Map<String, dynamic>.from(e)))
            .toList(),
        chart: ((j['chart'] as List?) ?? const [])
            .map((e) => PaymentChartSlice.fromJson(Map<String, dynamic>.from(e)))
            .toList(),
      );
}

class PaymentMethodStat {
  PaymentMethodStat({
    required this.method,
    required this.sales,
    required this.returns,
    required this.net,
    required this.salesTransactions,
    required this.returnsTransactions,
  });

  final String method;
  final double sales;
  final double returns;
  final double net;
  final int salesTransactions;
  final int returnsTransactions;

  int get transactions => salesTransactions + returnsTransactions;

  factory PaymentMethodStat.fromJson(Map<String, dynamic> j) => PaymentMethodStat(
        method: asStr(j['method']),
        sales: asNum(j['sales']).toDouble(),
        returns: asNum(j['returns']).toDouble(),
        net: asNum(j['net']).toDouble(),
        salesTransactions: asNum(j['sales_transactions']).toInt(),
        returnsTransactions: asNum(j['returns_transactions']).toInt(),
      );
}

class PaymentChartSlice {
  PaymentChartSlice({required this.label, required this.value});
  final String label;
  final double value;

  factory PaymentChartSlice.fromJson(Map<String, dynamic> j) =>
      PaymentChartSlice(label: asStr(j['label']), value: asNum(j['value']).toDouble());
}

class OverviewEmployee {
  OverviewEmployee({required this.id, required this.name, required this.quantity, required this.total});
  final String id;
  final String name;
  final double quantity;
  final double total;

  factory OverviewEmployee.fromJson(Map<String, dynamic> j) => OverviewEmployee(
        id: asStr(j['id']),
        name: asStr(j['name']),
        quantity: asNum(j['quantity']).toDouble(),
        total: asNum(j['total']).toDouble(),
      );
}

class OverviewProduct {
  OverviewProduct({
    required this.id,
    required this.name,
    required this.type,
    required this.salesQuantity,
    required this.returnQuantity,
    required this.saleTotal,
    required this.saleReturnTotal,
    required this.netAmount,
  });

  final String id;
  final String name;
  final String type;
  final double salesQuantity;
  final double returnQuantity;
  final double saleTotal;
  final double saleReturnTotal;
  final double netAmount;

  factory OverviewProduct.fromJson(Map<String, dynamic> j) => OverviewProduct(
        id: asStr(j['id']),
        name: asStr(j['name']),
        type: asStr(j['type']),
        salesQuantity: asNum(j['sales_quantity']).toDouble(),
        returnQuantity: asNum(j['return_quantity']).toDouble(),
        saleTotal: asNum(j['sale_total']).toDouble(),
        saleReturnTotal: asNum(j['sale_return_total']).toDouble(),
        netAmount: asNum(j['net_amount']).toDouble(),
      );
}
