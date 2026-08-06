part of 'cart_cubit.dart';

/// The live ticket, as an immutable value — the §5 shape.
///
/// Every total is derived here rather than on the cubit, so the numbers are a
/// pure function of the ticket: given the same state you always get the same
/// figures, and they can be asserted without building a cubit.
///
/// Rounding follows the server's `decimal(16,2)` generated columns at every
/// step — see the note on [CartLine] and §8.1 of the architecture skeleton.
class CartState extends Equatable {
  const CartState({
    this.lines = const [],
    this.customerName = AppStrings.walkInCustomer,
    this.customerMobile = '',
    this.stylistId,
    this.stylistName = '',
    this.editingSaleId,
    this.orderDiscount = 0,
    this.orderDiscountIsPercent = false,
    this.tipPercent = 0,
    this.payMode = PayMode.cash,
    this.customPayments = const [],
    this.sendToWhatsapp = false,
  });

  final List<CartLine> lines;
  final String customerName;
  final String customerMobile;
  final int? stylistId;
  final String stylistName;

  /// Set when this ticket is an edit of an existing sale.
  final String? editingSaleId;

  final double orderDiscount;
  final bool orderDiscountIsPercent;
  final double tipPercent;

  final PayMode payMode;
  final List<CustomPayment> customPayments;
  final bool sendToWhatsapp;

  bool get isEditing => editingSaleId != null;
  bool get isEmpty => lines.isEmpty;
  int get count => lines.fold(0, (a, l) => a + l.qty.round());

  // ---- totals ----
  // MySQL sums DECIMALs exactly; adding 2dp doubles still accumulates binary
  // error (99.99 + 19.99 = 119.97999999999999), so every aggregate is rounded
  // back to the 2dp the matching column holds.

  /// `SUM(sale_items.gross_amount)`.
  double get subtotal => round2(lines.fold(0, (a, l) => a + l.base));

  /// `SUM(sale_items.discount)` — the sale's `item_discount`.
  double get lineDiscounts => round2(lines.fold(0, (a, l) => a + l.discountAmount));

  /// The sale's `other_discount` column.
  double get orderDiscountAmount {
    if (!orderDiscountIsPercent) return round2(orderDiscount);
    final base = (subtotal - lineDiscounts).clamp(0, double.infinity);
    return round2(base * orderDiscount / 100.0);
  }

  double get totalDiscount => round2(lineDiscounts + orderDiscountAmount);

  /// `SUM(sale_items.tax_amount)`.
  double get taxTotal => round2(lines.fold(0, (a, l) => a + l.taxAmount));

  /// Mirrors the generated `grand_total`:
  /// `(gross_amount - item_discount + tax_amount) - other_discount`.
  double get netBeforeTip =>
      round2((subtotal - totalDiscount + taxTotal).clamp(0, double.infinity));
  double get tipAmount => round2(netBeforeTip * tipPercent / 100.0);
  double get total => round2(netBeforeTip + tipAmount);

  double get paidAmount => switch (payMode) {
        PayMode.credit => 0,
        PayMode.custom => round2(customPayments.fold(0.0, (a, p) => a + p.amount)),
        _ => total,
      };

  double get balance => round2(total - paidAmount);

  CartState copyWith({
    List<CartLine>? lines,
    String? customerName,
    String? customerMobile,
    int? stylistId,
    String? stylistName,
    String? editingSaleId,
    double? orderDiscount,
    bool? orderDiscountIsPercent,
    double? tipPercent,
    PayMode? payMode,
    List<CustomPayment>? customPayments,
    bool? sendToWhatsapp,
    bool clearStylist = false,
    bool clearEditingSaleId = false,
  }) =>
      CartState(
        lines: lines ?? this.lines,
        customerName: customerName ?? this.customerName,
        customerMobile: customerMobile ?? this.customerMobile,
        stylistId: clearStylist ? null : (stylistId ?? this.stylistId),
        stylistName: clearStylist ? '' : (stylistName ?? this.stylistName),
        editingSaleId:
            clearEditingSaleId ? null : (editingSaleId ?? this.editingSaleId),
        orderDiscount: orderDiscount ?? this.orderDiscount,
        orderDiscountIsPercent:
            orderDiscountIsPercent ?? this.orderDiscountIsPercent,
        tipPercent: tipPercent ?? this.tipPercent,
        payMode: payMode ?? this.payMode,
        customPayments: customPayments ?? this.customPayments,
        sendToWhatsapp: sendToWhatsapp ?? this.sendToWhatsapp,
      );

  @override
  List<Object?> get props => [
        lines,
        customerName,
        customerMobile,
        stylistId,
        stylistName,
        editingSaleId,
        orderDiscount,
        orderDiscountIsPercent,
        tipPercent,
        payMode,
        customPayments,
        sendToWhatsapp,
      ];
}
