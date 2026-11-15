import 'dart:async';

import 'package:flutter_test/flutter_test.dart';
import 'package:showcase/shared/widgets/photo.dart';

/// Warming a product's photos has to stay out of the way of the page loading.
///
/// Opening a product used to start every image at once — a gallery, two dozen
/// spin frames and a zoom copy, around thirty requests in the same instant.
/// They do not arrive sooner for being asked for together; they starve each
/// other and the page's own API calls, which is why the first visit looked
/// stuck and the second, off a warm cache, was fine.
void main() {
  /// Records how many tasks are in flight at once.
  ({List<Future<void> Function()> tasks, List<Completer<void>> gates, int Function() peak})
      probe(int count) {
    final gates = [for (var i = 0; i < count; i++) Completer<void>()];
    var inFlight = 0;
    var peak = 0;
    final tasks = [
      for (var i = 0; i < count; i++)
        () async {
          inFlight++;
          if (inFlight > peak) peak = inFlight;
          await gates[i].future;
          inFlight--;
        },
    ];
    return (tasks: tasks, gates: gates, peak: () => peak);
  }

  test('never runs more than the limit at once', () async {
    final p = probe(30);
    final done = runBounded(p.tasks, concurrency: 3);
    await pumpEventQueue();
    expect(p.peak(), 3, reason: 'thirty photos must not go out together');

    for (final gate in p.gates) {
      gate.complete();
      await pumpEventQueue();
    }
    await done;
    expect(p.peak(), 3);
  });

  test('still finishes every one of them', () async {
    var ran = 0;
    await runBounded([
      for (var i = 0; i < 12; i++) () async => ran++,
    ], concurrency: 3);
    expect(ran, 12);
  });

  test('one bad photo does not strand the rest', () async {
    // A URL that 404s must not stop the queue: the gallery renders its own
    // placeholder for that one and the others still arrive.
    var ran = 0;
    await runBounded([
      () async => ran++,
      () async => throw Exception('404'),
      () async => ran++,
      () async => throw Exception('timeout'),
      () async => ran++,
    ], concurrency: 2);
    expect(ran, 3);
  });

  test('leaving the page stops it queuing more', () async {
    // Two dozen spin frames warmed by a product page must not go on competing
    // with the screen the customer went back to.
    var ran = 0;
    var onPage = true;
    final done = runBounded(
      [for (var i = 0; i < 24; i++) () async => ran++],
      concurrency: 2,
      shouldContinue: () => onPage,
    );
    onPage = false;
    await done;

    expect(ran, lessThan(24), reason: 'the queue should have stopped early');
  });

  test('with nobody leaving, everything is still fetched', () async {
    var ran = 0;
    await runBounded(
      [for (var i = 0; i < 24; i++) () async => ran++],
      concurrency: 2,
      shouldContinue: () => true,
    );
    expect(ran, 24);
  });

  test('an empty list is not a hang', () async {
    await runBounded(const [], concurrency: 3).timeout(const Duration(seconds: 1));
  });

  test('a nonsense limit still makes progress', () async {
    var ran = 0;
    await runBounded([for (var i = 0; i < 4; i++) () async => ran++], concurrency: 0);
    expect(ran, 4);
  });
}
