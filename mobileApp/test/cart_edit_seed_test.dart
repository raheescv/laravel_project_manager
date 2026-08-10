import 'package:flutter_test/flutter_test.dart';
import 'package:invo/features/sale/logic/cart_cubit/cart_cubit.dart';
import 'package:invo/shared/domain/models/index.dart';

import 'support/test_harness.dart';

/// Re-opening a saved sale on the ticket.
///
/// Two things have to survive the round trip: how the sale was paid, and
/// whether it is still a parked draft. Neither is a field the server sends as
/// such — the payment mode has to be read back out of the payment rows, and the
/// draft decides whether checkout offers to complete the sale or only to save
/// it — so both are pinned here.
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

  /// A sale in the shape `SaleResource` returns it. A settled sale's payments
  /// always carry a `payment_method_id` — Cash and Card are configured accounts
  /// like any other, which is the whole reason the mode has to be inferred.
  Sale sale({
    String status = 'completed',
    List<Map<String, dynamic>> payments = const [],
    double grandTotal = 100,
    double tip = 0,
    double? paid,
  }) =>
      Sale.fromJson({
        'id': '41',
        'invoice_no': 'INV-41',
        'date': '2026-08-09',
        'status': status,
        'branch': 'Main',
        'customer': {'name': 'Dana', 'mobile': '555'},
        'items': [
          {
            'id': 7,
            'product_id': 3,
            'code': 'P3',
            'name': 'Blow Dry',
            'type': 'service',
            'quantity': 1,
            'unit_price': grandTotal,
            'discount': 0,
            'tax': 0,
          },
        ],
        'payments': payments,
        'summary': {
          'gross_amount': grandTotal,
          'item_discount': 0,
          'other_discount': 0,
          'tax_amount': 0,
          'tip': tip,
          'grand_total': grandTotal,
          'paid': paid ?? payments.fold<double>(0, (a, p) => a + (p['amount'] as num).toDouble()),
          'balance': 0,
        },
        'created_by': 'Cashier',
      });

  Map<String, dynamic> row(int id, String method, double amount) =>
      {'id': id, 'payment_method_id': id, 'method': method, 'amount': amount};

  group('payment mode', () {
    test('a sale settled in cash reopens on Cash, not Custom', () {
      cart.seedFromSale(sale(payments: [row(2, 'Cash', 100)]));

      expect(cart.payMode, PayMode.cash);
      expect(cart.customPayments, isEmpty);
      expect(cart.toPayload()['paymentMethod'], 'Cash');
    });

    test('a sale settled on a card account reopens on Card', () {
      // The configured account is rarely named exactly "Card" — the server
      // matches the mode against the account name, so the app reads it back the
      // same way.
      cart.seedFromSale(sale(payments: [row(3, 'Credit Card', 100)]));

      expect(cart.payMode, PayMode.card);
      expect(cart.customPayments, isEmpty);
    });

    test('the tip is part of what the single payment had to cover', () {
      cart.seedFromSale(sale(payments: [row(2, 'Cash', 110)], tip: 10));

      expect(cart.payMode, PayMode.cash);
    });

    test('a part payment stays Custom so the outstanding balance survives', () {
      // Cash and Card always settle the whole ticket, so a row that doesn't
      // cover it can only have come from the custom sheet. Collapsing it would
      // quietly pay off the balance on the next save.
      cart.seedFromSale(sale(payments: [row(2, 'Cash', 40)], paid: 40));

      expect(cart.payMode, PayMode.custom);
      expect(cart.customPayments, hasLength(1));
      expect(cart.customPayments.first.amount, 40);
      expect(cart.paidAmount, 40);
    });

    test('a split across two methods stays Custom', () {
      cart.seedFromSale(sale(payments: [row(2, 'Cash', 60), row(3, 'Card', 40)]));

      expect(cart.payMode, PayMode.custom);
      expect(cart.customPayments.map((p) => p.methodId), [2, 3]);
    });

    test('a method that is neither cash nor card stays Custom', () {
      cart.seedFromSale(sale(payments: [row(5, 'Bank Transfer', 100)]));

      expect(cart.payMode, PayMode.custom);
      expect(cart.customPayments.single.methodName, 'Bank Transfer');
    });

    test('a sale with no payment at all reopens on Credit', () {
      cart.seedFromSale(sale(payments: const [], paid: 0));

      expect(cart.payMode, PayMode.credit);
      expect(cart.paidAmount, 0);
    });
  });

  group('draft', () {
    test('a parked draft is flagged so checkout can offer to complete it', () {
      cart.seedFromSale(sale(status: 'draft', payments: [row(2, 'Cash', 100)]));

      expect(cart.isEditing, isTrue);
      expect(cart.isEditingDraft, isTrue);
    });

    test('a completed sale is an edit, never a completion', () {
      cart.seedFromSale(sale(payments: [row(2, 'Cash', 100)]));

      expect(cart.isEditing, isTrue);
      expect(cart.isEditingDraft, isFalse);
    });

    test('completing sends the status; a plain save leaves it off', () {
      cart.seedFromSale(sale(status: 'draft', payments: [row(2, 'Cash', 100)]));

      expect(cart.toPayload(status: 'completed')['status'], 'completed');
      expect(cart.toPayload().containsKey('status'), isFalse);
      // The lines keep their sale_item ids, so the server patches in place.
      final items = (cart.toPayload()['items'] as List).cast<Map<String, dynamic>>();
      expect(items.first['id'], 7);
    });

    test('clearing the ticket drops the draft flag with the sale id', () {
      cart.seedFromSale(sale(status: 'draft', payments: [row(2, 'Cash', 100)]));
      cart.clear();

      expect(cart.editingSaleId, isNull);
      expect(cart.isEditingDraft, isFalse);
    });

    test('starting a new ticket after a draft edit is not a draft', () {
      cart.seedFromSale(sale(status: 'draft', payments: [row(2, 'Cash', 100)]));
      cart.clear();
      cart.setClient('Walk-in', '');

      expect(cart.isEditing, isFalse);
      expect(cart.isEditingDraft, isFalse);
    });
  });
}
