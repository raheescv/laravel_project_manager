import 'package:equatable/equatable.dart';
import 'package:invo/features/auth/logic/auth_cubit/auth_cubit.dart';
import 'package:invo/features/sale/domain/models/pending_sale.dart';
import 'package:invo/shared/domain/constants/global_variables.dart';
import 'package:invo/shared/domain/helpers/formatters.dart';
import 'package:invo/shared/domain/models/index.dart';
import 'package:invo/shared/domain/services/sale_settings_sync.dart';
import 'package:invo/shared/logic/branch_cubit/branch_cubit.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import 'package:invo/shared/utils/components/app_strings.dart';
import 'package:invo/shared/utils/local_storage/local_storage_service.dart';
import 'package:uuid/uuid.dart';

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

  // ---- read facade (delegates to state) ----
  List<CartLine> get lines => state.lines;
  String get customerName => state.customerName;
  String get customerMobile => state.customerMobile;
  int? get stylistId => state.stylistId;
  String get stylistName => state.stylistName;
  String? get editingSaleId => state.editingSaleId;
  bool get isEditing => state.isEditing;
  bool get isEditingDraft => state.isEditingDraft;
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
      // The caching itself is shared with first-run provisioning; the only part
      // that belongs to the ticket is clearing a tip that has been switched off.
      final settings = await pullAndCacheSaleSettings();
      if (settings.tipEnabled == false) emit(state.copyWith(tipPercent: 0));
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
    final payment = _seedPayments(sale);

    emit(CartState(
      lines: lines,
      editingSaleId: sale.id,
      editingStatus: sale.status,
      customerName: sale.customerName.trim().isEmpty
          ? AppStrings.walkInCustomer
          : sale.customerName,
      customerMobile: sale.customerMobile,
      stylistId: firstWithEmployee?.employeeId,
      stylistName: firstWithEmployee?.employeeName ?? '',
      orderDiscount: sale.otherDiscount,
      // The ticket holds a tip as a percentage of the net; the sale stores the
      // amount it worked out to. Re-deriving it keeps a gratuity that was
      // already collected on the sale — sending the ticket back with tip 0 would
      // wipe it off the record, and the totals would still look settled because
      // the payment was reduced by the same amount.
      tipPercent: sale.grandTotal > 0 ? round2(sale.tip / sale.grandTotal * 100) : 0,
      payMode: payment.mode,
      customPayments: payment.rows,
    ));
  }

  /// Load a sale that is still queued on this device, for correction.
  ///
  /// Seeds from the row's own receipt snapshot rather than from the server, because
  /// the server has never seen this sale — the snapshot IS the sale. The line ids
  /// are dropped for the same reason: `saleItemId` addresses rows in a `sale_items`
  /// table that has nothing in it for this ticket yet.
  ///
  /// The captured quantities are carried on the state so the correction can hand
  /// back what the queued version took off the cached shelf.
  void seedFromPendingSale(PendingSale row) {
    seedFromSale(row.sale);
    emit(state.copyWith(
      // Not an edit of a server record — clear the id the seeding above set from
      // the snapshot's blank `id`, or checkout would try to PUT to nothing.
      clearEditingSaleId: true,
      editingPendingUuid: row.clientUuid,
      editingPendingSold: row.soldQuantities,
    ));
  }

  /// Derives the payment selection from an existing sale's payment rows.
  ///
  /// Cash and Card are not stored as modes: the server settles them against the
  /// configured cash/card account, so they come back as an ordinary payment row
  /// carrying a `payment_method_id` exactly like a split payment does. Treating
  /// that id as the mark of a custom payment reopened every single-method sale
  /// as "Custom" — so a lone row that covers the whole ticket is read back as
  /// the button the cashier actually pressed.
  ///
  /// The row has to settle the ticket exactly for that to hold, because the
  /// buttons can't express anything else: they always pay the total. A part
  /// payment collapsed to Cash would quietly clear the outstanding balance on
  /// the next save, and an overpayment would quietly lose the excess — both are
  /// custom-sheet amounts, so both stay on the custom sheet. Every figure
  /// involved is a 2dp column, so the tolerance is float noise, not a cent.
  ({PayMode mode, List<CustomPayment> rows}) _seedPayments(Sale sale) {
    final payments = sale.payments;
    final paid = sale.paid;
    if (payments.isEmpty) {
      return (mode: paid > 0 ? PayMode.cash : PayMode.credit, rows: const []);
    }
    // The tip is an extra on top of `grand_total`, and it is paid along with it.
    final ticket = sale.grandTotal + sale.tip;
    if (payments.length == 1 && (paid - ticket).abs() < 0.005) {
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

  /// The idempotency key for the ticket as it currently stands.
  ///
  /// Deliberately NOT regenerated per press of Charge. Supplying a key makes the
  /// server skip its duplicate heuristic, so a fresh key on every press would
  /// turn a second press into a second sale — exactly the double charge that
  /// heuristic exists to stop. A press that fails visibly (a 500, a rejected
  /// payment method) is retried with the SAME key, so if the first attempt did
  /// commit, the server recognises the replay and hands back the one sale.
  ///
  /// It is cleared by [emit] whenever the ticket actually changes, because a
  /// changed ticket is a different sale and must not be mistaken for a replay.
  String? _chargeUuid;

  @override
  void emit(CartState state) {
    // Any real change to the ticket retires the key. Guarded on equality so a
    // no-op emit (several setters re-emit an unchanged value) doesn't silently
    // hand the next press a new identity.
    //
    // A ticket correcting a queued sale is the exception, and pins the key
    // instead of clearing it: that sale has already been captured under it, the
    // outbox row is addressed by it, and letting an edit mint a new one would put
    // both the wrong version and the corrected one on the server.
    if (state != this.state) _chargeUuid = state.editingPendingUuid;
    super.emit(state);
  }

  /// Everything one press of Charge needs: the idempotency key, the payload to
  /// post, and a receipt-ready snapshot to fall back on if the post can't land.
  ///
  /// All three are produced together so the key on the wire is the key stored in
  /// the outbox — generating it in two places would queue a sale under an id the
  /// server never saw, and the retry would ring it up twice.
  /// [status] parks the ticket instead of completing it ('draft'). A draft is
  /// captured through exactly the same key-and-snapshot machinery as a sale: it
  /// still must not be replayed into two rows, and the outbox is the only durable
  /// place a device has to hold one. What differs is that a draft has taken no
  /// money and moved no goods — see [PendingSale.isDraft].
  ({String clientUuid, Map<String, dynamic> payload, Map<String, dynamic> offlineSale}) beginCharge({
    String? status,
  }) {
    final clientUuid = _chargeUuid ??= const Uuid().v4();
    final chargedAt = DateTime.now();
    return (
      clientUuid: clientUuid,
      payload: toPayload(status: status, clientUuid: clientUuid, clientCreatedAt: chargedAt),
      offlineSale: _offlineSaleJson(
        clientUuid: clientUuid,
        chargedAt: chargedAt,
        status: status ?? 'completed',
      ),
    );
  }

  /// The ticket in the shape `SaleResource` would have returned, so the invoice
  /// screen and [buildReceiptPdf] can render a queued sale through exactly the
  /// same [Sale] model as a committed one.
  ///
  /// `id` and `invoice_no` are deliberately blank — they belong to the server,
  /// and the provisional reference shown in their place is assigned by the
  /// outbox, which is the only thing that knows the device's own sequence.
  Map<String, dynamic> _offlineSaleJson({
    required String clientUuid,
    required DateTime chargedAt,
    String status = 'completed',
  }) =>
      {
        'id': '',
        'invoice_no': '',
        'client_uuid': clientUuid,
        'pending': true,
        'date': chargedAt.toIso8601String().substring(0, 10),
        'status': status,
        // The receipt header falls back to a generic mark on an empty branch, so
        // the active branch is carried over rather than left for the server to
        // fill in on a response that isn't coming yet.
        'branch': _resolve<BranchCubit>()?.selected?.name ?? '',
        'customer': {'name': state.customerName, 'mobile': state.customerMobile},
        'items': [
          for (final l in state.lines)
            {
              'product_id': l.productId,
              'employee_id': l.employeeId,
              'code': l.code,
              'name': l.name,
              'type': l.type,
              'employee': l.employeeName,
              'quantity': l.qty,
              'unit_price': l.unitPrice,
              'discount': l.discountAmount,
              'tax': l.taxPercent,
              'total': l.total,
            },
        ],
        'payments': [
          if (state.payMode == PayMode.custom)
            for (final p in state.customPayments)
              {'payment_method_id': p.methodId, 'method': p.methodName, 'amount': round2(p.amount)}
          else if (state.payMode != PayMode.credit)
            {'payment_method_id': null, 'method': state.payMode.label, 'amount': state.total},
        ],
        'summary': {
          'gross_amount': state.subtotal,
          'item_discount': state.lineDiscounts,
          'other_discount': state.orderDiscountAmount,
          'tax_amount': state.taxTotal,
          'tip': state.tipAmount,
          'grand_total': state.total,
          'paid': state.paidAmount,
          'balance': state.balance,
        },
        'created_by': _resolve<AuthCubit>()?.user?.name ?? '',
      };

  /// Look up an app-wide cubit without insisting it exists.
  ///
  /// The two call sites above only want display copy for the offline receipt —
  /// a branch name and a cashier name. Letting an unregistered dependency throw
  /// here would abort the charge itself, and `_charge()` would report a sale
  /// that was never attempted as "could not save".
  T? _resolve<T extends Object>() =>
      serviceLocator.isRegistered<T>() ? serviceLocator<T>() : null;

  /// Build the POST /sale payload (matches Sale StoreRequest exactly).
  /// Pass status: 'draft' to park the sale without completing it.
  Map<String, dynamic> toPayload({String? status, String? clientUuid, DateTime? clientCreatedAt}) => {
        if (status != null) 'status': status,
        if (clientUuid != null) 'clientUuid': clientUuid,
        if (clientCreatedAt != null) 'clientCreatedAt': clientCreatedAt.toIso8601String(),
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
                  // The rate the customer was actually charged. The server used to
                  // re-derive this from the product row at save time, which is fine
                  // for a live charge and wrong for a queued one: a rate edited
                  // while the sale sat in the outbox committed a total that no
                  // longer matched the cash in the drawer.
                  'tax': l.taxPercent,
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
