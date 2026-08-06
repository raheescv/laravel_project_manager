import 'package:equatable/equatable.dart';
import 'package:invo/features/settings/logic/print_settings_cubit/print_settings_cubit.dart';
import 'package:invo/shared/domain/constants/global_variables.dart';
import 'package:invo/shared/domain/helpers/formatters.dart';
import 'package:invo/shared/domain/models/index.dart';
import 'package:invo/shared/domain/repository/lookup_repository.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import 'package:invo/shared/utils/components/app_strings.dart';
import 'package:invo/shared/utils/local_storage/local_storage_service.dart';

part 'cart_state.dart';

/// One line on the ticket. Discount can be a percentage or a flat amount; tax
/// is a percentage. Mirrors the "Edit line item" sheet fields.
///
/// Immutable: [CartCubit] hands these out through `lines`, so a settable field
/// would let a widget change the total that gets posted to the server without
/// the cubit emitting a tick — the UI would keep showing the old figures while
/// the payload disagreed. Edits go through [CartCubit.updateLine] and friends,
/// which replace the element and then `refresh()`.
class CartLine extends Equatable {
  const CartLine({
    required this.productId,
    required this.name,
    required this.code,
    required this.type,
    required this.unitPrice,
    required this.qty,
    this.discountValue = 0,
    this.discountIsPercent = true,
    this.taxPercent = 0,
    this.employeeId,
    this.employeeName = '',
    this.thumbnail = '',
    this.saleItemId,
  });

  final int productId;
  // The existing sale_item id when this line came from a sale being edited;
  // null for a line added fresh. Sent back so the server patches in place.
  final int? saleItemId;
  final String name;
  final String code;
  final String type;
  final String thumbnail;
  final double unitPrice;
  final double qty;
  final double discountValue;
  final bool discountIsPercent;
  final double taxPercent;
  final int? employeeId;
  final String employeeName;

  /// Copy with the editable fields replaced. A null argument means "leave as
  /// is" — to *clear* the assigned employee use [withEmployee].
  CartLine copyWith({
    double? unitPrice,
    double? qty,
    double? discountValue,
    bool? discountIsPercent,
    double? taxPercent,
    int? employeeId,
    String? employeeName,
  }) =>
      CartLine(
        productId: productId,
        saleItemId: saleItemId,
        name: name,
        code: code,
        type: type,
        thumbnail: thumbnail,
        unitPrice: unitPrice ?? this.unitPrice,
        qty: qty ?? this.qty,
        discountValue: discountValue ?? this.discountValue,
        discountIsPercent: discountIsPercent ?? this.discountIsPercent,
        taxPercent: taxPercent ?? this.taxPercent,
        employeeId: employeeId ?? this.employeeId,
        employeeName: employeeName ?? this.employeeName,
      );

  /// Copy with the assigned employee replaced, including clearing it — the one
  /// case [copyWith]'s null-means-unchanged rule can't express.
  CartLine withEmployee(int? id, String name) => CartLine(
        productId: productId,
        saleItemId: saleItemId,
        name: this.name,
        code: code,
        type: type,
        thumbnail: thumbnail,
        unitPrice: unitPrice,
        qty: qty,
        discountValue: discountValue,
        discountIsPercent: discountIsPercent,
        taxPercent: taxPercent,
        employeeId: id,
        employeeName: name,
      );

  @override
  List<Object?> get props => [
        productId,
        saleItemId,
        name,
        code,
        type,
        thumbnail,
        unitPrice,
        qty,
        discountValue,
        discountIsPercent,
        taxPercent,
        employeeId,
        employeeName,
      ];

  // Rounded at exactly the points the server's generated columns are, so the
  // figures on the ticket are the figures MySQL will store:
  //   sale_items.gross_amount = unit_price * quantity
  //   sale_items.net_amount   = gross_amount - discount
  //   sale_items.tax_amount   = (net_amount * tax) / 100
  //   sale_items.total        = net_amount + tax_amount
  // — each of them decimal(16,2). Summing unrounded doubles and rounding once
  // at the end drifts from that by a cent on percentage discounts and tax.

