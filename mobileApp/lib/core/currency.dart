/// A selectable currency for the POS. [symbol] is what prefixes every amount
/// app-wide; for currencies without a clean single glyph we use the ISO code as
/// a label (renders cleanly everywhere, including the Helvetica receipt PDF).
class Currency {
  const Currency({
    required this.code,
    required this.symbol,
    required this.name,
    this.decimals = 2,
  });

  final String code;
  final String symbol;
  final String name;
  final int decimals;
}

class Currencies {
  static const usd = Currency(code: 'USD', symbol: r'$', name: 'US Dollar');
  static const inr = Currency(code: 'INR', symbol: '₹', name: 'Indian Rupee');
  static const eur = Currency(code: 'EUR', symbol: '€', name: 'Euro');
  static const gbp = Currency(code: 'GBP', symbol: '£', name: 'British Pound');
  static const aed = Currency(code: 'AED', symbol: 'AED ', name: 'UAE Dirham');
  static const sar = Currency(code: 'SAR', symbol: 'SAR ', name: 'Saudi Riyal');
  static const qar = Currency(code: 'QAR', symbol: 'QAR ', name: 'Qatari Riyal');
  static const jpy = Currency(code: 'JPY', symbol: '¥', name: 'Japanese Yen', decimals: 0);

  /// The currencies offered in the Settings picker.
  static const all = <Currency>[usd, inr, eur, gbp, aed, sar, qar, jpy];

  /// Default falls back to the Qatari Riyal (QAR) when no currency is stored.
  static Currency byCode(String? code) =>
      all.firstWhere((c) => c.code == code, orElse: () => qar);
}
