import 'package:flutter_test/flutter_test.dart';
import 'package:invo/features/sale/logic/catalog_cubit/catalog_cubit.dart';
import 'package:invo/shared/domain/constants/global_variables.dart';
import 'package:invo/shared/domain/repository/lookup_repository.dart';
import 'package:shared_preferences/shared_preferences.dart';

import 'support/fake_lookup_repository.dart';

void main() {
  TestWidgetsFlutterBinding.ensureInitialized();

  tearDown(() async => serviceLocator.reset());

  Future<({CatalogCubit cat, FakeLookupRepository repo})> makeController({int total = 55}) async {
    SharedPreferences.setMockInitialValues({});
    final repo = FakeLookupRepository(total: total);
    serviceLocator.registerLazySingleton<LookupRepository>(() => repo);
    return (cat: CatalogCubit(), repo: repo);
  }

  test('load fetches page 1; loadMore appends until the last page, then stops', () async {
    final (cat: cat, repo: svc) = await makeController(total: 55); // 55 → 3 pages of 20

    await cat.load();
    expect(cat.products.length, 20);
    expect(cat.hasMore, isTrue);

    await cat.loadMore();
    expect(cat.products.length, 40);
    expect(cat.hasMore, isTrue);

    await cat.loadMore();
    expect(cat.products.length, 55, reason: 'last page is partial (15 items)');
    expect(cat.hasMore, isFalse);

    // Once the last page is reached, further loadMore calls are no-ops.
    final calls = svc.productCalls;
    await cat.loadMore();
    expect(svc.productCalls, calls);
    // load() also fetches categories alongside page 1, but only the product
    // pages are recorded on requestedPages.
    expect(svc.requestedPages, [1, 2, 3]);

    // No duplicate ids leaked through the accumulation.
    expect(cat.products.map((p) => p.id).toSet().length, 55);
  });

  test('selecting a category reloads from page 1', () async {
    final (cat: cat, repo: svc) = await makeController(total: 55);

    await cat.load();
    await cat.loadMore();
    expect(cat.products.length, 40);

    cat.selectCategory(1); // triggers an in-place reload
    await Future<void>.delayed(const Duration(milliseconds: 10));
    expect(cat.products.length, 20, reason: 'reload resets to page 1');
    expect(svc.requestedPages.last, 1);
  });
}
