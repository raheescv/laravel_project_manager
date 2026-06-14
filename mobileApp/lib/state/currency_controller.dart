import 'package:flutter/foundation.dart';

import '../core/currency.dart';
import '../core/formatters.dart';
import '../core/storage.dart';

/// Holds the active [Currency] and persists the choice. Changing it updates the
/// app-wide [Money] symbol and notifies, so every amount reformats instantly.
class CurrencyController extends ChangeNotifier {
  CurrencyController(this._storage) {
    _currency = Currencies.byCode(_storage.currencyCode);
    _apply();
  }

  final Storage _storage;
  late Currency _currency;

  Currency get currency => _currency;

  void _apply() {
    Money.symbol = _currency.symbol;
    Money.decimals = _currency.decimals;
  }

  Future<void> setCurrency(Currency c) async {
    if (c.code == _currency.code) return;
    _currency = c;
    _apply();
    notifyListeners();
    await _storage.setCurrencyCode(c.code);
  }
}
