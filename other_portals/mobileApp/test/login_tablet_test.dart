import 'dart:io';

import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:google_fonts/google_fonts.dart';

import 'package:invo/features/auth/screens/v3/login_screen.dart';

import 'support/test_harness.dart';

/// The tablet login is one frosted card on the gradient — identity | keypad in
/// landscape, stacked in portrait. The phone keeps its single column.
void main() {
  setUpAll(() async {
    GoogleFonts.config.allowRuntimeFetching = false;
    // A height check needs real glyph widths — the test font's square glyphs
    // wrap every line. The bundled Plex stands in for Inter (see
    // day_session_tablet_test.dart).
    for (final weight in ['regular', '500', '600', '700', '800', '900']) {
      final file = weight == 'regular' || weight == '500' ? 'Regular' : 'Bold';
      final bytes = File('assets/fonts/IBMPlexSansArabic-$file.ttf').readAsBytesSync();
      await (FontLoader('Inter_$weight')..addFont(Future.value(ByteData.view(bytes.buffer)))).load();
    }
  });

  Future<void> pumpAt(WidgetTester tester, Size size) async {
    final d = TestHarness();
    await d.init(admin: true);
    addTearDown(d.dispose);
    tester.view.physicalSize = size;
    tester.view.devicePixelRatio = 1.0;
    addTearDown(tester.view.resetPhysicalSize);
    addTearDown(tester.view.resetDevicePixelRatio);
    // Android's status bar and button nav bar — on the till they leave ~614pt.
    tester.view.padding = const FakeViewPadding(top: 24, bottom: 48);
    tester.view.viewPadding = const FakeViewPadding(top: 24, bottom: 48);
    addTearDown(tester.view.resetPadding);
    addTearDown(tester.view.resetViewPadding);
    await tester.pumpWidget(d.wrap(const LoginScreen()));
    await tester.pump();
    await tester.pump(const Duration(milliseconds: 400));
    expect(tester.takeException(), isNull);
  }

  Finder card() => find.byType(BackdropFilter);

  /// Everything a cashier needs sits inside the window, without scrolling.
  void expectFits(WidgetTester tester, Size size) {
    final rect = tester.getRect(card());
    expect(rect.top, greaterThanOrEqualTo(0));
    expect(rect.bottom, lessThanOrEqualTo(size.height));
    final position = tester.state<ScrollableState>(find.byType(Scrollable).first).position;
    expect(position.maxScrollExtent, 0, reason: 'the till must not have to scroll');
  }

  const tills = {'till landscape': Size(1097, 686), 'till portrait': Size(686, 1097), 'iPad': Size(1194, 834)};

  for (final entry in tills.entries) {
    testWidgets('MPIN card fits the ${entry.key} without scrolling', (tester) async {
      await pumpAt(tester, entry.value);
      expect(card(), findsOneWidget);
      expect(find.text('Welcome back'), findsOneWidget);
      for (final k in ['1', '5', '9', '0']) {
        expect(find.text(k), findsOneWidget);
      }
      expectFits(tester, entry.value);
      // The keypad sits inside the card, not below it.
      expect(tester.getRect(card()).contains(tester.getCenter(find.text('0'))), isTrue);
    });

    testWidgets('Password card fits the ${entry.key} without scrolling', (tester) async {
      await pumpAt(tester, entry.value);
      await tester.tap(find.text('Password'));
      await tester.pump(const Duration(milliseconds: 300));
      expect(tester.takeException(), isNull);
      expect(find.text('Sign in'), findsOneWidget);
      expectFits(tester, entry.value);
    });
  }

  testWidgets('landscape shows the clock beside the keypad; portrait stacks without it', (tester) async {
    await pumpAt(tester, const Size(1097, 686));
    expect(tester.getCenter(find.text('Welcome back')).dx, lessThan(tester.getCenter(find.text('5')).dx));
    expect(find.textContaining(RegExp(r'^\d\d:\d\d$')), findsOneWidget);

    await pumpAt(tester, const Size(686, 1097));
    expect(tester.getCenter(find.text('Welcome back')).dy, lessThan(tester.getCenter(find.text('5')).dy));
    expect(find.textContaining(RegExp(r'^\d\d:\d\d$')), findsNothing);
  });

  // A landscape keyboard takes half the till's height; the Server sheet used to
  // overflow the rest ("RenderFlex overflowed by 34 pixels on the bottom").
  testWidgets('Server sheet survives the keyboard on the till landscape', (tester) async {
    await pumpAt(tester, const Size(1097, 686));
    await tester.tap(find.byTooltip('Connection settings'));
    await tester.pumpAndSettle();
    expect(find.text('Server'), findsOneWidget);

    tester.view.viewInsets = const FakeViewPadding(bottom: 330);
    addTearDown(tester.view.resetViewInsets);
    await tester.pump();
    expect(tester.takeException(), isNull);

    await tester.scrollUntilVisible(find.text('Save'), 50, scrollable: find.byType(Scrollable).last);
    expect(find.text('Save').hitTestable(), findsOneWidget);
  });

  testWidgets('phone keeps the single column, no card', (tester) async {
    await pumpAt(tester, const Size(390, 844));
    expect(card(), findsNothing);
    expect(find.text('Welcome back'), findsOneWidget);
  });
}
