import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:showcase/shared/widgets/chrome/idle_reset.dart';

/// A tablet left alone has to go back to the start, and a tablet being used
/// must never have the screen pulled out from under it.
void main() {
  testWidgets('fires once nobody has touched it', (tester) async {
    var fired = 0;
    await tester.pumpWidget(MaterialApp(
      home: IdleReset(
        after: const Duration(seconds: 10),
        onIdle: () => fired++,
        child: const Text('catalogue'),
      ),
    ));

    await tester.pump(const Duration(seconds: 9));
    expect(fired, 0, reason: 'still within the window');

    await tester.pump(const Duration(seconds: 2));
    expect(fired, 1);
  });

  testWidgets('a touch puts the clock back', (tester) async {
    var fired = 0;
    await tester.pumpWidget(MaterialApp(
      home: IdleReset(
        after: const Duration(seconds: 10),
        onIdle: () => fired++,
        child: const SizedBox.expand(child: Text('catalogue')),
      ),
    ));

    await tester.pump(const Duration(seconds: 9));
    await tester.tap(find.text('catalogue'));
    await tester.pump(const Duration(seconds: 9));

    // Eighteen seconds in, but never nine consecutive without a touch.
    expect(fired, 0);

    await tester.pump(const Duration(seconds: 2));
    expect(fired, 1);
  });

  testWidgets('it does not swallow the taps it is listening for',
      (tester) async {
    // The reason this uses Listener rather than GestureDetector: an arena
    // entry here would eat the taps meant for the buttons underneath.
    var taps = 0;
    await tester.pumpWidget(MaterialApp(
      home: IdleReset(
        onIdle: () {},
        child: Center(
          child: ElevatedButton(
            onPressed: () => taps++,
            child: const Text('choose'),
          ),
        ),
      ),
    ));

    await tester.tap(find.text('choose'));
    await tester.pump();
    expect(taps, 1);
  });

  testWidgets('scrolling counts as being used', (tester) async {
    // Someone reading a long size run is using the tablet even though they
    // have not tapped anything.
    var fired = 0;
    await tester.pumpWidget(MaterialApp(
      home: IdleReset(
        after: const Duration(seconds: 10),
        onIdle: () => fired++,
        child: ListView(
          children: [for (var i = 0; i < 60; i++) SizedBox(height: 40, child: Text('$i'))],
        ),
      ),
    ));

    await tester.pump(const Duration(seconds: 9));
    await tester.drag(find.byType(ListView), const Offset(0, -200));
    await tester.pump(const Duration(seconds: 9));
    expect(fired, 0);
  });

  testWidgets('a keystroke counts as being used', (tester) async {
    // The soft keyboard is the platform's: its taps go to the input method and
    // never reach the Listener, so without a way to report them a customer
    // typing a product code looks exactly like a customer who has walked off.
    var fired = 0;
    final field = TextEditingController();
    await tester.pumpWidget(MaterialApp(
      home: IdleReset(
        after: const Duration(seconds: 10),
        onIdle: () => fired++,
        child: Scaffold(
          body: Builder(
            builder: (context) => TextField(
              controller: field,
              onChanged: (_) => IdleReset.keepAlive(context),
            ),
          ),
        ),
      ),
    ));
    // showKeyboard, not tap: focusing this way is what proves the point —
    // nothing below sends a pointer event.
    await tester.showKeyboard(find.byType(TextField));

    await tester.pump(const Duration(seconds: 9));
    await tester.enterText(find.byType(TextField), 'nike air');
    await tester.pump(const Duration(seconds: 9));
    expect(fired, 0, reason: 'reset out from under someone mid-search');

    await tester.pump(const Duration(seconds: 2));
    expect(fired, 1);
  });

  testWidgets('a key event counts as being used', (tester) async {
    var fired = 0;
    await tester.pumpWidget(MaterialApp(
      home: IdleReset(
        after: const Duration(seconds: 10),
        onIdle: () => fired++,
        child: const ColoredBox(color: Color(0xFFFFFFFF), child: SizedBox.expand()),
      ),
    ));

    await tester.pump(const Duration(seconds: 9));
    await tester.sendKeyEvent(LogicalKeyboardKey.keyA);
    await tester.pump(const Duration(seconds: 9));
    expect(fired, 0);
  });

  testWidgets('keeping it alive off a panel with no timer does nothing',
      (tester) async {
    // Every screen is also mounted on its own in these tests, without the app
    // above it. Reporting a keystroke there must not throw.
    await tester.pumpWidget(MaterialApp(
      home: Builder(
        builder: (context) => TextButton(
          onPressed: () => IdleReset.keepAlive(context),
          child: const Text('type'),
        ),
      ),
    ));
    await tester.tap(find.text('type'));
    await tester.pump();
  });

  testWidgets('it goes on waiting after it has fired', (tester) async {
    // A reset can fail to take — the panel is offline and the size run will
    // not load. One that fires once and stops leaves the tablet stranded on
    // the last customer's screen with nothing left to try.
    var fired = 0;
    await tester.pumpWidget(MaterialApp(
      home: IdleReset(
        after: const Duration(seconds: 10),
        onIdle: () => fired++,
        child: const ColoredBox(color: Color(0xFFFFFFFF), child: SizedBox.expand()),
      ),
    ));

    await tester.pump(const Duration(seconds: 11));
    expect(fired, 1);
    await tester.pump(const Duration(seconds: 11));
    expect(fired, 2);
  });

  testWidgets('the timer fires the callback it has now, not the one it was armed with',
      (tester) async {
    // `onIdle` is a closure rebuilt above this widget on every theme, locale
    // or settings change. A timer holding the one from ten minutes ago would
    // be calling into a build that has since been replaced.
    var stale = 0;
    var current = 0;
    Widget panel(VoidCallback onIdle) => MaterialApp(
          home: IdleReset(
            after: const Duration(seconds: 10),
            onIdle: onIdle,
            child: const ColoredBox(color: Color(0xFFFFFFFF), child: SizedBox.expand()),
          ),
        );

    await tester.pumpWidget(panel(() => stale++));
    await tester.pump(const Duration(seconds: 5));
    await tester.pumpWidget(panel(() => current++));
    await tester.pump(const Duration(seconds: 6));

    expect(stale, 0);
    expect(current, 1);
  });
}
