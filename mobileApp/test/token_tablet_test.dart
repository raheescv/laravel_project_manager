import 'dart:io';

import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:google_fonts/google_fonts.dart';

import 'package:invo/features/token/domain/models/token_models.dart';
import 'package:invo/features/token/screens/v3/token_screen.dart';

import 'support/test_harness.dart';

/// The tablet Token page is "Now Serving": the number as a queue display on a
/// gradient stage, beside (or, in portrait, above) a column of actions. The
/// phone keeps the paper slip and floating dock.
void main() {
  setUpAll(() async {
    GoogleFonts.config.allowRuntimeFetching = false;
    // Height checks need real glyph widths — see day_session_tablet_test.dart.
    for (final weight in ['regular', '500', '600', '700', '800', '900']) {
      final file = weight == 'regular' || weight == '500' ? 'Regular' : 'Bold';
      final bytes = File('assets/fonts/IBMPlexSansArabic-$file.ttf').readAsBytesSync();
      await (FontLoader('Inter_$weight')..addFont(Future.value(ByteData.view(bytes.buffer)))).load();
    }
  });

  const counters = [
    TokenCounter(id: 'c1', name: 'Canteen', prefix: 'A'),
    TokenCounter(id: 'c2', name: 'Snacks', prefix: 'B'),
    TokenCounter(id: 'c3', name: 'Juice bar', prefix: 'C'),
  ];

  /// [screen] is the whole window (what `isTablet` reads); the page itself is
  /// boxed to [page], the space it gets beside the shell's side-rail.
  Future<TestHarness> pumpAt(WidgetTester tester, Size screen, Size page,
      {bool withCounters = false, bool issued = false}) async {
    final d = TestHarness();
    await d.init(admin: true);
    addTearDown(d.dispose);
    if (withCounters) await d.token.updateSettings(const TokenSettings(counters: counters));
    tester.view.physicalSize = screen;
    tester.view.devicePixelRatio = 1.0;
    addTearDown(tester.view.resetPhysicalSize);
    addTearDown(tester.view.resetDevicePixelRatio);
    await tester.pumpWidget(d.wrap(
        Align(alignment: Alignment.topLeft, child: SizedBox.fromSize(size: page, child: const TokenScreen()))));
    await tester.pump();
    if (issued) {
      await d.token.issue();
      await tester.pump();
    }
    await tester.pump(const Duration(milliseconds: 400));
    expect(tester.takeException(), isNull);
    return d;
  }

  const tillLandscape = (Size(1097, 686), Size(985, 593));
  const tillPortrait = (Size(686, 1097), Size(580, 1010));

  testWidgets('start of day previews the first number with New Token at the foot', (tester) async {
    await pumpAt(tester, tillLandscape.$1, tillLandscape.$2);
    expect(find.text('FIRST TOKEN OF THE DAY'), findsOneWidget);
    expect(find.text('A-001'), findsWidgets);
    expect(find.text('New Token'), findsOneWidget);
    expect(tester.getRect(find.text('Reprint')).bottom, lessThanOrEqualTo(tillLandscape.$2.height));
  });

  testWidgets('an issued token takes the stage and Reprint names it', (tester) async {
    await pumpAt(tester, tillLandscape.$1, tillLandscape.$2, issued: true);
    expect(find.text('NOW SERVING'), findsOneWidget);
    expect(find.text('Reprint A-001'), findsOneWidget);
    expect(find.textContaining('A-002'), findsWidgets);
  });

  for (final c in {'landscape': tillLandscape, 'portrait': tillPortrait}.entries) {
    testWidgets('counters fit the till ${c.key} and a tap picks one', (tester) async {
      final d = await pumpAt(tester, c.value.$1, c.value.$2, withCounters: true);
      // The selected counter is also named under the number on the stage.
      expect(find.text('Canteen'), findsNWidgets(2));
      expect(find.text('Snacks'), findsOneWidget);
      expect(find.text('Juice bar'), findsOneWidget);
      expect(tester.getRect(find.text('Reprint')).bottom, lessThanOrEqualTo(c.value.$2.height));

      await tester.tap(find.text('Snacks'));
      await tester.pump(const Duration(milliseconds: 300));
      expect(d.token.state.counter?.id, 'c2');
      expect(find.text('B-001'), findsOneWidget, reason: 'the stage previews the picked counter');
    });
  }

  testWidgets('landscape puts the actions beside the stage; portrait below it', (tester) async {
    await pumpAt(tester, tillLandscape.$1, tillLandscape.$2);
    final stage = tester.getRect(find.text('FIRST TOKEN OF THE DAY'));
    expect(tester.getCenter(find.text('New Token')).dx, greaterThan(stage.right));

    await pumpAt(tester, tillPortrait.$1, tillPortrait.$2);
    expect(tester.getCenter(find.text('New Token')).dy,
        greaterThan(tester.getCenter(find.text('FIRST TOKEN OF THE DAY')).dy + 300));
  });

  testWidgets('phone keeps the slip and dock', (tester) async {
    await pumpAt(tester, const Size(390, 844), const Size(390, 844));
    expect(find.text('FIRST TOKEN OF THE DAY'), findsNothing);
    expect(find.text('New Token'), findsOneWidget);
  });
}
