import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:google_fonts/google_fonts.dart';

import 'package:invo/features/admin/screens/v3/reports_screen.dart';
import 'package:invo/shared/widgets/astra_widgets.dart';

import 'support/test_harness.dart';

/// The tablet command bar — title, the range it is showing, the report
/// switcher and the preset cluster all live on one flat bar, the same chrome
/// as the Link Card screen.
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
    // The harness's day session opened on 14 Jun — not the calendar date — so
    // its own day leads the presets; the 30-day preset is gone.
    for (final label in ['14 Jun', 'Today', 'Yesterday', '7 Days', 'Month']) {
      expect(find.text(label), findsOneWidget, reason: '$label preset should sit in the bar');
    }
    expect(find.text('30 Days'), findsNothing);
    expect(find.text('Overview'), findsOneWidget);
    expect(find.text('Breakdown'), findsOneWidget);
    // The range control lives in the bar only — no second stacked filter card.
    expect(find.text('DATE RANGE'), findsNothing);
  });

  testWidgets('tablet switcher shares the bar row with the presets', (tester) async {
    final d = TestHarness();
    await d.init(admin: true);
    addTearDown(d.dispose);
    // Wide tablet, then the 1097-wide canteen till.
    for (final size in const [Size(1180, 820), Size(1097, 686)]) {
      tester.view.physicalSize = size;
      tester.view.devicePixelRatio = 1.0;
      addTearDown(tester.view.resetPhysicalSize);
      addTearDown(tester.view.resetDevicePixelRatio);
      await tester.pumpWidget(d.wrap(const ReportsScreen()));
      await tester.pump();
      await tester.pump(const Duration(milliseconds: 400));
      expect(tester.takeException(), isNull, reason: 'no overflow at ${size.width} wide');
      expect(tester.getCenter(find.text('Breakdown')).dy, closeTo(tester.getCenter(find.text('Today')).dy, 4),
          reason: 'switcher should sit in the bar beside the presets at ${size.width} wide');
    }
  });

  testWidgets('picking a preset never shifts the bar', (tester) async {
    final d = TestHarness();
    await d.init(admin: true);
    addTearDown(d.dispose);
    await pumpReports(tester, d);

    // The range line under the title changes length with the preset ("Today"
    // vs "1 - 23 Sep 2026"). The controls must not reflow under the pointer.
    final before = tester.getTopLeft(find.text('7 Days'));
    await tester.tap(find.text('Month'));
    await tester.pump();
    await tester.pump(const Duration(milliseconds: 400));
    expect(tester.getTopLeft(find.text('7 Days')), before,
        reason: 'the preset cluster must stay put when the range changes');

    await tester.tap(find.text('Today'));
    await tester.pump();
    await tester.pump(const Duration(milliseconds: 400));
    expect(tester.getTopLeft(find.text('7 Days')), before);
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

  testWidgets('phone carries the report switcher in the header, even on a 360 phone', (tester) async {
    final d = TestHarness();
    await d.init(admin: true);
    addTearDown(d.dispose);
    for (final size in const [Size(360, 740), Size(392, 812)]) {
      tester.view.physicalSize = size;
      tester.view.devicePixelRatio = 1.0;
      addTearDown(tester.view.resetPhysicalSize);
      addTearDown(tester.view.resetDevicePixelRatio);
      await tester.pumpWidget(d.wrap(const ReportsScreen()));
      await tester.pump();
      await tester.pump(const Duration(milliseconds: 400));
      expect(tester.takeException(), isNull, reason: 'no overflow at ${size.width} wide');

      final header = find.byType(EmeraldHeader);
      expect(find.descendant(of: header, matching: find.text('Overview')), findsOneWidget);
      expect(find.descendant(of: header, matching: find.text('Breakdown')), findsOneWidget);
      // Sharing the title row, not stacked under it.
      expect(tester.getCenter(find.text('Breakdown')).dy, closeTo(tester.getCenter(find.text('Reports')).dy, 6));
    }
  });
}
