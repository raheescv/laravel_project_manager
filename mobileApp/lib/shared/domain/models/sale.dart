import 'package:equatable/equatable.dart';

import '../helpers/formatters.dart';

/// A page of sales plus the full-set totals (count + sum of paid) so the Sales
/// screen can show an accurate "N invoices · total" line regardless of paging.
class SalesPage extends Equatable {
  const SalesPage({
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


  @override
  List<Object?> get props => [
        rows,
        total,
        totalPaid,
        currentPage,
        lastPage,
      ];
}

// ---- Sale (SaleResource) ----

class SaleLine extends Equatable {
  const SaleLine({
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


  @override
  List<Object?> get props => [
        name,
        nameArabic,
        type,
        employee,
        quantity,
        unitPrice,
        discount,
        total,
        itemId,
        productId,
        employeeId,
        code,
        tax,
      ];
}

class SalePayment extends Equatable {
  const SalePayment({required this.method, required this.amount, this.paymentId, this.paymentMethodId});
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


  @override
  List<Object?> get props => [
        method,
        amount,
        paymentId,
        paymentMethodId,
      ];
}

class Sale extends Equatable {
  const Sale({
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


  @override
  List<Object?> get props => [
        id,
        invoiceNo,
        date,
        status,
        branch,
        customerName,
        customerMobile,
        lines,
        payments,
        grossAmount,
        itemDiscount,
        otherDiscount,
        taxAmount,
        tip,
        grandTotal,
        paid,
        balance,
        createdBy,
      ];
}
