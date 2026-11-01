import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:google_fonts/google_fonts.dart';

import 'package:invo/features/sales_returns/screens/v3/sales_returns_list_screen.dart';
import 'package:invo/shared/domain/helpers/formatters.dart';

import 'support/test_harness.dart';

/// The Returns list matches the Sales list: a figures strip + filter chips,
/// then a full-width ledger table on a tablet (the receipt opens in a drawer on
/// tap) and one ledger card on a phone. Invoices only — no Items tab.
void main() {
  setUpAll(() => GoogleFonts.config.allowRuntimeFetching = false);

  const rows = [
    {'id': 7, 'reference_no': 'SR-0007', 'date': '2026-10-31', 'status': 'completed', 'payment_methods': 'Cash',
      'customer': {'name': 'Fatima Al-Kuwari'}, 'summary': {'paid': 310}},
    {'id': 8, 'reference_no': 'SR-0008', 'date': '2026-10-31', 'status': 'draft', 'payment_methods': 'Card',
      'customer': {'name': 'Hessa Al-Thani'}, 'summary': {'paid': 95}},
  ];

  Future<void> open(WidgetTester tester, Size size) async {
    final h = TestHarness();
    await h.init(admin: true);
    addTearDown(h.dispose);
    h.saleReturn.rows = rows;
    tester.view.physicalSize = size;
    tester.view.devicePixelRatio = 1.0;
    addTearDown(tester.view.resetPhysicalSize);
    addTearDown(tester.view.resetDevicePixelRatio);
    await tester.pumpWidget(h.wrap(const SalesReturnListScreen()));
    await tester.pump();
    await tester.pump(const Duration(milliseconds: 400));
  }

  for (final size in const [Size(1194, 834), Size(1097, 686), Size(834, 1194)]) {
    testWidgets('tablet is a ledger table at ${size.width.toInt()}×${size.height.toInt()}', (tester) async {
      await open(tester, size);
      expect(find.text('REFERENCE'), findsOneWidget);
      expect(find.text('REFUND'), findsOneWidget);
      expect(find.text('SR-0007'), findsOneWidget);
      expect(find.text('2 of 2'), findsOneWidget, reason: 'the totals footer');
      expect(find.text('REFUNDED'), findsOneWidget);
      expect(find.text('− ${Money.of(405)}'), findsOneWidget);
      expect(find.text('Items'), findsNothing, reason: 'returns list invoices only');
      expect(tester.takeException(), isNull);
    });
  }

  testWidgets('phone keeps one ledger card under the tools card', (tester) async {
    await open(tester, const Size(390, 844));
    expect(find.text('RETURNS'), findsOneWidget);
    expect(find.text('SR-0008'), findsOneWidget);
    expect(find.text('REFERENCE'), findsNothing, reason: 'no table on a phone');
    expect(tester.takeException(), isNull);
  });
}
