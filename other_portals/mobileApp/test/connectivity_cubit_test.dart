import 'package:flutter_test/flutter_test.dart';
import 'package:invo/shared/logic/connectivity_cubit/connectivity_cubit.dart';
import 'package:invo/shared/utils/router/http_utils/common_exception.dart';
import 'package:invo/shared/utils/router/http_utils/reachability.dart';
import 'package:dio/dio.dart';

/// The offline banner is only as honest as this cubit. The asymmetry it encodes
/// is the point: a failed request proves offline, but a present network
/// interface never proves online — a till on shop wifi with a dead uplink
/// reports "connected" all day.
void main() {
  TestWidgetsFlutterBinding.ensureInitialized();

  late ConnectivityCubit cubit;

  setUp(() => cubit = ConnectivityCubit());
  tearDown(() => cubit.close());

  test('says nothing until something is actually known', () {
    // No banner on a cold start: claiming "offline" before the first request
    // would cry wolf every launch.
    expect(cubit.state.status, NetworkStatus.unknown);
    expect(cubit.state.isOffline, isFalse);
  });

  test('a request that reached the server puts it online', () {
    cubit.reportOutcome(reachable: true);

    expect(cubit.state.status, NetworkStatus.online);
    expect(cubit.state.isOffline, isFalse);
  });

  test('a request that never landed puts it offline', () {
    cubit.reportOutcome(reachable: false);

    expect(cubit.state.isOffline, isTrue);
    expect(cubit.state.since, isNotNull);
  });

  test('recovery needs a request to succeed, not just a status flip', () {
    cubit.reportOutcome(reachable: false);
    expect(cubit.state.isOffline, isTrue);

    cubit.reportOutcome(reachable: true);
    expect(cubit.state.isOffline, isFalse);
  });

  test('`since` is stamped on a change and left alone on a repeat', () async {
    cubit.reportOutcome(reachable: false);
    final first = cubit.state.since;

    await Future<void>.delayed(const Duration(milliseconds: 5));
    cubit.reportOutcome(reachable: false);

    // Repeated failures are one outage, not a new one each time — otherwise a
    // "offline for N minutes" readout would reset on every retry.
    expect(cubit.state.since, first);
  });

  group('what counts as unreachable', () {
    DioException dio(DioExceptionType type, {Object? error}) =>
        DioException(requestOptions: RequestOptions(path: '/x'), type: type, error: error);

    test('transport failures are unreachable', () {
      expect(isServerUnreachable(dio(DioExceptionType.connectionError)), isTrue);
      expect(isServerUnreachable(dio(DioExceptionType.connectionTimeout)), isTrue);
      expect(isServerUnreachable(dio(DioExceptionType.sendTimeout)), isTrue);
      expect(isServerUnreachable(dio(DioExceptionType.receiveTimeout)), isTrue);
      expect(isServerUnreachable(dio(DioExceptionType.unknown)), isTrue);
    });

    test('an answer from the server is never unreachable', () {
      // An ApiException only exists once the envelope has been read, and a
      // cancelled request says nothing about the network.
      expect(isServerUnreachable(ApiException('Denied', statusCode: 403)), isFalse);
      expect(isServerUnreachable(ApiException('Boom', statusCode: 500)), isFalse);
      expect(isServerUnreachable(dio(DioExceptionType.cancel)), isFalse);
      expect(isServerUnreachable(dio(DioExceptionType.badResponse)), isFalse);
    });

    test('bytes that arrived but would not parse mean the server was reached', () {
      expect(
        isServerUnreachable(dio(DioExceptionType.unknown, error: const FormatException())),
        isFalse,
      );
    });

    test('anything that is not a Dio failure is not a network verdict', () {
      expect(isServerUnreachable(StateError('unrelated')), isFalse);
    });
  });
}
