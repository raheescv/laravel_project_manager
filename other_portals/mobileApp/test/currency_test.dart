import 'package:flutter_test/flutter_test.dart';
import 'package:invo/shared/domain/constants/global_variables.dart';
import 'package:invo/shared/domain/helpers/formatters.dart';
import 'package:invo/shared/domain/models/index.dart';
import 'package:invo/shared/domain/repository/lookup_repository.dart';
import 'package:invo/shared/logic/currency_cubit/currency_cubit.dart';
import 'package:invo/shared/utils/local_storage/local_storage_service.dart';
import 'package:shared_preferences/shared_preferences.dart';

import 'support/fake_lookup_repository.dart';

void main() {
  TestWidgetsFlutterBinding.ensureInitialized();

  tearDown(() async => serviceLocator.reset());

  test('changing currency reformats Money app-wide and persists the choice', () async {
    SharedPreferences.setMockInitialValues({});
    final storage = await LocalStorageService.create();
    serviceLocator
      ..registerSingleton<LocalStorageService>(storage)
      ..registerLazySingleton<LookupRepository>(() => FakeLookupRepository());

    final c = CurrencyCubit();

    // Defaults to USD ($) — the app's original behaviour (Currencies.all[0]).
    expect(c.currency.code, 'USD');
    expect(Money.of(1500), contains(r'$'));

    await c.setCurrency(Currencies.inr);
    expect(c.currency.code, 'INR');
    expect(Money.symbol, '₹');
    expect(Money.of(1500), contains('₹'));
    expect(storage.currencyCode, 'INR');

    // A fresh cubit (e.g. next launch) restores the persisted currency.
    final restored = CurrencyCubit();
    expect(restored.currency.code, 'INR');

    // Reset the global Money symbol so it can't leak into other tests.
    await c.setCurrency(Currencies.usd);
    expect(Money.symbol, r'$');
  });
}
