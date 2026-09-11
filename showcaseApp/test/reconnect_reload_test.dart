import 'package:flutter_test/flutter_test.dart';
import 'package:shared_preferences/shared_preferences.dart';
import 'package:showcase/features/product/logic/product_cubit/product_cubit.dart';
import 'package:showcase/shared/domain/constants/app_config.dart';
import 'package:showcase/shared/domain/constants/data_fetching_status.dart';
import 'package:showcase/shared/domain/constants/global_variables.dart';
import 'package:showcase/shared/domain/models/index.dart';
import 'package:showcase/shared/domain/repository/catalog_repository.dart';
import 'package:showcase/shared/logic/branch_cubit/branch_cubit.dart';
import 'package:showcase/shared/logic/connectivity_cubit/connectivity_cubit.dart';
import 'package:showcase/shared/logic/funnel_cubit/funnel_cubit.dart';
import 'package:showcase/shared/logic/product_list_cubit/product_list_cubit.dart';
import 'package:showcase/shared/utils/local_storage/local_storage_service.dart';
import 'package:showcase/shared/utils/router/http_utils/common_exception.dart';
import 'package:showcase/shared/utils/router/http_utils/http_service.dart';

/// What failed while the panel was cut off is asked for again when the server
/// is back — and only what failed. A screen that loaded is what the customer
/// is looking at, and reloading it would blank their answer for one they
/// already have.
void main() {
  late _Shop shop;
  late ConnectivityCubit connectivity;

  setUp(() async {
    SharedPreferences.setMockInitialValues({});
    final storage = await LocalStorageService.create();
    final config = AppConfig.resolve(savedBaseUrl: null, savedTenant: null);
    shop = _Shop();
    connectivity = ConnectivityCubit();
    serviceLocator
      ..registerSingleton<LocalStorageService>(storage)
      ..registerSingleton<AppConfig>(config)
      ..registerSingleton<HttpService>(HttpService(config: config))
      ..registerSingleton<CatalogRepository>(shop)
      ..registerSingleton<ConnectivityCubit>(connectivity);
  });

  tearDown(() => serviceLocator.reset());

  /// The kiosk boots; the branch cubit is what boot builds first.
  BranchCubit boot() {
    final branch = BranchCubit();
    serviceLocator.registerSingleton<BranchCubit>(branch);
    addTearDown(branch.close);
    return branch;
  }

  /// The wire goes down and, later, comes back. What HttpService's interceptor
  /// would report either side of an outage.
  Future<void> outage() async {
    shop.down = true;
    connectivity.reportOutcome(reachable: false);
    await pumpEventQueue();
  }

  Future<void> recovery() async {
    shop.down = false;
    connectivity.reportOutcome(reachable: true);
    await pumpEventQueue();
  }

  group('the branch list', () {
    test('a kiosk switched on before its network loads its shop when it can', () async {
      shop.down = true;
      connectivity.reportOutcome(reachable: false);
      final branch = boot();
      final funnel = FunnelCubit();
      addTearDown(funnel.close);
      await pumpEventQueue();
      expect(branch.state.status, DataFetchStatus.failed);
      expect(branch.state.branches, isEmpty);
      expect(funnel.state.sizesStatus, DataFetchStatus.failed);
      expect(shop.sizesScopedTo, [null], reason: 'nothing saved, nothing to scope by');

      await recovery();

      expect(shop.branchesAsked, 2);
      expect(branch.state.selected?.id, 7);
      expect(funnel.state.sizesStatus, DataFetchStatus.success);
      // The size run reloads off the same reconnect as the branch list, and
      // has to wait for it: asked again unscoped it would read as sold out.
      expect(shop.sizesScopedTo, [null, 7]);
    });

    test('is left alone when it loaded', () async {
      final branch = boot();
      await branch.ready;
      expect(branch.state.branches, hasLength(1));
      await outage();
      await recovery();
      expect(shop.branchesAsked, 1);
    });
  });

  group('the funnel', () {
    test('asks again only for the step that failed', () async {
      boot();
      final funnel = FunnelCubit();
      addTearDown(funnel.close);
      await pumpEventQueue();
      expect(funnel.state.sizesStatus, DataFetchStatus.success);

      await outage();
      await funnel.chooseSize('42');
      expect(funnel.state.brandsStatus, DataFetchStatus.failed);
      final sizesBefore = shop.sizesAsked;

      await recovery();

      expect(funnel.state.brandsStatus, DataFetchStatus.success);
      expect(shop.brandsAsked, 2);
      expect(shop.sizesAsked, sizesBefore, reason: 'the size run loaded; not touched');
    });
  });

  group('the results grid', () {
    test('reloads a page that never came', () async {
      boot();
      await outage();
      final list = ProductListCubit(filters: const ProductFilters(size: '42'))..load();
      addTearDown(list.close);
      await pumpEventQueue();
      expect(list.state.status, DataFetchStatus.failed);

      await recovery();

      expect(list.state.status, DataFetchStatus.success);
      expect(list.state.items, hasLength(1));
      expect(shop.productsAsked, 2);
    });

    test('keeps a page that did', () async {
      boot();
      final list = ProductListCubit(filters: const ProductFilters(size: '42'))..load();
      addTearDown(list.close);
      await pumpEventQueue();
      expect(list.state.status, DataFetchStatus.success);

      await outage();
      await recovery();

      expect(shop.productsAsked, 1);
    });
  });

  group('the product page', () {
    test('reloads a page that never came', () async {
      boot();
      await outage();
      final page = ProductCubit(productId: 1);
      addTearDown(page.close);
      await pumpEventQueue();
      expect(page.state.status, DataFetchStatus.failed);

      await recovery();

      expect(page.state.status, DataFetchStatus.success);
      expect(page.state.relatedStatus, DataFetchStatus.success);
      expect(shop.productAsked, 2);
    });

    test('fetches only the rail when that is what was missing', () async {
      boot();
      shop.relatedDown = true;
      final page = ProductCubit(productId: 1);
      addTearDown(page.close);
      await pumpEventQueue();
      expect(page.state.status, DataFetchStatus.success);
      expect(page.state.relatedStatus, DataFetchStatus.failed);

      shop.relatedDown = false;
      await outage();
      await recovery();

      expect(page.state.relatedStatus, DataFetchStatus.success);
      expect(shop.relatedAsked, 2);
      expect(shop.productAsked, 1, reason: 'the photo, the price and the stock must not blink');
    });
  });
}

