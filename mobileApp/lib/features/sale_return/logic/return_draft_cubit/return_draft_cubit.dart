import 'package:invo/shared/domain/helpers/formatters.dart';
import 'package:invo/features/sale/logic/cart_cubit/cart_cubit.dart'
    show CustomPayment, PayMode, PayModeX;
import 'package:invo/shared/domain/models/index.dart';
import 'package:equatable/equatable.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import 'package:invo/shared/utils/components/app_strings.dart';

part 'return_draft_state.dart';

/// One editable return line: a returnable sale line plus the quantity being
/// returned (0..returnableQuantity). The line discount is prorated to the
/// returned quantity, matching the backend (V1\SaleReturn\CreateAction).
class ReturnLine extends Equatable {
  const ReturnLine(this.source,
      {this.returnQty = 0, this.returnItemId, this.editBonus = 0});

  final ReturnableSaleLine source;
  final double returnQty;

  /// The existing sale_return_item id when editing; null for a fresh line.
  final int? returnItemId;

  /// Quantity already on the return being edited — added back to the
  /// returnable allowance so an edit can keep what it previously returned.
  final double editBonus;

  ReturnLine copyWith({double? returnQty, int? returnItemId, double? editBonus}) =>
      ReturnLine(source,
          returnQty: returnQty ?? this.returnQty,
          returnItemId: returnItemId ?? this.returnItemId,
          editBonus: editBonus ?? this.editBonus);

  @override
  List<Object?> get props => [source, returnQty, returnItemId, editBonus];

  int get saleItemId => source.saleItemId;
  String get name => source.name;
  String get type => source.type;
  String get employee => source.employee;
  String get thumbnail => '';
  double get unitPrice => source.unitPrice;
  double get sold => source.soldQuantity;
  double get returnable => source.returnableQuantity + editBonus;
  double get tax => source.tax;

  // Rounded at the same points as the server's decimal(16,2) generated columns
  // on sale_return_items — see the note on CartLine. Pro-rating the original
  // line discount is exactly the kind of division that leaves 3+ decimals.
  double get discount => round2(source.soldQuantity > 0
      ? source.discount * returnQty / source.soldQuantity
      : 0);

  double get base => round2(unitPrice * returnQty);
  double get net => round2((base - discount).clamp(0, double.infinity));
  double get taxAmount => round2(net * tax / 100.0);
  double get total => net + taxAmount;

  bool get isReturning => returnQty > 0;
  bool get fullyReturned => returnable <= 0;
}

/// The live sale-return draft: the source invoice, per-line return quantities,
/// and the refund payment selection — modelled on [CartCubit].
class ReturnDraftCubit extends Cubit<ReturnDraftState> {
  ReturnDraftCubit() : super(const ReturnDraftState());

  // Read facade over `state`, so existing call sites were untouched.
  String get saleId => state.saleId;
  String get invoiceNo => state.invoiceNo;
  String get saleDate => state.saleDate;
  String get customerName => state.customerName;
  String get customerMobile => state.customerMobile;
  int? get accountId => state.accountId;
  String get editingReturnId => state.editingReturnId;
  bool get isEditing => state.isEditing;
  List<ReturnLine> get lines => state.lines;
  PayMode get payMode => state.payMode;
  List<CustomPayment> get customPayments => state.customPayments;
  bool get isSeeded => state.isSeeded;
  bool get isEmpty => state.isEmpty;
  List<ReturnLine> get returningLines => state.returningLines;
  int get count => state.count;
  double get subtotal => state.subtotal;
  double get totalDiscount => state.totalDiscount;
  double get taxTotal => state.taxTotal;
  double get total => state.total;
  double get refundAmount => state.refundAmount;
  double get balance => state.balance;

  void seed(ReturnableSale sale) => emit(ReturnDraftState(
        saleId: sale.saleId,
        invoiceNo: sale.invoiceNo.isNotEmpty ? sale.invoiceNo : sale.referenceNo,
        saleDate: sale.date,
        customerName: sale.customerName.isEmpty
            ? AppStrings.walkInCustomer
            : sale.customerName,
        customerMobile: sale.customerMobile,
        accountId: sale.accountId,
        lines: sale.lines.map(ReturnLine.new).toList(),
      ));

  void seedForEdit(ReturnableSale sale, SaleReturn existing) {
    seed(sale);
    final bySaleItem = <int, SaleReturnLine>{};
    for (final l in existing.lines) {
      if (l.saleItemId != null) bySaleItem[l.saleItemId!] = l;
    }
    final payment = _seedPayments(existing.payments, existing.paid);
    emit(state.copyWith(
      editingReturnId: existing.id,
      accountId: existing.accountId ?? state.accountId,
      lines: [
        for (final line in state.lines)
          if (bySaleItem[line.saleItemId] case final prior?)
            line.copyWith(
                editBonus: prior.quantity,
                returnQty: prior.quantity,
                returnItemId: prior.itemId)
          else
            line,
      ],
      payMode: payment.mode,
      customPayments: payment.rows,
    ));
  }

  ({PayMode mode, List<CustomPayment> rows}) _seedPayments(
      List<SaleReturnPayment> payments, double paid) {
    if (payments.isEmpty) {
      return (mode: paid > 0 ? PayMode.cash : PayMode.credit, rows: const []);
    }
    if (payments.length == 1 && payments.first.paymentMethodId == null) {
      final name = payments.first.method.toLowerCase();
      if (name.contains('cash')) return (mode: PayMode.cash, rows: const []);
      if (name.contains('card')) return (mode: PayMode.card, rows: const []);
    }
    final rows = payments
        .where((p) => p.paymentMethodId != null)
        .map((p) => CustomPayment(
            methodId: p.paymentMethodId!, methodName: p.method, amount: p.amount))
        .toList();
    if (rows.isEmpty) {
      return (mode: paid > 0 ? PayMode.cash : PayMode.credit, rows: const []);
    }
    return (mode: PayMode.custom, rows: rows);
  }

  void setQty(ReturnLine line, double qty) {
    final i = state.lines.indexOf(line);
    if (i == -1) return;
    final rows = [...state.lines];
    rows[i] = line.copyWith(returnQty: qty.clamp(0, line.returnable).toDouble());
    emit(state.copyWith(lines: rows));
  }

  void changeQty(ReturnLine line, double delta) =>
      setQty(line, line.returnQty + delta);

  void setPayMode(PayMode mode) => emit(state.copyWith(
        payMode: mode,
        customPayments: mode == PayMode.custom ? null : const [],
      ));

  void setCustomPayments(List<CustomPayment> payments) => emit(state.copyWith(
        customPayments: payments,
        payMode: PayMode.custom,
      ));

  void clear() => emit(const ReturnDraftState());

  /// Build the POST /sale-return payload (matches SaleReturn StoreRequest).
  Map<String, dynamic> toPayload() => {
        'sale_id': int.tryParse(state.saleId) ?? state.saleId,
        if (state.accountId != null) 'account_id': state.accountId,
        'items': state.returningLines
            .map((l) => {
                  if (l.returnItemId != null) 'id': l.returnItemId,
                  'sale_item_id': l.saleItemId,
                  'quantity': l.returnQty,
                  'unitPrice': l.unitPrice,
                  'discount': l.discount,
                })
            .toList(),
        'paymentMethod': state.payMode.apiValue,
        'totalPayment': state.total,
        if (state.payMode == PayMode.custom)
          'payments': state.customPayments
              .map((p) => {
                    'payment_method_id': p.methodId,
                    'amount': round2(p.amount),
                  })
              .toList(),
      };
}
