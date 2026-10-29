import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:google_fonts/google_fonts.dart';

import 'package:invo/features/sale/screens/v3/new_sale_screen.dart';

import 'support/test_harness.dart';

/// Typing on the tablet New Sale screen with the keyboard up: a landscape till
/// is left ~300pt, and the ticket rail used to overflow under it. The rail now
/// scrolls instead, the discount field keeps its focus, and it comes back
/// whole once the keyboard goes. Sizes are harsher than the I24T02 (status
/// bar, a tall keyboard, enlarged text) so a real device has headroom.
void main() {
  setUpAll(() => GoogleFonts.config.allowRuntimeFetching = false);

  Future<TestHarness> open(WidgetTester tester, Size size) async {
    final d = TestHarness();
    await d.init();
    addTearDown(d.dispose);
    final products = await d.demoProducts();
    for (final product in products) {
      d.cart.add(product);
    }
    tester.view.physicalSize = size;
    tester.view.devicePixelRatio = 1.0;
    tester.view.padding = const FakeViewPadding(top: 24, bottom: 24);
    tester.platformDispatcher.textScaleFactorTestValue = 1.15;
    addTearDown(tester.view.reset);
    addTearDown(tester.platformDispatcher.clearTextScaleFactorTestValue);
    await tester.pumpWidget(d.wrap(const NewSaleScreen()));
    await tester.pump(const Duration(milliseconds: 400));
    return d;
  }

  for (final size in const [Size(1097, 686), Size(686, 1097), Size(1194, 834)]) {
    for (final percent in [true, false]) {
      testWidgets('order discount (${percent ? '%' : 'amount'}) with the keyboard up at ${size.width.toInt()}×${size.height.toInt()}',
          (tester) async {
        final d = await open(tester, size);
        if (percent) {
          await tester.tap(find.text('%'));
          await tester.pump();
        }

        final field = find.byType(TextField).last;
        await tester.tap(field);
        tester.view.viewInsets = FakeViewPadding(bottom: size.height * 0.62);
        await tester.pump();
        await tester.enterText(field, '10');
        await tester.pump(const Duration(milliseconds: 400));

        expect(tester.takeException(), isNull, reason: 'the rail must not overflow under the keyboard');
        expect(tester.widget<TextField>(find.byType(TextField).last).focusNode!.hasFocus, isTrue);
        expect(d.cart.orderDiscount, 10);
        expect(d.cart.orderDiscountIsPercent, percent);
        final keyboardTop = size.height * 0.38;
        expect(tester.getRect(find.byType(TextField).last).bottom, lessThanOrEqualTo(keyboardTop),
            reason: 'the field being typed stays above the keyboard');

        tester.view.resetViewInsets();
        FocusManager.instance.primaryFocus?.unfocus();
        await tester.pump(const Duration(milliseconds: 400));
        expect(tester.takeException(), isNull);
        expect(find.text('Current ticket'), findsOneWidget);
        expect(tester.getRect(find.text('Charge')).bottom, lessThanOrEqualTo(size.height));
        expect(d.cart.orderDiscount, 10);
      });
    }
  }

  testWidgets('catalog search with the keyboard up on the landscape till', (tester) async {
    await open(tester, const Size(1097, 686));
    await tester.tap(find.byType(TextField).first);
    tester.view.viewInsets = const FakeViewPadding(bottom: 686 * 0.62);
    await tester.pump(const Duration(milliseconds: 400));
    expect(tester.takeException(), isNull);
  });
}
