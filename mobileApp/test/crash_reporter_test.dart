import 'package:flutter/foundation.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:invo/shared/utils/crash_reporter.dart';

void main() {
  setUp(CrashReporter.clear);
  tearDown(() {
    CrashReporter.clear();
    CrashReporter.onReport = null;
  });

  test('records an error with its context', () {
    CrashReporter.report(StateError('boom'), StackTrace.current, context: 'while charging');
    expect(CrashReporter.recent, hasLength(1));
    expect(CrashReporter.recent.single.context, 'while charging');
    expect(CrashReporter.recent.single.toString(), contains('while charging'));
  });

  test('keeps only the most recent 20', () {
    for (var i = 0; i < 25; i++) {
      CrashReporter.report(StateError('e$i'), null);
    }
    expect(CrashReporter.recent, hasLength(20));
    // Oldest dropped, newest kept.
    expect(CrashReporter.recent.first.error.toString(), contains('e5'));
    expect(CrashReporter.recent.last.error.toString(), contains('e24'));
  });

  test('uploads by default without a sink attached', () {
    // No HttpService registered in this test, so _upload no-ops rather than
    // throwing — a crash must never be made worse by the crash reporter.
    expect(() => CrashReporter.report(StateError('boom'), null), returnsNormally);
    expect(CrashReporter.recent, hasLength(1));
  });

  test('an attached sink replaces the default upload', () {
    final seen = <CrashRecord>[];
    CrashReporter.onReport = seen.add;
    CrashReporter.report(StateError('boom'), null);
    expect(seen, hasLength(1));
  });

  test('a throwing sink never masks the error being reported', () {
    CrashReporter.onReport = (_) => throw StateError('sink is down');
    expect(() => CrashReporter.report(StateError('boom'), null), returnsNormally);
    expect(CrashReporter.recent, hasLength(1));
  });

  test('install() wires the framework error handler', () {
    final previous = FlutterError.onError;
    addTearDown(() => FlutterError.onError = previous);
    CrashReporter.install();
    FlutterError.onError!(FlutterErrorDetails(
      exception: StateError('rendered badly'),
      library: 'test',
      context: ErrorDescription('building Thing'),
    ));
    expect(CrashReporter.recent, hasLength(1));
    expect(CrashReporter.recent.single.context, contains('building Thing'));
  });
}
