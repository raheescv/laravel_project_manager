import 'dart:io';

import 'package:dio/dio.dart';
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

  group('an offline device is not a crash', () {
    test('a dropped connection is neither recorded nor uploaded', () {
      final seen = <CrashRecord>[];
      CrashReporter.onReport = seen.add;

      CrashReporter.report(
        DioException.connectionError(
            requestOptions: RequestOptions(path: '/photo.jpg'), reason: 'Failed host lookup'),
        StackTrace.current,
        context: 'resolving an image codec',
      );

      expect(CrashReporter.recent, isEmpty);
      expect(seen, isEmpty, reason: 'the upload cannot land offline anyway');
    });

    test('a bare SocketException is dropped too', () {
      // Thrown by a plugin or the framework on its own HttpClient, so it never
      // becomes a DioException. `dart:io` is not importable in the reporter.
      CrashReporter.report(
        const SocketException('Failed host lookup: \'talent.astraqatar.com\''), null);

      expect(CrashReporter.recent, isEmpty);
    });

    test('a real fault still gets through', () {
      CrashReporter.report(StateError('null check on a null value'), null);

      expect(CrashReporter.recent, hasLength(1));
    });
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
