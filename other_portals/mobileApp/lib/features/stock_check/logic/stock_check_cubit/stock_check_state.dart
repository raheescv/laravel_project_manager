part of 'stock_check_cubit.dart';

/// State for [StockCheckCubit] — the §5 shape. Screens keep their own view
/// state (working copy, dirty set, filters); this carries only the outcome of
/// the last repository call.
class StockCheckState extends Equatable {
  const StockCheckState({
    this.status = DataFetchStatus.idle,
    this.errorMessage,
  });

  final DataFetchStatus status;
  final String? errorMessage;

  bool get isBusy => status == DataFetchStatus.waiting;
  bool get failed => status == DataFetchStatus.failed;

  StockCheckState copyWith({
    DataFetchStatus? status,
    String? errorMessage,
    bool clearError = false,
  }) =>
      StockCheckState(
        status: status ?? this.status,
        errorMessage: clearError ? null : (errorMessage ?? this.errorMessage),
      );

  @override
  List<Object?> get props => [status, errorMessage];
}
