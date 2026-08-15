import 'package:equatable/equatable.dart';

import '../helpers/formatters.dart';

/// A simple labelled metric (dashboard cards).
class Metric extends Equatable {
  const Metric({required this.title, required this.value, required this.type, this.percentage});
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


  @override
  List<Object?> get props => [
        title,
        value,
        type,
        percentage,
      ];
}

class DashboardData extends Equatable {
  const DashboardData(
      {required this.today, required this.payments, required this.business, this.date = ''});
  final List<Metric> today;

  /// Today's collections grouped by payment method (Cash / Card / Bank / …),
  /// highest first — powers the dashboard's "Today's payments" block.
  final List<Metric> payments;
  final List<Metric> business;

  /// `yyyy-MM-dd` — the business day every figure here is anchored on: the
  /// branch's open day-session date, or the calendar date when no session is
  /// open (App\Actions\V1\Dashboard\GetAction). The client scopes the blocks it
  /// fetches separately — the top performers list — to this same day.
  final String date;

  factory DashboardData.fromJson(Map<String, dynamic> j) => DashboardData(
        today: _list(j['todaySummary']),
        payments: _list(j['paymentSplit']),
        business: _list(j['bussinessOverview']),
        date: asStr(j['date']),
      );

  static List<Metric> _list(dynamic v) => ((v as List?) ?? const [])
      .map((e) => Metric.fromJson(Map<String, dynamic>.from(e)))
      .toList();


  @override
  List<Object?> get props => [
        today,
        payments,
        business,
        date,
      ];
}

// ---------------------------------------------------------------------------
// Sales Overview report (GET /admin/reports?type=overview) — mirrors the web
// OverviewReport: sales performance + payment overview + ranked breakdowns.
// ---------------------------------------------------------------------------

class SalesOverview extends Equatable {
  const SalesOverview({
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


  @override
  List<Object?> get props => [
        summary,
        payments,
        employees,
        products,
      ];
}

class OverviewSummary extends Equatable {
  const OverviewSummary({
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


  @override
  List<Object?> get props => [
        grossSales,
        discount,
        netSales,
        totalSales,
        totalItem,
        productSale,
        serviceSale,
        noOfSales,
        noOfSalesReturns,
        successRate,
        collectionRate,
      ];
}

class OverviewPayments extends Equatable {
  const OverviewPayments({
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


  @override
  List<Object?> get props => [
        salesTotal,
        salesTransactions,
        returnsTotal,
        returnsTransactions,
        netPayment,
        totalTransactions,
        credit,
        methods,
        chart,
      ];
}

class PaymentMethodStat extends Equatable {
  const PaymentMethodStat({
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


  @override
  List<Object?> get props => [
        method,
        sales,
        returns,
        net,
        salesTransactions,
        returnsTransactions,
      ];
}

class PaymentChartSlice extends Equatable {
  const PaymentChartSlice({required this.label, required this.value});
  final String label;
  final double value;

  factory PaymentChartSlice.fromJson(Map<String, dynamic> j) =>
      PaymentChartSlice(label: asStr(j['label']), value: asNum(j['value']).toDouble());


  @override
  List<Object?> get props => [
        label,
        value,
      ];
}

class OverviewEmployee extends Equatable {
  const OverviewEmployee({required this.id, required this.name, required this.quantity, required this.total});
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


  @override
  List<Object?> get props => [
        id,
        name,
        quantity,
        total,
      ];
}

class OverviewProduct extends Equatable {
  const OverviewProduct({
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


  @override
  List<Object?> get props => [
        id,
        name,
        type,
        salesQuantity,
        returnQuantity,
        saleTotal,
        saleReturnTotal,
        netAmount,
      ];
}
