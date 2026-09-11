import 'dart:async';
import 'dart:math';

import 'package:equatable/equatable.dart';
import 'package:flutter_bloc/flutter_bloc.dart';

import '../../utils/components/theme/type_presets.dart';
import '../../utils/router/http_utils/common_exception.dart';

part 'connectivity_state.dart';

/// Something cheap to ask the server, to find out whether it is back. Must fail
/// the way `HttpService` fails — an [OfflineException] for nothing on the wire,
/// any other [ApiException] for an answer of some kind.
typedef Probe = Future<void> Function();

/// Whether the last request reached the server. Fed by `HttpService`, so the
/// banner reflects what requests actually did rather than what a connectivity
/// plugin thinks the radio is doing.
///
/// And, once offline, it asks on its own. Nobody is standing at a kiosk to tap
/// "Try again": a panel that lost the shop's wifi at nine would otherwise wear
/// the banner and its failed screens until the idle timer happened to reload
/// the size run, or a customer happened to tap something that made a request.
/// So the cubit sends a [Probe] on a backoff — two seconds, then four, doubling
/// to a ceiling of thirty — for as long as it takes, and the false→true edge
/// is announced on [onReconnected] so whatever failed while the panel was cut
/// off can ask again.
class ConnectivityCubit extends Cubit<ConnectivityState> {
  ConnectivityCubit({this.probe, this.jitter = 0.2}) : super(const ConnectivityState());

  /// Where the first retry starts, and where the doubling stops. Thirty seconds
  /// is the most a kiosk stays offline after its network is back, and a fleet
  /// of them asking every thirty seconds is nothing to a catalogue server.
  static const Duration firstRetry = Duration(seconds: 2);
  static const Duration maxRetry = Duration(seconds: 30);

  /// Null means nothing is asked on its own — a harness with no server behind
  /// it — and the banner says only "offline", never "reconnecting".
  final Probe? probe;

  /// Spread on each delay, as a fraction of it, so the kiosks of one shop —
  /// which lost the same router at the same moment — do not all ask at once.
  /// Zero in tests, where the schedule has to be exact.
  final double jitter;
  final Random _random = Random();

  Timer? _timer;
  bool _probing = false;
  int _attempt = 0;

  /// The false→true edge, once per outage. Cubits with a failed load listen
  /// here and ask again — and only for what failed, so a customer's loaded
  /// results are not pulled out from under them.
  final StreamController<void> _reconnected = StreamController<void>.broadcast();
  Stream<void> get onReconnected => _reconnected.stream;

  void reportOutcome({required bool reachable}) {
    if (isClosed || reachable == state.online) return;
    if (reachable) {
      _stopProbing();
      // Coming back is also the moment to re-ask for any typeface that could
      // not be fetched while the panel was cut off. The faces are resolved once
      // and remembered, so nothing else would ever ask again — and a kiosk that
      // was switched on before its network came up would wear the platform's
      // fallback until somebody restarted it.
      TypeFace.forgetResolved();
      emit(state.copyWith(online: true, reconnecting: false));
      _reconnected.add(null);
      return;
    }
    // Already offline is a no-op above, and that is what keeps the schedule
    // honest: a probe's own failure reports here too, and must not put the
    // backoff back to two seconds every time it fires.
    emit(state.copyWith(online: false, reconnecting: probe != null));
    _scheduleProbe();
  }

  void _scheduleProbe() {
    if (probe == null || isClosed || state.online || _timer != null || _probing) return;
    _timer = Timer(_delayFor(_attempt), _runProbe);
  }

  Duration _delayFor(int attempt) {
    final doubled = firstRetry * pow(2, min(attempt, 6)).toInt();
    final capped = doubled > maxRetry ? maxRetry : doubled;
    if (jitter == 0) return capped;
    final spread = 1 + (_random.nextDouble() * 2 - 1) * jitter;
    return Duration(milliseconds: (capped.inMilliseconds * spread).round());
  }

  Future<void> _runProbe() async {
    _timer = null;
    if (isClosed || state.online) return;
    _probing = true;
    try {
      await probe!();
      reportOutcome(reachable: true);
    } on OfflineException {
      // Still nothing on the wire. Back off and ask again.
      _attempt++;
    } on ApiException {
      // Any answer at all — a 404, a 500 — is the server back on the line,
      // which is the same rule `HttpService` applies to every other request.
      reportOutcome(reachable: true);
    } catch (_) {
      // Belt and braces: a probe that throws something untyped is treated as
      // no answer. Letting it escape a timer callback would end the loop and
      // leave the panel offline for good with nothing left to try.
      _attempt++;
    } finally {
      _probing = false;
    }
    if (!state.online) _scheduleProbe();
  }

  void _stopProbing() {
    _timer?.cancel();
    _timer = null;
    _attempt = 0;
  }

  @override
  Future<void> close() {
    _stopProbing();
    _reconnected.close();
    return super.close();
  }
}
