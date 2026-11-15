import 'dart:async';
import 'dart:typed_data';

import 'package:dio/dio.dart';
import 'package:fake_async/fake_async.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:showcase/shared/domain/constants/app_config.dart';
import 'package:showcase/shared/utils/router/http_utils/common_exception.dart';
import 'package:showcase/shared/utils/router/http_utils/http_service.dart';

const config = AppConfig(baseUrl: 'https://store.example', tenant: 'store');

ResponseBody success() => ResponseBody.fromString(
      '{"success":true,"data":[{"size":"42"}]}', 200,
      headers: {Headers.contentTypeHeader: ['application/json']},
    );

void main() {
  test('a temporary connection failure recovers without another customer tap', () {
    fakeAsync((clock) {
      final adapter = _Adapter((options, call) {
        if (call == 1) throw DioException.connectionError(requestOptions: options, reason: 'Connection reset by peer');
        return Future.value(success());
      });
      final http = HttpService(config: config, adapter: adapter)..activeBranchId = 6;
      dynamic result;
      Object? error;
      http.get('/sizes').then((value) => result = value, onError: (Object e) { error = e; });
      clock.flushMicrotasks();
      http.activeBranchId = 2;
      clock.elapse(const Duration(seconds: 1));
      expect(error, isNull);
      expect(result, [{'size': '42'}]);
      expect(adapter.requests, hasLength(2));
      for (final request in adapter.requests) {
        expect(request.queryParameters, {'tenant': 'store', 'branch_id': 6});
        expect(request.headers['X-Tenant-Subdomain'], 'store');
      }
    });
  });

  test('persistent failures stop after one retry and manual retry can recover', () {
    fakeAsync((clock) {
      final adapter = _Adapter((options, call) {
        if (call <= 2) throw DioException.connectionError(requestOptions: options, reason: 'Network unavailable');
        return Future.value(success());
      });
      final http = HttpService(config: config, adapter: adapter);
      Object? error;
      http.get('/sizes').catchError((Object e) { error = e; return null; });
      clock.elapse(const Duration(seconds: 2));
      expect(error, isA<OfflineException>());
      expect(adapter.requests, hasLength(2));
      dynamic result;
      http.get('/sizes').then((value) => result = value);
      clock.elapse(Duration.zero);
      expect(result, [{'size': '42'}]);
    });
  });

  test('deadline cancels the outstanding request and permits a fresh request', () {
    fakeAsync((clock) {
      final adapter = _Adapter((options, call) => call == 1
          ? Completer<ResponseBody>().future : Future.value(success()));
      final http = HttpService(config: config, adapter: adapter);
      Object? error;
      http.get('/sizes').catchError((Object e) { error = e; return null; });
      clock.elapse(const Duration(seconds: 25));
      expect(error, isA<OfflineException>());
      expect(adapter.cancelled, 1);
      dynamic result;
      http.get('/sizes').then((value) => result = value);
      clock.elapse(Duration.zero);
      expect(result, [{'size': '42'}]);
    });
  });

  test('retry shares the original deadline', () {
    fakeAsync((clock) {
      final adapter = _Adapter((options, call) async {
        if (call == 1) {
          await Future<void>.delayed(const Duration(seconds: 24));
          throw DioException.connectionError(requestOptions: options, reason: 'Connection reset');
        }
        return Completer<ResponseBody>().future;
      });
      final http = HttpService(config: config, adapter: adapter);
      Object? error;
      http.get('/sizes').catchError((Object e) { error = e; return null; });
      clock.elapse(const Duration(seconds: 25));
      expect(error, isA<OfflineException>());
      expect(adapter.requests, hasLength(2));
      expect(adapter.requests.last.cancelToken?.isCancelled, isTrue);
    });
  });

  for (final type in [DioExceptionType.badCertificate, DioExceptionType.unknown]) {
    test('$type is not retried as a temporary connection failure', () async {
      final adapter = _Adapter((options, call) => throw DioException(requestOptions: options, type: type));
      final http = HttpService(config: config, adapter: adapter);
      await expectLater(http.get('/sizes'), throwsA(isA<ApiException>()));
      expect(adapter.requests, hasLength(1));
    });
  }

  test('server errors are surfaced without retrying', () async {
    final adapter = _Adapter((options, call) async => ResponseBody.fromString(
          '{"success":false,"message":"Invalid branch"}', 422,
          headers: {Headers.contentTypeHeader: ['application/json']},
        ));
    final http = HttpService(config: config, adapter: adapter);
    await expectLater(http.get('/sizes'), throwsA(isA<ApiException>()
        .having((e) => e.statusCode, 'status', 422)));
    expect(adapter.requests, hasLength(1));
  });
}

class _Adapter implements HttpClientAdapter {
  _Adapter(this.respond);
  final Future<ResponseBody> Function(RequestOptions, int) respond;
  final requests = <RequestOptions>[];
  int cancelled = 0;

  @override
  Future<ResponseBody> fetch(RequestOptions options, Stream<Uint8List>? requestStream, Future<void>? cancelFuture) {
    requests.add(options);
    cancelFuture?.then((_) { cancelled++; });
    return respond(options, requests.length);
  }

  @override
  void close({bool force = false}) {}
}
