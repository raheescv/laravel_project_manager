import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:google_fonts/google_fonts.dart';

import 'package:invo/features/admin/logic/admin_cubit/admin_cubit.dart';
import 'package:invo/features/sales/screens/v3/sales_list_screen.dart';
import 'package:invo/shared/domain/helpers/formatters.dart';
import 'package:invo/shared/domain/models/index.dart';
import 'package:invo/shared/widgets/astra_range_picker.dart';

import 'support/test_harness.dart';

/// While a day session is open, "today" is the session's date — a day opened
/// yesterday and still running stays yesterday past midnight. Every quick
/// date preset (Today, Yesterday, 7 / 30 days, Month) counts back from it.
/// The harness signs in with the day open on 2026-06-14.
void main() {
  setUpAll(() => GoogleFonts.config.allowRuntimeFetching = false);

  final sessionDay = DateTime(2026, 6, 14);
  final calendarDay = DateUtils.dateOnly(DateTime.now());

  group('Dates.presetRange', () {
    test('counts every preset back from the day it is given', () {
      expect(Dates.presetRange('today', sessionDay), (start: sessionDay, end: sessionDay));
      expect(Dates.presetRange('yesterday', sessionDay), (start: DateTime(2026, 6, 13), end: DateTime(2026, 6, 13)));
      expect(Dates.presetRange('7d', sessionDay), (start: DateTime(2026, 6, 8), end: sessionDay));
      expect(Dates.presetRange('30d', sessionDay), (start: DateTime(2026, 5, 16), end: sessionDay));
      expect(Dates.presetRange('month', sessionDay), (start: DateTime(2026, 6), end: sessionDay));
    });

    test('yesterday crosses a month boundary', () {
      expect(Dates.presetRange('yesterday', DateTime(2026, 7, 1)),
          (start: DateTime(2026, 6, 30), end: DateTime(2026, 6, 30)));
    });

    test('custom and all are not presets', () {
      expect(Dates.presetRange('custom', sessionDay), isNull);
      expect(Dates.presetRange('all', sessionDay), isNull);
    });
  });

  group('business day', () {
    test('is the open session date, and the calendar date once it closes', () async {
      final d = TestHarness();
      await d.init();
      addTearDown(d.dispose);

      expect(d.authCubit.businessDay, sessionDay);
      expect(d.authCubit.sessionDay, sessionDay, reason: 'the session trails the calendar');
      expect(d.authCubit.defaultDatePreset, 'session');
      await d.authCubit.syncDaySession(status: 'closed');
      expect(d.authCubit.businessDay, calendarDay);
      expect(d.authCubit.sessionDay, isNull);
      expect(d.authCubit.defaultDatePreset, 'today');
    });
  });

  group('Reports range', () {
    test('opens on the session day; Today and the rest count from the calendar', () async {
      final d = TestHarness();
      await d.init(admin: true);
      addTearDown(d.dispose);
      final admin = AdminCubit();
      addTearDown(admin.close);

      expect((admin.rangePreset, admin.startDate, admin.endDate), ('session', sessionDay, sessionDay),
          reason: 'reports open on the session day');
      admin.setPreset('today');
      expect((admin.startDate, admin.endDate), (calendarDay, calendarDay));
      admin.setPreset('yesterday');
      final yesterday = calendarDay.subtract(const Duration(days: 1));
      expect((admin.startDate, admin.endDate), (yesterday, yesterday));
      admin.setPreset('7d');
      expect((admin.startDate, admin.endDate), (calendarDay.subtract(const Duration(days: 6)), calendarDay));
      admin.setPreset('session');
      expect((admin.startDate, admin.endDate), (sessionDay, sessionDay));
      await pumpEventQueue(); // let the loads each preset fires finish before close
    });

    test('a preset moves with the day when it closes; a custom range stays', () async {
      final d = TestHarness();
      await d.init(admin: true);
      addTearDown(d.dispose);
      final admin = AdminCubit();
      addTearDown(admin.close);

      expect(admin.reanchorRange(), isFalse, reason: 'already on the session day');

      await d.authCubit.syncDaySession(status: 'closed');
      expect(admin.reanchorRange(), isTrue);
      expect((admin.rangePreset, admin.startDate, admin.endDate), ('today', calendarDay, calendarDay),
          reason: 'with the day shut the default is today');

      admin.setPreset('yesterday');
      await d.authCubit.syncDaySession(status: 'open', date: '2026-06-14');
      expect(admin.reanchorRange(), isFalse, reason: 'a picked preset other than the default day stays');
      expect(admin.rangePreset, 'yesterday');

      admin.setCustomRange(DateTime(2026, 1, 1), DateTime(2026, 1, 5));
      expect(admin.reanchorRange(), isFalse);
      expect(admin.startDate, DateTime(2026, 1, 1));
      await pumpEventQueue();
    });

    test('the Sale Bill Report looks up its session in the date filter on screen', () async {
      final d = TestHarness();
      await d.init(admin: true);
      addTearDown(d.dispose);
      final admin = AdminCubit();
      addTearDown(admin.close);

      await admin.filteredDaySession();
      expect(d.admin.currentSessionRange, (start: sessionDay, end: sessionDay),
          reason: 'the report opens on the open session day');

      admin.setPreset('7d');
      await admin.filteredDaySession();
      expect(d.admin.currentSessionRange, (start: calendarDay.subtract(const Duration(days: 6)), end: calendarDay));

      await d.authCubit.syncDaySession(status: 'closed');
      admin.setPreset('today');
      await admin.filteredDaySession();
      expect(d.admin.currentSessionRange, (start: calendarDay, end: calendarDay),
          reason: 'with no session open, Today is the calendar date');
      await pumpEventQueue();
    });

    test('a session opened outside the date filter is never printed', () async {
      final d = TestHarness();
      await d.init(admin: true);
      addTearDown(d.dispose);
      final admin = AdminCubit();
      addTearDown(admin.close);
      // What a server that ignores the filter answers: its current session.
      d.admin.currentSessionAnswer = const DaySessionSummary(
          id: '42', branch: 'Main', status: 'open', openedAt: '2026-06-14 08:00:00',
          closedAt: '', openedBy: 'Maya', closedBy: '');

      expect((await admin.filteredDaySession())?.id, '42');
      admin.setPreset('yesterday');
      expect(await admin.filteredDaySession(), isNull);
      await pumpEventQueue();
    });
  });

  testWidgets('the Sales list opens on the session day', (tester) async {
    final d = TestHarness();
    await d.init();
    addTearDown(d.dispose);
    tester.view.physicalSize = const Size(430, 1400);
    tester.view.devicePixelRatio = 1.0;
    addTearDown(tester.view.resetPhysicalSize);
    addTearDown(tester.view.resetDevicePixelRatio);

    await tester.pumpWidget(d.wrap(const SalesListScreen()));
    await tester.pump(const Duration(milliseconds: 400));
    expect(d.sale.salesRange, (from: '2026-06-14', to: '2026-06-14'));
  });

  testWidgets('the range picker presets count from the business day it is given', (tester) async {
    final d = TestHarness();
    await d.init();
    addTearDown(d.dispose);
    tester.view.physicalSize = const Size(1194, 834);
    tester.view.devicePixelRatio = 1.0;
    addTearDown(tester.view.resetPhysicalSize);
    addTearDown(tester.view.resetDevicePixelRatio);

    late Future<DateTimeRange?> result;
    await tester.pumpWidget(d.wrap(Builder(
      builder: (context) => Scaffold(
        body: Center(
          child: TextButton(
            onPressed: () => result = showAstraDateRangePicker(
              context,
              title: 'Sales range',
              firstDate: DateTime(calendarDay.year - 3),
              lastDate: calendarDay,
              today: sessionDay,
            ),
            child: const Text('open'),
          ),
        ),
      ),
    )));
    await tester.tap(find.text('open'));
    await tester.pumpAndSettle();

    await tester.tap(find.text('Yesterday'));
    await tester.pump();
    await tester.tap(find.text('Apply'));
    await tester.pumpAndSettle();

    final range = await result;
    expect((range!.start, range.end), (DateTime(2026, 6, 13), DateTime(2026, 6, 13)));
  });
}
