import 'dart:async';

import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:go_router/go_router.dart';

import 'package:invo/features/admin/logic/admin_cubit/admin_cubit.dart';
import 'package:invo/features/admin/logic/day_session_cubit/day_session_cubit.dart';
import 'package:invo/features/admin/screens/v3/dashboard_screen.dart';
import 'package:invo/features/shell/screens/v3/home_shell.dart';
import 'package:invo/shared/domain/models/index.dart';
import 'package:invo/shared/widgets/astra_bottom_nav.dart';

import 'support/test_harness.dart';

/// The dashboard is the screen people come back to — after a sale, after
/// opening the day, after a colleague's shift — and every return has to show
/// what the server holds now, not what this device last saw. The cubits behind
/// it are provided once for the life of the app, so none of that is free.
void main() {
  TestWidgetsFlutterBinding.ensureInitialized();

  const closedDay = DayStatus(
    status: 'closed',
    date: '2026-06-15',
    openedAt: '',
    lastClosedAt: '2026-06-14 22:10:00',
  );

  Future<void> settle(WidgetTester tester) async {
    await tester.pump();
    await tester.pump(const Duration(milliseconds: 600));
  }

  void phone(WidgetTester tester) {
    tester.view.physicalSize = const Size(390, 844);
    tester.view.devicePixelRatio = 1.0;
    addTearDown(tester.view.resetPhysicalSize);
    addTearDown(tester.view.resetDevicePixelRatio);
  }

  group('AdminCubit', () {
    test("drops the last cashier's figures on sign-out", () async {
      final d = TestHarness();
      await d.init();
      addTearDown(d.dispose);
      final cubit = AdminCubit();
      addTearDown(cubit.close);

      await cubit.loadDashboard();
      expect(cubit.dashboard, isNotNull);

      await d.authCubit.logout();
      await pumpEventQueue();

      expect(cubit.dashboard, isNull);
      expect(cubit.topStylists, isEmpty);
    });

    test('a reload re-reads the day for the branch and syncs the cached user', () async {
      final d = TestHarness();
      await d.init();
      addTearDown(d.dispose);
      final cubit = AdminCubit();
      addTearDown(cubit.close);
      expect(d.authCubit.user?.dayOpen, isTrue);

      d.admin.dayStatusAnswer = closedDay;
      await cubit.loadDashboard();

      expect(d.authCubit.user?.dayOpen, isFalse);
      expect(d.authCubit.user?.lastClosedSessionAt, '2026-06-14 22:10:00');
    });
  });

  group('DaySessionCubit', () {
    test('refresh() takes the day from the server, not the cached user', () async {
      final d = TestHarness();
      await d.init();
      addTearDown(d.dispose);
      final cubit = DaySessionCubit();
      addTearDown(cubit.close);
      expect(cubit.isOpen, isTrue); // seeded from the cached user

      d.admin.dayStatusAnswer = closedDay;
      await cubit.refresh();

      expect(cubit.isOpen, isFalse);
      expect(cubit.syncing, isFalse);
      expect(d.authCubit.user?.dayOpen, isFalse);
      expect(d.admin.dayStatusCalls, 1);
    });

    test("mirrors a day moved by the dashboard's own re-read", () async {
      final d = TestHarness();
      await d.init();
      addTearDown(d.dispose);
      final day = DaySessionCubit();
      addTearDown(day.close);
      final admin = AdminCubit();
      addTearDown(admin.close);
      expect(day.isOpen, isTrue);

      d.admin.dayStatusAnswer = closedDay;
      await admin.loadDashboard();
      await pumpEventQueue();

      expect(day.isOpen, isFalse);
    });

    test('forgets the day on sign-out', () async {
      final d = TestHarness();
      await d.init();
      addTearDown(d.dispose);
      final cubit = DaySessionCubit();
      addTearDown(cubit.close);
      expect(cubit.isOpen, isTrue);

      await d.authCubit.logout();
      await pumpEventQueue();

      expect(cubit.isOpen, isFalse);
      expect(cubit.session, isNull);
    });
  });

  group('DashboardScreen', () {
    testWidgets('reloads when its tab comes back on show', (tester) async {
      final d = TestHarness();
      await d.init();
      addTearDown(d.dispose);
      phone(tester);
      final active = ValueNotifier<bool>(true);
      addTearDown(active.dispose);

      await tester.pumpWidget(d.wrap(ValueListenableBuilder<bool>(
        valueListenable: active,
        builder: (_, v, __) => DashboardScreen(active: v),
      )));
      await settle(tester);
      expect(d.admin.dashboardCalls, 1);

      active.value = false;
      await settle(tester);
      expect(d.admin.dashboardCalls, 1);

      active.value = true;
      await settle(tester);
      expect(d.admin.dashboardCalls, 2);
      expect(tester.takeException(), isNull);
    });

    testWidgets('reloads when a page pushed over the shell pops', (tester) async {
      final d = TestHarness();
      await d.init();
      addTearDown(d.dispose);
      phone(tester);

      await tester.pumpWidget(d.wrap(const HomeShell()));
      await settle(tester);
      expect(d.admin.dashboardCalls, 1);

      final router = GoRouter.of(tester.element(find.byType(HomeShell)));
      unawaited(router.push('/new-sale'));
      await settle(tester);
      expect(d.admin.dashboardCalls, 1);

      router.pop();
      await settle(tester);
      expect(d.admin.dashboardCalls, 2);
      expect(tester.takeException(), isNull);
    });

    testWidgets('reloads when go() brings the shell back', (tester) async {
      // New Sale returns with context.go(Routes.home), which removes the page
      // above the shell rather than popping it; Flutter still runs that as a
      // pop, so the observer hears it.
      final d = TestHarness();
      await d.init();
      addTearDown(d.dispose);
      phone(tester);

      await tester.pumpWidget(d.wrap(const HomeShell()));
      await settle(tester);
      final router = GoRouter.of(tester.element(find.byType(HomeShell)));
      unawaited(router.push('/new-sale'));
      await settle(tester);
      expect(d.admin.dashboardCalls, 1);

      router.go('/');
      await settle(tester);
      expect(d.admin.dashboardCalls, 2);
      expect(tester.takeException(), isNull);
    });

    testWidgets('reloads when the Home tab is re-selected in the shell', (tester) async {
      final d = TestHarness();
      await d.init();
      addTearDown(d.dispose);
      phone(tester);

      await tester.pumpWidget(d.wrap(const HomeShell()));
      await settle(tester);
      expect(d.admin.dashboardCalls, 1);

      Finder navItem(String label) =>
          find.descendant(of: find.byType(AstraNavBar), matching: find.text(label));

      await tester.tap(navItem('Sales'));
      await settle(tester);
      expect(d.admin.dashboardCalls, 1);

      await tester.tap(navItem('Home'));
      await settle(tester);
      expect(d.admin.dashboardCalls, 2);
      expect(tester.takeException(), isNull);
    });
  });
}
