import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:google_fonts/google_fonts.dart';

import 'package:invo/features/sale/logic/cart_cubit/cart_cubit.dart';
import 'package:invo/features/sale/screens/v3/review_pay_screen.dart';
import 'package:invo/shared/domain/helpers/formatters.dart';
import 'package:invo/shared/domain/models/index.dart';

import 'support/test_harness.dart';

/// Split on Review & Pay: two methods split evenly at once, more ask which
/// two; the methods sit above the summary as chips; typing one amount leaves
/// the rest to the others.
void main() {
  setUpAll(() => GoogleFonts.config.allowRuntimeFetching = false);

  const cash = PaymentMethod(id: 1, name: 'Cash');
  const card = PaymentMethod(id: 2, name: 'Card');
  const bank = PaymentMethod(id: 3, name: 'Bank Transfer');

  double paid(CartCubit cart) => round2(cart.customPayments.fold(0.0, (a, p) => a + p.amount));
  List<double> amounts(CartCubit cart) => [for (final p in cart.customPayments) p.amount];

  group('shareSplit', () {
    CustomPayment row(int id, {double amount = 0, bool typed = false}) =>
        CustomPayment(methodId: id, methodName: '$id', amount: amount, typed: typed);

    test('splits evenly, the last row taking the rounding cent', () {
      final rows = shareSplit([row(1), row(2), row(3)], 280);
      expect([for (final r in rows) r.amount], [93.33, 93.33, 93.34]);
    });

    test('typed rows keep their amount; the others share the rest', () {
      final rows = shareSplit([row(1, amount: 100, typed: true), row(2), row(3)], 280);
      expect([for (final r in rows) r.amount], [100, 90, 90]);
    });

    test('typed rows paying past the total leave the others nothing', () {
      final rows = shareSplit([row(1, amount: 300, typed: true), row(2)], 280);
      expect(rows.last.amount, 0);
    });

    test('a currency with no minor unit splits in whole units', () {
      final rows = shareSplit([row(1), row(2), row(3)], 100, decimals: 0);
      expect([for (final r in rows) r.amount], [33, 33, 34]);
    });
  });

  group('CartCubit split', () {
    late TestHarness d;
    setUp(() async {
      d = TestHarness();
      await d.init();
      d.cart.add((await d.demoProducts()).first);
    });
    tearDown(() => d.dispose());

    test('typing one amount gives the other method the rest', () {
      d.cart.splitBetween([cash, card]);
      final total = d.cart.total;
      d.cart.setSplitAmount(cash.id, 10);
      expect(amounts(d.cart), [10, round2(total - 10)]);
      expect(d.cart.balance, 0);

      // Typing the second one hands the rest back to the first.
      d.cart.setSplitAmount(card.id, 4);
      expect(amounts(d.cart), [round2(total - 4), 4]);
      expect(d.cart.balance, 0);
    });

    test('adding a method evens the split out again; removing keeps it settled', () {
      d.cart.splitBetween([cash, card]);
      d.cart.setSplitAmount(cash.id, 10);
      d.cart.toggleSplitMethod(bank);
      expect(d.cart.customPayments.map((p) => p.methodId), [1, 2, 3]);
      expect(d.cart.customPayments.any((p) => p.typed), isFalse);
      expect(paid(d.cart), d.cart.total);

      d.cart.setSplitAmount(cash.id, 10);
      d.cart.setSplitAmount(card.id, 5);
      d.cart.toggleSplitMethod(bank);
      expect(d.cart.customPayments.length, 2);
      expect(paid(d.cart), d.cart.total, reason: 'the last row goes back to taking the rest');
    });

    test('Even resets every typed amount', () {
      d.cart.splitBetween([cash, card]);
      d.cart.setSplitAmount(cash.id, 1);
      d.cart.evenSplit();
      expect(d.cart.customPayments.every((p) => !p.typed), isTrue);
      expect((amounts(d.cart).first - amounts(d.cart).last).abs(), lessThanOrEqualTo(0.01));
    });
  });

  Future<TestHarness> open(WidgetTester tester, Size size, {List<PaymentMethod>? methods}) async {
    final d = TestHarness();
    await d.init();
    addTearDown(d.dispose);
    if (methods != null) d.lookup.methods = methods;
    d.cart.add((await d.demoProducts()).first);
    tester.view.physicalSize = size;
    tester.view.devicePixelRatio = 1.0;
    addTearDown(tester.view.resetPhysicalSize);
    addTearDown(tester.view.resetDevicePixelRatio);
    await tester.pumpWidget(d.wrap(const ReviewPayScreen()));
    await tester.pump();
    await tester.pump(const Duration(milliseconds: 400));
    return d;
  }

  Future<void> settle(WidgetTester tester) async {
    await tester.pump();
    await tester.pump(const Duration(milliseconds: 400));
  }

  testWidgets('two methods split evenly at once, with no question', (tester) async {
    final d = await open(tester, const Size(390, 844), methods: const [cash, card]);

    await tester.tap(find.text('Split'));
    await settle(tester);

    expect(find.text('Split the payment'), findsNothing);
    expect(d.cart.payMode, PayMode.custom);
    expect(d.cart.customPayments.map((p) => p.methodName), ['Cash', 'Card']);
    expect(d.cart.balance, 0);
    // The panel is an equation: the total, then a slot per method.
    expect(find.text('Tap a slot to change it'), findsOneWidget);
    expect(find.text('AUTO · SHARES REST'), findsNWidgets(2));
    expect(tester.takeException(), isNull);
  });

  testWidgets('more methods ask which two; the second tap applies it', (tester) async {
    final d = await open(tester, const Size(390, 844));

    await tester.tap(find.text('Split'));
    await settle(tester);
    expect(find.text('Split the payment'), findsOneWidget);

    await tester.tap(find.text('Card').last);
    await settle(tester);
    expect(d.cart.payMode, isNot(PayMode.custom), reason: 'one pick is not a split yet');

    await tester.tap(find.text('Bank Transfer').last);
    await settle(tester);
    expect(d.cart.customPayments.map((p) => p.methodName), ['Card', 'Bank Transfer']);
    expect(d.cart.balance, 0);

    // The card stays open on the equation until it is applied.
    expect(find.text('Apply split · 2 ways'), findsOneWidget);
    await tester.tap(find.text('Apply split · 2 ways'));
    await settle(tester);
    expect(find.text('Split the payment'), findsNothing);

    // Cash is a chip above the summary — one tap adds it, evenly.
    await tester.tap(find.text('Cash').last);
    await settle(tester);
    expect(d.cart.customPayments.length, 3);
    expect(d.cart.balance, 0);
    expect(tester.takeException(), isNull);
  });

  testWidgets('the keypad sets one amount and the other method takes the rest', (tester) async {
    final d = await open(tester, const Size(390, 844), methods: const [cash, card]);
    await tester.tap(find.text('Split'));
    await settle(tester);

    await tester.tap(find.text(Money.plain(d.cart.customPayments.first.amount)).first);
    await settle(tester);
    expect(find.textContaining('Cash pays'), findsOneWidget);

    await tester.tap(find.text('1').last);
    await tester.pump();
    expect(d.cart.customPayments.first.amount, 1);
    expect(d.cart.customPayments.last.amount, round2(d.cart.total - 1));
    expect(d.cart.balance, 0);

    await tester.tap(find.text('Done'));
    await settle(tester);
    expect(find.textContaining('Cash pays'), findsNothing, reason: 'opened on the slot, Done closes the card');
    expect(tester.takeException(), isNull);
  });

  testWidgets('on a tablet the panel is the equation; a slot opens its keypad', (tester) async {
    final d = await open(tester, const Size(1194, 834), methods: const [cash, card]);
    await tester.tap(find.text('Split'));
    await settle(tester);
    expect(find.text('Tap a slot to change it'), findsOneWidget);
    expect(find.text('Settled'), findsOneWidget);

    await tester.tap(find.text('AUTO · SHARES REST').first);
    await settle(tester);
    expect(find.textContaining('Cash pays'), findsOneWidget);
    expect(d.cart.payMode, PayMode.custom);
    expect(tester.takeException(), isNull);
  });

  testWidgets('on the till the sheets float over the pay rail and fit', (tester) async {
    final d = await open(tester, const Size(1097, 686));

    await tester.tap(find.text('Split'));
    await settle(tester);
    expect(find.text('Split the payment'), findsOneWidget);
    expect(tester.getTopLeft(find.text('Split the payment')).dx, greaterThan(1097 * 0.5),
        reason: 'the picker sits over the rail, clear of the receipt');

    await tester.tap(find.text('Cash').last);
    await tester.tap(find.text('Card').last);
    await settle(tester);
    expect(d.cart.customPayments.length, 2);

    // Tap the Card slot in the card itself — its keypad opens in place.
    await tester.tap(find.text('AUTO · SHARES REST').last);
    await settle(tester);
    expect(find.textContaining('Card pays'), findsOneWidget);
    await tester.tap(find.text('All the rest'));
    await tester.pump();
    expect(d.cart.customPayments.last.amount, d.cart.total);
    expect(d.cart.customPayments.first.amount, 0);
    expect(tester.takeException(), isNull);
  });

  for (final size in const [Size(390, 844), Size(360, 640), Size(1097, 686)]) {
    testWidgets('the split card fits three ways with its keypad at ${size.width.toInt()}×${size.height.toInt()}', (tester) async {
      final d = await open(tester, size);
      await tester.tap(find.text('Split'));
      await settle(tester);
      await tester.tap(find.text('Cash').last);
      await tester.tap(find.text('Card').last);
      await settle(tester);
      await tester.tap(find.text('Bank Transfer').last);
      await settle(tester);
      expect(d.cart.customPayments.length, 3);
      await tester.tap(find.text('AUTO · SHARES REST').first);
      await settle(tester);
      expect(find.text('All the rest'), findsOneWidget);
      expect(tester.takeException(), isNull, reason: 'the split card overflows at $size');
    });
  }

  for (final size in const [Size(390, 844), Size(360, 640), Size(1097, 686), Size(1194, 834)]) {
    testWidgets('a three-way split fits unscrolled at ${size.width.toInt()}×${size.height.toInt()}', (tester) async {
      final d = await open(tester, size);
      d.cart.splitBetween(const [cash, card, bank]);
      await settle(tester);
      expect(tester.takeException(), isNull, reason: 'the split overflows at $size');
      expect(find.byType(SingleChildScrollView), findsNothing, reason: 'the split scrolls at $size');
      expect(find.text('Bank Transfer'), findsWidgets);
    });
  }
}
