import 'package:flutter/widgets.dart';
import 'package:intl/intl.dart';

/// Defensive JSON readers. The catalog API omits fields it has nothing for and
/// returns numbers as strings in places, so every model parses through these
/// rather than casting.
num asNum(dynamic v) {
  if (v == null) return 0;
  if (v is num) return v;
  return num.tryParse(v.toString()) ?? 0;
}

int asInt(dynamic v) => asNum(v).toInt();

String asStr(dynamic v) => v?.toString() ?? '';

bool asBool(dynamic v) {
  if (v is bool) return v;
  if (v is num) return v != 0;
  final s = asStr(v).toLowerCase();
  return s == 'true' || s == '1';
}

List<Map<String, dynamic>> asMapList(dynamic v) {
  if (v is! List) return const [];
  return v
      .whereType<Map>()
      .map((e) => Map<String, dynamic>.from(e))
      .toList(growable: false);
}

final NumberFormat _money = NumberFormat('#,##0.##');

/// Price as the showcase prints it: `QAR 549`. The currency word comes from the
/// tenant's settings, never a hardcoded symbol.
String money(num value, {String currency = 'QAR'}) =>
    '$currency ${_money.format(value)}';

/// Decode width for a network image, so a 2000px product photo is not decoded
/// at full resolution into a 180px tile. Always pass this to `Image.network`.
int decodeWidthFor(BuildContext context, double logicalWidth) =>
    (logicalWidth * MediaQuery.devicePixelRatioOf(context)).round().clamp(1, 4096);
