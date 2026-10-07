// A screen's own state (its cubit, controllers) must survive the window crossing
// a layout breakpoint — an iPad rotating or entering split view. MaxWidthBox
// used to return the bare child below its cap and a Padding above it, so the
// tree changed shape at the cap and everything inside was rebuilt from scratch:
// the checklist's detail cubit was closed while a fixture sheet still held it
// ("Cannot emit new states after calling close").
import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';

import 'package:invo/shared/domain/helpers/responsive.dart';

class _Counter extends StatefulWidget {
  const _Counter();

  @override
  State<_Counter> createState() => _CounterState();
}

class _CounterState extends State<_Counter> {
  int taps = 0;
  bool disposed = false;

  @override
  void dispose() {
    disposed = true;
    super.dispose();
  }

  @override
  Widget build(BuildContext context) =>
      GestureDetector(onTap: () => setState(() => taps++), child: Text('taps $taps', textDirection: TextDirection.ltr));
}

void main() {
  testWidgets('MaxWidthBox keeps its child alive when the width crosses the cap', (tester) async {
    tester.view.devicePixelRatio = 1;
    addTearDown(tester.view.reset);

    Future<void> pumpAt(double width) async {
      tester.view.physicalSize = Size(width, 800);
      await tester.pumpWidget(const Directionality(
        textDirection: TextDirection.ltr,
        child: MaxWidthBox(maxWidth: 560, child: _Counter()),
      ));
    }

    await pumpAt(400); // below the cap
    await tester.tap(find.text('taps 0'));
    await tester.pump();
    final state = tester.state<_CounterState>(find.byType(_Counter));

    await pumpAt(1000); // above the cap — the tree must keep its shape
    expect(tester.state<_CounterState>(find.byType(_Counter)), same(state));
    expect(state.disposed, isFalse);
    expect(find.text('taps 1'), findsOneWidget);

    await pumpAt(420); // and back
    expect(tester.state<_CounterState>(find.byType(_Counter)), same(state));
  });
}
