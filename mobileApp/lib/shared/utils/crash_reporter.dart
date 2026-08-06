import 'dart:async';
import 'dart:collection';

import 'package:flutter/foundation.dart';

import '../../flavors.dart';
import '../api/end_points.dart';
import '../domain/constants/global_variables.dart';
import 'router/http_utils/http_service.dart';

/// One captured uncaught error.
class CrashRecord {
  CrashRecord(this.error, this.stack, {this.context})
      : at = DateTime.now();

  final Object error;
  final StackTrace? stack;

  /// What the framework was doing, when known (e.g. "building MyWidget").
  final String? context;
  final DateTime at;

  @override
  String toString() =>
      '[${at.toIso8601String()}] ${context == null ? '' : '$context: '}$error';
}

/// Captures uncaught errors so a crash on a shop-floor device leaves a trace.
///
/// There is no crash-reporting SDK in this app. Reports are POSTed to
/// `POST /api/v1/client-error`, which files them in `api_logs` next to the
/// server-side request log, and are also kept in memory ([recent]) so a
/// diagnostics view can read them back without a round-trip.
///
/// [onReport] stays available as a seam if a different sink is ever wanted;
/// setting it replaces the default upload.
class CrashReporter {
  CrashReporter._();

  static const int _maxRecords = 20;
  static final ListQueue<CrashRecord> _records = ListQueue<CrashRecord>();

  /// The most recent captures, newest last. Bounded — this is a diagnostic
  /// aid, not a log store.
  static List<CrashRecord> get recent => List.unmodifiable(_records);

  /// Optional sink for a remote reporter. See the class doc.
  static void Function(CrashRecord record)? onReport;

  /// Installs the two global handlers. Called once from `main()` before
  /// `runApp`, so it also covers errors thrown during boot.
  static void install() {
    FlutterError.onError = (details) {
      // Keep the framework's own behaviour (red screen in debug, console dump)
      // and record on top of it.
      FlutterError.presentError(details);
      report(details.exception, details.stack,
          context: details.context?.toDescription());
    };

    // Errors that escape the Flutter zone — a failed unawaited Future, a
    // platform channel throwing after its caller returned. Returning true marks
    // them handled: on a till, one stray async error must not take the app down
    // mid-sale.
    PlatformDispatcher.instance.onError = (error, stack) {
      report(error, stack, context: 'uncaught async error');
      return true;
    };
  }

  /// Record an error. Safe to call directly for a caught-but-notable failure.
  static void report(Object error, StackTrace? stack, {String? context}) {
    final record = CrashRecord(error, stack, context: context);
    _records.addLast(record);
    while (_records.length > _maxRecords) {
      _records.removeFirst();
    }
    if (F.isDev) {
      debugPrint('CRASH $record');
      if (stack != null) debugPrint(stack.toString());
    }
    try {
      (onReport ?? _upload)(record);
    } catch (_) {
      // A reporting failure must never mask the error being reported.
    }
  }

  /// Default sink. Fire-and-forget and completely silent: a till mid-sale must
  /// never see a toast because the error log was unreachable, and a failure
  /// here would otherwise re-enter [report] and loop.
  static void _upload(CrashRecord record) {
    if (!serviceLocator.isRegistered<HttpService>()) return;
    unawaited(() async {
      try {
        await serviceLocator<HttpService>().post(EndPoints.clientError, body: {
          'message': record.error.toString(),
          if (record.context != null) 'context': record.context,
          if (record.stack != null) 'stack': record.stack.toString(),
          'platform': defaultTargetPlatform.name,
          'occurred_at': record.at.toIso8601String(),
        });
      } catch (_) {
        // Offline, unauthenticated, throttled — the record is still in [recent].
      }
    }());
  }

  /// Test seam — drops everything captured so far.
  @visibleForTesting
  static void clear() => _records.clear();
}
