part of 'branch_cubit.dart';

class BranchState extends Equatable {
  const BranchState({
    this.status = DataFetchStatus.idle,
    this.branches = const [],
    this.selected,
    this.showingAll = false,
    this.errorMessage,
  });

  final DataFetchStatus status;
  final List<Branch> branches;
  final Branch? selected;

  /// Looking at the whole chain rather than one shop. Distinct from a null
  /// [selected], which only means the branches have not loaded yet.
  final bool showingAll;

  final String? errorMessage;

  BranchState copyWith({
    DataFetchStatus? status,
    List<Branch>? branches,
    Branch? selected,
    bool? showingAll,
    String? errorMessage,
    bool clearError = false,
    bool clearSelected = false,
  }) =>
      BranchState(
        status: status ?? this.status,
        branches: branches ?? this.branches,
        selected: clearSelected ? null : (selected ?? this.selected),
        showingAll: showingAll ?? this.showingAll,
        errorMessage: clearError ? null : (errorMessage ?? this.errorMessage),
      );

  @override
  List<Object?> get props =>
      [status, branches, selected, showingAll, errorMessage];
}
