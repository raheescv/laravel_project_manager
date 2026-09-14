import 'package:equatable/equatable.dart';

import '../helpers/formatters.dart';
import 'dashboard.dart';

/// The three reports the Reports screen can hand over as a PDF.
enum ReportExportKind {
  overview('Sales Overview', 'sales-overview'),
  items('Item Sales', 'item-sales'),
  stylists('Staff Sales', 'staff-sales');

  const ReportExportKind(this.title, this.slug);

  /// Printed as the document title and used in the share caption.
  final String title;

  /// File-name stem, e.g. `item-sales_2026-09-01_2026-09-14.pdf`.
  final String slug;
}

/// One ranked line of a breakdown export — an item (itemwise) or a staff member
/// (employeewise). Fields the report type doesn't carry stay zero / empty.
class ReportExportLine extends Equatable {
  const ReportExportLine({
    required this.name,
    this.code = '',
    this.quantity = 0,
    this.bills = 0,
    this.items = 0,
    this.amount = 0,
  });

  final String name;
  final String code;
  final double quantity;
  final int bills;
  final int items;
  final double amount;

  factory ReportExportLine.item(Map<String, dynamic> j) => ReportExportLine(
        name: asStr(j['item_name']),
        code: asStr(j['item_code']),
        quantity: asNum(j['quantity']).toDouble(),
        bills: asNum(j['bills_count']).toInt(),
        amount: asNum(j['total']).toDouble(),
      );

  factory ReportExportLine.stylist(Map<String, dynamic> j) => ReportExportLine(
        name: asStr(j['employee_name']),
        bills: asNum(j['bills_count']).toInt(),
        items: asNum(j['items_count']).toInt(),
        amount: asNum(j['revenue']).toDouble(),
      );

  @override
  List<Object?> get props => [name, code, quantity, bills, items, amount];
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
    this.lines = const [],
    this.lineCount = 0,
    this.totalAmount = 0,
    this.totalQuantity = 0,
    this.rankByQty = false,
    this.productType,
  });

  final ReportExportKind kind;
  final DateTime startDate;
  final DateTime endDate;
  final DateTime generatedAt;

  // ---- overview ----
  final SalesOverview? overview;
  final List<ReportExportDay> days;

  /// False when the range had more bills than an export walks. [days] is then
  /// empty — a capped walk holds only the newest bills, so its oldest day would
  /// print short rather than missing.
  final bool daysComplete;

  // ---- breakdowns ----
  /// Every line up to the export cap, in the server's rank order.
  final List<ReportExportLine> lines;

  /// How many lines the report has in full — more than [lines] when capped.
  final int lineCount;
  final double totalAmount;
  final double totalQuantity;
  final bool rankByQty;

  /// `product` / `service`, or null for every type.
  final String? productType;

  bool get truncated => lines.length < lineCount;

  String get fileName => '${kind.slug}_${Dates.iso(startDate)}_${Dates.iso(endDate)}.pdf';
}
