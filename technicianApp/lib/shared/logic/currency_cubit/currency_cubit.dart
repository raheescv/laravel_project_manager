import 'dart:convert';

import 'package:invo/shared/domain/constants/global_variables.dart';
import 'package:invo/shared/domain/helpers/formatters.dart';
import 'package:invo/shared/domain/models/index.dart';
import 'package:invo/shared/domain/repository/lookup_repository.dart';
import 'package:invo/shared/logic/base/holder_cubit.dart';
import 'package:invo/shared/utils/local_storage/local_storage_service.dart';

/// Holds the active [Currency] and the list configured on the web
/// (Settings → Currencies). The list is fetched once, cached and used offline;
/// changing the active currency updates the app-wide [Money] formatter.
class CurrencyCubit extends HolderCubit {
  CurrencyCubit() {
    _currencies = _loadCached();
    _baseCode = _storage.baseCurrencyCode;
    _currency = _resolveActive();
    _apply();
  }

  LocalStorageService get _storage => serviceLocator<LocalStorageService>();
  LookupRepository get _repo => serviceLocator<LookupRepository>();

  late List<Currency> _currencies;
  late Currency _currency;
  String? _baseCode;

  Currency get currency => _currency;
  List<Currency> get available => _currencies.where((c) => c.active).toList();
  Currency? get base => _byCode(_baseCode) ?? _firstWhereOrNull((c) => c.isBase);
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
    refresh();
    await _storage.setCurrencyCode(c.code);
  }

  Future<void> refreshCurrencies() async {
    try {
      final result = await _repo.currencies();
      if (result.currencies.isEmpty) return;

      _currencies = result.currencies;
      _baseCode = result.baseCode ?? _baseCode;

      await _storage.setCurrenciesJson(
        jsonEncode(_currencies.map((c) => c.toJson()).toList()),
      );
      if (result.baseCode != null && result.baseCode!.isNotEmpty) {
        await _storage.setBaseCurrencyCode(result.baseCode!);
      }

      _currency = _byCode(_currency.code) ?? _resolveActive();
      _apply();
      refresh();
    } catch (_) {
      // Offline or server error — keep the cached list.
    }
  }

  double convert(num amount, String fromCode, String toCode) {
    if (fromCode == toCode) return amount.toDouble();
    final from = _byCode(fromCode)?.rateToBase;
    final to = _byCode(toCode)?.rateToBase;
    if (from == null || to == null || to == 0) return amount.toDouble();
    return (amount.toDouble() * from) / to;
  }
}
