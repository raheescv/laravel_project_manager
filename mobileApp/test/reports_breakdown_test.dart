import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:google_fonts/google_fonts.dart';

import 'package:invo/features/admin/screens/v3/reports_screen.dart';

import 'support/test_harness.dart';

/// Reports ▸ Breakdown, the "Ledger" layout: one quiet toolbar, a ranked table
/// under named columns, and a grand-total bar. A big screen sorts from the
/// column heads; a phone sorts from the filter sheet. Either way the sort and
/// its direction ride on the request, so the server does the ranking.
void main() {
  setUpAll(() => GoogleFonts.config.allowRuntimeFetching = false);

  const phone = Size(392, 812);

  Future<void> openBreakdown(WidgetTester tester, TestHarness d,
      {Size size = const Size(1180, 900)}) async {
    tester.view.physicalSize = size;
    tester.view.devicePixelRatio = 1.0;
    addTearDown(tester.view.resetPhysicalSize);
    addTearDown(tester.view.resetDevicePixelRatio);
    await tester.pumpWidget(d.wrap(const ReportsScreen()));
    await tester.pump();
    await tester.pump(const Duration(milliseconds: 400));
    await tester.tap(find.text('Breakdown'));
    await tester.pump();
    await tester.pump(const Duration(milliseconds: 400));
  }

  Future<void> settle(WidgetTester tester) async {
    await tester.pump();
    await tester.pump(const Duration(milliseconds: 400));
  }

  testWidgets('the ledger names every column and totals the list', (tester) async {
    final d = TestHarness();
    await d.init(admin: true);
    addTearDown(d.dispose);
    await openBreakdown(tester, d);

    for (final label in ['ITEM', 'QTY', 'BILLS', 'SHARE', 'AMOUNT']) {
      expect(find.text(label), findsOneWidget, reason: '$label should head its column');
    }
    // The figures the old layout buried in a caption now have columns of their
    // own: Signature Cut is 40 sold over 40 bills.
    expect(find.text('Signature Cut'), findsOneWidget);
    expect(find.text('40'), findsWidgets);
    expect(find.text('Grand total'), findsOneWidget);
    expect(find.text('2 items'), findsOneWidget);
    expect(tester.takeException(), isNull);
  });

  testWidgets('a big screen sorts from the column head and flips on a second tap',
      (tester) async {
    final d = TestHarness();
    await d.init(admin: true);
    addTearDown(d.dispose);
    await openBreakdown(tester, d);

    expect(d.admin.lastSort, 'amount');
    expect(d.admin.lastDirection, 'desc');
    // The live sort is arrowed in its own column head — Amount, high to low.
    final amountHead = find.ancestor(of: find.text('AMOUNT'), matching: find.byType(Row)).first;
    expect(find.descendant(of: amountHead, matching: find.byIcon(Icons.arrow_downward_rounded)),
        findsOneWidget);

    await tester.tap(find.text('BILLS'));
    await settle(tester);
    expect(d.admin.lastSort, 'bills');
    expect(d.admin.lastDirection, 'desc');

    await tester.tap(find.text('BILLS'));
    await settle(tester);
    expect(d.admin.lastDirection, 'asc', reason: 're-tapping the live column flips it');

    final billsHead = find.ancestor(of: find.text('BILLS'), matching: find.byType(Row)).first;
    expect(find.descendant(of: billsHead, matching: find.byIcon(Icons.arrow_upward_rounded)),
        findsOneWidget,
        reason: 'the arrow follows the live sort');
  });

  testWidgets('the grand total stands on the table grid', (tester) async {
    final d = TestHarness();
    await d.init(admin: true);
    addTearDown(d.dispose);
    await openBreakdown(tester, d);

    // Fake data: 5,040 across 58 units. Each total sits under its column.
    final qtyTotal = find.text('58');
    final amountTotal = find.textContaining('5,040');
    expect(qtyTotal, findsOneWidget);
    expect(amountTotal, findsOneWidget);

    double right(Finder f) => tester.getRect(f).right;
    expect(right(qtyTotal), closeTo(right(find.text('QTY')), 1.5),
        reason: 'the quantity total lines up with the Qty column');
    expect(right(amountTotal), closeTo(right(find.text('AMOUNT')), 1.5),
        reason: 'the amount total lines up with the Amount column');
    // And with the figures above them, not just the heads.
    expect(right(amountTotal), closeTo(right(find.textContaining('3,240')), 1.5));
  });

  testWidgets('a big screen needs no filter button at all', (tester) async {
    final d = TestHarness();
    await d.init(admin: true);
    addTearDown(d.dispose);
    await openBreakdown(tester, d);

    // The heads sort, the toolbar carries the type and the command bar the
    // range, so nothing is left for a filter panel.
    expect(find.text('Filters'), findsNothing);
    expect(find.text('Sort · '), findsNothing);
    for (final type in ['All', 'Product', 'Service']) {
      expect(find.text(type), findsOneWidget, reason: 'the type filter stays on the toolbar');
    }
  });

  testWidgets('a phone sorts from its own sheet', (tester) async {
    final d = TestHarness();
    await d.init(admin: true);
    addTearDown(d.dispose);
    await openBreakdown(tester, d, size: phone);

    // The phone toolbar carries the sort it is on, and opens the sheet.
    expect(find.text('Sort · '), findsOneWidget);
    await tester.tap(find.byIcon(Icons.swap_vert_rounded).first);
    await tester.pumpAndSettle();
    expect(find.text('Sort by'), findsOneWidget);

    await tester.tap(find.text('Name').last);
    await settle(tester);
    expect(d.admin.lastSort, 'name');

    // Back on the list, the applied chip offers the way back to the defaults.
    await tester.tapAt(const Offset(196, 80));
    await tester.pumpAndSettle();
    expect(find.text('Reset'), findsOneWidget);
  });

  testWidgets('phone keeps the name, amount and share on two lines', (tester) async {
    final d = TestHarness();
    await d.init(admin: true);
    addTearDown(d.dispose);
    await openBreakdown(tester, d, size: phone);

    // Qty and Bills drop out at phone width rather than squeezing the name.
    expect(find.text('ITEM'), findsOneWidget);
    expect(find.text('QTY'), findsNothing);
    expect(find.text('BILLS'), findsNothing);
    expect(find.text('Signature Cut'), findsOneWidget);
    expect(find.text('Grand total'), findsOneWidget);
    expect(tester.takeException(), isNull);
  });
}
