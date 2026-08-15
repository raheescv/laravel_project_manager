import 'package:dio/dio.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:invo/features/sale/logic/catalog_cubit/catalog_cubit.dart';
import 'package:invo/shared/domain/constants/data_fetching_status.dart';
import 'package:invo/shared/domain/constants/global_variables.dart';
import 'package:invo/shared/domain/models/index.dart';
import 'package:invo/shared/domain/repository/catalog_snapshot_repository.dart';
import 'package:invo/shared/domain/services/catalog_snapshot_service.dart';
import 'package:invo/shared/utils/components/app_strings.dart';
import 'package:invo/shared/utils/router/http_utils/common_exception.dart';

import 'support/fake_lookup_repository.dart';
import 'support/offline_harness.dart';

/// The payoff for everything provisioning downloaded: with the network gone, the
/// New Sale screen still shows a product grid and a category rail, because both
/// came off the device.
///
/// The cached lists are deliberately named differently from the live ones, so
/// every assertion here says which side actually answered rather than merely
/// that *something* did.
void main() {
  late _FlakyLookup lookup;
  late CatalogSnapshotService snapshot;
  late CatalogCubit catalog;
  late int branchId;

  /// Twenty-five products: more than one page of twenty, so paging a cached
  /// catalog is exercised rather than assumed.
  Map<String, dynamic> cached(int id, {String type = 'service', int categoryId = 1}) => {
        'id': id,
        'name': 'Cached Item $id',
        'code': 'C$id',
        'barcode': 'B$id',
        'type': type,
        'mrp': 20.0,
        'tax': 0,
        'total_stock': 5,
        'main_category': {'id': categoryId, 'name': categoryId == 1 ? 'Hair' : 'Retail'},
      };

  setUp(() async {
    await setUpOfflineHarness();
    lookup = _FlakyLookup();
    branchId = await registerBranchContext(lookup: lookup);
    snapshot = CatalogSnapshotService();
    serviceLocator.registerSingleton<CatalogSnapshotRepository>(snapshot);

    // What a successful online session would have left behind.
    await snapshot.replace(
      branchId: branchId,
      products: [
        for (var id = 1; id <= 24; id++) cached(id),
        cached(25, type: 'product', categoryId: 2),
      ],
      categoriesByType: {
        '': [
          Category(id: 1, name: 'Cached Hair', productCount: 24),
          Category(id: 2, name: 'Cached Retail', productCount: 1),
        ],
        'product': [Category(id: 2, name: 'Cached Retail', productCount: 1)],
        'service': [Category(id: 1, name: 'Cached Hair', productCount: 24)],
      },
    );
    catalog = CatalogCubit();
  });

  tearDown(() async {
    await catalog.close();
    await tearDownOfflineHarness();
  });

  /// Several of these screens' actions are fire-and-forget (`selectType`,
  /// `selectCategory`, the debounced search), so the assertion itself is what is
  /// polled — no test here waits out a fixed guess at how long a load takes.
  Future<void> until(bool Function() done,
      {Duration limit = const Duration(seconds: 5)}) async {
    final deadline = DateTime.now().add(limit);
    while (DateTime.now().isBefore(deadline)) {
      if (done()) return;
      await Future<void>.delayed(const Duration(milliseconds: 10));
    }
  }

  group('selling with no network', () {
    test('the grid is served from the device', () async {
      lookup.offline = true;

      await catalog.load();

      expect(catalog.products, hasLength(20));
      expect(catalog.products.first.name, startsWith('Cached'));
      expect(catalog.state.status, DataFetchStatus.success);
      expect(catalog.error, isNull);
    });

    test('the category rail comes off the device too', () async {
      lookup.offline = true;

      await catalog.load();

      // Products without categories is not a working grid — the rail is how a
      // cashier navigates a catalog too long to scroll, so caching one and not
      // the other leaves the till technically able to sell and practically not.
      expect(catalog.categories.map((c) => c.name), ['Cached Hair', 'Cached Retail']);
    });

    test('the rows say they are cached, and how old they are', () async {
      lookup.offline = true;

      await catalog.load();

      // Nobody should discount against a price they believe is live. The grid
      // shows this as "Offline · catalog from …".
      expect(catalog.state.servingCached, isTrue);
      expect(catalog.state.cachedAt, isNotNull);
    });

    test('switching to Products shows that filter own cached categories', () async {
      lookup.offline = true;
      await catalog.load();

      catalog.selectType('product');
      await until(() => catalog.categories.length == 1);

      expect(catalog.categories.single.name, 'Cached Retail');
      expect(catalog.products.single.name, 'Cached Item 25');
    });

    test('a category tap filters the cached grid', () async {
      lookup.offline = true;
      await catalog.load();

      catalog.selectCategory(2);
      await until(() => catalog.products.length == 1);

      expect(catalog.products.single.name, 'Cached Item 25');
      expect(catalog.state.servingCached, isTrue);
    });

    test('search matches the cached rows by name, code and barcode', () async {
      lookup.offline = true;
      await catalog.load();

      catalog.setSearch('C13'); // the product code
      await until(() => catalog.products.length == 1);

      expect(catalog.products.single.name, 'Cached Item 13');
    });

    test('scrolling keeps paging the snapshot instead of reaching for the network',
        () async {
      lookup.offline = true;
      await catalog.load();
      final attempts = lookup.productCalls;
      expect(catalog.hasMore, isTrue);

      await catalog.loadMore();

      // Splicing live rows into a cached list pages them out of order and shows
      // the same product twice — and on a dead link it is a five-second stall
      // per scroll for nothing.
      expect(catalog.products, hasLength(25));
      expect(lookup.productCalls, attempts);
      expect(catalog.hasMore, isFalse);
    });

    test('a scan resolves against the snapshot', () async {
      lookup.offline = true;

      final found = await catalog.findByBarcode('B7');

      // The one interaction a cashier repeats hundreds of times a day and has no
      // way to work around.
      expect(found?.name, 'Cached Item 7');
    });
  });

  group('what the fallback must never do', () {
    test('a refusal from the server is not papered over with a stale catalog', () async {
      // The server answered — that is a real answer. Falling back here would
      // hide an expired token behind a catalog that quietly stops updating,
      // and the till would look fine for days.
      lookup.refuseWith = ApiException('Unauthenticated.', statusCode: 401);

      await catalog.load();

      expect(catalog.state.status, DataFetchStatus.failed);
      expect(catalog.error, 'Unauthenticated.');
      expect(catalog.state.servingCached, isFalse);
    });

    test('a scan refusal is passed on rather than answered from the snapshot', () async {
      lookup.refuseWith = ApiException('Unauthenticated.', statusCode: 401);

      await expectLater(catalog.findByBarcode('B7'), throwsA(isA<ApiException>()));
    });

    test('a till that has never been online says exactly that', () async {
      await snapshot.clear();
      lookup.offline = true;

      await catalog.load();

      // "Could not load the catalog" sends a cashier retrying a dead screen all
      // shift. This wording is the difference between that and knowing the till
      // has to be reconnected once before it can ever sell offline.
      expect(catalog.error, AppStrings.noOfflineCatalog);
      expect(catalog.state.servingCached, isFalse);
    });

    test('coming back online replaces the cached rows and drops the notice', () async {
      lookup.offline = true;
      await catalog.load();
      expect(catalog.state.servingCached, isTrue);

      lookup.offline = false;
      await catalog.load();

      // `servingCached` alone, deliberately: it is what every reader of
      // `cachedAt` checks first, so the notice and the low-stock warning both go
      // the moment live rows land. The timestamp itself is left behind on this
      // path and is unreachable while the flag is down.
      expect(catalog.products.first.name, startsWith('Live'));
      expect(catalog.state.servingCached, isFalse);
    });
  });
}

