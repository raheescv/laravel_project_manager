import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:invo/shared/domain/models/index.dart';
import 'package:invo/features/settings/logic/pos_settings_cubit/pos_settings_cubit.dart';
import 'package:invo/features/settings/screens/v3/settings_screen.dart';
import 'package:invo/shared/utils/router/routes.dart';

import 'support/test_harness.dart';

/// The per-user, device-local landing choice (Settings → Start screen) and the
/// guard that keeps it from parking an account on a screen it can't open.
void main() {
  TestWidgetsFlutterBinding.ensureInitialized();
  setUpAll(() => GoogleFonts.config.allowRuntimeFetching = false);

  group('StartScreen', () {
    test('defaults to the dashboard, which is where the app always landed', () {
      expect(StartScreen.fromKey(null), StartScreen.home);
      // A key from a build that offered an option this one doesn't.
      expect(StartScreen.fromKey('stock'), StartScreen.home);
      expect(StartScreen.fromKey('sale'), StartScreen.sale);
      expect(StartScreen.fromKey('reports'), StartScreen.reports);
    });

    test('Reports lands on the shell tab, or the dashboard without the permission', () {
      expect(StartScreen.reports.resolve(canViewReports: true), Routes.homeTab(2));
      expect(StartScreen.reports.resolve(canViewReports: false), Routes.home);
      // Everything else is open to every signed-in user.
      expect(StartScreen.sale.resolve(canViewReports: false), Routes.sale);
      expect(StartScreen.sales.resolve(canViewReports: false), Routes.homeTab(1));
    });

    test('offers Reports only to an account that can view it', () {
      expect(StartScreen.optionsFor(canViewReports: true), StartScreen.values);
      expect(StartScreen.optionsFor(canViewReports: false),
          [StartScreen.home, StartScreen.sale, StartScreen.sales]);
    });
  });

  group('PosSettingsCubit.startScreen', () {
    late TestHarness harness;

    setUp(() async {
      harness = TestHarness();
      await harness.init();
    });

    tearDown(() async => harness.dispose());

    test('survives a restart — the choice lives on the device, not the session', () async {
      expect(harness.posSettings.startScreen, StartScreen.home);

      await harness.posSettings.setStartScreen(StartScreen.sale);
      expect(harness.posSettings.startScreen, StartScreen.sale);
      expect(harness.storage.startScreenFor('14'), StartScreen.sale.key);
      // Saved for this user — the device-wide fallback is left alone.
      expect(harness.storage.posStartScreen, isNull);

      // A cubit built fresh off the same store, as on the next cold start.
      expect(PosSettingsCubit().startScreen, StartScreen.sale);
    });

    test('is saved per user — another account on the same till keeps its own', () async {
      await harness.posSettings.setStartScreen(StartScreen.reports);
      expect(harness.posSettings.startScreenFor('14'), StartScreen.reports);
      // Nobody else picked one, so they keep the device-wide default.
      expect(harness.posSettings.startScreenFor('22'), StartScreen.home);

      // A second cashier unlocks the till: Settings follows them.
      harness.authCubit.seedSession(const ApiUser(
          id: '22', name: 'Sam Lee', code: 'EMP-022', email: '', mobile: '', isAdmin: false,
          designation: '', role: 'staff', branchId: '3', daySessionStatus: 'open', daySessionDate: ''));
      await Future<void>.delayed(Duration.zero);
      expect(harness.posSettings.startScreen, StartScreen.home);

      await harness.posSettings.setStartScreen(StartScreen.sale);
      expect(harness.posSettings.startScreenFor('22'), StartScreen.sale);
      expect(harness.posSettings.startScreenFor('14'), StartScreen.reports);
    });

    test('a device-wide choice from an older build still applies until the user picks', () async {
      await harness.storage.setPosStartScreen(StartScreen.sales.key);
      expect(harness.posSettings.startScreenFor('14'), StartScreen.sales);
    });
  });

  group('Settings → Start screen', () {
    late TestHarness harness;

    setUp(() async {
      harness = TestHarness();
      await harness.init();
    });

    tearDown(() async => harness.dispose());

    Future<void> pumpSettings(WidgetTester tester, Size size) async {
      tester.view.physicalSize = size;
      tester.view.devicePixelRatio = 1.0;
      addTearDown(tester.view.resetPhysicalSize);
      addTearDown(tester.view.resetDevicePixelRatio);
      await tester.pumpWidget(harness.wrap(const SettingsScreen()));
      await tester.pump(const Duration(milliseconds: 400));
    }

    testWidgets('phone card opens the picker and the choice sticks', (tester) async {
      await pumpSettings(tester, const Size(390, 844));

      await tester.scrollUntilVisible(find.text('Start screen'), 120,
          scrollable: find.byType(Scrollable).first);
      await tester.tap(find.text('Start screen'));
      await tester.pumpAndSettle();

      // The sheet lists every option; the card behind it names only the current
      // one, so tap the row inside the sheet.
      await tester.tap(find.text(StartScreen.sale.blurb));
      await tester.pumpAndSettle();

      expect(harness.posSettings.startScreen, StartScreen.sale);
      expect(find.text('Opens on New Sale after sign-in'), findsOneWidget);
    });

    testWidgets('tablet nav opens the start-screen panel, not a neighbour', (tester) async {
      await pumpSettings(tester, const Size(1194, 834));

      await tester.scrollUntilVisible(find.text('Start screen'), 120,
          scrollable: find.byType(Scrollable).first);
      await tester.tap(find.text('Start screen'));
      await tester.pump();

      // Every option is a row in the detail pane — this is what breaks if the
      // panel's case indices and the nav order ever drift apart.
      for (final option in StartScreen.values) {
        expect(find.text(option.blurb), findsOneWidget, reason: option.label);
      }

      await tester.tap(find.text(StartScreen.sale.blurb));
      await tester.pump();
      expect(harness.posSettings.startScreen, StartScreen.sale);
    });
  });
}
