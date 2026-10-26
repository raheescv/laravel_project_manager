import 'dart:math' as math;

import 'package:equatable/equatable.dart';
import 'package:intl/intl.dart';

import 'package:invo/shared/domain/helpers/formatters.dart';

/// The sequence key used when no counters are set up — one number stream for
/// the whole device.
const String kTokenSingleStream = '';

/// How big the number prints on the slip (and draws on the on-screen slip).
enum TokenNumberSize {
  medium('m', 'M', 0.78),
  large('l', 'L', 1.0),
  extraLarge('xl', 'XL', 1.18);

  const TokenNumberSize(this.key, this.label, this.scale);

  final String key;
  final String label;
  final double scale;

  static TokenNumberSize fromKey(String? key) =>
      values.firstWhere((v) => v.key == key, orElse: () => large);
}

/// A service desk that keeps its own sequence and prefix ("Cashier" → B-007).
class TokenCounter extends Equatable {
  const TokenCounter({required this.id, required this.name, required this.prefix});

  factory TokenCounter.fromJson(Map<String, dynamic> j) =>
      TokenCounter(id: asStr(j['id']), name: asStr(j['name']), prefix: asStr(j['prefix']));

  final String id;
  final String name;
  final String prefix;

  Map<String, dynamic> toJson() => {'id': id, 'name': name, 'prefix': prefix};

  TokenCounter copyWith({String? name, String? prefix}) =>
      TokenCounter(id: id, name: name ?? this.name, prefix: prefix ?? this.prefix);

  @override
  List<Object?> get props => [id, name, prefix];
}

/// Device-local Token configuration (APP Settings → Token). Every till keeps
/// its own: the format, the counters and the slip wording are a decision about
/// this kiosk, and there is no server copy to sync with.
class TokenSettings extends Equatable {
  const TokenSettings({
    this.prefix = 'A',
    this.separator = '-',
    this.digits = 3,
    this.startAt = 1,
    this.deviceTag = '',
    this.counters = const [],
    this.header = '',
    this.footerEnglish = '',
    this.footerArabic = '',
    this.showLogo = true,
    this.showDateTime = true,
    this.numberSize = TokenNumberSize.large,
    this.autoPrint = true,
  });

  factory TokenSettings.fromJson(Map<String, dynamic> j) => TokenSettings(
        prefix: asStr(j['prefix']),
        separator: j['separator'] == null ? '-' : asStr(j['separator']),
        digits: _clampDigits(asNum(j['digits'] ?? 3).toInt()),
        startAt: math.max(0, asNum(j['start_at'] ?? 1).toInt()),
        deviceTag: asStr(j['device_tag']),
        counters: (j['counters'] as List<dynamic>? ?? [])
            .map((e) => TokenCounter.fromJson(Map<String, dynamic>.from(e)))
            .toList(),
        header: asStr(j['header']),
        footerEnglish: asStr(j['footer_en']),
        footerArabic: asStr(j['footer_ar']),
        showLogo: j['show_logo'] != false,
        showDateTime: j['show_date_time'] != false,
        numberSize: TokenNumberSize.fromKey(asStr(j['number_size'])),
        autoPrint: j['auto_print'] != false,
      );

  static const int minDigits = 1;
  static const int maxDigits = 6;

  final String prefix;
  final String separator;
  final int digits;
  final int startAt;

  /// Optional per-device tag ("K1") so two kiosks in one branch never print
  /// the same number — each device counts on its own.
  final String deviceTag;
  final List<TokenCounter> counters;
  final String header;
  final String footerEnglish;
  final String footerArabic;
  final bool showLogo;
  final bool showDateTime;
  final TokenNumberSize numberSize;
  final bool autoPrint;

  bool get hasCounters => counters.isNotEmpty;

  static int _clampDigits(int v) => v.clamp(minDigits, maxDigits);

  TokenCounter? counterById(String? id) {
    for (final c in counters) {
      if (c.id == id) return c;
    }
    return null;
  }

  /// `K1-A-007`: device tag, prefix (the counter's when there is one), then
  /// the zero-padded sequence, joined by [separator]. Empty parts drop out.
  String format(int sequence, {TokenCounter? counter}) {
    final lead = [deviceTag.trim(), (counter?.prefix ?? prefix).trim()]
        .where((s) => s.isNotEmpty)
        .join(separator);
    final number = sequence.toString().padLeft(digits, '0');
    return lead.isEmpty ? number : '$lead$separator$number';
  }

  Map<String, dynamic> toJson() => {
        'prefix': prefix,
        'separator': separator,
        'digits': digits,
        'start_at': startAt,
        'device_tag': deviceTag,
        'counters': counters.map((c) => c.toJson()).toList(),
        'header': header,
        'footer_en': footerEnglish,
        'footer_ar': footerArabic,
        'show_logo': showLogo,
        'show_date_time': showDateTime,
        'number_size': numberSize.key,
        'auto_print': autoPrint,
      };

