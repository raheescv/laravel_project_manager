import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:google_fonts/google_fonts.dart';

import 'package:invo/features/sale/screens/v3/new_sale_screen.dart';

import 'support/test_harness.dart';

/// Typing the order discount on the tablet ticket rail: the keyboard leaves a
/// landscape till ~300pt, and the rail used to overflow under it. The client
/// card steps aside while the field has focus, the field keeps focus, and the
/// rail comes back whole once the keyboard goes.
void main() {
  setUpAll(() => GoogleFonts.config.allowRuntimeFetching = false);

  for (final size in const [Size(1097, 686), Size(686, 1097), Size(1194, 834)]) {
    for (final percent in [true, false]) {
      testWidgets('order discount (${percent ? '%' : 'amount'}) with the keyboard up at ${size.width.toInt()}×${size.height.toInt()}',
          (tester) async {
        final d = TestHarness();
        await d.init();
        addTearDown(d.dispose);
        final products = await d.demoProducts();
        for (final product in products) {
          d.cart.add(product);
        }
        tester.view.physicalSize = size;
        tester.view.devicePixelRatio = 1.0;
        addTearDown(tester.view.resetPhysicalSize);
        addTearDown(tester.view.resetDevicePixelRatio);
        addTearDown(tester.view.resetViewInsets);
        await tester.pumpWidget(d.wrap(const NewSaleScreen()));
        await tester.pump(const Duration(milliseconds: 400));
        if (percent) {
          await tester.tap(find.text('%'));
          await tester.pump();
        }

        final field = find.byType(TextField).last;
        await tester.tap(field);
        tester.view.viewInsets = FakeViewPadding(bottom: size.height * 0.55);
        await tester.pump();
        await tester.enterText(field, '10');
        await tester.pump(const Duration(milliseconds: 400));

        expect(tester.takeException(), isNull, reason: 'the rail must not overflow under the keyboard');
        expect(tester.widget<TextField>(find.byType(TextField).last).focusNode!.hasFocus, isTrue);
        expect(d.cart.orderDiscount, 10);
        expect(d.cart.orderDiscountIsPercent, percent);
        expect(find.text('Charge'), findsOneWidget);

        tester.view.resetViewInsets();
        FocusManager.instance.primaryFocus?.unfocus();
        await tester.pump(const Duration(milliseconds: 400));
        expect(tester.takeException(), isNull);
        expect(find.text('Current ticket'), findsOneWidget);
        expect(d.cart.orderDiscount, 10);
      });
    }
  }
}
