part of 'return_draft_cubit.dart';

/// The live sale-return draft, as an immutable value — the §5 shape, mirroring
/// [CartState]. Totals are derived here so they are a pure function of the
/// draft, rounded at the server's `decimal(16,2)` points (§8.1).
class ReturnDraftState extends Equatable {
  const ReturnDraftState({
    this.saleId = '',
    this.invoiceNo = '',
    this.saleDate = '',
    this.customerName = AppStrings.walkInCustomer,
    this.customerMobile = '',
    this.accountId,
    this.editingReturnId = '',
    this.lines = const [],
    this.payMode = PayMode.cash,
    this.customPayments = const [],
  });

  final String saleId;
  final String invoiceNo;
  final String saleDate;
  final String customerName;
  final String customerMobile;
  final int? accountId;

  /// Set when editing an existing return rather than authoring a new one.
  final String editingReturnId;

  final List<ReturnLine> lines;
  final PayMode payMode;
  final List<CustomPayment> customPayments;

  bool get isEditing => editingReturnId.isNotEmpty;
  bool get isSeeded => saleId.isNotEmpty;
  bool get isEmpty => !lines.any((l) => l.isReturning);
  List<ReturnLine> get returningLines => lines.where((l) => l.isReturning).toList();
  int get count => lines.fold(0, (a, l) => a + l.returnQty.round());

  // Aggregates rounded back to 2dp: MySQL sums DECIMALs exactly, adding 2dp
  // doubles does not.
  double get subtotal => round2(returningLines.fold(0.0, (a, l) => a + l.base));
  double get totalDiscount =>
      round2(returningLines.fold(0.0, (a, l) => a + l.discount));
  double get taxTotal => round2(returningLines.fold(0.0, (a, l) => a + l.taxAmount));
  double get total => round2(returningLines.fold(0.0, (a, l) => a + l.total));

  double get refundAmount => switch (payMode) {
        PayMode.credit => 0,
        PayMode.custom => round2(customPayments.fold(0.0, (a, p) => a + p.amount)),
        _ => total,
      };

  double get balance => round2(total - refundAmount);

  ReturnDraftState copyWith({
    String? saleId,
    String? invoiceNo,
    String? saleDate,
    String? customerName,
    String? customerMobile,
    int? accountId,
    String? editingReturnId,
    List<ReturnLine>? lines,
    PayMode? payMode,
    List<CustomPayment>? customPayments,
  }) =>
      ReturnDraftState(
        saleId: saleId ?? this.saleId,
        invoiceNo: invoiceNo ?? this.invoiceNo,
        saleDate: saleDate ?? this.saleDate,
        customerName: customerName ?? this.customerName,
        customerMobile: customerMobile ?? this.customerMobile,
        accountId: accountId ?? this.accountId,
        editingReturnId: editingReturnId ?? this.editingReturnId,
        lines: lines ?? this.lines,
        payMode: payMode ?? this.payMode,
        customPayments: customPayments ?? this.customPayments,
      );

  @override
  List<Object?> get props => [
        saleId, invoiceNo, saleDate, customerName, customerMobile, accountId,
        editingReturnId, lines, payMode, customPayments,
      ];
}
