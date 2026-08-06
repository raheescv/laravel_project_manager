import 'package:flutter_test/flutter_test.dart';
import 'package:invo/features/sale/logic/cart_cubit/cart_cubit.dart';
import 'package:invo/shared/domain/helpers/formatters.dart';
import 'package:invo/shared/domain/models/index.dart';

import 'support/test_harness.dart';

/// The money path: line maths, order discount, tax, tip, split payments and the
/// payload the Sale API receives. These are the numbers the cashier and the
/// server both have to agree on, so they are asserted exactly.
void main() {
  TestWidgetsFlutterBinding.ensureInitialized();

  late TestHarness d;
  late CartCubit cart;

  setUp(() async {
    d = TestHarness();
    await d.init();
    cart = d.cart;
  });

  tearDown(() async => d.dispose());

  Product product({
    int id = 1,
    double mrp = 100,
    double tax = 0,
    String name = 'Item',
  }) =>
      Product(
        id: id,
        code: 'P$id',
        name: name,
        barcode: '',
        mrp: mrp,
        tax: tax,
        type: 'service',
        categoryName: 'Other',
        duration: '',
        totalStock: 0,
        thumbnail: '',
      );

  group('line maths', () {
    test('base is unit price x quantity', () {
      cart.add(product(mrp: 45));
      cart.setQty(cart.lines.first, 3);
      expect(cart.subtotal, 135);
      expect(cart.total, 135);
    });

    test('percentage line discount comes off the base', () {
      cart.add(product(mrp: 200));
      cart.updateLine(cart.lines.first,
          discountValue: 10, discountIsPercent: true);
      expect(cart.lineDiscounts, 20);
      expect(cart.total, 180);
    });

    test('flat line discount is taken as-is', () {
      cart.add(product(mrp: 200));
      cart.updateLine(cart.lines.first,
          discountValue: 35, discountIsPercent: false);
      expect(cart.lineDiscounts, 35);
      expect(cart.total, 165);
    });

    test('tax applies after the line discount, not before', () {
      cart.add(product(mrp: 100, tax: 10));
      cart.updateLine(cart.lines.first,
          discountValue: 50, discountIsPercent: false);
      // taxable = 100 - 50 = 50, tax = 5
      expect(cart.taxTotal, 5);
      expect(cart.total, 55);
    });

    test('a discount larger than the line clamps taxable at zero', () {
      cart.add(product(mrp: 100, tax: 10));
      cart.updateLine(cart.lines.first,
          discountValue: 250, discountIsPercent: false);
      expect(cart.taxTotal, 0);
      // netBeforeTip clamps at zero rather than going negative.
      expect(cart.total, 0);
    });

    test('adding the same product twice increments quantity, not lines', () {
      final p = product(mrp: 20);
      cart.add(p);
      cart.add(p);
      expect(cart.lines.length, 1);
      expect(cart.lines.first.qty, 2);
      expect(cart.subtotal, 40);
    });

    test('dropping quantity to zero removes the line', () {
      cart.add(product());
      cart.setQty(cart.lines.first, 0);
      expect(cart.isEmpty, isTrue);
      expect(cart.total, 0);
    });
  });

  group('order discount', () {
    test('flat order discount is subtracted from the net', () {
      cart.add(product(mrp: 300));
      cart.setOrderDiscountIsPercent(false);
      cart.setOrderDiscount(50);
      expect(cart.orderDiscountAmount, 50);
      expect(cart.total, 250);
    });

    test('percentage order discount is computed after line discounts', () {
      cart.add(product(mrp: 200));
      cart.updateLine(cart.lines.first,
          discountValue: 50, discountIsPercent: false);
      cart.setOrderDiscountIsPercent(true);
      cart.setOrderDiscount(10);
      // base 200 - line discount 50 = 150; 10% of 150 = 15
      expect(cart.orderDiscountAmount, 15);
      expect(cart.totalDiscount, 65);
      expect(cart.total, 135);
    });
  });

  group('tip', () {
    test('tip is a percentage of the net after discounts and tax', () {
      cart.add(product(mrp: 100, tax: 10));
      cart.setTip(10);
      // net = 100 + 10 tax = 110; tip = 11
      expect(cart.tipAmount, 110 * 0.10);
      expect(cart.total, closeTo(121, 1e-9));
    });

    test('no tip leaves the total untouched', () {
      cart.add(product(mrp: 100));
      cart.setTip(0);
      expect(cart.tipAmount, 0);
      expect(cart.total, 100);
    });
  });

  group('payment and balance', () {
    test('cash settles the ticket in full', () {
      cart.add(product(mrp: 80));
      cart.setPayMode(PayMode.cash);
      expect(cart.paidAmount, 80);
      expect(cart.balance, 0);
    });

    test('credit pays nothing, leaving the full balance owing', () {
      cart.add(product(mrp: 80));
      cart.setPayMode(PayMode.credit);
      expect(cart.paidAmount, 0);
      expect(cart.balance, 80);
    });

    test('split payments sum, and a short split leaves a balance', () {
      cart.add(product(mrp: 100));
      cart.setCustomPayments([
        CustomPayment(methodId: 1, methodName: 'Cash', amount: 60),
        CustomPayment(methodId: 2, methodName: 'Card', amount: 30),
      ]);
      expect(cart.payMode, PayMode.custom);
      expect(cart.paidAmount, 90);
      expect(cart.balance, 10);
    });

    test('switching away from custom clears the split rows', () {
      cart.add(product(mrp: 100));
      cart.setCustomPayments(
          [CustomPayment(methodId: 1, methodName: 'Cash', amount: 100)]);
      cart.setPayMode(PayMode.cash);
      expect(cart.customPayments, isEmpty);
      expect(cart.paidAmount, 100);
    });
  });

  group('server parity — rounds where the decimal(16,2) columns do', () {
    // sale_items: gross_amount = unit_price * quantity
    //             net_amount   = gross_amount - discount
    //             tax_amount   = (net_amount * tax) / 100
    // sales:      total        = gross_amount - item_discount + tax_amount
    //             grand_total  = total - other_discount
    // Each is a generated decimal(16,2), so MySQL rounds every intermediate.

    test('a percentage discount that lands on 3 decimals is rounded per line', () {
      cart.add(product(mrp: 33.33));
      cart.setQty(cart.lines.first, 3);
      cart.updateLine(cart.lines.first,
          discountValue: 7.5, discountIsPercent: true);
      final line = cart.lines.first;
      // gross = 99.99; raw 7.5% = 7.49925 -> the column stores 7.50
      expect(line.base, 99.99);
      expect(line.discountAmount, 7.50);
      expect(line.taxable, 92.49); // net_amount = 99.99 - 7.50
    });

    test('tax is charged on the rounded net, not the raw one', () {
      cart.add(product(mrp: 33.33, tax: 5));
      cart.setQty(cart.lines.first, 3);
      cart.updateLine(cart.lines.first,
          discountValue: 7.5, discountIsPercent: true);
      final line = cart.lines.first;
      // (92.49 * 5) / 100 = 4.6245 -> 4.62
      expect(line.taxAmount, 4.62);
      expect(line.total, 97.11); // 92.49 + 4.62
    });

    test('every money getter is already 2dp, so nothing drifts on summation', () {
      cart.add(product(id: 1, mrp: 33.33, tax: 5));
      cart.setQty(cart.lines.first, 3);
      cart.updateLine(cart.lines.first,
          discountValue: 7.5, discountIsPercent: true);
      cart.add(product(id: 2, mrp: 19.99, tax: 5));
      cart.setOrderDiscountIsPercent(true);
      cart.setOrderDiscount(3);
      cart.setTip(10);

      for (final entry in {
        'subtotal': cart.subtotal,
        'lineDiscounts': cart.lineDiscounts,
        'orderDiscountAmount': cart.orderDiscountAmount,
        'taxTotal': cart.taxTotal,
        'netBeforeTip': cart.netBeforeTip,
        'tipAmount': cart.tipAmount,
        'total': cart.total,
      }.entries) {
        expect(entry.value, round2(entry.value),
            reason: '${entry.key} must already be 2dp');
      }
    });

    test('the displayed total is exactly the total that gets sent', () {
      cart.add(product(id: 1, mrp: 33.33, tax: 5));
      cart.setQty(cart.lines.first, 3);
      cart.updateLine(cart.lines.first,
          discountValue: 7.5, discountIsPercent: true);
      cart.setTip(12.5);
      expect(cart.toPayload()['totalPayment'], cart.total);
    });

    test('a split payment covering the total leaves no phantom balance', () {
      cart.add(product(mrp: 33.33, tax: 5));
      cart.setQty(cart.lines.first, 3);
      cart.updateLine(cart.lines.first,
          discountValue: 7.5, discountIsPercent: true);
      cart.setCustomPayments([
        CustomPayment(methodId: 1, methodName: 'Cash', amount: cart.total),
      ]);
      expect(cart.balance, 0);
    });
  });

  group('state (§5 shape)', () {
    test('every edit emits a new state', () async {
      final seen = <CartState>[];
      final sub = cart.stream.listen(seen.add);
      cart.add(product(mrp: 10));
      cart.setTip(5);
      cart.setClient('Dana', '555');
      await Future<void>.delayed(Duration.zero);
      await sub.cancel();
      expect(seen, hasLength(3));
      expect(seen.last.customerName, 'Dana');
    });

    test('an edit that changes nothing does not emit', () async {
      cart.add(product(mrp: 10));
      final seen = <CartState>[];
      final sub = cart.stream.listen(seen.add);
      cart.setTip(0);          // already 0
      cart.setPayMode(PayMode.cash); // already cash
      await Future<void>.delayed(Duration.zero);
      await sub.cancel();
      // Equatable dedups — the old int-tick base class always emitted, so every
      // watcher rebuilt on a no-op.
      expect(seen, isEmpty);
    });

    test('totals are a pure function of the state', () {
      const state = CartState(lines: [
        CartLine(productId: 1, name: 'A', code: 'A', type: 'service', unitPrice: 50, qty: 2, taxPercent: 10),
      ]);
      expect(state.subtotal, 100);
      expect(state.taxTotal, 10);
      expect(state.total, 110);
    });

    test('clearing resets to the initial state', () {
      cart.add(product(mrp: 10));
      cart.setClient('Dana', '555');
      cart.clear();
      expect(cart.state, const CartState());
    });
  });

  group('immutability', () {
    test('lines are value-equal, so removal works on an equal copy', () {
      cart.add(product(mrp: 40));
      final copy = cart.lines.first.copyWith();
      expect(copy, cart.lines.first);
      cart.removeLine(copy);
      expect(cart.isEmpty, isTrue);
    });

    test('an edit replaces the element rather than mutating it', () {
      cart.add(product(mrp: 40));
      final before = cart.lines.first;
      cart.updateLine(before, unitPrice: 60);
      // The old reference still reads the old price — nothing can change the
      // ticket behind the cubit's back.
      expect(before.unitPrice, 40);
      expect(cart.lines.first.unitPrice, 60);
      expect(cart.subtotal, 60);
    });
  });

  group('toPayload', () {
    test('money fields are rounded to 2 decimals', () {
      // 33.33 x 3 with a 7.5% line discount and 5% tax — the case where binary
      // doubles drift from the server's decimal arithmetic.
      cart.add(product(mrp: 33.33, tax: 5));
      cart.setQty(cart.lines.first, 3);
      cart.updateLine(cart.lines.first,
          discountValue: 7.5, discountIsPercent: true);

      final payload = cart.toPayload();
      final items = payload['items'] as List;
      expect(items, hasLength(1));

      for (final key in ['discount', 'tip', 'totalPayment']) {
        final v = payload[key] as double;
        expect(v, closeTo(double.parse(v.toStringAsFixed(2)), 1e-9),
            reason: '$key must be rounded to 2 decimals');
      }
      final lineDiscount = (items.first as Map)['discount'] as double;
      expect(lineDiscount,
          closeTo(double.parse(lineDiscount.toStringAsFixed(2)), 1e-9));
    });

    test('carries the client, quantities and payment mode', () {
      cart.add(product(id: 7, mrp: 25));
      cart.setQty(cart.lines.first, 2);
      cart.setClient('Dana', '555');
      cart.setPayMode(PayMode.card);

      final payload = cart.toPayload();
      expect(payload['customerName'], 'Dana');
      expect(payload['phoneNumber'], '555');
      expect(payload['paymentMethod'], 'Card');
      expect(payload['totalPayment'], 50);

      final item = (payload['items'] as List).first as Map;
      expect(item['productId'], 7);
      expect(item['quantity'], 2);
      expect(item['unitPrice'], 25);
    });

    test('an empty mobile is omitted rather than sent blank', () {
      cart.add(product());
      cart.setClient('Dana', '');
      expect(cart.toPayload().containsKey('phoneNumber'), isFalse);
    });

    test('split payment rows ride along only in custom mode', () {
      cart.add(product(mrp: 100));
      cart.setCustomPayments(
          [CustomPayment(methodId: 3, methodName: 'Card', amount: 100)]);
      final payload = cart.toPayload();
      expect(payload['payments'], hasLength(1));
      expect((payload['payments'] as List).first,
          {'payment_method_id': 3, 'amount': 100.0});

      cart.setPayMode(PayMode.cash);
      expect(cart.toPayload().containsKey('payments'), isFalse);
    });

    test('a draft carries its status', () {
      cart.add(product());
      expect(cart.toPayload(status: 'draft')['status'], 'draft');
      expect(cart.toPayload().containsKey('status'), isFalse);
    });
  });

  group('clear', () {
    test('resets lines, client, discounts, tip and payment', () {
      cart.add(product(mrp: 100));
      cart.setClient('Dana', '555');
      cart.setOrderDiscount(10);
      cart.setTip(15);
      cart.setPayMode(PayMode.credit);

      cart.clear();

      expect(cart.isEmpty, isTrue);
      expect(cart.customerName, 'Walk-in');
      expect(cart.customerMobile, '');
      expect(cart.orderDiscount, 0);
      expect(cart.tipPercent, 0);
      expect(cart.payMode, PayMode.cash);
      expect(cart.customPayments, isEmpty);
      expect(cart.editingSaleId, isNull);
      expect(cart.total, 0);
    });
  });
}
