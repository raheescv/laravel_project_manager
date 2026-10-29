import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:google_fonts/google_fonts.dart';

import 'package:invo/features/sale/widgets/v3/cash_tender_card.dart';

import 'support/test_harness.dart';

/// The Received pad's note tally: a counted note shows its count as a badge
/// and carries a − that takes one back, without a long-press.
void main() {
  setUpAll(() => GoogleFonts.config.allowRuntimeFetching = false);

  testWidgets('a counted note shows its count and the − takes one back', (tester) async {
    final d = TestHarness();
    await d.init();
    addTearDown(d.dispose);
    double? tendered;
    final ladder = CashDenominations.current();
    final note = ladder[3];
    final label = note.toStringAsFixed(0);
    await tester.pumpWidget(d.wrap(Scaffold(
      body: SingleChildScrollView(
        child: CashTenderPanel(total: 10, onChanged: (v) => tendered = v),
      ),
    )));
    await tester.pump();

    expect(find.byIcon(Icons.remove), findsNothing, reason: 'no − until a note is counted');
    await tester.tap(find.text(label));
    await tester.pump();
    await tester.tap(find.text(label));
    await tester.pump();
    expect(find.text('×2'), findsOneWidget);
    expect(find.text('2 notes'), findsOneWidget);
    expect(tendered, note * 2);

    await tester.tap(find.byIcon(Icons.remove));
    await tester.pump();
    expect(find.text('×1'), findsOneWidget);
    expect(tendered, note);

    await tester.tap(find.byIcon(Icons.remove));
    await tester.pump();
    expect(find.textContaining('×'), findsNothing);
    expect(find.byIcon(Icons.remove), findsNothing);
    expect(tendered, isNull);
    expect(tester.takeException(), isNull);
  });
}
