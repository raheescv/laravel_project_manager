part of 'currency_cubit.dart';

/// State for [CurrencyCubit] — the §5 shape.
class CurrencyState extends Equatable {
  const CurrencyState({
    required this.currency,
    this.currencies = const [],
    this.baseCode,
  });

  /// The active currency; drives the app-wide [Money] formatter.
  final Currency currency;

  /// Everything configured on the web, active or not.
  final List<Currency> currencies;
  final String? baseCode;

  /// Only the currencies a cashier can pick.
  List<Currency> get available => currencies.where((c) => c.active).toList();

  CurrencyState copyWith({
    Currency? currency,
    List<Currency>? currencies,
    String? baseCode,
  }) =>
      CurrencyState(
        currency: currency ?? this.currency,
        currencies: currencies ?? this.currencies,
        baseCode: baseCode ?? this.baseCode,
      );

  @override
  List<Object?> get props => [currency, currencies, baseCode];
}
