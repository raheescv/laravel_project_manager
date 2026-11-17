part of 'token_cubit.dart';

class TokenState extends Equatable {
  const TokenState({
    required this.settings,
    required this.ledger,
    this.status = DataFetchStatus.idle,
    this.branchId,
    this.counterId,
    this.errorMessage,
  });

  /// [DataFetchStatus.waiting] while a number is being taken — the screen
  /// disables New Token so a double tap can't issue two.
  final DataFetchStatus status;
  final TokenSettings settings;
  final TokenLedger ledger;
  final int? branchId;

  /// The counter New Token issues for; null without counters.
  final String? counterId;
  final String? errorMessage;

  bool get issuing => status == DataFetchStatus.waiting;

  TokenCounter? get counter =>
      settings.counterById(counterId) ?? (settings.hasCounters ? settings.counters.first : null);

  TokenSlip? get last => ledger.last;

  /// The number the next tap on New Token will print — shown on the empty slip.
  String get nextDisplayNo {
    final c = counter;
    return settings.format(ledger.nextSequence(c?.id ?? kTokenSingleStream, settings.startAt), counter: c);
  }

  TokenState copyWith({
    DataFetchStatus? status,
    TokenSettings? settings,
    TokenLedger? ledger,
    int? branchId,
    String? counterId,
    String? errorMessage,
    bool clearError = false,
  }) =>
      TokenState(
        status: status ?? this.status,
        settings: settings ?? this.settings,
        ledger: ledger ?? this.ledger,
        branchId: branchId ?? this.branchId,
        counterId: counterId ?? this.counterId,
        errorMessage: clearError ? null : errorMessage ?? this.errorMessage,
      );

  @override
  List<Object?> get props => [status, settings, ledger, branchId, counterId, errorMessage];
}
