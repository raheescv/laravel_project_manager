import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:invo/shared/widgets/nav_hide.dart';

/// The shell's real nesting: NavHide above the Scaffold, the page an inner
/// Scaffold inside an IndexedStack, and the bar in the bottomNavigationBar slot
/// — the scroll notification has to travel all of that to reach the bar.
Widget _shell({int rows = 40, VoidCallback? onTab}) => MaterialApp(
      home: NavHide(
        child: Scaffold(
          extendBody: true,
          body: IndexedStack(
            children: [
              Scaffold(
                backgroundColor: Colors.transparent,
                body: Column(children: [
                  const SizedBox(height: 60, child: Text('header')),
                  Expanded(
                    child: RefreshIndicator(
                      onRefresh: () async {},
                      child: ListView.builder(
                        itemCount: rows,
                        itemBuilder: (_, i) => SizedBox(height: 60, child: Text('row $i')),
                      ),
                    ),
                  ),
                ]),
              ),
            ],
          ),
          bottomNavigationBar: NavHideSlide(
            drag: true,
            child: SizedBox(
              height: 80,
              child: GestureDetector(onTap: onTab, child: const Text('nav')),
            ),
          ),
        ),
      ),
    );

/// 0 while the bar is on show, up to its `offscreen` fraction once it is gone.
/// `find.ancestor` walks outwards, so the first hit is the bar's own one — the
/// route transition further up is a FractionalTranslation too.
double _hidden(WidgetTester tester) => tester
    .widgetList<FractionalTranslation>(find.ancestor(of: find.text('nav'), matching: find.byType(FractionalTranslation)))
    .first
    .translation
    .dy;

void main() {
  testWidgets('the bar leaves on the way down and comes back on the way up', (tester) async {
    await tester.pumpWidget(_shell());
    expect(_hidden(tester), 0);

    await tester.drag(find.text('row 2'), const Offset(0, -220));
    await tester.pumpAndSettle();
    expect(_hidden(tester), greaterThan(0), reason: 'scrolling down should send the bar off the bottom');

    await tester.drag(find.byType(ListView), const Offset(0, 120));
    await tester.pumpAndSettle();
    expect(_hidden(tester), 0, reason: 'an upward drag should bring it back');
  });

  testWidgets('dragging the bar itself downwards sends it away', (tester) async {
    await tester.pumpWidget(_shell());
    // Follows the finger part-way…
    final drag = await tester.startGesture(tester.getCenter(find.text('nav')));
    await drag.moveBy(const Offset(0, 40));
    await tester.pump();
    final partway = _hidden(tester);
    expect(partway, greaterThan(0));
    // …and stays gone once let go past the halfway mark.
    await drag.moveBy(const Offset(0, 40));
    await tester.pump();
    expect(_hidden(tester), greaterThan(partway));
    await drag.up();
    await tester.pumpAndSettle();
    expect(_hidden(tester), greaterThan(1), reason: 'released past the threshold it should settle off-screen');
  });

  testWidgets('a short drag on the bar snaps it back', (tester) async {
    await tester.pumpWidget(_shell());
    final drag = await tester.startGesture(tester.getCenter(find.text('nav')));
    await drag.moveBy(const Offset(0, 12));
    await tester.pump();
    await drag.up();
    await tester.pumpAndSettle();
    expect(_hidden(tester), 0);
  });

  testWidgets('a tab still takes a tap rather than the drag', (tester) async {
    var tapped = false;
    await tester.pumpWidget(_shell(onTab: () => tapped = true));
    await tester.tap(find.text('nav'));
    await tester.pumpAndSettle();
    expect(tapped, isTrue);
    expect(_hidden(tester), 0);
  });

  testWidgets('a bar flicked away stays away until the page is pulled back', (tester) async {
    await tester.pumpWidget(_shell());
    final drag = await tester.startGesture(tester.getCenter(find.text('nav')));
    await drag.moveBy(const Offset(0, 80));
    await drag.up();
    await tester.pumpAndSettle();
    expect(_hidden(tester), greaterThan(1));

    // Scrolling further down leaves it away…
    await tester.drag(find.byType(ListView), const Offset(0, -120));
    await tester.pumpAndSettle();
    expect(_hidden(tester), greaterThan(1));

    // …and pulling back up is what returns it.
    await tester.drag(find.byType(ListView), const Offset(0, 120));
    await tester.pumpAndSettle();
    expect(_hidden(tester), 0);
  });

  testWidgets('a page too short to scroll keeps its bar', (tester) async {
    await tester.pumpWidget(_shell(rows: 2));
    await tester.drag(find.byType(ListView), const Offset(0, -220));
    await tester.pumpAndSettle();
    expect(_hidden(tester), 0);
  });

  testWidgets('a nudge smaller than the threshold does not flip it', (tester) async {
    await tester.pumpWidget(_shell());
    await tester.drag(find.byType(ListView), const Offset(0, -10));
    await tester.pumpAndSettle();
    expect(_hidden(tester), 0);
  });
}
