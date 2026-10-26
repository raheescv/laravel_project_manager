import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:google_fonts/google_fonts.dart';

import 'package:invo/features/sale/screens/v3/invoice_screen.dart';
import 'package:invo/features/sales/screens/v3/sales_list_screen.dart';

import 'support/test_harness.dart';

/// The Sales list's Items tab: every sold line on its own row (served by
/// FakeSaleRepository.saleItems), at phone and tablet sizes, and on a tablet a
/// tapped line opens its invoice in the drawer beside the table.
void main() {
  setUpAll(() => GoogleFonts.config.allowRuntimeFetching = false);

  Future<TestHarness> pumpAt(WidgetTester tester, Size size) async {
    final d = TestHarness();
    await d.init(admin: true);
    addTearDown(d.dispose);
    tester.view.physicalSize = size;
    tester.view.devicePixelRatio = 1.0;
    addTearDown(tester.view.resetPhysicalSize);
    addTearDown(tester.view.resetDevicePixelRatio);
    await tester.pumpWidget(d.wrap(const SalesListScreen()));
    await tester.pump();
    await tester.pump(const Duration(milliseconds: 400));
    return d;
  }

  Future<void> openItemsTab(WidgetTester tester) async {
    await tester.tap(find.text('Items'));
    await tester.pump();
    await tester.pump(const Duration(milliseconds: 400));
  }

  const sizes = <String, Size>{
    'phone': Size(390, 844),
    'small tablet portrait': Size(768, 1024),
    'tablet landscape': Size(1194, 834),
  };

  for (final vp in sizes.entries) {
    testWidgets('Items tab lists sold lines without overflow on ${vp.key}', (tester) async {
      await pumpAt(tester, vp.value);
      await openItemsTab(tester);

      expect(tester.takeException(), isNull);
      expect(find.text('Linen Shirt'), findsOneWidget);
      // The figures strip switches to the line totals.
      expect(find.text('LINES'), findsOneWidget);
      expect(find.text('QTY'), findsWidgets);
    });
  }

  testWidgets('the tablet table names the employee on each line', (tester) async {
    await pumpAt(tester, const Size(1194, 834));
    await openItemsTab(tester);

    expect(find.text('EMPLOYEE'), findsOneWidget);
    expect(find.text('Mariam'), findsOneWidget);
  });

  testWidgets('the phone row names the employee on each line', (tester) async {
    await pumpAt(tester, const Size(390, 844));
    await openItemsTab(tester);

    expect(find.textContaining('Mariam'), findsOneWidget);
  });

  testWidgets('a tapped line opens its invoice in the tablet drawer', (tester) async {
    await pumpAt(tester, const Size(1194, 834));
    await openItemsTab(tester);

    expect(find.byType(InvoiceScreen), findsNothing);
    await tester.tap(find.text('Linen Shirt'));
    await tester.pump();
    await tester.pump(const Duration(milliseconds: 400));

    expect(tester.takeException(), isNull);
    expect(find.byType(InvoiceScreen), findsOneWidget);
  });
}
