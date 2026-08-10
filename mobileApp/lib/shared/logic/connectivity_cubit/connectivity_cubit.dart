import 'dart:async';

import 'package:connectivity_plus/connectivity_plus.dart';
import 'package:equatable/equatable.dart';
import 'package:flutter/foundation.dart' show visibleForTesting;
import 'package:flutter_bloc/flutter_bloc.dart';

part 'connectivity_state.dart';

/// Whether the app can actually reach its server, for the app-wide offline
/// banner.
///
/// Two sources, weighted differently on purpose:
///
///  * **What requests actually did** is the truth. A response — any response,
///    including a 500 — proves the server was reached. A connection failure
///    proves it was not. [HttpService] reports both through [reportOutcome].
///  * **What the OS reports** is only a hint. "No interface" (airplane mode,
///    wifi off) is conclusive and flips the banner on immediately; but a till
///    sitting on shop wifi whose uplink is dead reports *connected*, so a
///    present interface never on its own means online.
///
/// That asymmetry is the whole design: the banner appears the instant the device
/// knows it is disconnected, and disappears only once something has genuinely
/// reached the server.
class ConnectivityCubit extends Cubit<ConnectivityState> {
  ConnectivityCubit() : super(const ConnectivityState()) {
    _sub = Connectivity().onConnectivityChanged.listen(_applyInterfaces);
    unawaited(_seed());
  }

  StreamSubscription<List<ConnectivityResult>>? _sub;

  Future<void> _seed() async {
    try {
      _applyInterfaces(await Connectivity().checkConnectivity());
    } on Exception catch (_) {
      // The platform channel is unavailable (tests, unsupported desktop host).
      // Staying `unknown` is right: nothing is claimed until a request speaks.
    }
  }

  void _applyInterfaces(List<ConnectivityResult> results) {
    final hasInterface = results.any((r) => r != ConnectivityResult.none);
    if (isClosed) return;
    if (!hasInterface) {
      // Conclusive: there is nowhere for a request to go.
      _set(NetworkStatus.offline, hasInterface: false);
      return;
    }
    // An interface came back. That is not proof of reach, so the status is left
    // alone — the next request outcome settles it. Recording the interface still
    // matters: it is what lets the banner say "no network" versus
    // "can't reach the server".
    emit(state.copyWith(hasInterface: true));
  }

  /// Called by [HttpService] for every request that produced a definite signal.
  void reportOutcome({required bool reachable}) =>
      _set(reachable ? NetworkStatus.online : NetworkStatus.offline);

  /// Test seam: stand in for the OS interface report, which has no platform channel
  /// in a unit test. Distinguishing "no network" from "can't reach the server" is
  /// user-facing copy, so it needs to be assertable.
  @visibleForTesting
  void reportInterfacesForTest({required bool hasInterface}) => _applyInterfaces(
        hasInterface ? [ConnectivityResult.wifi] : [ConnectivityResult.none],
      );

  void _set(NetworkStatus status, {bool? hasInterface}) {
    if (isClosed) return;
    if (state.status == status) {
      if (hasInterface != null && hasInterface != state.hasInterface) {
        emit(state.copyWith(hasInterface: hasInterface));
      }
      return;
    }
    emit(state.copyWith(status: status, since: DateTime.now(), hasInterface: hasInterface));
  }

  @override
  Future<void> close() {
    _sub?.cancel();
    return super.close();
  }
}