  /// `gross_amount`.
  double get base => round2(unitPrice * qty);

  /// The `discount` column — also what [CartCubit.toPayload] sends.
  double get discountAmount => round2(
      discountIsPercent ? base * discountValue / 100.0 : discountValue);

  /// `net_amount`.
  double get taxable => round2((base - discountAmount).clamp(0, double.infinity));

  /// `tax_amount`.
  double get taxAmount => round2(taxable * taxPercent / 100.0);

  /// `total` — both operands are already 2dp, so the sum is exact.
  double get total => taxable + taxAmount;

  String get discountLabel => discountValue <= 0
      ? ''
      : discountIsPercent
          ? '${discountValue.toStringAsFixed(discountValue.truncateToDouble() == discountValue ? 0 : 1)}% off'
          // Flat discounts are money, so they follow the configured currency —
          // never a hardcoded symbol.
          : '${Money.of(discountValue)} off';
}

/// How the ticket is being settled. Mirrors the web POS "Confirm Sale" modes.
enum PayMode { cash, card, credit, custom }

extension PayModeX on PayMode {
  /// The `paymentMethod` string the Sale API expects for this mode.
  String get apiValue => switch (this) {
        PayMode.cash => 'Cash',
        PayMode.card => 'Card',
        PayMode.credit => 'credit',
        PayMode.custom => 'custom',
      };

  String get label => switch (this) {
        PayMode.cash => 'Cash',
        PayMode.card => 'Card',
        PayMode.credit => 'Credit',
        PayMode.custom => 'Custom',
      };
}

/// One row of a custom (split) payment — a configured method + the amount paid.
class CustomPayment {
  CustomPayment(
      {required this.methodId, required this.methodName, required this.amount});
  final int methodId;
  final String methodName;
  final double amount;
}

/// The live ticket. Holds a [CartState] and replaces it on every edit — no
/// mutable field is ever handed out, so a widget cannot change the total that
/// gets posted without the cubit emitting.
///
/// The plain getters below forward to `state` so existing
/// `context.watch<CartCubit>().total` call sites keep working; new code can use
/// `BlocSelector<CartCubit, CartState, T>` to rebuild on one field instead of
/// the whole ticket — which the old tick-based base class made impossible.
class CartCubit extends Cubit<CartState> {
  CartCubit() : super(const CartState());

  LocalStorageService get _storage => serviceLocator<LocalStorageService>();
  LookupRepository get _lookup => serviceLocator<LookupRepository>();

  // ---- read facade (delegates to state) ----
  List<CartLine> get lines => state.lines;
  String get customerName => state.customerName;
  String get customerMobile => state.customerMobile;
  int? get stylistId => state.stylistId;
  String get stylistName => state.stylistName;
  String? get editingSaleId => state.editingSaleId;
  bool get isEditing => state.isEditing;
  double get orderDiscount => state.orderDiscount;
  bool get orderDiscountIsPercent => state.orderDiscountIsPercent;
  double get tipPercent => state.tipPercent;
  PayMode get payMode => state.payMode;
  List<CustomPayment> get customPayments => state.customPayments;
  bool get sendToWhatsapp => state.sendToWhatsapp;
  bool get isEmpty => state.isEmpty;
  int get count => state.count;
  double get subtotal => state.subtotal;
  double get lineDiscounts => state.lineDiscounts;
  double get orderDiscountAmount => state.orderDiscountAmount;
  double get totalDiscount => state.totalDiscount;
  double get taxTotal => state.taxTotal;
  double get netBeforeTip => state.netBeforeTip;
  double get tipAmount => state.tipAmount;
  double get total => state.total;
  double get paidAmount => state.paidAmount;
  double get balance => state.balance;

