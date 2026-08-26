part of 'branch_cubit.dart';

class BranchState extends Equatable {
  const BranchState({
    this.status = DataFetchStatus.idle,
    this.branches = const [],
    this.selected,
    this.errorMessage,
  });

  final DataFetchStatus status;
  final List<Branch> branches;
  final Branch? selected;
  final String? errorMessage;

  BranchState copyWith({
    DataFetchStatus? status,
    List<Branch>? branches,
    Branch? selected,
    String? errorMessage,
    bool clearError = false,
  }) =>
      BranchState(
        status: status ?? this.status,
        branches: branches ?? this.branches,
        selected: selected ?? this.selected,
        errorMessage: clearError ? null : (errorMessage ?? this.errorMessage),
      );

  @override
  List<Object?> get props => [status, branches, selected, errorMessage];
}
