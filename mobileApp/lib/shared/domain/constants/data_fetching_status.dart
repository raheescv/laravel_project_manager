/// The lifecycle of an async data fetch held on a Cubit/Bloc state.
///
/// Transition: `idle → waiting → success | failed`. Use [idle] as the initial
/// value and [refreshCompleted] for pull-to-refresh completion signals.
enum DataFetchStatus {
  idle,
  waiting,
  success,
  failed,
  refreshCompleted,
}