/// One shop's catalogue behind a wire that can be [down]. Counts what was
/// asked, and for the size run, which branch it was asked for.
class _Shop implements CatalogRepository {
  bool down = false;
  bool relatedDown = false;

  int branchesAsked = 0;
  int sizesAsked = 0;
  int brandsAsked = 0;
  int productsAsked = 0;
  int productAsked = 0;
  int relatedAsked = 0;
  final List<int?> sizesScopedTo = [];

  void _wire() {
    if (down) throw OfflineException();
  }

  static Product _product(int id) =>
      Product.fromJson({'id': id, 'name': 'p$id', 'code': 'P$id', 'mrp': 100});

  @override
  Future<List<Branch>> branches() async {
    branchesAsked++;
    _wire();
    return const [Branch(id: 7, name: 'Galleria', code: 'GAL', location: 'Galleria', mobile: '')];
  }

  @override
  Future<List<SizeOption>> sizes({int? mainCategoryId, int? brandId, int? branchId}) async {
    sizesAsked++;
    sizesScopedTo.add(branchId);
    _wire();
    return const [];
  }

  @override
  Future<List<BrandOption>> brands({int? mainCategoryId, String? size, bool inStockOnly = true}) async {
    brandsAsked++;
    _wire();
    return const [];
  }

  @override
  Future<Paginated<Product>> products({
    int? mainCategoryId,
    int? brandId,
    String? size,
    String? color,
    String? search,
    double? minPrice,
    double? maxPrice,
    bool inStockOnly = true,
    bool has360 = false,
    String sortBy = 'name',
    String sortDirection = 'asc',
    int page = 1,
    int perPage = 24,
  }) async {
    productsAsked++;
    _wire();
    return Paginated<Product>(
      items: [_product(1)],
      currentPage: 1,
      lastPage: 1,
      total: 1,
      hasMorePages: false,
    );
  }

  @override
  Future<Product> product(int id) async {
    productAsked++;
    _wire();
    return _product(id);
  }

  @override
  Future<List<Product>> related(Product product, {int limit = 8, bool inStockOnly = true}) async {
    relatedAsked++;
    _wire();
    if (relatedDown) throw OfflineException();
    return [_product(2)];
  }

  @override
  Future<List<ColorOption>> colors() async => const [];

  @override
  Future<List<CategoryOption>> categories({String? size, int? branchId, bool inStockOnly = true}) async =>
      const [];

  @override
  dynamic noSuchMethod(Invocation i) => super.noSuchMethod(i);
}
