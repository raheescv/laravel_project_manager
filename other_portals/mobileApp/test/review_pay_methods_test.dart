import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:google_fonts/google_fonts.dart';

import 'package:invo/features/sale/logic/cart_cubit/cart_cubit.dart';
import 'package:invo/features/sale/screens/v3/review_pay_screen.dart';
import 'package:invo/features/student_card/domain/models/student_card.dart';
import 'package:invo/shared/domain/models/index.dart';

import 'support/test_harness.dart';

/// The terminal's payment methods: Cash and Card always, then the first two
/// configured POS Payment Buttons; Credit and Split always; the student Wallet
/// only once a card has been tapped. And it all fits the screen, unscrolled.
void main() {
  setUpAll(() => GoogleFonts.config.allowRuntimeFetching = false);

  const cash = PaymentMethod(id: 2, name: 'Cash', slug: 'cash');
  const card = PaymentMethod(id: 3, name: 'Card', slug: 'card');
  const bank = PaymentMethod(id: 9, name: 'Bank Transfer', slug: 'bank_transfer');
  const qr = PaymentMethod(id: 10, name: 'QR Pay', slug: 'qr_pay');
  const voucher = PaymentMethod(id: 11, name: 'Voucher', slug: 'voucher');
  const student = StudentCard(accountId: 7, name: 'Sara Ahmed', balance: 500, available: 500, cardMethodId: 12);

  Future<TestHarness> open(WidgetTester tester, Size size, {bool tapped = false}) async {
    final d = TestHarness();
    await d.init();
    addTearDown(d.dispose);
    await d.storage.setPosPaymentButtons([cash, card, bank, qr, voucher]);
    d.cart.add((await d.demoProducts()).first);
    if (tapped) d.cart.setStudent(student);
    tester.view.physicalSize = size;
    tester.view.devicePixelRatio = 1.0;
    addTearDown(tester.view.resetPhysicalSize);
    addTearDown(tester.view.resetDevicePixelRatio);
    await tester.pumpWidget(d.wrap(const ReviewPayScreen()));
    await tester.pump();
    await tester.pump(const Duration(milliseconds: 400));
    return d;
  }

  testWidgets('Cash, Card, two configured methods, Credit and Split', (tester) async {
    final d = await open(tester, const Size(390, 844));

    for (final label in ['Cash', 'Card', 'Bank Transfer', 'QR Pay', 'Credit', 'Split']) {
      expect(find.text(label), findsOneWidget, reason: '$label is on the terminal');
    }
    expect(find.text('Voucher'), findsNothing, reason: 'only the first two configured buttons are shown');
    expect(find.text('Wallet'), findsNothing, reason: 'no card has been tapped');

    await tester.tap(find.text('QR Pay'));
    await tester.pump();
    expect(d.cart.payMode, PayMode.method);
    expect(d.cart.payMethod, qr);
    expect(find.text('Fully paid by QR Pay'), findsOneWidget);

    await tester.tap(find.text('Card'));
    await tester.pump();
    expect(d.cart.payMode, PayMode.card);
    expect(tester.takeException(), isNull);
  });

  testWidgets('a tapped card adds the Wallet and keeps Credit and Split', (tester) async {
    final d = await open(tester, const Size(390, 844), tapped: true);

    expect(d.cart.payMode, PayMode.studentCard);
    expect(find.text('Wallet'), findsOneWidget);
    expect(find.text('Credit'), findsOneWidget);
    expect(find.text('Split'), findsOneWidget);
    expect(find.text('Sara Ahmed'), findsWidgets);

    await tester.tap(find.text('Cash'));
    await tester.pump();
    expect(d.cart.payMode, PayMode.cash);
    await tester.tap(find.text('Wallet'));
    await tester.pump();
    expect(d.cart.payMode, PayMode.studentCard);
    expect(tester.takeException(), isNull);
  });

  for (final size in const [Size(390, 844), Size(360, 640), Size(1097, 686), Size(1194, 834)]) {
    testWidgets('every method fits unscrolled at ${size.width.toInt()}×${size.height.toInt()}', (tester) async {
      final d = await open(tester, size, tapped: true);
      for (final label in ['Cash', 'Card', 'Bank Transfer', 'Credit', 'Wallet']) {
        await tester.tap(find.text(label));
        await tester.pump();
        await tester.pump(const Duration(milliseconds: 200));
        expect(tester.takeException(), isNull, reason: '$label overflows at $size');
        expect(find.byType(SingleChildScrollView), findsNothing, reason: '$label scrolls at $size');
      }
      await d.dispose();
    });
  }
}
