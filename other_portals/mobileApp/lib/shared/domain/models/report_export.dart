import 'package:equatable/equatable.dart';

import '../helpers/formatters.dart';
import 'dashboard.dart';

/// The reports the Reports screen can hand over as a PDF.
enum ReportExportKind {
  overview('Sales Overview', 'sales-overview'),
  items('Item Sales', 'item-sales'),
  categories('Category Sales', 'category-sales'),
  stylists('Staff Sales', 'staff-sales'),
  commission('Staff Commission', 'staff-commission');

  const ReportExportKind(this.title, this.slug);

  /// Printed as the document title and used in the share caption.
  final String title;

  /// File-name stem, e.g. `item-sales_2026-09-01_2026-09-14.pdf`.
  final String slug;
}

/// One ranked line of a breakdown export — an item (itemwise), a category
/// (categorywise), a staff member (employeewise) or a staff member × product
/// commission line (commission). Fields the report type doesn't carry stay
/// zero / empty.
class ReportExportLine extends Equatable {
  const ReportExportLine({
    this.id = '',
    required this.name,
    this.code = '',
    this.quantity = 0,
    this.bills = 0,
    this.items = 0,
    this.amount = 0,
    this.staff = '',
    this.net = 0,
    this.rate = 0,
  });

  /// The staff member's user id on a staff line ('' on item lines), so the
  /// signed-in person's row can be highlighted.
  final String id;
  final String name;
  final String code;
  final double quantity;
  final int bills;

  /// Items a staff member sold, or distinct products sold under a category.
  final int items;

  /// What the line ranks by: the net amount, a staff member's revenue, or the
  /// commission on a commission line.
  final double amount;

  /// Who earned a commission line ('' on every other report).
  final String staff;

  /// Net sales (sales − returns) a commission line was worked out on.
  final double net;

  /// The commission % configured for the staff member × product pair.
  final double rate;

  factory ReportExportLine.item(Map<String, dynamic> j) => ReportExportLine(
        name: asStr(j['item_name']),
        code: asStr(j['item_code']),
        quantity: asNum(j['quantity']).toDouble(),
        bills: asNum(j['bills_count']).toInt(),
        amount: asNum(j['total']).toDouble(),
      );

  factory ReportExportLine.category(Map<String, dynamic> j) => ReportExportLine(
        name: asStr(j['category_name']),
        quantity: asNum(j['quantity']).toDouble(),
        bills: asNum(j['bills_count']).toInt(),
        items: asNum(j['products_count']).toInt(),
        amount: asNum(j['total']).toDouble(),
      );

  factory ReportExportLine.stylist(Map<String, dynamic> j) => ReportExportLine(
        id: asStr(j['employee_id']),
        name: asStr(j['employee_name']),
        bills: asNum(j['bills_count']).toInt(),
        items: asNum(j['items_count']).toInt(),
        amount: asNum(j['revenue']).toDouble(),
      );

  factory ReportExportLine.commission(Map<String, dynamic> j) => ReportExportLine(
        id: asStr(j['employee_id']),
        name: asStr(j['product_name']),
        staff: asStr(j['employee_name']),
        quantity: asNum(j['quantity']).toDouble(),
        net: asNum(j['net_amount']).toDouble(),
        rate: asNum(j['commission_percentage']).toDouble(),
        amount: asNum(j['commission']).toDouble(),
      );

  @override
  List<Object?> get props => [id, name, code, quantity, bills, items, amount, staff, net, rate];
}

/// One day of the overview's day-by-day table, rolled up from bill-wise rows.
class ReportExportDay extends Equatable {
  const ReportExportDay({
    required this.date,
    this.invoices = 0,
    this.gross = 0,
    this.discount = 0,
    this.paid = 0,
  });

  /// `yyyy-MM-dd`.
  final String date;
  final int invoices;
  final double gross;
  final double discount;
  final double paid;

  /// This day with one more bill-wise row (`/admin/reports?type=billwise`) on it.
  ReportExportDay add(Map<String, dynamic> bill) => ReportExportDay(
        date: date,
        invoices: invoices + 1,
        gross: gross + asNum(bill['gross_amount']).toDouble(),
        discount: discount + asNum(bill['discount']).toDouble(),
        paid: paid + asNum(bill['paid']).toDouble(),
      );

  @override
  List<Object?> get props => [date, invoices, gross, discount, paid];
}

/// Everything one report PDF prints, fetched fresh for the filters on screen
/// (see AdminCubit.exportReport).
class ReportExport {
  const ReportExport({
    required this.kind,
    required this.startDate,
    required this.endDate,
    required this.generatedAt,
    this.overview,
    this.days = const [],
    this.daysComplete = true,
    this.categories = const [],
    this.categoryCount = 0,
    this.lines = const [],
    this.lineCount = 0,
    this.totalAmount = 0,
    this.totalQuantity = 0,
    this.totalNet = 0,
    this.staffCount = 0,
    this.staffName = '',
    this.rankByQty = false,
    this.productType,
  });

  final ReportExportKind kind;
  final DateTime startDate;
  final DateTime endDate;
  final DateTime generatedAt;

  // ---- overview ----
  final SalesOverview? overview;

  /// Empty for a one-day range, where the table would only repeat the totals.
  final List<ReportExportDay> days;

  /// False when the range had more bills than an export walks. [days] is then
  /// empty — a capped walk holds only the newest bills, so its oldest day would
  /// print short rather than missing.
  final bool daysComplete;

  /// Sales by category for the range, ranked by amount — only when the export
  /// asked for it (the thermal summary).
  final List<ReportExportLine> categories;

  /// How many categories there are in full — more than [categories] when capped.
  final int categoryCount;

  // ---- breakdowns ----
  /// Every line up to the export cap, in the server's rank order.
  final List<ReportExportLine> lines;

  /// How many lines the report has in full — more than [lines] when capped.
  final int lineCount;
  final double totalAmount;
  final double totalQuantity;

  /// Net sales the commission report's commission was worked out on.
  final double totalNet;

  /// How many staff members the commission report covers.
  final int staffCount;

  /// The one staff member the commission report is narrowed to, '' for all.
  final String staffName;
  final bool rankByQty;

  /// `product` / `service`, or null for every type.
  final String? productType;

  bool get truncated => lines.length < lineCount;

  String get fileName => '${kind.slug}_${Dates.iso(startDate)}_${Dates.iso(endDate)}.pdf';
}
