part of 'day_session_cubit.dart';

/// State for [DaySessionCubit] — the §5 shape.
class DaySessionState extends Equatable {
  const DaySessionState({
    required this.selected,
    this.status = 'closed',
    this.session,
    this.report,
    this.busy = false,
    this.syncing = false,
    this.errorMessage,
  });

  /// `open` | `closed` — mirrors the signed-in user's day-session status.
  final String status;

  /// The open/close moment the user has dialled in, to the minute.
  final DateTime selected;
  final DaySession? session;

  /// The session the Sale Bill Report prints for: the open one, or the one
  /// opened last once the day is shut. Null until looked up, and for a branch
  /// that has never opened a day.
  final DaySessionSummary? report;

  /// The toggle is in flight.
  final bool busy;

  /// A server re-read is in flight ([DaySessionCubit.refresh]). The toggle
  /// waits for it: the endpoint *toggles*, so acting on a status the server
  /// has already moved would do the opposite of what the button says.
  final bool syncing;
  final String? errorMessage;

  bool get isOpen => status == 'open';

  DaySessionState copyWith({
    String? status,
    DateTime? selected,
    DaySession? session,
    bool clearSession = false,
    DaySessionSummary? report,
    bool clearReport = false,
    bool? busy,
    bool? syncing,
    String? errorMessage,
    bool clearError = false,
  }) =>
      DaySessionState(
        status: status ?? this.status,
        selected: selected ?? this.selected,
        session: clearSession ? null : (session ?? this.session),
        report: clearReport ? null : (report ?? this.report),
        busy: busy ?? this.busy,
        syncing: syncing ?? this.syncing,
        errorMessage: clearError ? null : (errorMessage ?? this.errorMessage),
      );

  @override
  List<Object?> get props => [status, selected, session, report, busy, syncing, errorMessage];
}
