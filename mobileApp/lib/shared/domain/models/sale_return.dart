import 'package:equatable/equatable.dart';

import '../helpers/formatters.dart';

/// One line on a saved sale return — mirrors [SaleLine].
class SaleReturnLine extends Equatable {
  const SaleReturnLine({
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
        saleItemId,
        productId,
        employeeId,
        tax,
      ];
}

/// A refund payment on a saved sale return — mirrors [SalePayment].
class SaleReturnPayment extends Equatable {
  const SaleReturnPayment({required this.method, required this.amount, this.paymentId, this.paymentMethodId});
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


  @override
  List<Object?> get props => [
        method,
        amount,
        paymentId,
        paymentMethodId,
      ];
}

/// A saved sale return (SaleReturnResource). Mirrors [Sale]; sale returns have
/// no invoice_no, so [referenceNo] carries the document number.
class SaleReturn extends Equatable {
  const SaleReturn({
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


  @override
  List<Object?> get props => [
        id,
        referenceNo,
        saleId,
        accountId,
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
        total,
        grandTotal,
        paid,
        balance,
        createdBy,
      ];
}

/// A page of sale returns plus the full-set totals — mirrors [SalesPage].
class SaleReturnsPage extends Equatable {
  const SaleReturnsPage({
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

/// One returnable line of a sale (ReturnableSaleResource). Carries the source
/// `sale_item_id` and the remaining returnable quantity so the New Return screen
/// can cap each line's stepper.
class ReturnableSaleLine extends Equatable {
  const ReturnableSaleLine({
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


  @override
  List<Object?> get props => [
        saleItemId,
        productId,
        inventoryId,
        unitId,
        conversionFactor,
        name,
        nameArabic,
        type,
        employee,
        employeeId,
        unitPrice,
        discount,
        tax,
        soldQuantity,
        returnedQuantity,
        returnableQuantity,
      ];
}

/// A sale presented for return (ReturnableSaleResource) — seeds the return draft.
class ReturnableSale extends Equatable {
  const ReturnableSale({
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


  @override
  List<Object?> get props => [
        saleId,
        invoiceNo,
        referenceNo,
        date,
        status,
        branch,
        accountId,
        customerName,
        customerMobile,
        lines,
      ];
}
