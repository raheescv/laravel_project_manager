import 'package:flutter/widgets.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:invo/features/sale/logic/cart_cubit/cart_cubit.dart';
import 'package:invo/features/sale/screens/v3/review_pay_screen.dart';
import 'package:invo/shared/domain/models/index.dart';

import 'support/test_harness.dart';

/// Checkout on a re-opened DRAFT.
///
/// A draft is the one ticket with two legitimate endings — save it and leave it
/// parked, or complete it, which is the moment stock moves and the journal is
/// posted. The status the screen puts on the wire is the whole difference
/// between them, and it is decided in the widget layer, so it is asserted here
/// rather than on the cubit.

/// The primary call to action carries the money, so it is matched on the digits
/// — "Complete Draft" in the header would otherwise match too.
final _completeCta = RegExp(r'Complete .*\d');
final _updateCta = RegExp(r'Update .*\d');

void main() {
  setUpAll(() => GoogleFonts.config.allowRuntimeFetching = false);

  Sale draft({String status = 'draft'}) => Sale.fromJson({
        'id': '77',
        'invoice_no': 'INV-77',
        'date': '2026-08-09',
        'status': status,
        'branch': 'Main',
        'customer': {'name': 'Dana', 'mobile': ''},
        'items': [
          {
            'id': 5,
            'product_id': 1,
            'code': 'SC-01',
            'name': 'Signature Cut',
            'type': 'service',
            'quantity': 1,
            'unit_price': 45,
            'discount': 0,
            'tax': 0,
          },
        ],
        'payments': [
          {'id': 1, 'payment_method_id': 2, 'method': 'Cash', 'amount': 45},
        ],
        'summary': {
          'gross_amount': 45,
          'item_discount': 0,
          'other_discount': 0,
          'tax_amount': 0,
          'tip': 0,
          'grand_total': 45,
          'paid': 45,
          'balance': 0,
        },
        'created_by': 'Cashier',
      });

  Future<TestHarness> open(WidgetTester tester, Sale sale) async {
    final d = TestHarness();
    await d.init();
    addTearDown(d.dispose);
    d.cart.seedFromSale(sale);

    tester.view.physicalSize = const Size(430, 1100);
    tester.view.devicePixelRatio = 1.0;
    addTearDown(tester.view.resetPhysicalSize);
    addTearDown(tester.view.resetDevicePixelRatio);

    await tester.pumpWidget(d.wrap(const ReviewPayScreen()));
    await tester.pump();
    await tester.pump(const Duration(milliseconds: 400));
    return d;
  }

  testWidgets('a draft offers both endings; a completed sale offers only Update', (tester) async {
    final d = await open(tester, draft());
    expect(find.text('Update Draft'), findsOneWidget);
    // "Complete <total>" on the button; the header reads "Complete Draft".
    expect(find.textContaining(_completeCta), findsOneWidget);
    await d.dispose();
  });

  testWidgets('Complete sends status completed', (tester) async {
    final d = await open(tester, draft());

    await tester.tap(find.textContaining(_completeCta));
    await tester.pump();
    await tester.pump(const Duration(milliseconds: 600));

    expect(tester.takeException(), isNull);
    expect(d.sale.lastPayload?['status'], 'completed');
    // The line keeps its sale_item id, so the server patches the row in place.
    final items = (d.sale.lastPayload?['items'] as List).cast<Map<String, dynamic>>();
    expect(items.first['id'], 5);
  });

  testWidgets('Update Draft sends no status, leaving the sale parked', (tester) async {
    final d = await open(tester, draft());

    await tester.tap(find.text('Update Draft'));
    await tester.pump();
    await tester.pump(const Duration(milliseconds: 600));

    expect(tester.takeException(), isNull);
    expect(d.sale.lastPayload, isNotNull);
    expect(d.sale.lastPayload?.containsKey('status'), isFalse);
  });

  testWidgets('an already-completed sale is only ever updated', (tester) async {
    final d = await open(tester, draft(status: 'completed'));

    expect(find.text('Update Draft'), findsNothing);
    expect(find.textContaining(_updateCta), findsOneWidget);

    await tester.tap(find.textContaining(_updateCta));
    await tester.pump();
    await tester.pump(const Duration(milliseconds: 600));

    expect(d.sale.lastPayload?.containsKey('status'), isFalse);
  });

  testWidgets('the restored Cash mode survives to the payload', (tester) async {
    final d = await open(tester, draft());

    // The reason problem 2 mattered: before the fix this reopened as Custom and
    // the ticket went back with a split-payment breakdown it never had.
    expect(d.cart.payMode, PayMode.cash);
    await tester.tap(find.textContaining(_completeCta));
    await tester.pump();
    await tester.pump(const Duration(milliseconds: 600));

    expect(d.sale.lastPayload?['paymentMethod'], 'Cash');
    expect(d.sale.lastPayload?.containsKey('payments'), isFalse);
  });
}
