/// The lifecycle of an async data fetch held on a Cubit state.
///
/// Transition: `idle → waiting → success | failed`. [idle] is the initial value;
/// [refreshCompleted] signals a pull-to-refresh finishing without changing the
/// data already on screen.
enum DataFetchStatus { idle, waiting, success, failed, refreshCompleted }

extension DataFetchStatusX on DataFetchStatus {
  bool get isWaiting => this == DataFetchStatus.waiting;
  bool get isSuccess => this == DataFetchStatus.success;
  bool get isFailed => this == DataFetchStatus.failed;
}
