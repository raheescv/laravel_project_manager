import 'dart:convert';

import 'package:invo/shared/domain/constants/global_variables.dart';
import 'package:invo/shared/domain/helpers/formatters.dart';
import 'package:invo/shared/domain/models/index.dart';
import 'package:invo/shared/domain/repository/lookup_repository.dart';
import 'package:equatable/equatable.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import 'package:invo/shared/utils/local_storage/local_storage_service.dart';

part 'currency_state.dart';

/// Holds the active [Currency] and the list configured on the web
/// (Settings → Currencies). The list is fetched once, cached and used offline;
/// changing the active currency updates the app-wide [Money] formatter.
class CurrencyCubit extends Cubit<CurrencyState> {
  CurrencyCubit() : super(_initialState()) {
    _apply(state.currency);
  }

  /// Built before `super`, so it cannot touch instance members.
  static CurrencyState _initialState() {
    final storage = serviceLocator<LocalStorageService>();
    final list = _cachedOrBuiltIn(storage.currenciesJson);
    final baseCode = storage.baseCurrencyCode;
    Currency? byCode(String? code) =>
        code == null ? null : list.where((c) => c.code == code).firstOrNull;
    final active = byCode(storage.currencyCode) ??
        byCode(baseCode) ??
        (list.isNotEmpty ? list.first : Currencies.qar);
    return CurrencyState(currency: active, currencies: list, baseCode: baseCode);
  }

  LocalStorageService get _storage => serviceLocator<LocalStorageService>();
  LookupRepository get _repo => serviceLocator<LookupRepository>();

  // Read facade over `state`.
  Currency get currency => state.currency;
  List<Currency> get available => state.available;
  Currency? get base =>
      _byCode(state.baseCode) ?? _firstWhereOrNull((c) => c.isBase);
  bool get isCached => (_storage.currenciesJson ?? '').isNotEmpty;

  /// [Money] is an app-wide static (see §8.2), so it is re-pointed whenever the
  /// active currency changes.
  void _apply(Currency c) {
    Money.symbol = c.symbol;
    Money.decimals = c.decimals;
  }

  static List<Currency> _cachedOrBuiltIn(String? raw) {
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

  Currency? _byCode(String? code) =>
      code == null ? null : _firstWhereOrNull((c) => c.code == code);

  Currency? _firstWhereOrNull(bool Function(Currency) test) {
    for (final c in state.currencies) {
      if (test(c)) return c;
    }
    return null;
  }

  Future<void> setCurrency(Currency c) async {
    if (c.code == state.currency.code) return;
    _apply(c);
    emit(state.copyWith(currency: c));
    await _storage.setCurrencyCode(c.code);
  }

  Future<void> refreshCurrencies() async {
    try {
      final result = await _repo.currencies();
      if (result.currencies.isEmpty) return;

      final rows = result.currencies;
      final baseCode = result.baseCode ?? state.baseCode;

      await _storage.setCurrenciesJson(
        jsonEncode(rows.map((c) => c.toJson()).toList()),
      );
      if (result.baseCode != null && result.baseCode!.isNotEmpty) {
        await _storage.setBaseCurrencyCode(result.baseCode!);
      }

      final active = rows.where((c) => c.code == state.currency.code).firstOrNull ??
          rows.where((c) => c.code == baseCode).firstOrNull ??
          rows.first;
      _apply(active);
      emit(CurrencyState(currency: active, currencies: rows, baseCode: baseCode));
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
