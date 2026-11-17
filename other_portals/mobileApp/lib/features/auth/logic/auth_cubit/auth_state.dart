part of 'auth_cubit.dart';

/// State for [AuthCubit] — the §5 shape.
///
/// The router's `refreshListenable` watches this cubit's stream, so every
/// emitted state is a chance for the redirect to re-run. [status] is therefore
/// the field that drives navigation; keep it accurate.
class AuthState extends Equatable {
  const AuthState({
    this.status = AuthStatus.unknown,
    this.user,
    this.errorMessage,
    this.busy = false,
    this.branchPending = false,
  });

  final AuthStatus status;
  final ApiUser? user;
  final String? errorMessage;

  /// A sign-in / unlock is in flight.
  final bool busy;

  /// Signed in, but this user works more than one branch and has not said which
  /// one this session is for yet. The router holds them on the branch picker.
  final bool branchPending;

  bool get isSignedIn => status == AuthStatus.signedIn;

  /// Whether the signed-in user holds the given Spatie permission slug.
  bool hasPermission(String permission) => user?.hasPermission(permission) ?? false;

  AuthState copyWith({
    AuthStatus? status,
    ApiUser? user,
    String? errorMessage,
    bool? busy,
    bool? branchPending,
    bool clearError = false,
    bool clearUser = false,
  }) =>
      AuthState(
        status: status ?? this.status,
        user: clearUser ? null : (user ?? this.user),
        errorMessage: clearError ? null : (errorMessage ?? this.errorMessage),
        busy: busy ?? this.busy,
        branchPending: branchPending ?? this.branchPending,
      );

  @override
  List<Object?> get props => [status, user, errorMessage, busy, branchPending];
}
