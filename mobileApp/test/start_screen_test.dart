import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:invo/features/settings/logic/pos_settings_cubit/pos_settings_cubit.dart';
import 'package:invo/features/settings/screens/v3/settings_screen.dart';
import 'package:invo/shared/utils/router/routes.dart';

import 'support/test_harness.dart';

/// The device-local landing choice (Settings → Start screen) and the guard that
/// keeps it from parking an account on a screen it can't open.
void main() {
  TestWidgetsFlutterBinding.ensureInitialized();
  setUpAll(() => GoogleFonts.config.allowRuntimeFetching = false);

  group('StartScreen', () {
    test('defaults to the dashboard, which is where the app always landed', () {
      expect(StartScreen.fromKey(null), StartScreen.home);
      // A key from a build that offered an option this one doesn't.
      expect(StartScreen.fromKey('reports'), StartScreen.home);
      expect(StartScreen.fromKey('sale'), StartScreen.sale);
    });

    test('shell destinations fall back to New Sale without the dashboard', () {
      expect(StartScreen.home.resolve(canViewDashboard: true), Routes.home);
      expect(StartScreen.home.resolve(canViewDashboard: false), Routes.sale);
      // The Sales option is the shell's tab, so it needs the same permission.
      expect(StartScreen.sales.resolve(canViewDashboard: false), Routes.sale);
      // New Sale is nobody's privilege — it lands whatever the account holds.
      expect(StartScreen.sale.resolve(canViewDashboard: false), Routes.sale);
    });

    test('offers only the destinations the account can reach', () {
      expect(StartScreen.optionsFor(canViewDashboard: true), StartScreen.values);
      expect(StartScreen.optionsFor(canViewDashboard: false), [StartScreen.sale]);
    });
  });

  group('PosSettingsCubit.startScreen', () {
    late TestHarness harness;

    setUp(() async {
      harness = TestHarness();
      await harness.init();
    });

    tearDown(() async => harness.dispose());

    test('survives a restart — the choice is the device\'s, not the session\'s', () async {
      expect(harness.posSettings.startScreen, StartScreen.home);

      await harness.posSettings.setStartScreen(StartScreen.sale);
      expect(harness.posSettings.startScreen, StartScreen.sale);
      expect(harness.storage.posStartScreen, StartScreen.sale.key);

      // A cubit built fresh off the same store, as on the next cold start.
      expect(PosSettingsCubit().startScreen, StartScreen.sale);
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
