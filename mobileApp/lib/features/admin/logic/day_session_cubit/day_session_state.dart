part of 'day_session_cubit.dart';

/// State for [DaySessionCubit] — the §5 shape.
class DaySessionState extends Equatable {
  const DaySessionState({
    required this.selected,
    this.status = 'closed',
    this.session,
    this.busy = false,
    this.errorMessage,
  });

  /// `open` | `closed` — mirrors the signed-in user's day-session status.
  final String status;

  /// The open/close moment the user has dialled in, to the minute.
  final DateTime selected;
  final DaySession? session;
  final bool busy;
  final String? errorMessage;

  bool get isOpen => status == 'open';

  DaySessionState copyWith({
    String? status,
    DateTime? selected,
    DaySession? session,
    bool? busy,
    String? errorMessage,
    bool clearError = false,
  }) =>
      DaySessionState(
        status: status ?? this.status,
        selected: selected ?? this.selected,
        session: session ?? this.session,
        busy: busy ?? this.busy,
        errorMessage: clearError ? null : (errorMessage ?? this.errorMessage),
      );

  @override
  List<Object?> get props => [status, selected, session, busy, errorMessage];
}
