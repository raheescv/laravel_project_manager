import 'package:flutter/material.dart';
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
}
