import 'dart:async';

import 'package:flutter_test/flutter_test.dart';
import 'package:showcase/shared/logic/funnel_cubit/funnel_cubit.dart';
import 'package:showcase/shared/logic/product_list_cubit/product_list_cubit.dart';
import 'package:showcase/features/product/logic/product_cubit/product_cubit.dart';
import 'package:showcase/shared/domain/constants/data_fetching_status.dart';
import 'package:showcase/shared/domain/constants/global_variables.dart';
import 'package:showcase/shared/domain/models/index.dart';
import 'package:showcase/shared/domain/repository/catalog_repository.dart';
import 'package:showcase/shared/logic/branch_cubit/branch_cubit.dart';
import 'package:showcase/shared/utils/local_storage/local_storage_service.dart';
import 'package:showcase/shared/utils/router/http_utils/common_exception.dart';
import 'package:shared_preferences/shared_preferences.dart';
import 'package:showcase/shared/domain/constants/app_config.dart';
import 'package:showcase/shared/utils/router/http_utils/http_service.dart';

/// No screen may be left on its spinner.
///
/// Every cubit caught `ApiException` and nothing else, so anything the API
/// layer had not thought to type — a malformed body, a cast that did not hold,
/// a null where a map was expected — escaped the handler and left the status on
/// `waiting`. The page then showed a loading state that could never resolve and
/// offered no retry: a dead end, indistinguishable from a slow network.
void main() {
  setUp(() async {
    SharedPreferences.setMockInitialValues({});
    final storage = await LocalStorageService.create();
    final config = AppConfig.resolve(savedBaseUrl: null, savedTenant: null);
    serviceLocator
      ..registerSingleton<LocalStorageService>(storage)
      ..registerSingleton<AppConfig>(config)
      ..registerSingleton<HttpService>(HttpService(config: config));
  });

  tearDown(() => serviceLocator.reset());

  void useRepo(CatalogRepository repo) {
    serviceLocator
      ..registerSingleton<CatalogRepository>(repo)
      ..registerSingleton<BranchCubit>(BranchCubit());
  }

  group('the product page', () {
    test('lands on failed when the API answers with a typed error', () async {
      useRepo(_Throws(ApiException('nope')));
      final cubit = ProductCubit(productId: 1);
      await pumpEventQueue();
      expect(cubit.state.status, DataFetchStatus.failed);
    });

    test('lands on failed when something untyped is thrown', () async {
      // The real one: `data as Map` on a null payload.
      useRepo(_Throws(TypeError()));
      final cubit = ProductCubit(productId: 1);
      await pumpEventQueue();
      expect(cubit.state.status, DataFetchStatus.failed,
          reason: 'must not be left on waiting');
      expect(cubit.state.status, isNot(DataFetchStatus.waiting));
    });

    test('lands on failed when the repository never returns a product', () async {
      useRepo(_Throws(StateError('boom')));
      final cubit = ProductCubit(productId: 1);
      await pumpEventQueue();
      expect(cubit.state.status, DataFetchStatus.failed);
    });
  });

  group('the size run', () {
    test('fails visibly rather than spinning', () async {
      useRepo(_Throws(TypeError()));
      final funnel = FunnelCubit();
      await pumpEventQueue();
      expect(funnel.state.sizesStatus, DataFetchStatus.failed);
    });
  });

  /// The results grid asks three questions at once — the page, the swatches and
  /// the departments — and they answer in whatever order the server manages.
  /// Anything that lands late has to fold itself into the state as it is *then*,
  /// not into a copy of the state it was handed when it set off.
  group('the results grid, when a side request answers last', () {
    test('the swatches do not put the loaded page back to a skeleton', () async {
      final repo = _OutOfOrder();
      useRepo(repo);
      final cubit = ProductListCubit(filters: const ProductFilters(size: '42'))
        ..load()
        ..loadColors();
      await pumpEventQueue();

      repo.answerProducts(1);
      await pumpEventQueue();
      expect(cubit.state.status, DataFetchStatus.success);
      expect(cubit.state.items, hasLength(1));

      // The one the double-tap on the size run makes likely: an extra /brands
      // in flight delays /colors past /products, and it answers into a page
      // that has already arrived.
      repo.answerColors();
      await pumpEventQueue();

      expect(cubit.state.status, DataFetchStatus.success,
          reason: 'the grid must not be sent back to waiting');
      expect(cubit.state.items, hasLength(1),
          reason: 'the loaded page must survive the swatches landing');
      expect(cubit.state.colors, hasLength(1));
    });

    test('the departments do not put it back either', () async {
      final repo = _OutOfOrder();
      useRepo(repo);
      final cubit = ProductListCubit(filters: const ProductFilters(size: '42'))
        ..load()
        ..loadCategories();
      await pumpEventQueue();

      repo.answerProducts(1);
      await pumpEventQueue();
      repo.answerCategories();
      await pumpEventQueue();

      expect(cubit.state.status, DataFetchStatus.success);
      expect(cubit.state.items, hasLength(1));
      expect(cubit.state.categories, hasLength(1));
    });
  });
}

/// Every read is a completer the test answers by hand, so the page and the two
/// side requests can be made to land in any order.
class _OutOfOrder implements CatalogRepository {
  final _productsC = Completer<Paginated<Product>>();
  final _colorsC = Completer<List<ColorOption>>();
  final _categoriesC = Completer<List<CategoryOption>>();

  void answerProducts(int count) => _productsC.complete(Paginated<Product>(
        items: [
          for (var i = 0; i < count; i++)
            Product.fromJson({'id': i + 1, 'name': 'p$i', 'code': 'P$i', 'mrp': 100}),
        ],
        currentPage: 1,
        lastPage: 1,
        total: count,
        hasMorePages: false,
      ));

  void answerColors() => _colorsC.complete(const [ColorOption(color: 'black', productCount: 1)]);

  void answerCategories() =>
      _categoriesC.complete(const [CategoryOption(id: 1, name: 'Shoes', productCount: 1)]);

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
  }) =>
      _productsC.future;

  @override
  Future<List<ColorOption>> colors() => _colorsC.future;

  @override
  Future<List<CategoryOption>> categories({String? size, int? branchId, bool inStockOnly = true}) =>
      _categoriesC.future;

  @override
  Future<List<Branch>> branches() async => const [];
  @override
  Future<List<SizeOption>> sizes({int? mainCategoryId, int? brandId, int? branchId}) async => const [];
  @override
  Future<List<BrandOption>> brands({int? mainCategoryId, String? size, bool inStockOnly = true}) async =>
      const [];
  @override
  dynamic noSuchMethod(Invocation i) => super.noSuchMethod(i);
}

/// Answers every call by throwing [error].
class _Throws implements CatalogRepository {
  _Throws(this.error);

  final Object error;

  Never _fail() => throw error;

  @override
  Future<List<Branch>> branches() async => const [];
  @override
  Future<Product> product(int id) async => _fail();
  @override
  Future<List<SizeOption>> sizes({int? mainCategoryId, int? brandId, int? branchId}) async => _fail();
  @override
  Future<List<CategoryOption>> categories({String? size, int? branchId, bool inStockOnly = true}) async => _fail();
  @override
  Future<List<BrandOption>> brands({int? mainCategoryId, String? size, bool inStockOnly = true}) async => _fail();
  @override
  dynamic noSuchMethod(Invocation i) => super.noSuchMethod(i);
}
