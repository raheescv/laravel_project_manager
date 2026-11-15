/// A selectable currency for the POS. [symbol] is what prefixes every amount
/// app-wide; for currencies without a clean single glyph we use the ISO code as
/// a label (renders cleanly everywhere, including the Helvetica receipt PDF).
///
/// [rateToBase] is how many BASE-currency units one unit of this currency is
/// worth (the base currency itself is 1.0). It mirrors the web `rate_to_base`
/// stored under Settings → Currencies, so conversion is identical on both sides.
class Currency {
  const Currency({
    required this.code,
    required this.symbol,
    required this.name,
    this.decimals = 2,
    this.rateToBase = 1.0,
    this.isBase = false,
    this.active = true,
  });

  final String code;
  final String symbol;
  final String name;
  final int decimals;
  final double rateToBase;
  final bool isBase;
  final bool active;

  factory Currency.fromJson(Map<String, dynamic> j) {
    bool truthy(dynamic v) => v == true || v == 1 || v == '1';
    return Currency(
      code: (j['code'] ?? '').toString(),
      symbol: (j['symbol'] ?? j['code'] ?? '').toString(),
      name: (j['name'] ?? j['code'] ?? '').toString(),
      decimals: int.tryParse('${j['decimals']}') ?? 2,
      rateToBase: double.tryParse('${j['rate_to_base']}') ?? 1.0,
      isBase: truthy(j['is_base']),
      // Missing `active` defaults to true (web only ships active currencies).
      active: j['active'] == null ? true : truthy(j['active']),
    );
  }

  Map<String, dynamic> toJson() => {
        'code': code,
        'symbol': symbol,
        'name': name,
        'decimals': decimals,
        'rate_to_base': rateToBase,
        'is_base': isBase,
        'active': active,
      };
}

class Currencies {
  static const usd = Currency(code: 'USD', symbol: r'$', name: 'US Dollar');
  static const inr = Currency(code: 'INR', symbol: '₹', name: 'Indian Rupee');
  static const eur = Currency(code: 'EUR', symbol: '€', name: 'Euro');
  static const gbp = Currency(code: 'GBP', symbol: '£', name: 'British Pound');
  static const aed = Currency(code: 'AED', symbol: 'AED ', name: 'UAE Dirham');
  static const sar = Currency(code: 'SAR', symbol: 'SAR ', name: 'Saudi Riyal');
  static const qar = Currency(code: 'QAR', symbol: 'QAR ', name: 'Qatari Riyal', isBase: true);
  static const jpy = Currency(code: 'JPY', symbol: '¥', name: 'Japanese Yen', decimals: 0);

  /// Built-in fallback list, used only until the app has fetched/cached the
  /// currencies configured on the web (Settings → Currencies).
  static const all = <Currency>[usd, inr, eur, gbp, aed, sar, qar, jpy];

  /// Default falls back to the Qatari Riyal (QAR) when no currency is stored.
  static Currency byCode(String? code) =>
      all.firstWhere((c) => c.code == code, orElse: () => qar);
}