/// A server that can be unplugged or made hostile between calls. Its rows are
/// named "Live …" so no assertion can confuse them with the cached ones.
class _FlakyLookup extends FakeLookupRepository {
  bool offline = false;
  ApiException? refuseWith;

  DioException get _unreachable =>
      DioException.connectionError(requestOptions: RequestOptions(), reason: 'no route');

  void _check() {
    if (refuseWith != null) throw refuseWith!;
    if (offline) throw _unreachable;
  }

  @override
  Future<Paginated<Product>> products({
    String? search,
    int? mainCategoryId,
    String? type,
    int page = 1,
    int perPage = 50,
  }) async {
    productCalls++;
    requestedPages.add(page);
    _check();
    return Paginated(
      items: [
        Product(
          id: 900,
          code: 'L900',
          name: 'Live Item 900',
          barcode: 'L900',
          mrp: 30,
          tax: 0,
          type: 'service',
          categoryName: 'Hair',
          duration: '45',
          totalStock: 5,
          thumbnail: '',
        ),
      ],
      currentPage: page,
      lastPage: 1,
      total: 1,
    );
  }

  @override
  Future<List<Category>> categories({String? type}) async {
    _check();
    return [Category(id: 1, name: 'Live Hair', productCount: 1)];
  }

  @override
  Future<Product?> productByBarcode(String barcode) async {
    _check();
    return null;
  }
}
