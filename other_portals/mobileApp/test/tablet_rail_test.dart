import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:google_fonts/google_fonts.dart';

import 'package:invo/features/shell/screens/v3/home_shell.dart';
import 'package:invo/shared/widgets/astra_new_sale_fab.dart';
import 'package:invo/shared/widgets/astra_side_rail.dart';

import 'support/test_harness.dart';

/// The tablet rail ("Ink Notch"): the logomark is the way home, Settings and a
/// flat Log out are pinned at the foot, and New Sale floats over the content
/// as a draggable button that remembers where it was left.
void main() {
  setUpAll(() => GoogleFonts.config.allowRuntimeFetching = false);

  // The canteen till — the shortest tablet the rail has to fit.
  const till = Size(1097, 686);

  Future<TestHarness> pumpShell(WidgetTester tester) async {
    final d = TestHarness();
    await d.init(admin: true);
    addTearDown(d.dispose);
    tester.view.physicalSize = till;
    tester.view.devicePixelRatio = 1.0;
    addTearDown(tester.view.resetPhysicalSize);
    addTearDown(tester.view.resetDevicePixelRatio);
    await tester.pumpWidget(d.wrap(const HomeShell()));
    await tester.pump();
    await tester.pump(const Duration(milliseconds: 400));
    expect(tester.takeException(), isNull);
    return d;
  }

  Finder inRail(Finder f) => find.descendant(of: find.byType(AstraSideRail), matching: f);

  int shellIndex(WidgetTester tester) => tester
      .widget<IndexedStack>(find.descendant(of: find.byType(AstraRailShell), matching: find.byType(IndexedStack)).first)
      .index!;

  testWidgets('rail has no Dashboard link; Settings and Log out are pinned at the foot', (tester) async {
    await pumpShell(tester);

    expect(inRail(find.text('Home')), findsNothing);
    expect(inRail(find.text('Settings')), findsOneWidget);
    expect(inRail(find.text('LOG OUT')), findsOneWidget);
    expect(inRail(find.byIcon(Icons.add)), findsNothing);
    expect(find.byType(AstraNewSaleFab), findsOneWidget);

    // Pinned: neither sits inside the scrolling module list.
    final list = find.descendant(of: find.byType(AstraSideRail), matching: find.byType(SingleChildScrollView));
    expect(find.descendant(of: list, matching: find.text('Settings')), findsNothing);
    expect(find.descendant(of: list, matching: find.text('LOG OUT')), findsNothing);
  });

  testWidgets('tapping the logo returns to the Dashboard', (tester) async {
    await pumpShell(tester);

    await tester.tap(inRail(find.text('Sales')));
    await tester.pump(const Duration(milliseconds: 300));
    expect(shellIndex(tester), 1);

    await tester.tap(find.byKey(const ValueKey('rail-brand')));
    await tester.pump(const Duration(milliseconds: 300));
    expect(shellIndex(tester), 0);
  });

  testWidgets('New Sale button snaps to the nearer edge and remembers it', (tester) async {
    final d = await pumpShell(tester);
    final button = find.descendant(of: find.byType(AstraNewSaleFab), matching: find.byType(GestureDetector));

    final before = tester.getCenter(button);
    expect(before.dx, greaterThan(till.width / 2), reason: 'starts bottom-right');

    await tester.drag(button, const Offset(-700, -200));
    await tester.pump();
    await tester.pump(const Duration(milliseconds: 500));

    final after = tester.getCenter(button);
    expect(after.dx, lessThan(till.width / 2));
    expect(after.dy, lessThan(before.dy));
    expect(d.storage.railFabPosition, startsWith('left:'));
  });

  testWidgets('tapping the New Sale button opens a sale', (tester) async {
    await pumpShell(tester);

    await tester.tap(find.descendant(of: find.byType(AstraNewSaleFab), matching: find.byType(GestureDetector)));
    await tester.pump();
    await tester.pump(const Duration(milliseconds: 400));

    expect(find.text('sale route'), findsOneWidget);
  });
}
