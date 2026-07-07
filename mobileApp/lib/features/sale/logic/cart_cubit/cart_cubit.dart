import 'package:invo/shared/domain/constants/global_variables.dart';
import 'package:invo/shared/domain/models/index.dart';
import 'package:invo/shared/domain/repository/lookup_repository.dart';
import 'package:invo/shared/logic/base/holder_cubit.dart';
import 'package:invo/shared/utils/local_storage/local_storage_service.dart';

/// One editable line on the ticket. Discount can be a percentage or a flat
/// amount; tax is a percentage. Mirrors the "Edit line item" sheet fields.
class CartLine {
  CartLine({
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
  double unitPrice;
  double qty;
  double discountValue;
  bool discountIsPercent;
  double taxPercent;
  int? employeeId;
  String employeeName;

  double get base => unitPrice * qty;
  double get discountAmount =>
      discountIsPercent ? base * discountValue / 100.0 : discountValue;
  double get taxable => (base - discountAmount).clamp(0, double.infinity);
  double get taxAmount => taxable * taxPercent / 100.0;
  double get total => taxable + taxAmount;

  String get discountLabel => discountValue <= 0
      ? ''
      : discountIsPercent
          ? '${discountValue.toStringAsFixed(discountValue.truncateToDouble() == discountValue ? 0 : 1)}% off'
          : '\$${discountValue.toStringAsFixed(2)} off';
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

/// The live ticket: lines, client, default stylist, order discount, tip & payment.
class CartCubit extends HolderCubit {
  CartCubit();

  final List<CartLine> _lines = [];
  List<CartLine> get lines => List.unmodifiable(_lines);

  String customerName = 'Walk-in';
  String customerMobile = '';
  int? stylistId;
  String stylistName = '';

  String? editingSaleId;
  bool get isEditing => editingSaleId != null;

  double orderDiscount = 0;
  bool orderDiscountIsPercent = false;
  double tipPercent = 0;

  PayMode payMode = PayMode.cash;
  List<CustomPayment> customPayments = [];
  bool sendToWhatsapp = false;

  LocalStorageService get _storage => serviceLocator<LocalStorageService>();
  LookupRepository get _lookup => serviceLocator<LookupRepository>();

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
      var changed = false;
      final qty = settings.defaultQuantity;
      if (qty != null && qty != _storage.defaultQuantity) {
        await _storage.setDefaultQuantity(qty);
        changed = true;
      }
      final tip = settings.tipEnabled;
      if (tip != null && tip != _storage.tipEnabled) {
        await _storage.setTipEnabled(tip);
        if (!tip) tipPercent = 0;
        changed = true;
      }
      if (changed) refresh();
    } catch (_) {
      // Offline or server error — keep the cached values.
    }
  }

  bool get isEmpty => _lines.isEmpty;
  int get count => _lines.fold(0, (a, l) => a + l.qty.round());

  void setClient(String name, String mobile) {
    customerName = name.isEmpty ? 'Walk-in' : name;
    customerMobile = mobile;
    refresh();
  }

  void setStylist(int? id, String name, {bool applyToLines = true}) {
    stylistId = id;
    stylistName = name;
    if (applyToLines) {
      for (final l in _lines) {
        l.employeeId = id;
        l.employeeName = name;
      }
    }
    refresh();
  }

  void add(Product p) {
    final existing = _lines.where((l) => l.productId == p.id).firstOrNull;
    if (existing != null) {
      existing.qty += defaultQty;
    } else {
      _lines.add(CartLine(
        productId: p.id,
        name: p.name,
        code: p.code,
        type: p.type,
        unitPrice: p.mrp,
        qty: defaultQty,
        taxPercent: p.tax,
        thumbnail: p.thumbnail,
        employeeId: stylistId,
        employeeName: stylistName,
      ));
    }
    refresh();
  }

  void changeQty(CartLine line, double delta) {
    line.qty = (line.qty + delta).clamp(0, 999999);
    if (line.qty <= 0) _lines.remove(line);
    refresh();
  }

  /// Sets an exact (typed) quantity on a line. A value of 0 or less removes the
  /// line, matching the stepper's behaviour.
  void setQty(CartLine line, double qty) {
    if (qty <= 0) {
      _lines.remove(line);
    } else {
      line.qty = qty.clamp(0.001, 999999);
    }
    refresh();
  }

  void removeLine(CartLine line) {
    _lines.remove(line);
    refresh();
  }

  void updateLine(
    CartLine line, {
    double? unitPrice,
    double? qty,
    double? discountValue,
    bool? discountIsPercent,
    double? taxPercent,
    int? employeeId,
    String? employeeName,
  }) {
    if (unitPrice != null) line.unitPrice = unitPrice;
    if (qty != null) line.qty = qty.clamp(0.001, 999);
    if (discountValue != null) line.discountValue = discountValue;
    if (discountIsPercent != null) line.discountIsPercent = discountIsPercent;
    if (taxPercent != null) line.taxPercent = taxPercent;
    if (employeeId != null) line.employeeId = employeeId;
    if (employeeName != null) line.employeeName = employeeName;
    refresh();
  }

  void setOrderDiscount(double v) {
    orderDiscount = v;
    refresh();
  }

  void setOrderDiscountIsPercent(bool v) {
    orderDiscountIsPercent = v;
    refresh();
  }

  void setTip(double percent) {
    tipPercent = percent;
    refresh();
  }

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

  void setSendToWhatsapp(bool value) {
    sendToWhatsapp = value;
    refresh();
  }

  // ---- totals ----
  double get subtotal => _lines.fold(0, (a, l) => a + l.base);
  double get lineDiscounts => _lines.fold(0, (a, l) => a + l.discountAmount);

  double get orderDiscountAmount {
    if (!orderDiscountIsPercent) return orderDiscount;
    final base = (subtotal - lineDiscounts).clamp(0, double.infinity);
    return base * orderDiscount / 100.0;
  }

  double get totalDiscount => lineDiscounts + orderDiscountAmount;
  double get taxTotal => _lines.fold(0, (a, l) => a + l.taxAmount);
  double get netBeforeTip =>
      (subtotal - totalDiscount + taxTotal).clamp(0, double.infinity);
  double get tipAmount => netBeforeTip * tipPercent / 100.0;
  double get total => netBeforeTip + tipAmount;

  double get paidAmount => switch (payMode) {
        PayMode.credit => 0,
        PayMode.custom => customPayments.fold(0.0, (a, p) => a + p.amount),
        _ => total,
      };

  double get balance => total - paidAmount;

  void clear() {
    _lines.clear();
    customerName = 'Walk-in';
    customerMobile = '';
    orderDiscount = 0;
    orderDiscountIsPercent = false;
    tipPercent = 0;
    payMode = PayMode.cash;
    customPayments = [];
    sendToWhatsapp = false;
    editingSaleId = null;
    refresh();
  }

  void seedFromSale(Sale sale) {
    _lines
      ..clear()
      ..addAll(
        sale.lines.where((l) => l.productId != null).map(
              (l) => CartLine(
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
              ),
            ),
      );

    editingSaleId = sale.id;
    customerName =
        sale.customerName.trim().isEmpty ? 'Walk-in' : sale.customerName;
    customerMobile = sale.customerMobile;
    final firstWithEmployee =
        _lines.where((l) => l.employeeId != null).firstOrNull;
    stylistId = firstWithEmployee?.employeeId;
    stylistName = firstWithEmployee?.employeeName ?? '';
    orderDiscount = sale.otherDiscount;
    orderDiscountIsPercent = false;
    tipPercent = 0;
    sendToWhatsapp = false;
    _seedPayments(sale.payments, sale.paid);
    refresh();
  }

  void _seedPayments(List<SalePayment> payments, double paid) {
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

  /// Build the POST /sale payload (matches Sale StoreRequest exactly).
  /// Pass status: 'draft' to park the sale without completing it.
  Map<String, dynamic> toPayload({String? status}) => {
        if (status != null) 'status': status,
        'customerName': customerName,
        if (customerMobile.isNotEmpty) 'phoneNumber': customerMobile,
        'items': _lines
            .map((l) => {
                  if (l.saleItemId != null) 'id': l.saleItemId,
                  'productId': l.productId,
                  if (l.employeeId != null) 'employeeId': l.employeeId,
                  'quantity': l.qty,
                  'unitPrice': l.unitPrice,
                  'discount': double.parse(l.discountAmount.toStringAsFixed(2)),
                })
            .toList(),
        'discount': double.parse(orderDiscountAmount.toStringAsFixed(2)),
        'tip': double.parse(tipAmount.toStringAsFixed(2)),
        'paymentMethod': payMode.apiValue,
        'totalPayment': double.parse(total.toStringAsFixed(2)),
        if (payMode == PayMode.custom)
          'payments': customPayments
              .map((p) => {
                    'payment_method_id': p.methodId,
                    'amount': double.parse(p.amount.toStringAsFixed(2)),
                  })
              .toList(),
        'sendToWhatsapp': sendToWhatsapp,
      };
}

extension _FirstOrNull<E> on Iterable<E> {
  E? get firstOrNull {
    final it = iterator;
    return it.moveNext() ? it.current : null;
  }
}
