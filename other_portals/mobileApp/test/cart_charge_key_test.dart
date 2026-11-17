import 'package:flutter_test/flutter_test.dart';
import 'package:invo/features/sale/logic/cart_cubit/cart_cubit.dart';
import 'package:invo/shared/domain/models/index.dart';

import 'support/offline_harness.dart';

/// The idempotency key belongs to the TICKET, not to a press of Charge.
///
/// Supplying a key makes the server skip its duplicate heuristic, so a key that
/// changed per press would turn a cashier's second press — after a visible
/// error, with the ticket untouched — into a second committed sale. These tests
/// pin both halves: the key is stable while the ticket is, and it is replaced
/// the moment the ticket is not.
void main() {
  late CartCubit cart;

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

  const other = Product(
    id: 2,
    code: 'S2',
    name: 'Trim',
    barcode: '',
    mrp: 25,
    tax: 0,
    type: 'service',
    categoryName: 'Hair',
    duration: '15',
    totalStock: 0,
    thumbnail: '',
  );

  setUp(() async {
    await setUpOfflineHarness();
    cart = CartCubit();
  });

  tearDown(() async {
    await cart.close();
    await tearDownOfflineHarness();
  });

  test('two presses of Charge on an unchanged ticket carry the same key', () async {
    cart.add(product);

    final first = cart.beginCharge();
    final second = cart.beginCharge();

    expect(second.clientUuid, first.clientUuid);
    expect(second.payload['clientUuid'], first.clientUuid);
  });

  test('the key on the wire is the key on the offline snapshot', () async {
    cart.add(product);

    final ticket = cart.beginCharge();

    // Queued under a key the server never saw, the replay would ring the sale
    // up a second time.
    expect(ticket.payload['clientUuid'], ticket.clientUuid);
    expect(ticket.offlineSale['client_uuid'], ticket.clientUuid);
    expect(ticket.offlineSale['pending'], isTrue);
  });

  test('changing the ticket retires the key', () async {
    cart.add(product);
    final first = cart.beginCharge();

    cart.add(other);
    final second = cart.beginCharge();

    expect(second.clientUuid, isNot(first.clientUuid));
  });

  test('a quantity change retires the key', () async {
    cart.add(product);
    final first = cart.beginCharge();

    cart.changeQty(cart.lines.first, 1);
    final second = cart.beginCharge();

    expect(second.clientUuid, isNot(first.clientUuid));
  });

  test('an order-level discount retires the key', () async {
    cart.add(product);
    final first = cart.beginCharge();

    cart.setOrderDiscount(5);
    final second = cart.beginCharge();

    expect(second.clientUuid, isNot(first.clientUuid));
  });

  test('switching payment mode retires the key', () async {
    cart.add(product);
    final first = cart.beginCharge();

    cart.setPayMode(PayMode.card);
    final second = cart.beginCharge();

    expect(second.clientUuid, isNot(first.clientUuid));
  });

  test('clearing the ticket retires the key so the next sale is its own', () async {
    cart.add(product);
    final first = cart.beginCharge();

    cart.clear();
    cart.add(product);
    final second = cart.beginCharge();

    expect(second.clientUuid, isNot(first.clientUuid));
  });

  test('the offline snapshot carries the figures the customer was charged', () async {
    cart.add(product);
    cart.setOrderDiscount(10);

    final ticket = cart.beginCharge();
    final summary = ticket.offlineSale['summary'] as Map<String, dynamic>;

    expect(summary['gross_amount'], cart.subtotal);
    expect(summary['other_discount'], cart.orderDiscountAmount);
    expect(summary['grand_total'], cart.total);
    expect(ticket.payload['totalPayment'], cart.total);
    // The receipt renders from this snapshot, so a missing line is a blank line
    // on a receipt handed to a customer.
    expect(ticket.offlineSale['items'], hasLength(1));
  });

  test('the till clock is sent alongside the key', () async {
    cart.add(product);

    final ticket = cart.beginCharge();

    expect(DateTime.parse(ticket.payload['clientCreatedAt'] as String).isUtc, isFalse);
    expect(ticket.payload['clientCreatedAt'], isA<String>());
  });
}