  TokenSettings copyWith({
    String? prefix,
    String? separator,
    int? digits,
    int? startAt,
    String? deviceTag,
    List<TokenCounter>? counters,
    String? header,
    String? footerEnglish,
    String? footerArabic,
    bool? showLogo,
    bool? showDateTime,
    TokenNumberSize? numberSize,
    bool? autoPrint,
  }) =>
      TokenSettings(
        prefix: prefix ?? this.prefix,
        separator: separator ?? this.separator,
        digits: digits == null ? this.digits : _clampDigits(digits),
        startAt: startAt == null ? this.startAt : math.max(0, startAt),
        deviceTag: deviceTag ?? this.deviceTag,
        counters: counters ?? this.counters,
        header: header ?? this.header,
        footerEnglish: footerEnglish ?? this.footerEnglish,
        footerArabic: footerArabic ?? this.footerArabic,
        showLogo: showLogo ?? this.showLogo,
        showDateTime: showDateTime ?? this.showDateTime,
        numberSize: numberSize ?? this.numberSize,
        autoPrint: autoPrint ?? this.autoPrint,
      );

  @override
  List<Object?> get props => [
        prefix, separator, digits, startAt, deviceTag, counters, header,
        footerEnglish, footerArabic, showLogo, showDateTime, numberSize, autoPrint,
      ];
}

/// One issued token — what the slip prints and what Reprint prints again.
class TokenSlip extends Equatable {
  const TokenSlip({
    required this.sequence,
    required this.displayNo,
    required this.issuedAt,
    this.counterId = kTokenSingleStream,
    this.counterName = '',
  });

  factory TokenSlip.fromJson(Map<String, dynamic> j) => TokenSlip(
        sequence: asNum(j['sequence']).toInt(),
        displayNo: asStr(j['display_no']),
        issuedAt: DateTime.tryParse(asStr(j['issued_at'])) ?? DateTime.now(),
        counterId: asStr(j['counter_id']),
        counterName: asStr(j['counter_name']),
      );

  final int sequence;
  final String displayNo;
  final DateTime issuedAt;
  final String counterId;
  final String counterName;

  Map<String, dynamic> toJson() => {
        'sequence': sequence,
        'display_no': displayNo,
        'issued_at': issuedAt.toIso8601String(),
        'counter_id': counterId,
        'counter_name': counterName,
      };

  @override
  List<Object?> get props => [sequence, displayNo, issuedAt, counterId, counterName];
}

/// The day's numbering for one branch on this device. Sequences are date-wise:
/// a ledger from an earlier day is replaced by a fresh one, so every morning
/// starts again from [TokenSettings.startAt].
class TokenLedger extends Equatable {
  const TokenLedger({required this.date, this.sequences = const {}, this.issued = 0, this.last});

  factory TokenLedger.today(DateTime now) => TokenLedger(date: dayKey(now));

  factory TokenLedger.fromJson(Map<String, dynamic> j) => TokenLedger(
        date: asStr(j['date']),
        sequences: (j['sequences'] as Map? ?? {})
            .map((k, v) => MapEntry(asStr(k), asNum(v).toInt())),
        issued: asNum(j['issued']).toInt(),
        last: j['last'] is Map ? TokenSlip.fromJson(Map<String, dynamic>.from(j['last'])) : null,
      );

  static String dayKey(DateTime d) => DateFormat('yyyy-MM-dd').format(d);

  /// `yyyy-MM-dd` in device time.
  final String date;

  /// Last sequence issued per counter id ([kTokenSingleStream] without counters).
  final Map<String, int> sequences;

  /// Tokens issued today across every counter.
  final int issued;
  final TokenSlip? last;

  bool isFor(DateTime now) => date == dayKey(now);

  /// This ledger if it is today's, otherwise a fresh one for [now].
  TokenLedger rolledTo(DateTime now) => isFor(now) ? this : TokenLedger.today(now);

  /// The sequence the next token on [counterId] takes. Raising [startAt]
  /// mid-day jumps ahead; lowering it never re-issues a number already used.
  int nextSequence(String counterId, int startAt) {
    final previous = sequences[counterId];
    return previous == null ? startAt : math.max(previous + 1, startAt);
  }

  TokenLedger record(TokenSlip slip) => TokenLedger(
        date: date,
        sequences: {...sequences, slip.counterId: slip.sequence},
        issued: issued + 1,
        last: slip,
      );

  Map<String, dynamic> toJson() => {
        'date': date,
        'sequences': sequences,
        'issued': issued,
        if (last != null) 'last': last!.toJson(),
      };

  @override
  List<Object?> get props => [date, sequences, issued, last];
}
