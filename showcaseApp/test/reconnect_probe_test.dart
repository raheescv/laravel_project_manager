import 'package:fake_async/fake_async.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:showcase/shared/logic/connectivity_cubit/connectivity_cubit.dart';
import 'package:showcase/shared/utils/router/http_utils/common_exception.dart';

/// Nobody is standing at a kiosk to tap "Try again".
///
/// A panel that lost the shop's wifi wore the banner until the idle timer
/// happened to reload the size run, or a customer happened to tap something
/// that made a request. So once offline the cubit asks on its own, on a
/// doubling backoff, and the moment something gets through it says so once —
/// and only once — on [ConnectivityCubit.onReconnected].
void main() {
  test('asks again on a doubling backoff that stops at thirty seconds', () {
    fakeAsync((clock) {
      final wire = _Wire(clock);
      final cubit = ConnectivityCubit(probe: wire.probe, jitter: 0);
      cubit.reportOutcome(reachable: false);
      clock.elapse(const Duration(seconds: 200));
      expect(
        wire.asked,
        [2, 6, 14, 30, 60, 90, 120, 150, 180].map((s) => Duration(seconds: s)),
      );
      cubit.close();
    });
  });

  test('the first probe that gets through ends the outage', () {
    fakeAsync((clock) {
      final wire = _Wire(clock);
      final cubit = ConnectivityCubit(probe: wire.probe, jitter: 0);
      var announced = 0;
      cubit.onReconnected.listen((_) => announced++);

      cubit.reportOutcome(reachable: false);
      expect(cubit.state, const ConnectivityState(online: false, reconnecting: true));
      clock.elapse(const Duration(seconds: 7));
      expect(wire.asked, hasLength(2), reason: 'at 2 and at 6, both failed');

      wire.up = true;
      clock.elapse(const Duration(seconds: 10));
      expect(wire.asked, hasLength(3), reason: 'the one at 14 got through');
      expect(cubit.state, const ConnectivityState());
      expect(announced, 1);

      clock.elapse(const Duration(minutes: 5));
      expect(wire.asked, hasLength(3), reason: 'online, nothing left to ask');
      expect(clock.pendingTimers, isEmpty);
      cubit.close();
    });
  });

  test("a probe's own failure does not put the backoff back to the start", () {
    fakeAsync((clock) {
      final wire = _Wire(clock);
      final cubit = ConnectivityCubit(probe: wire.probe, jitter: 0);
      cubit.reportOutcome(reachable: false);
      clock.elapse(const Duration(seconds: 7));
      expect(wire.asked, hasLength(2));

      // What HttpService's interceptor reports when the probe at 6 s fails.
      cubit.reportOutcome(reachable: false);
      clock.elapse(const Duration(seconds: 6));
      expect(wire.asked, hasLength(2), reason: 'the next is due at 14, not 15');
      clock.elapse(const Duration(seconds: 1));
      expect(wire.asked, hasLength(3));
      cubit.close();
    });
  });

  test("a customer's request getting through cancels the pending probe", () {
    fakeAsync((clock) {
      final wire = _Wire(clock);
      final cubit = ConnectivityCubit(probe: wire.probe, jitter: 0);
      var announced = 0;
      cubit.onReconnected.listen((_) => announced++);

      cubit.reportOutcome(reachable: false);
      clock.elapse(const Duration(seconds: 3));
      expect(wire.asked, hasLength(1));

      cubit.reportOutcome(reachable: true);
      clock.elapse(const Duration(minutes: 1));
      expect(wire.asked, hasLength(1), reason: 'the one due at 6 was cancelled');
      expect(cubit.state.online, isTrue);
      expect(announced, 1);
      expect(clock.pendingTimers, isEmpty);
      cubit.close();
    });
  });

  test('a second outage starts the backoff from the top again', () {
    fakeAsync((clock) {
      final wire = _Wire(clock);
      final cubit = ConnectivityCubit(probe: wire.probe, jitter: 0);
      cubit.reportOutcome(reachable: false);
      clock.elapse(const Duration(seconds: 31));
      expect(wire.asked, hasLength(4), reason: '2, 6, 14, 30');
      wire.up = true;
      clock.elapse(const Duration(seconds: 30));
      expect(cubit.state.online, isTrue);

      wire.up = false;
      final again = clock.elapsed;
      cubit.reportOutcome(reachable: false);
      clock.elapse(const Duration(seconds: 3));
      expect(wire.asked.last, again + const Duration(seconds: 2));
      cubit.close();
    });
  });

  test('online to online announces nothing', () {
    fakeAsync((clock) {
      final cubit = ConnectivityCubit(probe: _Wire(clock).probe, jitter: 0);
      var announced = 0;
      cubit.onReconnected.listen((_) => announced++);
      cubit.reportOutcome(reachable: true);
      cubit.reportOutcome(reachable: true);
      clock.flushMicrotasks();
      expect(announced, 0);
      expect(clock.pendingTimers, isEmpty);
      cubit.close();
    });
  });

  test('any answer at all is the server back on the line', () {
    fakeAsync((clock) {
      // A 500 is not "offline": the request reached something. The same rule
      // HttpService applies to every other request.
      final cubit = ConnectivityCubit(
        probe: () async => throw ApiException('Server Error', statusCode: 500),
        jitter: 0,
      );
      cubit.reportOutcome(reachable: false);
      clock.elapse(const Duration(seconds: 2));
      expect(cubit.state.online, isTrue);
      expect(clock.pendingTimers, isEmpty);
      cubit.close();
    });
  });

  test('closing stops the loop', () {
    fakeAsync((clock) {
      final wire = _Wire(clock);
      final cubit = ConnectivityCubit(probe: wire.probe, jitter: 0);
      cubit.reportOutcome(reachable: false);
      clock.elapse(const Duration(seconds: 3));
      cubit.close();
      clock.elapse(const Duration(minutes: 2));
      expect(wire.asked, hasLength(1));
      expect(clock.pendingTimers, isEmpty);
    });
  });

  test('with nothing to probe, it is offline and says so honestly', () {
    fakeAsync((clock) {
      final cubit = ConnectivityCubit();
      cubit.reportOutcome(reachable: false);
      expect(cubit.state, const ConnectivityState(online: false, reconnecting: false));
      clock.elapse(const Duration(minutes: 2));
      expect(clock.pendingTimers, isEmpty);
      cubit.close();
    });
  });
}

/// The wire between the kiosk and the server. Records when it was asked, and
/// answers by whether it is [up].
class _Wire {
  _Wire(this.clock);

  final FakeAsync clock;
  bool up = false;
  final List<Duration> asked = [];

  Future<void> probe() async {
    asked.add(clock.elapsed);
    if (!up) throw OfflineException();
  }
}
