import 'dart:convert';

import 'package:flutter/foundation.dart';

import '../core/api_service.dart';
import '../core/currency.dart';
import '../core/formatters.dart';
import '../core/storage.dart';

/// Holds the active [Currency] and the list of currencies configured on the web
/// (Settings → Currencies). The list is fetched once, cached in [Storage] and
/// used offline; changing the active currency updates the app-wide [Money]
/// formatter and notifies, so every amount reformats instantly.
class CurrencyController extends ChangeNotifier {
  CurrencyController(this._storage, {this.service}) {
    _currencies = _loadCached();
    _baseCode = _storage.baseCurrencyCode;
    _currency = _resolveActive();
    _apply();
  }

  final Storage _storage;
  final ApiService? service;

  late List<Currency> _currencies;
  late Currency _currency;
  String? _baseCode;

  /// The active currency every amount is formatted in.
  Currency get currency => _currency;

  /// Active currencies offered in the picker.
  List<Currency> get available => _currencies.where((c) => c.active).toList();

  /// The configured base currency (rate 1.0), or null if unknown.
  Currency? get base => _byCode(_baseCode) ?? _firstWhereOrNull((c) => c.isBase);

  /// True once a list has been fetched/cached from the server (vs. the built-in
  /// fallback). Lets the UI show an "offline / cached" affordance.
  bool get isCached => (_storage.currenciesJson ?? '').isNotEmpty;

  void _apply() {
    Money.symbol = _currency.symbol;
    Money.decimals = _currency.decimals;
  }

  List<Currency> _loadCached() {
    final raw = _storage.currenciesJson;
    if (raw != null && raw.isNotEmpty) {
      try {
        final decoded = jsonDecode(raw);
        if (decoded is List) {
          final list = decoded
              .map((e) => Currency.fromJson(Map<String, dynamic>.from(e)))
              .where((c) => c.code.isNotEmpty)
              .toList();
          if (list.isNotEmpty) return list;
        }
      } catch (_) {
        // Corrupt cache — fall through to the built-in list.
      }
    }
    return Currencies.all;
  }

  Currency _resolveActive() {
    return _byCode(_storage.currencyCode) ??
        _byCode(_baseCode) ??
        (_currencies.isNotEmpty ? _currencies.first : Currencies.qar);
  }

  Currency? _byCode(String? code) {
    if (code == null) return null;
    return _firstWhereOrNull((c) => c.code == code);
  }

  Currency? _firstWhereOrNull(bool Function(Currency) test) {
    for (final c in _currencies) {
      if (test(c)) return c;
    }
    return null;
  }

  Future<void> setCurrency(Currency c) async {
    if (c.code == _currency.code) return;
    _currency = c;
    _apply();
    notifyListeners();
    await _storage.setCurrencyCode(c.code);
  }

  /// Fetch the currency list from the server and cache it for offline use. On
  /// any error (e.g. no network) the cached/fallback list is kept untouched.
  Future<void> refresh() async {
    final api = service;
    if (api == null) return;
    try {
      final result = await api.currencies();
      if (result.currencies.isEmpty) return;

      _currencies = result.currencies;
      _baseCode = result.baseCode ?? _baseCode;

      await _storage.setCurrenciesJson(
        jsonEncode(_currencies.map((c) => c.toJson()).toList()),
      );
      if (result.baseCode != null && result.baseCode!.isNotEmpty) {
        await _storage.setBaseCurrencyCode(result.baseCode!);
      }

      // Keep the active currency valid; if it vanished, fall back to base/first.
      _currency = _byCode(_currency.code) ?? _resolveActive();
      _apply();
      notifyListeners();
    } catch (_) {
      // Offline or server error — keep the cached list.
    }
  }

  /// Convert an [amount] between two currency codes using their rate-to-base
  /// (base units per 1 unit). Unknown codes return the amount unchanged.
  double convert(num amount, String fromCode, String toCode) {
    if (fromCode == toCode) return amount.toDouble();
    final from = _byCode(fromCode)?.rateToBase;
    final to = _byCode(toCode)?.rateToBase;
    if (from == null || to == null || to == 0) return amount.toDouble();
    return (amount.toDouble() * from) / to;
  }
}
