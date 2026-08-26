import 'package:flutter_bloc/flutter_bloc.dart';

/// Whether the last request reached the server. Fed by `HttpService`, so the
/// banner reflects what requests actually did rather than what a connectivity
/// plugin thinks the radio is doing.
class ConnectivityCubit extends Cubit<bool> {
  ConnectivityCubit() : super(true);

  void reportOutcome({required bool reachable}) {
    if (reachable != state) emit(reachable);
  }
}
