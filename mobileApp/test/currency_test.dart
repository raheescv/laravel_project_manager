import 'package:flutter_test/flutter_test.dart';
import 'package:invo/core/currency.dart';
import 'package:invo/core/formatters.dart';
import 'package:invo/core/storage.dart';
import 'package:invo/state/currency_controller.dart';
import 'package:shared_preferences/shared_preferences.dart';

void main() {
  test('changing currency reformats Money app-wide and persists the choice', () async {
    SharedPreferences.setMockInitialValues({});
    final storage = await Storage.create();
    final c = CurrencyController(storage);

    // Defaults to USD ($) — the app's original behaviour.
    expect(c.currency.code, 'USD');
    expect(Money.of(1500), contains(r'$'));

    await c.setCurrency(Currencies.inr);
    expect(c.currency.code, 'INR');
    expect(Money.symbol, '₹');
    expect(Money.of(1500), contains('₹'));
    expect(storage.currencyCode, 'INR');

    // A fresh controller (e.g. next launch) restores the persisted currency.
    final restored = CurrencyController(storage);
    expect(restored.currency.code, 'INR');

    // Reset the global Money symbol so it can't leak into other tests.
    await c.setCurrency(Currencies.usd);
    expect(Money.symbol, r'$');
  });
}
