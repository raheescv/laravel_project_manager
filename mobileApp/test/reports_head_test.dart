import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:google_fonts/google_fonts.dart';

import 'package:invo/features/admin/screens/v3/reports_screen.dart';

import 'support/test_harness.dart';

/// The tablet command bar — title, the range it is showing, the preset cluster
/// and the report switcher all live on (or directly under) one flat bar, the
/// same chrome as the Link Card screen.
void main() {
  setUpAll(() => GoogleFonts.config.allowRuntimeFetching = false);

  Future<void> pumpReports(WidgetTester tester, TestHarness d) async {
    tester.view.physicalSize = const Size(1180, 820);
    tester.view.devicePixelRatio = 1.0;
    addTearDown(tester.view.resetPhysicalSize);
    addTearDown(tester.view.resetDevicePixelRatio);
    await tester.pumpWidget(d.wrap(const ReportsScreen()));
    await tester.pump();
    await tester.pump(const Duration(milliseconds: 400));
  }

  testWidgets('tablet command bar carries the title, presets and switcher', (tester) async {
    final d = TestHarness();
    await d.init(admin: true);
    addTearDown(d.dispose);
    await pumpReports(tester, d);

    expect(find.text('Reports'), findsOneWidget);
    for (final label in ['Today', '7 Days', '30 Days', 'Month']) {
      expect(find.text(label), findsOneWidget, reason: '$label preset should sit in the bar');
    }
    expect(find.text('Overview'), findsOneWidget);
    expect(find.text('Breakdown'), findsOneWidget);
    // The range control lives in the bar only — no second stacked filter card.
    expect(find.text('DATE RANGE'), findsNothing);
  });

  testWidgets('picking a preset never shifts the bar', (tester) async {
    final d = TestHarness();
    await d.init(admin: true);
    addTearDown(d.dispose);
    await pumpReports(tester, d);

    // The range line under the title changes length with the preset ("Today"
    // vs "1 - 23 Sep 2026"). The controls must not reflow under the pointer.
    final before = tester.getTopLeft(find.text('30 Days'));
    await tester.tap(find.text('Month'));
    await tester.pump();
    await tester.pump(const Duration(milliseconds: 400));
    expect(tester.getTopLeft(find.text('30 Days')), before,
        reason: 'the preset cluster must stay put when the range changes');

    await tester.tap(find.text('Today'));
    await tester.pump();
    await tester.pump(const Duration(milliseconds: 400));
    expect(tester.getTopLeft(find.text('30 Days')), before);
  });

  testWidgets('switcher moves between the two reports', (tester) async {
    final d = TestHarness();
    await d.init(admin: true);
    addTearDown(d.dispose);
    await pumpReports(tester, d);

    await tester.tap(find.text('Breakdown'));
    await tester.pump();
    await tester.pump(const Duration(milliseconds: 300));
    expect(tester.takeException(), isNull);
    expect(find.text('Breakdown'), findsOneWidget);
  });
}