  /// The store's configured default quantity (Settings → Sale Configuration
  /// → Default Quantity), used both as a new line's starting qty and as the
  /// quantity stepper's increment.
  double get defaultQty => _storage.defaultQuantity ?? 1;

  /// Whether the "Add a Tip" option is shown at checkout (Settings → Sale
  /// Configuration → Enable Tip on the web). Defaults to enabled offline.
  bool get tipEnabled => _storage.tipEnabled ?? true;

  /// Pulls the latest sale settings (default quantity, tip availability) from
  /// the server and caches them so the POS reflects the current web settings.
  /// Called when the New Sale screen opens; no-ops offline (cached values are
  /// kept).
  Future<void> syncSaleSettings() async {
    try {
      final settings = await _lookup.saleSettings();
      final qty = settings.defaultQuantity;
      if (qty != null && qty != _storage.defaultQuantity) {
        await _storage.setDefaultQuantity(qty);
      }
      final tip = settings.tipEnabled;
      if (tip != null && tip != _storage.tipEnabled) {
        await _storage.setTipEnabled(tip);
        if (!tip) emit(state.copyWith(tipPercent: 0));
      }
      // Cache the default Product/Service filter so the catalog can preselect
      // it. Read by CatalogCubit.
      final type = settings.defaultProductType;
      if (type != null && type != _storage.defaultProductType) {
        await _storage.setDefaultProductType(type);
      }
      // Thermal-print options ride along on the same response — hand them to
      // the print cubit so receipts follow the web Sale Configuration.
      await serviceLocator<PrintSettingsCubit>().applyRemote(settings.print);
    } catch (_) {
      // Offline or server error — keep the cached values.
    }
  }

  void setClient(String name, String mobile) => emit(state.copyWith(
        customerName: name.isEmpty ? AppStrings.walkInCustomer : name,
        customerMobile: mobile,
      ));

  void setStylist(int? id, String name, {bool applyToLines = true}) {
    emit(state.copyWith(
      stylistId: id,
      stylistName: name,
      clearStylist: id == null,
      lines: applyToLines
          ? [for (final l in state.lines) l.withEmployee(id, name)]
          : null,
    ));
  }

  void add(Product p) {
    final index = state.lines.indexWhere((l) => l.productId == p.id);
    final next = [...state.lines];
    if (index != -1) {
      next[index] = next[index].copyWith(qty: next[index].qty + defaultQty);
    } else {
      next.add(CartLine(
        productId: p.id,
        name: p.name,
        code: p.code,
        type: p.type,
        unitPrice: p.mrp,
        qty: defaultQty,
        taxPercent: p.tax,
        thumbnail: p.thumbnail,
        employeeId: state.stylistId,
        employeeName: state.stylistName,
      ));
    }
    emit(state.copyWith(lines: next));
  }

  /// Replace [line] with [next], or drop it when [next] is null. Lines carry
  /// value equality and [add] merges by product, so `indexOf` resolves to
  /// exactly the intended row.
  void _replace(CartLine line, CartLine? next) {
    final i = state.lines.indexOf(line);
    if (i == -1) return;
    final rows = [...state.lines];
    if (next == null) {
      rows.removeAt(i);
    } else {
      rows[i] = next;
    }
    emit(state.copyWith(lines: rows));
  }

  void changeQty(CartLine line, double delta) {
    final next = (line.qty + delta).clamp(0, 999999).toDouble();
    _replace(line, next <= 0 ? null : line.copyWith(qty: next));
  }

  /// Sets an exact (typed) quantity on a line. A value of 0 or less removes the
  /// line, matching the stepper's behaviour.
  void setQty(CartLine line, double qty) => _replace(
      line, qty <= 0 ? null : line.copyWith(qty: qty.clamp(0.001, 999999).toDouble()));

  void removeLine(CartLine line) => _replace(line, null);

