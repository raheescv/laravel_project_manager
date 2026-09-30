import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:google_fonts/google_fonts.dart';

import 'package:invo/features/admin/logic/day_session_cubit/day_session_cubit.dart';
import 'package:invo/features/admin/screens/v3/day_session_screen.dart';

import 'support/test_harness.dart';

/// The tablet Day Session is the "Status Board": a full-width lifecycle
/// stepper and a moment card with one-tap Day / Time choices, the action
/// inline at the foot. The phone keeps its picker pair and floating dock.
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
    await tester.pumpWidget(d.wrap(const DaySessionScreen()));
    await tester.pump();
    await tester.pump(const Duration(milliseconds: 400));
    expect(tester.takeException(), isNull);
    return d;
  }

  DaySessionCubit cubitOf(WidgetTester tester) =>
      BlocProvider.of<DaySessionCubit>(tester.element(find.byType(DaySessionScreen)));

  DateTime nowToMinute() {
    final n = DateTime.now();
    return DateTime(n.year, n.month, n.day, n.hour, n.minute);
  }

  testWidgets('tablet shows the full-width stepper and the quick moment choices', (tester) async {
    await pumpAt(tester, const Size(1194, 834));

    expect(find.text('SESSION LIFECYCLE'), findsOneWidget);
    for (final label in ['Now', '−15 min', '−30 min', '−1 hr', 'Custom', 'Today', 'Other date']) {
      expect(find.text(label), findsOneWidget, reason: label);
    }
    // The moment starts at now, so the card says so; the phone's "Set to now"
    // button is gone — the Now choice does that job.
    expect(find.text('Set to now'), findsOneWidget);
    await tester.tap(find.text('−15 min'));
    await tester.pump();
    expect(find.text('Set to now'), findsNothing);
  });

  testWidgets('a quick time choice moves the selected moment back', (tester) async {
    await pumpAt(tester, const Size(1194, 834));
    final before = nowToMinute();

    await tester.tap(find.text('−30 min'));
    await tester.pump();

    final selected = cubitOf(tester).selected;
    final expected = before.subtract(const Duration(minutes: 30));
    // A minute may tick over between the two reads.
    expect(selected.difference(expected).inMinutes.abs(), lessThanOrEqualTo(1));

    await tester.tap(find.text('Now'));
    await tester.pump();
    expect(cubitOf(tester).selected.difference(nowToMinute()).inMinutes.abs(), lessThanOrEqualTo(1));
  });

  testWidgets('the action sits at the foot of the window, not under the cards', (tester) async {
    await pumpAt(tester, const Size(1194, 834));
    final cta = find.textContaining(RegExp(r'^(Close|Open) day · '));
    expect(cta, findsOneWidget);
    expect(tester.getRect(cta).bottom, greaterThan(834 - 110));
  });

  testWidgets('the date picker stays calendar-sized on a tablet', (tester) async {
    await pumpAt(tester, const Size(1194, 834));
    await tester.tap(find.text('Other date'));
    await tester.pump();
    await tester.pump(const Duration(milliseconds: 400));
    expect(tester.takeException(), isNull);
    expect(tester.getSize(find.byType(CalendarDatePicker)).width, lessThanOrEqualTo(360));
  });

  testWidgets('portrait tablet stacks without overflow', (tester) async {
    await pumpAt(tester, const Size(744, 1133));
    expect(find.text('−1 hr'), findsOneWidget);
  });

  testWidgets('phone keeps its own layout', (tester) async {
    await pumpAt(tester, const Size(390, 844));
    expect(find.text('Set to now'), findsOneWidget);
    expect(find.text('−15 min'), findsNothing);
  });

  test('setMoment sets date and time together, to the minute', () {
    final d = TestHarness();
    return d.init().then((_) {
      final c = DaySessionCubit();
      c.setMoment(DateTime(2026, 9, 29, 23, 50, 41));
      expect(c.selected, DateTime(2026, 9, 29, 23, 50));
      return d.dispose();
    });
  });
}
