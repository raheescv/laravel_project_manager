import 'package:intl/intl.dart';

/// Currency + date helpers. The currency [symbol] is user-configurable from
/// Settings (see CurrencyCubit) and applies app-wide; defaults to `QAR `.
class Money {
  static String symbol = 'QAR ';
  static int decimals = 2;

  // Cache one formatter per (symbol, decimals) so changing currency just swaps
  // which cached formatter is used rather than rebuilding on every call.
  static final Map<String, NumberFormat> _fullCache = {};
  static final Map<String, NumberFormat> _compactCache = {};

  static NumberFormat get _full => _fullCache.putIfAbsent(
        '$symbol|$decimals',
        () => NumberFormat.currency(symbol: symbol, decimalDigits: decimals),
      );
  static NumberFormat get _compact => _compactCache.putIfAbsent(
        '$symbol|$decimals',
        () => NumberFormat.compactCurrency(
            symbol: symbol, decimalDigits: decimals == 0 ? 0 : 1),
      );

  // Plain grouped number with no currency symbol — for tight cells.
  static final Map<int, NumberFormat> _plainCache = {};
  static NumberFormat _plain(int dec) => _plainCache.putIfAbsent(
      dec, () => NumberFormat.decimalPatternDigits(decimalDigits: dec));

  static String of(num? v) => _full.format(v ?? 0);
  static String compact(num? v) => _compact.format(v ?? 0);

  /// Grouped number without the currency symbol, e.g. `15,626.00`.
  static String plain(num? v, {int? decimals}) =>
      _plain(decimals ?? Money.decimals).format(v ?? 0);
}

class Dates {
  static String human(String? iso) {
    if (iso == null || iso.isEmpty) return '';
    final d = DateTime.tryParse(iso);
    if (d == null) return iso;
    return DateFormat('d MMM yyyy').format(d);
  }

  static String today() => DateFormat('yyyy-MM-dd').format(DateTime.now());

  /// `yyyy-MM-dd` for a given date — the format the report API expects.
  static String iso(DateTime d) => DateFormat('yyyy-MM-dd').format(d);

  /// `yyyy-MM-dd HH:mm:ss` — the datetime format the day-session API expects.
  static String isoDateTime(DateTime d) =>
      DateFormat('yyyy-MM-dd HH:mm:ss').format(d);

  /// `Sat, 21 Jun 2026` — the weekday-prefixed date used on the day-session screen.
  static String weekday(DateTime d) => DateFormat('EEE, d MMM yyyy').format(d);

  /// `9:12 AM` — a clock time for a given moment.
  static String time(DateTime d) => DateFormat('h:mm a').format(d);

  /// `21 Jun 2026 · 9:12 AM` from an ISO/datetime string (empty if unparseable).
  static String humanDateTime(String? iso) {
    if (iso == null || iso.isEmpty) return '';
    final d = DateTime.tryParse(iso);
    if (d == null) return iso;
    return DateFormat('d MMM yyyy · h:mm a').format(d);
  }

  /// A compact range label, e.g. "8 – 14 Jun 2026" or "8 Jun – 2 Jul 2026".
  static String range(DateTime start, DateTime end) {
    if (start.year == end.year &&
        start.month == end.month &&
        start.day == end.day) {
      return DateFormat('d MMM yyyy').format(start);
    }
    final sameMonth = start.year == end.year && start.month == end.month;
    final sameYear = start.year == end.year;
    final startFmt = sameMonth ? 'd' : (sameYear ? 'd MMM' : 'd MMM yyyy');
    return '${DateFormat(startFmt).format(start)} – ${DateFormat('d MMM yyyy').format(end)}';
  }
}

/// Formats a sale quantity for display: whole numbers show without decimals,
/// fractional quantities show up to 3 decimals with trailing zeros trimmed
/// (e.g. 1 → "1", 1.5 → "1.5", 2.500 → "2.5", 0.001 → "0.001"). Mirrors the web
/// POS which allows quantities in 0.001 steps.
String qtyLabel(num v) {
  if (v == v.roundToDouble()) return v.toInt().toString();
  var s = v.toStringAsFixed(3);
  s = s.replaceFirst(RegExp(r'0+$'), '');
  s = s.replaceFirst(RegExp(r'\.$'), '');
  return s;
}

num asNum(dynamic v) {
  if (v == null) return 0;
  if (v is num) return v;
  return num.tryParse(v.toString()) ?? 0;
}

String asStr(dynamic v) => v?.toString() ?? '';
