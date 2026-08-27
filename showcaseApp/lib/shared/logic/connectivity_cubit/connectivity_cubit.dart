import 'package:flutter_bloc/flutter_bloc.dart';

import '../../utils/components/theme/type_presets.dart';

/// Whether the last request reached the server. Fed by `HttpService`, so the
/// banner reflects what requests actually did rather than what a connectivity
/// plugin thinks the radio is doing.
class ConnectivityCubit extends Cubit<bool> {
  ConnectivityCubit() : super(true);

  void reportOutcome({required bool reachable}) {
    if (reachable == state) return;
    // Coming back is also the moment to re-ask for any typeface that could not
    // be fetched while the panel was cut off. The faces are resolved once and
    // remembered, so nothing else would ever ask again — and a kiosk that was
    // switched on before its network came up would wear the platform's fallback
    // until somebody restarted it.
    if (reachable) TypeFace.forgetResolved();
    emit(reachable);
  }
}
