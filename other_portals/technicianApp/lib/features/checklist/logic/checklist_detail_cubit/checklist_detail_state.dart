part of 'checklist_detail_cubit.dart';

class ChecklistDetailState extends Equatable {
  const ChecklistDetailState({
    this.status = DataFetchStatus.idle,
    this.detail,
    this.phase,
    this.busy = false,
    this.actionError,
    this.errorMessage,
    this.pdfStatus = DataFetchStatus.idle,
  });

  /// Load status of [detail].
  final DataFetchStatus status;
  final ChecklistDetail? detail;

  /// The phase being worked (from the route, then the server's answer).
  final String? phase;

  /// A write is in flight.
  final bool busy;

  /// Last write error — surfaced by the screen as a toast / inline message.
  final String? actionError;

  /// Fatal load error (blocks the screen when there is no [detail]).
  final String? errorMessage;

  /// The PDF download — tracked apart from [busy] so fetching it never blocks
  /// the rest of the hand-over.
  final DataFetchStatus pdfStatus;

  bool get pdfLoading => pdfStatus == DataFetchStatus.waiting;

  ChecklistDetailState copyWith({
    DataFetchStatus? status,
    ChecklistDetail? detail,
    String? phase,
    bool? busy,
    String? actionError,
    String? errorMessage,
    DataFetchStatus? pdfStatus,
    bool clearActionError = false,
    bool clearError = false,
  }) =>
      ChecklistDetailState(
        status: status ?? this.status,
        detail: detail ?? this.detail,
        phase: phase ?? this.phase,
        busy: busy ?? this.busy,
        actionError: clearActionError ? null : (actionError ?? this.actionError),
        errorMessage: clearError ? null : (errorMessage ?? this.errorMessage),
        pdfStatus: pdfStatus ?? this.pdfStatus,
      );

  @override
  List<Object?> get props => [status, detail, phase, busy, actionError, errorMessage, pdfStatus];
}
