import 'dart:async';

import 'package:flutter_test/flutter_test.dart';
import 'package:shared_preferences/shared_preferences.dart';
import 'package:showcase/shared/logic/funnel_cubit/funnel_cubit.dart';
import 'package:showcase/shared/logic/product_list_cubit/product_list_cubit.dart';
import 'package:showcase/shared/domain/constants/app_config.dart';
import 'package:showcase/shared/domain/constants/data_fetching_status.dart';
import 'package:showcase/shared/domain/constants/global_variables.dart';
import 'package:showcase/shared/domain/models/index.dart';
import 'package:showcase/shared/domain/repository/catalog_repository.dart';
import 'package:showcase/shared/logic/branch_cubit/branch_cubit.dart';
import 'package:showcase/shared/utils/local_storage/local_storage_service.dart';
import 'package:showcase/shared/utils/router/http_utils/http_service.dart';

/// A size chip gets tapped twice.
///
/// Not by accident — a customer reads the run, taps 40, then sees 42 is what
/// they meant. Both taps ask the funnel a question, and the second one is the
/// real one. An in-flight guard that only knows "a request is running" answers
/// that by dropping the second tap, which leaves the brand step listing the
/// brands for a size the customer already moved off; and if the first answer
/// lands after the second, the screen shows an answer to a question nobody
/// asked. Either way the run stalls, and going back and forward again fixes it
/// — which is what makes it look intermittent.
///
/// So the guard is keyed on the question: identical is a duplicate, different
/// supersedes, and a superseded answer is dropped when it finally lands.
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

  /// The branch cubit loads from the repository the moment it is built, so it
  /// cannot be registered before there is one.
  _Slow useRepo() {
    final repo = _Slow();
    serviceLocator
      ..registerSingleton<CatalogRepository>(repo)
      ..registerSingleton<BranchCubit>(BranchCubit());
    return repo;
  }

  group('two taps on the size run', () {
    test('the brand step answers for the size tapped last', () async {
      final repo = useRepo();
      final funnel = FunnelCubit();
      await pumpEventQueue();

      unawaited(funnel.chooseSize('40'));
      await pumpEventQueue();
      unawaited(funnel.chooseSize('42'));
      await pumpEventQueue();

      // The stale answer lands first, the way a slow first request does.
      repo.answer('40', ['forty']);
      await pumpEventQueue();
      expect(funnel.state.brands, isEmpty,
          reason: 'the answer for 40 is no longer the question being asked');
      expect(funnel.state.brandsStatus, DataFetchStatus.waiting);

      repo.answer('42', ['forty-two']);
      await pumpEventQueue();
      expect(funnel.state.brands.single.name, 'forty-two');
      expect(funnel.state.brandsStatus, DataFetchStatus.success);
    });

    test('the second tap actually reaches the API', () async {
      final repo = useRepo();
      final funnel = FunnelCubit();
      await pumpEventQueue();

      unawaited(funnel.chooseSize('40'));
      await pumpEventQueue();
      unawaited(funnel.chooseSize('42'));
      await pumpEventQueue();

      expect(repo.asked, contains('42'),
          reason: 'dropping it strands the brand step on the previous size');
    });

    test('the same size twice is one request, not two', () async {
      final repo = useRepo();
      final funnel = FunnelCubit();
      await pumpEventQueue();

      unawaited(funnel.chooseSize('40'));
      unawaited(funnel.chooseSize('40'));
      await pumpEventQueue();

      expect(repo.asked.where((s) => s == '40'), hasLength(1));
    });
  });

  group('the results list', () {
    test('a changed filter supersedes the request already running', () async {
      final repo = useRepo();
      final cubit = ProductListCubit(filters: const ProductFilters(size: '40'));

      unawaited(cubit.load());
      await pumpEventQueue();
      unawaited(cubit.apply(const ProductFilters(size: '42')));
      await pumpEventQueue();

      expect(repo.askedProducts, hasLength(2),
          reason: 'the second query has to be sent, not swallowed');

      repo.answerProducts();
      await pumpEventQueue();
      expect(cubit.state.status, isNot(DataFetchStatus.waiting),
          reason: 'must not be left spinning on a superseded request');
    });
  });
}

/// Hands out a completer per question so a test can answer them out of order.
class _Slow implements CatalogRepository {
  final asked = <String>[];
  final askedProducts = <String>[];
  final _pending = <String, Completer<List<BrandOption>>>{};
  final _products = <Completer<Paginated<Product>>>[];

  void answer(String size, List<String> names) {
    _pending.remove(size)!.complete([
      for (var i = 0; i < names.length; i++)
        BrandOption(id: i + 1, name: names[i], productCount: 1),
    ]);
  }

  void answerProducts() {
    for (final c in _products) {
      if (!c.isCompleted) {
        c.complete(const Paginated<Product>(
          items: [],
          currentPage: 1,
          lastPage: 1,
          total: 0,
          hasMorePages: false,
        ));
      }
    }
  }

  @override
  Future<List<BrandOption>> brands({int? mainCategoryId, String? size, bool inStockOnly = true}) {
    asked.add(size ?? '');
    return (_pending[size ?? ''] = Completer<List<BrandOption>>()).future;
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
  }) {
    askedProducts.add(size ?? '');
    final c = Completer<Paginated<Product>>();
    _products.add(c);
    return c.future;
  }

  @override
  Future<List<SizeOption>> sizes({int? mainCategoryId, int? brandId, int? branchId}) async => const [];
  @override
  Future<List<Branch>> branches() async => const [];
  @override
  Future<List<ColorOption>> colors() async => const [];
  @override
  Future<Branding> branding() => throw UnimplementedError();
  @override
  Future<Product> product(int id) => throw UnimplementedError();
  @override
  Future<List<Product>> related(Product product, {int limit = 8, bool inStockOnly = true}) async => const [];
  @override
  Future<List<CategoryOption>> categories({String? size, int? branchId, bool inStockOnly = true}) async => const [];
}
