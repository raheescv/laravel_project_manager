import 'package:flutter_bloc/flutter_bloc.dart';

/// A Cubit that owns mutable working data directly (the same shape the app's
/// former `ChangeNotifier` controllers had) and signals the UI to rebuild by
/// emitting a monotonically increasing tick.
///
/// This keeps screen code reading `cubit.field` / `cubit.method()` exactly as
/// before while moving the app onto `flutter_bloc` + `get_it`. Call [refresh]
/// wherever a controller used to call `notifyListeners()`. Watch with
/// `context.watch<XCubit>()` (rebuilds on each tick).
abstract class HolderCubit extends Cubit<int> {
  HolderCubit() : super(0);

  /// Notify listeners that the owned data changed. A no-op once closed: these
  /// cubits refresh after awaits, and the screen that owned one (a tablet
  /// detail pane switching jobs, a popped route) may have closed it meanwhile —
  /// emitting then throws "Cannot emit new states after calling close".
  void refresh() {
    if (isClosed) return;
    emit(state + 1);
  }
}
