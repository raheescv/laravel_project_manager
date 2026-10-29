import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:google_fonts/google_fonts.dart';

import 'package:invo/features/sale/screens/v3/new_sale_screen.dart';

import 'support/test_harness.dart';

/// The tablet ticket rail on the canteen till (I24T02, 1097×686 landscape and
/// its portrait turn): every line on the ticket and the Charge button must be
/// on screen together, which the old ~130pt line cards could not manage.
void main() {
  setUpAll(() => GoogleFonts.config.allowRuntimeFetching = false);

  for (final size in const [Size(1097, 686), Size(686, 1097), Size(1194, 834)]) {
    testWidgets('rail shows the whole ticket and Charge at ${size.width.toInt()}×${size.height.toInt()}', (tester) async {
      final d = TestHarness();
      await d.init();
      addTearDown(d.dispose);
      final products = await d.demoProducts();
      for (final product in products) {
        d.cart.add(product);
      }
      d.cart.add(products.first);
      tester.view.physicalSize = size;
      tester.view.devicePixelRatio = 1.0;
      addTearDown(tester.view.resetPhysicalSize);
      addTearDown(tester.view.resetDevicePixelRatio);
      await tester.pumpWidget(d.wrap(const NewSaleScreen()));
      await tester.pump();
      await tester.pump(const Duration(milliseconds: 400));
      expect(tester.takeException(), isNull, reason: 'no overflow at $size');

      final screen = Offset.zero & size;
      final charge = tester.getRect(find.text('Charge'));
      expect(screen.contains(charge.bottomRight), isTrue, reason: 'Charge must be on screen');
      final rail = find.ancestor(of: find.text('Current ticket'), matching: find.byType(Column)).first;
      for (final product in products) {
        // The name shows once on its catalog tile and once on the rail's line.
        final onRail = find.descendant(of: rail, matching: find.text(product.name));
        expect(onRail, findsOneWidget, reason: '${product.name} should be on the ticket');
        expect(tester.getRect(onRail).bottom, lessThan(charge.top), reason: '${product.name} line must sit above the total');
      }
      // A repeated item shows what one costs under its line total.
      expect(find.textContaining(' ea'), findsOneWidget);
    });
  }
}
