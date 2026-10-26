import 'package:flutter_test/flutter_test.dart';
import 'package:invo/features/sale/logic/cart_cubit/cart_cubit.dart';
import 'package:invo/shared/domain/models/index.dart';

import 'support/test_harness.dart';

/// System Configurations → POS Payment Buttons on the web: up to three quick
/// buttons ahead of Credit and Custom. Cash and Card keep their own modes; any
/// other account pays the whole ticket by id, as a one-row custom payment.
void main() {
  TestWidgetsFlutterBinding.ensureInitialized();

  late TestHarness d;

  const cash = PaymentMethod(id: 2, name: 'Cash', slug: 'cash');
  const card = PaymentMethod(id: 3, name: 'Card', slug: 'card');
  const bank = PaymentMethod(id: 9, name: 'Bank Transfer', slug: 'bank_transfer');

  const product = Product(
    id: 1,
    code: 'S1',
    name: 'Blow Dry',
    barcode: '',
    mrp: 60,
    tax: 0,
    type: 'service',
    categoryName: 'Hair',
    duration: '30',
    totalStock: 0,
    thumbnail: '',
  );

  setUp(() async {
    d = TestHarness();
    await d.init();
  });

  tearDown(() async => d.dispose());

  test('a new ticket is paid by Cash before the buttons are synced', () {
    final cart = CartCubit();
    expect(cart.payMode, PayMode.cash);
    expect(cart.payMethod, isNull);
  });

  test('a new ticket is paid by the first configured button', () async {
    await d.storage.setPosPaymentButtons([bank, cash, card]);
    final cart = CartCubit();

    expect(cart.payMode, PayMode.method);
    expect(cart.payMethod, bank);
  });

  test('the buttons survive the local cache', () async {
    await d.storage.setPosPaymentButtons([cash, card, bank]);
    expect(d.storage.posPaymentButtons, [cash, card, bank]);
  });

  test('a non-cash button sends one custom row for the whole ticket', () async {
    await d.storage.setPosPaymentButtons([cash, card, bank]);
    final cart = CartCubit()..add(product);
    cart.setPayButton(bank);

    final payload = cart.toPayload();
    expect(payload['paymentMethod'], 'custom');
    expect(payload['payments'], [
      {'payment_method_id': 9, 'amount': 60.0},
    ]);
    expect(cart.paidAmount, 60);
  });

  test('the Cash and Card buttons keep their own modes', () async {
    await d.storage.setPosPaymentButtons([cash, card, bank]);
    final cart = CartCubit()..add(product);

    cart.setPayButton(card);
    expect(cart.payMode, PayMode.card);
    expect(cart.payMethod, isNull);
    expect(cart.toPayload()['paymentMethod'], 'Card');
  });

  test('an edited sale paid by a button reopens on that button, any other method on Custom', () async {
    await d.storage.setPosPaymentButtons([cash, card, bank]);
    final cart = CartCubit();

    Sale sale(int methodId, String method) => Sale.fromJson({
          'id': '41',
          'invoice_no': 'INV-41',
          'date': '2026-10-26',
          'status': 'completed',
          'customer': {'name': 'Dana', 'mobile': ''},
          'items': [
            {'id': 7, 'product_id': 1, 'code': 'S1', 'name': 'Blow Dry', 'type': 'service', 'quantity': 1, 'unit_price': 60, 'discount': 0, 'tax': 0},
          ],
          'payments': [
            {'id': 1, 'payment_method_id': methodId, 'method': method, 'amount': 60},
          ],
          'summary': {'gross_amount': 60, 'item_discount': 0, 'other_discount': 0, 'tax_amount': 0, 'tip': 0, 'grand_total': 60, 'paid': 60, 'balance': 0},
        });

    cart.seedFromSale(sale(9, 'Bank Transfer'));
    expect(cart.payMode, PayMode.method);
    expect(cart.payMethod?.id, 9);

    cart.seedFromSale(sale(12, 'Visa Card'));
    expect(cart.payMode, PayMode.custom);
    expect(cart.customPayments.single.methodId, 12);
  });
}
