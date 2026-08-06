part of 'profile_cubit.dart';

/// State for [ProfileCubit] — the §5 shape.
class ProfileState extends Equatable {
  const ProfileState({
    this.status = DataFetchStatus.idle,
    this.errorMessage,
  });

  final DataFetchStatus status;
  final String? errorMessage;

  /// A write is in flight — screens disable their save button on this.
  bool get isSaving => status == DataFetchStatus.waiting;
  bool get failed => status == DataFetchStatus.failed;
  bool get succeeded => status == DataFetchStatus.success;

  ProfileState copyWith({
    DataFetchStatus? status,
    String? errorMessage,
    bool clearError = false,
  }) =>
      ProfileState(
        status: status ?? this.status,
        errorMessage: clearError ? null : (errorMessage ?? this.errorMessage),
      );

  @override
  List<Object?> get props => [status, errorMessage];
}
