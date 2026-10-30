import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:google_fonts/google_fonts.dart';

import 'package:invo/features/sale/screens/v3/review_pay_screen.dart';
import 'package:invo/shared/widgets/tablet_widgets.dart';

import 'support/test_harness.dart';

/// Review & Pay on a tablet: the page head covers the receipt column only, so
/// the terminal rail runs from the top of the window. On the canteen till's
/// short landscape (1097×686) that is what lets the note pad sit above the
/// Charge bar without scrolling; the note button lives in the page head.
void main() {
  setUpAll(() => GoogleFonts.config.allowRuntimeFetching = false);

  for (final size in const [Size(1097, 686), Size(686, 1097), Size(1194, 834)]) {
    testWidgets('pay rail starts at the top at ${size.width.toInt()}×${size.height.toInt()}', (tester) async {
      final d = TestHarness();
      await d.init();
      addTearDown(d.dispose);
      final products = await d.demoProducts();
      for (final product in products.take(3)) {
        d.cart.add(product);
      }
      tester.view.physicalSize = size;
      tester.view.devicePixelRatio = 1.0;
      addTearDown(tester.view.resetPhysicalSize);
      addTearDown(tester.view.resetDevicePixelRatio);
      await tester.pumpWidget(d.wrap(const ReviewPayScreen()));
      await tester.pump();
      await tester.pump(const Duration(milliseconds: 400));
      expect(tester.takeException(), isNull, reason: 'no overflow at $size');

      final head = tester.getRect(find.byType(TabletPageHead));
      expect(tester.getRect(find.text('PAY WITH')).top, lessThan(head.bottom),
          reason: 'the rail should use the height beside the page head');
      expect(tester.getRect(find.text('PAY WITH')).left, greaterThan(head.right),
          reason: 'the page head should stop at the ticket column');
      expect(tester.getRect(find.text('AMOUNT DUE')).right, lessThanOrEqualTo(head.right),
          reason: 'the amount due is the foot of the receipt, not the rail');

      // The note sheet's keyboard must not crush the rail under it.
      tester.view.viewInsets = const FakeViewPadding(bottom: 340);
      addTearDown(tester.view.resetViewInsets);
      await tester.pump();
      expect(tester.takeException(), isNull, reason: 'no overflow with the keyboard up at $size');
      tester.view.resetViewInsets();
      await tester.pump();

      final charge = tester.getRect(find.textContaining('Charge'));
      expect(tester.getRect(find.textContaining('Exact')).bottom, lessThan(charge.top),
          reason: 'the cash pad and its tools should be on screen above the Charge bar');
      expect(tester.getRect(find.byTooltip('Add a note')).bottom, lessThanOrEqualTo(head.bottom),
          reason: 'the note button sits in the page head');
    });
  }
}
