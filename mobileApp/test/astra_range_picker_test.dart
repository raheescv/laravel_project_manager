import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:google_fonts/google_fonts.dart';

import 'package:invo/shared/widgets/astra_range_picker.dart';

import 'support/test_harness.dart';

/// The shared range picker (Sales, Returns, Reports): a calendar-sized card
/// with presets and a month grid, not Material's full-screen picker.
void main() {
  setUpAll(() => GoogleFonts.config.allowRuntimeFetching = false);

  Future<Future<DateTimeRange?>> open(WidgetTester tester, Size size, {DateTimeRange? initial}) async {
    final d = TestHarness();
    await d.init();
    addTearDown(d.dispose);
    tester.view.physicalSize = size;
    tester.view.devicePixelRatio = 1.0;
    addTearDown(tester.view.resetPhysicalSize);
    addTearDown(tester.view.resetDevicePixelRatio);
    late Future<DateTimeRange?> result;
    await tester.pumpWidget(d.wrap(Builder(
      builder: (context) => Scaffold(
        body: Center(
          child: TextButton(
            onPressed: () {
              final now = DateTime.now();
              result = showAstraDateRangePicker(
                context,
                title: 'Sales range',
                firstDate: DateTime(now.year - 3),
                lastDate: DateTime(now.year, now.month, now.day),
                initialDateRange: initial,
              );
            },
            child: const Text('open'),
          ),
        ),
      ),
    )));
    await tester.tap(find.text('open'));
    await tester.pump();
    await tester.pump(const Duration(milliseconds: 400));
    expect(tester.takeException(), isNull);
    return result;
  }

  testWidgets('stays a calendar-sized card on a tablet', (tester) async {
    await open(tester, const Size(1194, 834));
    expect(find.text('SALES RANGE'), findsOneWidget);
    // The card, not `Dialog` — that fills the window to centre it.
    final card = find.descendant(of: find.byType(Dialog), matching: find.byType(SingleChildScrollView)).first;
    expect(tester.getSize(card).width, lessThanOrEqualTo(380));
  });

  testWidgets('a preset applies in one tap', (tester) async {
    final result = await open(tester, const Size(1194, 834));
    await tester.tap(find.text('Last 7 days'));
    await tester.pump();
    expect(find.text('7 days'), findsOneWidget);
    await tester.tap(find.text('Apply'));
    await tester.pumpAndSettle();

    final range = await result;
    final today = DateUtils.dateOnly(DateTime.now());
    expect(range!.end, today);
    expect(range.start, today.subtract(const Duration(days: 6)));
  });

  testWidgets('tapping two days picks the range between them', (tester) async {
    final today = DateUtils.dateOnly(DateTime.now());
    final monthStart = DateTime(today.year, today.month);
    // Seed inside the current month so both taps are on the visible grid.
    final result = await open(tester, const Size(1194, 834),
        initial: DateTimeRange(start: monthStart, end: monthStart));
    await tester.tap(find.text('1').first);
    await tester.pump();
    expect(find.text('Tap an end date'), findsOneWidget);
    await tester.tap(find.text('${today.day}').last);
    await tester.pump();
    await tester.tap(find.text('Apply'));
    await tester.pumpAndSettle();

    final range = await result;
    expect(range!.start, monthStart);
    expect(range.end, today);
  });

  testWidgets('fits a phone in landscape by scrolling', (tester) async {
    await open(tester, const Size(844, 390));
    expect(find.text('Apply'), findsOneWidget);
  });
}
