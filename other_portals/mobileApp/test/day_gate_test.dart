import 'dart:async';

import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import 'package:flutter_test/flutter_test.dart';

import 'package:invo/features/admin/screens/v3/day_session_screen.dart';
import 'package:invo/features/sale/screens/v3/day_closed_screen.dart';
import 'package:invo/features/sale/screens/v3/new_sale_screen.dart';
import 'package:invo/shared/domain/constants/mobile_permissions.dart';
import 'package:invo/shared/domain/models/index.dart';
import 'package:invo/shared/utils/components/theme/index.dart';
import 'package:invo/shared/utils/router/app_router.dart';
import 'package:invo/shared/utils/router/day_gate.dart';
import 'package:invo/shared/utils/router/routes.dart';

import 'support/test_harness.dart';

/// No selling into a closed day: the sale flow sends whoever can open the day
/// to Day Session, everyone else to the "ask your admin" screen, and both move
/// on to New Sale once the server says the day is open.
void main() {
  TestWidgetsFlutterBinding.ensureInitialized();

  const closedDay = DayStatus(
    status: 'closed',
    date: '2026-09-17',
    openedAt: '',
    lastClosedAt: '2026-09-16 22:10:00',
  );
  const openDay = DayStatus(
    status: 'open',
    date: '2026-09-17',
    openedAt: '2026-09-17 09:00:00',
    lastClosedAt: '2026-09-16 22:10:00',
  );

  ApiUser cashier({required String day, List<String> permissions = const []}) => ApiUser(
        id: '21',
        name: 'Sam Rivera',
        code: 'EMP-021',
        email: 'sam@astra.co',
        mobile: '',
        isAdmin: false,
        designation: 'Cashier',
        role: 'staff',
        branchId: '3',
        daySessionStatus: day,
        daySessionDate: '2026-09-17',
        permissions: permissions,
      );

  Future<TestHarness> harness(ApiUser user, {DayStatus server = closedDay}) async {
    final d = TestHarness();
    await d.init(admin: false);
    d.authCubit.seedSession(user);
    d.admin.dayStatusAnswer = server;
    return d;
  }

  Widget app(TestHarness d) => MultiBlocProvider(
        providers: d.providers(),
        child: MaterialApp.router(
          theme: buildAstraTheme(AstraPresets.emeraldGold),
          routerConfig: createRouter(d.authCubit),
        ),
      );

  Future<void> settle(WidgetTester tester) async {
    for (var i = 0; i < 6; i++) {
      await tester.pump(const Duration(milliseconds: 200));
    }
  }

  void phone(WidgetTester tester) {
    tester.view.physicalSize = const Size(390, 844);
    tester.view.devicePixelRatio = 1.0;
    addTearDown(tester.view.resetPhysicalSize);
    addTearDown(tester.view.resetDevicePixelRatio);
  }

  group('DayGate.redirectFor', () {
    test('lets the sale flow through while the day is open', () async {
      final d = await harness(cashier(day: 'open'));
      addTearDown(d.dispose);
      expect(DayGate.redirectFor(d.authCubit), isNull);
    });

    test('sends someone who may open the day to Day Session', () async {
      final d = await harness(cashier(day: 'closed', permissions: [PermissionSlug.daySession]));
      addTearDown(d.dispose);
      expect(DayGate.redirectFor(d.authCubit), Routes.daySessionForSale);
    });

    test('sends everyone else to ask an admin', () async {
      final d = await harness(cashier(day: 'closed'));
      addTearDown(d.dispose);
      expect(DayGate.redirectFor(d.authCubit), Routes.dayClosed);
    });

    test('guards New Sale, the cart and Review & Pay only', () {
      expect(DayGate.guards(Routes.sale), isTrue);
      expect(DayGate.guards(Routes.cart), isTrue);
      expect(DayGate.guards(Routes.review), isTrue);
      expect(DayGate.guards(Routes.sales), isFalse);
      expect(DayGate.guards(Routes.daySession), isFalse);
    });
  });

  group('router', () {
    testWidgets('a cashier with no day to sell into is told to ask an admin', (tester) async {
      phone(tester);
      final d = await harness(cashier(day: 'closed'));
      addTearDown(d.dispose);

      await tester.pumpWidget(app(d));
      await settle(tester);

      expect(find.byType(DayClosedScreen), findsOneWidget);
      expect(find.byType(NewSaleScreen), findsNothing);
      expect(d.admin.dayStatusCalls, greaterThan(0));
    });

    testWidgets('the ask-an-admin screen moves on once the day is opened elsewhere', (tester) async {
      phone(tester);
      final d = await harness(cashier(day: 'closed'));
      addTearDown(d.dispose);

      await tester.pumpWidget(app(d));
      await settle(tester);
      expect(find.byType(DayClosedScreen), findsOneWidget);

      d.admin.dayStatusAnswer = openDay;
      await tester.tap(find.text('Check again'));
      await settle(tester);

      expect(find.byType(DayClosedScreen), findsNothing);
      expect(find.byType(NewSaleScreen), findsOneWidget);
    });

    testWidgets('a stale closed day at sign-in does not hold the cashier', (tester) async {
      phone(tester);
      final d = await harness(cashier(day: 'closed'), server: openDay);
      addTearDown(d.dispose);

      await tester.pumpWidget(app(d));
      await settle(tester);

      expect(find.byType(NewSaleScreen), findsOneWidget);
    });

    testWidgets('someone who may open the day is taken straight to Day Session', (tester) async {
      phone(tester);
      final d = await harness(cashier(day: 'closed', permissions: [PermissionSlug.daySession]));
      addTearDown(d.dispose);

      await tester.pumpWidget(app(d));
      await settle(tester);

      final screen = tester.widget<DaySessionScreen>(find.byType(DaySessionScreen));
      expect(screen.forSale, isTrue);
      expect(find.byType(NewSaleScreen), findsNothing);

      // Opening it carries straight on to the sale they came for. (The server
      // answers open from then on — New Sale asks it again on the way in.)
      d.admin.dayStatusAnswer = openDay;
      await tester.tap(find.textContaining('Open day'));
      await settle(tester);
      expect(find.byType(DaySessionScreen), findsNothing);
      expect(find.byType(NewSaleScreen), findsOneWidget);
    });

    testWidgets('pushed over the dashboard, the gate keeps Back to it', (tester) async {
      // Wider than [phone]: the dashboard's rows overflow at 390pt in the test
      // font, which is not what this test is about.
      tester.view.physicalSize = const Size(560, 1000);
      tester.view.devicePixelRatio = 1.0;
      addTearDown(tester.view.resetPhysicalSize);
      addTearDown(tester.view.resetDevicePixelRatio);
      final d = await harness(cashier(day: 'open', permissions: [PermissionSlug.salesOverview]));
      addTearDown(d.dispose);
      final router = createRouter(d.authCubit);
      await tester.pumpWidget(MultiBlocProvider(
        providers: d.providers(),
        child: MaterialApp.router(
          theme: buildAstraTheme(AstraPresets.emeraldGold),
          routerConfig: router,
        ),
      ));
      await settle(tester);

      // Closed underneath the till since sign-in; New Sale finds out on the way in.
      d.admin.dayStatusAnswer = closedDay;
      unawaited(router.push(Routes.sale));
      await settle(tester);
      expect(find.byType(DayClosedScreen), findsOneWidget);
      expect(router.canPop(), isTrue);

      d.admin.dayStatusAnswer = openDay;
      await tester.tap(find.text('Check again'));
      await settle(tester);
      expect(find.byType(NewSaleScreen), findsOneWidget);

      // The gate stood in for New Sale in the stack rather than resetting it,
      // so Back still leads to the dashboard the sale was started from.
      final stack = router.routerDelegate.currentConfiguration.matches;
      expect(stack.map((m) => m.matchedLocation).toList(), [Routes.home, Routes.sale]);
    });

    testWidgets('New Sale leaves when the server says the day is closed', (tester) async {
      phone(tester);
      final d = await harness(cashier(day: 'open'));
      addTearDown(d.dispose);

      await tester.pumpWidget(app(d));
      await settle(tester);

      expect(find.byType(DayClosedScreen), findsOneWidget);
      expect(find.byType(NewSaleScreen), findsNothing);
    });
  });
}
