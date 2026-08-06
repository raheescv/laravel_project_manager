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
  });

  final AuthStatus status;
  final ApiUser? user;
  final String? errorMessage;

  /// A sign-in / unlock is in flight.
  final bool busy;

  bool get isSignedIn => status == AuthStatus.signedIn;

  /// Whether the signed-in user holds the given Spatie permission slug.
  bool hasPermission(String permission) => user?.hasPermission(permission) ?? false;

  AuthState copyWith({
    AuthStatus? status,
    ApiUser? user,
    String? errorMessage,
    bool? busy,
    bool clearError = false,
    bool clearUser = false,
  }) =>
      AuthState(
        status: status ?? this.status,
        user: clearUser ? null : (user ?? this.user),
        errorMessage: clearError ? null : (errorMessage ?? this.errorMessage),
        busy: busy ?? this.busy,
      );

  @override
  List<Object?> get props => [status, user, errorMessage, busy];
}
