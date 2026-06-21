import 'package:flutter/foundation.dart';

import '../models/models.dart';
import 'cart_controller.dart' show CustomPayment, PayMode, PayModeX;

/// One editable return line: a returnable sale line plus the quantity being
/// returned (0..returnableQuantity). The line discount is prorated to the
/// returned quantity, matching the backend (V1\SaleReturn\CreateAction).
class ReturnLine {
  ReturnLine(this.source);

  final ReturnableSaleLine source;
  double returnQty = 0;

  int get saleItemId => source.saleItemId;
  String get name => source.name;
  String get type => source.type;
  String get employee => source.employee;
  String get thumbnail => '';
  double get unitPrice => source.unitPrice;
  double get sold => source.soldQuantity;
  double get returnable => source.returnableQuantity;
  double get tax => source.tax;

  /// Original line discount prorated to the returned quantity.
  double get discount =>
      source.soldQuantity > 0 ? source.discount * returnQty / source.soldQuantity : 0;

  double get base => unitPrice * returnQty;
  double get net => (base - discount).clamp(0, double.infinity);
  double get taxAmount => net * tax / 100.0;
  double get total => net + taxAmount;

  bool get isReturning => returnQty > 0;
  bool get fullyReturned => returnable <= 0;
}

/// The live sale-return draft: the source invoice, per-line return quantities,
/// and the refund payment selection — modelled on [CartController].
class ReturnDraftController extends ChangeNotifier {
  String saleId = '';
  String invoiceNo = '';
  String saleDate = '';
  String customerName = 'Walk-in';
  String customerMobile = '';
  int? accountId;

  final List<ReturnLine> _lines = [];
  List<ReturnLine> get lines => List.unmodifiable(_lines);

  PayMode payMode = PayMode.cash;
  List<CustomPayment> customPayments = [];

  bool get isSeeded => saleId.isNotEmpty;
  bool get isEmpty => !_lines.any((l) => l.isReturning);
  List<ReturnLine> get returningLines => _lines.where((l) => l.isReturning).toList();
  int get count => _lines.fold(0, (a, l) => a + l.returnQty.round());

  /// Seed the draft from the selected invoice's returnable lines.
  void seed(ReturnableSale sale) {
    saleId = sale.saleId;
    invoiceNo = sale.invoiceNo.isNotEmpty ? sale.invoiceNo : sale.referenceNo;
    saleDate = sale.date;
    customerName = sale.customerName.isEmpty ? 'Walk-in' : sale.customerName;
    customerMobile = sale.customerMobile;
    accountId = sale.accountId;
    _lines
      ..clear()
      ..addAll(sale.lines.map(ReturnLine.new));
    payMode = PayMode.cash;
    customPayments = [];
    notifyListeners();
  }

  void setQty(ReturnLine line, double qty) {
    line.returnQty = qty.clamp(0, line.returnable);
    notifyListeners();
  }

  void changeQty(ReturnLine line, double delta) => setQty(line, line.returnQty + delta);

  /// Switch refund mode. Leaving "custom" clears any split breakdown so a later
  /// Cash/Card refund doesn't carry stale rows.
  void setPayMode(PayMode mode) {
    payMode = mode;
    if (mode != PayMode.custom) customPayments = [];
    notifyListeners();
  }

  void setCustomPayments(List<CustomPayment> payments) {
    customPayments = payments;
    payMode = PayMode.custom;
    notifyListeners();
  }

  // ---- totals (only the returning lines count) ----
  double get subtotal => returningLines.fold(0.0, (a, l) => a + l.base);
  double get totalDiscount => returningLines.fold(0.0, (a, l) => a + l.discount);
  double get taxTotal => returningLines.fold(0.0, (a, l) => a + l.taxAmount);
  double get total => returningLines.fold(0.0, (a, l) => a + l.total);

  /// What is actually refunded now: the full total for Cash/Card, nothing for
  /// Credit, and the sum of the rows for a Custom split.
  double get refundAmount => switch (payMode) {
        PayMode.credit => 0,
        PayMode.custom => customPayments.fold(0.0, (a, p) => a + p.amount),
        _ => total,
      };

  /// Positive → still owed to the customer; negative → over-refunded; 0 → settled.
  double get balance => total - refundAmount;

  void clear() {
    saleId = '';
    invoiceNo = '';
    saleDate = '';
    customerName = 'Walk-in';
    customerMobile = '';
    accountId = null;
    _lines.clear();
    payMode = PayMode.cash;
    customPayments = [];
    notifyListeners();
  }

  /// Build the POST /sale-return payload (matches SaleReturn StoreRequest).
  Map<String, dynamic> toPayload() => {
        'sale_id': int.tryParse(saleId) ?? saleId,
        if (accountId != null) 'account_id': accountId,
        'items': returningLines
            .map((l) => {
                  'sale_item_id': l.saleItemId,
                  'quantity': l.returnQty,
                  'unitPrice': l.unitPrice,
                  'discount': double.parse(l.discount.toStringAsFixed(2)),
                })
            .toList(),
        'paymentMethod': payMode.apiValue,
        'totalPayment': double.parse(total.toStringAsFixed(2)),
        if (payMode == PayMode.custom)
          'payments': customPayments
              .map((p) => {
                    'payment_method_id': p.methodId,
                    'amount': double.parse(p.amount.toStringAsFixed(2)),
                  })
              .toList(),
      };
}
