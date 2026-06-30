import 'package:invo/features/sale/logic/cart_cubit/cart_cubit.dart'
    show CustomPayment, PayMode, PayModeX;
import 'package:invo/shared/domain/models/index.dart';
import 'package:invo/shared/logic/base/holder_cubit.dart';

/// One editable return line: a returnable sale line plus the quantity being
/// returned (0..returnableQuantity). The line discount is prorated to the
/// returned quantity, matching the backend (V1\SaleReturn\CreateAction).
class ReturnLine {
  ReturnLine(this.source);

  final ReturnableSaleLine source;
  double returnQty = 0;

  int? returnItemId;
  double editBonus = 0;

  int get saleItemId => source.saleItemId;
  String get name => source.name;
  String get type => source.type;
  String get employee => source.employee;
  String get thumbnail => '';
  double get unitPrice => source.unitPrice;
  double get sold => source.soldQuantity;
  double get returnable => source.returnableQuantity + editBonus;
  double get tax => source.tax;

  double get discount => source.soldQuantity > 0
      ? source.discount * returnQty / source.soldQuantity
      : 0;

  double get base => unitPrice * returnQty;
  double get net => (base - discount).clamp(0, double.infinity);
  double get taxAmount => net * tax / 100.0;
  double get total => net + taxAmount;

  bool get isReturning => returnQty > 0;
  bool get fullyReturned => returnable <= 0;
}

/// The live sale-return draft: the source invoice, per-line return quantities,
/// and the refund payment selection — modelled on [CartCubit].
class ReturnDraftCubit extends HolderCubit {
  ReturnDraftCubit();

  String saleId = '';
  String invoiceNo = '';
  String saleDate = '';
  String customerName = 'Walk-in';
  String customerMobile = '';
  int? accountId;

  String editingReturnId = '';
  bool get isEditing => editingReturnId.isNotEmpty;

  final List<ReturnLine> _lines = [];
  List<ReturnLine> get lines => List.unmodifiable(_lines);

  PayMode payMode = PayMode.cash;
  List<CustomPayment> customPayments = [];

  bool get isSeeded => saleId.isNotEmpty;
  bool get isEmpty => !_lines.any((l) => l.isReturning);
  List<ReturnLine> get returningLines =>
      _lines.where((l) => l.isReturning).toList();
  int get count => _lines.fold(0, (a, l) => a + l.returnQty.round());

  void seed(ReturnableSale sale) {
    saleId = sale.saleId;
    invoiceNo = sale.invoiceNo.isNotEmpty ? sale.invoiceNo : sale.referenceNo;
    saleDate = sale.date;
    customerName = sale.customerName.isEmpty ? 'Walk-in' : sale.customerName;
    customerMobile = sale.customerMobile;
    accountId = sale.accountId;
    editingReturnId = '';
    _lines
      ..clear()
      ..addAll(sale.lines.map(ReturnLine.new));
    payMode = PayMode.cash;
    customPayments = [];
    refresh();
  }

  void seedForEdit(ReturnableSale sale, SaleReturn existing) {
    seed(sale);
    editingReturnId = existing.id;
    if (existing.accountId != null) accountId = existing.accountId;

    final bySaleItem = <int, SaleReturnLine>{};
    for (final l in existing.lines) {
      if (l.saleItemId != null) bySaleItem[l.saleItemId!] = l;
    }
    for (final line in _lines) {
      final prior = bySaleItem[line.saleItemId];
      if (prior != null) {
        line.editBonus = prior.quantity;
        line.returnQty = prior.quantity;
        line.returnItemId = prior.itemId;
      }
    }
    _seedPayments(existing.payments, existing.paid);
    refresh();
  }

  void _seedPayments(List<SaleReturnPayment> payments, double paid) {
    if (payments.isEmpty) {
      payMode = paid > 0 ? PayMode.cash : PayMode.credit;
      customPayments = [];
      return;
    }
    if (payments.length == 1 && payments.first.paymentMethodId == null) {
      final name = payments.first.method.toLowerCase();
      if (name.contains('cash')) {
        payMode = PayMode.cash;
        customPayments = [];
        return;
      }
      if (name.contains('card')) {
        payMode = PayMode.card;
        customPayments = [];
        return;
      }
    }
    final rows = payments
        .where((p) => p.paymentMethodId != null)
        .map((p) => CustomPayment(
            methodId: p.paymentMethodId!, methodName: p.method, amount: p.amount))
        .toList();
    if (rows.isEmpty) {
      payMode = paid > 0 ? PayMode.cash : PayMode.credit;
      customPayments = [];
      return;
    }
    payMode = PayMode.custom;
    customPayments = rows;
  }

  void setQty(ReturnLine line, double qty) {
    line.returnQty = qty.clamp(0, line.returnable);
    refresh();
  }

  void changeQty(ReturnLine line, double delta) =>
      setQty(line, line.returnQty + delta);

  void setPayMode(PayMode mode) {
    payMode = mode;
    if (mode != PayMode.custom) customPayments = [];
    refresh();
  }

  void setCustomPayments(List<CustomPayment> payments) {
    customPayments = payments;
    payMode = PayMode.custom;
    refresh();
  }

  // ---- totals (only the returning lines count) ----
  double get subtotal => returningLines.fold(0.0, (a, l) => a + l.base);
  double get totalDiscount =>
      returningLines.fold(0.0, (a, l) => a + l.discount);
  double get taxTotal => returningLines.fold(0.0, (a, l) => a + l.taxAmount);
  double get total => returningLines.fold(0.0, (a, l) => a + l.total);

  double get refundAmount => switch (payMode) {
        PayMode.credit => 0,
        PayMode.custom => customPayments.fold(0.0, (a, p) => a + p.amount),
        _ => total,
      };

  double get balance => total - refundAmount;

  void clear() {
    saleId = '';
    invoiceNo = '';
    saleDate = '';
    customerName = 'Walk-in';
    customerMobile = '';
    accountId = null;
    editingReturnId = '';
    _lines.clear();
    payMode = PayMode.cash;
    customPayments = [];
    refresh();
  }

  /// Build the POST /sale-return payload (matches SaleReturn StoreRequest).
  Map<String, dynamic> toPayload() => {
        'sale_id': int.tryParse(saleId) ?? saleId,
        if (accountId != null) 'account_id': accountId,
        'items': returningLines
            .map((l) => {
                  if (l.returnItemId != null) 'id': l.returnItemId,
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