  void updateLine(
    CartLine line, {
    double? unitPrice,
    double? qty,
    double? discountValue,
    bool? discountIsPercent,
    double? taxPercent,
    int? employeeId,
    String? employeeName,
  }) =>
      _replace(
        line,
        line.copyWith(
          unitPrice: unitPrice,
          qty: qty?.clamp(0.001, 999).toDouble(),
          discountValue: discountValue,
          discountIsPercent: discountIsPercent,
          taxPercent: taxPercent,
          employeeId: employeeId,
          employeeName: employeeName,
        ),
      );

  void setOrderDiscount(double v) => emit(state.copyWith(orderDiscount: v));

  void setOrderDiscountIsPercent(bool v) =>
      emit(state.copyWith(orderDiscountIsPercent: v));

  void setTip(double percent) => emit(state.copyWith(tipPercent: percent));

  void setPayMode(PayMode mode) => emit(state.copyWith(
        payMode: mode,
        customPayments: mode == PayMode.custom ? null : const [],
      ));

  void setCustomPayments(List<CustomPayment> payments) => emit(state.copyWith(
        customPayments: payments,
        payMode: PayMode.custom,
      ));

  void setSendToWhatsapp(bool value) => emit(state.copyWith(sendToWhatsapp: value));

  void clear() => emit(const CartState());

  void seedFromSale(Sale sale) {
    final lines = sale.lines
        .where((l) => l.productId != null)
        .map((l) => CartLine(
              productId: l.productId!,
              saleItemId: l.itemId,
              name: l.name,
              code: l.code,
              type: l.type,
              unitPrice: l.unitPrice,
              qty: l.quantity,
              discountValue: l.discount,
              discountIsPercent: false,
              taxPercent: l.tax,
              employeeId: l.employeeId,
              employeeName: l.employee,
            ))
        .toList();
    final firstWithEmployee = lines.where((l) => l.employeeId != null).firstOrNull;
    final payment = _seedPayments(sale.payments, sale.paid);

    emit(CartState(
      lines: lines,
      editingSaleId: sale.id,
      customerName: sale.customerName.trim().isEmpty
          ? AppStrings.walkInCustomer
          : sale.customerName,
      customerMobile: sale.customerMobile,
      stylistId: firstWithEmployee?.employeeId,
      stylistName: firstWithEmployee?.employeeName ?? '',
      orderDiscount: sale.otherDiscount,
      payMode: payment.mode,
      customPayments: payment.rows,
    ));
  }

  /// Derives the payment selection from an existing sale's payment rows.
  ({PayMode mode, List<CustomPayment> rows}) _seedPayments(
      List<SalePayment> payments, double paid) {
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

  /// Build the POST /sale payload (matches Sale StoreRequest exactly).
  /// Pass status: 'draft' to park the sale without completing it.
  Map<String, dynamic> toPayload({String? status}) => {
        if (status != null) 'status': status,
        'customerName': state.customerName,
        if (state.customerMobile.isNotEmpty) 'phoneNumber': state.customerMobile,
        'items': state.lines
            .map((l) => {
                  if (l.saleItemId != null) 'id': l.saleItemId,
                  'productId': l.productId,
                  if (l.employeeId != null) 'employeeId': l.employeeId,
                  'quantity': l.qty,
                  'unitPrice': l.unitPrice,
                  // Already 2dp — the getters round where the server's columns
                  // do, so what is displayed is exactly what is sent.
                  'discount': l.discountAmount,
                })
            .toList(),
        'discount': state.orderDiscountAmount,
        'tip': state.tipAmount,
        'paymentMethod': state.payMode.apiValue,
        'totalPayment': state.total,
        if (state.payMode == PayMode.custom)
          'payments': state.customPayments
              .map((p) => {
                    'payment_method_id': p.methodId,
                    'amount': round2(p.amount),
                  })
              .toList(),
        'sendToWhatsapp': state.sendToWhatsapp,
      };
}

extension _FirstOrNull<E> on Iterable<E> {
  E? get firstOrNull {
    final it = iterator;
    return it.moveNext() ? it.current : null;
  }
}
