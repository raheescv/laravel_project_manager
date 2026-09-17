part of 'student_card_cubit.dart';

/// Card lookup (the till), student search and card linking (Link Card screen).
class StudentCardState extends Equatable {
  const StudentCardState({
    this.status = DataFetchStatus.idle,
    this.card,
    this.results = const [],
    this.errorMessage,
  });

  final DataFetchStatus status;

  /// The student from the last lookup or link.
  final StudentCard? card;

  /// The last student search.
  final List<StudentCard> results;
  final String? errorMessage;

  bool get isBusy => status == DataFetchStatus.waiting;

  StudentCardState copyWith({
    DataFetchStatus? status,
    StudentCard? card,
    List<StudentCard>? results,
    String? errorMessage,
    bool clearCard = false,
    bool clearError = false,
  }) =>
      StudentCardState(
        status: status ?? this.status,
        card: clearCard ? null : (card ?? this.card),
        results: results ?? this.results,
        errorMessage: clearError ? null : (errorMessage ?? this.errorMessage),
      );

  @override
  List<Object?> get props => [status, card, results, errorMessage];
}
