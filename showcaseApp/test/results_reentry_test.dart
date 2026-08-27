import 'dart:async';

import 'package:flutter_test/flutter_test.dart';
import 'package:shared_preferences/shared_preferences.dart';
import 'package:showcase/shared/logic/product_list_cubit/product_list_cubit.dart';
import 'package:showcase/shared/domain/constants/app_config.dart';
import 'package:showcase/shared/domain/constants/data_fetching_status.dart';
import 'package:showcase/shared/domain/constants/global_variables.dart';
import 'package:showcase/shared/domain/models/index.dart';
import 'package:showcase/shared/domain/repository/catalog_repository.dart';
import 'package:showcase/shared/logic/branch_cubit/branch_cubit.dart';
import 'package:showcase/shared/utils/local_storage/local_storage_service.dart';
import 'package:showcase/shared/utils/router/http_utils/http_service.dart';

/// Walking back out of the results and in again.
///
/// Going back pops the results screen, which closes its cubit while its first
/// request is still in the air. Whatever that does must not leave the next
/// visit stuck on a skeleton.
void main() {
  late _SlowCatalog repo;

  setUp(() async {
    SharedPreferences.setMockInitialValues({});
    final storage = await LocalStorageService.create();
    final config = AppConfig.resolve(savedBaseUrl: null, savedTenant: null);
    repo = _SlowCatalog();
    serviceLocator
      ..registerSingleton<LocalStorageService>(storage)
      ..registerSingleton<AppConfig>(config)
      ..registerSingleton<HttpService>(HttpService(config: config))
      ..registerSingleton<CatalogRepository>(repo)
      ..registerSingleton<BranchCubit>(BranchCubit());
    await serviceLocator<BranchCubit>().ready;
  });

  tearDown(() => serviceLocator.reset());

  test('a visit that is abandoned mid-request does not throw', () async {
    final first = ProductListCubit(filters: const ProductFilters(size: '42'))..load();
    await pumpEventQueue();
    expect(first.state.status, DataFetchStatus.waiting);

    // Going back: the provider closes the cubit while the request is in flight.
    await first.close();
    repo.release();
    await pumpEventQueue();
    // Nothing to assert on the closed cubit — the point is that resolving the
    // abandoned request does not blow up.
  });

  test('the next visit still loads after one was abandoned', () async {
    final first = ProductListCubit(filters: const ProductFilters(size: '42'))..load();
    await pumpEventQueue();
    await first.close();

    final second = ProductListCubit(filters: const ProductFilters(size: '43'))..load();
    await pumpEventQueue();
    repo.release();
    await pumpEventQueue();

    expect(second.state.status, DataFetchStatus.success,
        reason: 'the second visit must not inherit the first one being abandoned');
    expect(second.state.items, isNotEmpty);
    await second.close();
  });

  test('asking twice for the same page only asks the server once', () async {
    // The screen calls load() from the provider that builds it, and a branch
    // or filter listener can call it again in the same frame. Each extra call
    // used to start its own request and supersede the one already running,
    // which is what turned one visit into a stream of requests.
    final cubit = ProductListCubit(filters: const ProductFilters(size: '42'));
    unawaited(cubit.load());
    unawaited(cubit.load());
    unawaited(cubit.load());
    await pumpEventQueue();

    expect(repo.productCalls, 1);

    repo.release();
    await pumpEventQueue();
    expect(cubit.state.status, DataFetchStatus.success);
    await cubit.close();
  });

  test('a genuinely new query still supersedes the one running', () async {
    final cubit = ProductListCubit(filters: const ProductFilters(size: '42'));
    unawaited(cubit.load());
    await pumpEventQueue();
    expect(repo.productCalls, 1);

    unawaited(cubit.apply(const ProductFilters(size: '43')));
    await pumpEventQueue();
    expect(repo.productCalls, 2, reason: 'a new filter is not a duplicate');
    await cubit.close();
  });

  test('a superseded page still clears the footer spinner', () async {
    // Opening a product and coming back leaves the list alive. If a page-2
    // request was superseded on the way, loadingMore stayed true and every
    // later loadMore bailed at the guard — infinite scroll dead, footer
    // spinning, for the life of the screen.
    final cubit = ProductListCubit(filters: const ProductFilters(size: '42'));
    unawaited(cubit.load());
    await pumpEventQueue();
    repo.release();
    await pumpEventQueue();
    expect(cubit.state.hasMore, isTrue);

    unawaited(cubit.loadMore());
    await pumpEventQueue();
    expect(cubit.state.loadingMore, isTrue);

    // Something supersedes it — a filter change, a branch switch.
    unawaited(cubit.apply(const ProductFilters(size: '43')));
    await pumpEventQueue();
    repo.release();
    await pumpEventQueue();

    expect(cubit.state.loadingMore, isFalse,
        reason: 'a superseded page must not leave the footer spinning');
    await cubit.close();
  });

  test('a filter change mid-request still settles', () async {
    // apply() supersedes an in-flight load; the superseded one bows out and the
    // new one has to be the one that emits.
    final cubit = ProductListCubit(filters: const ProductFilters(size: '42'))..load();
    await pumpEventQueue();
    unawaited(cubit.apply(const ProductFilters(size: '43')));
    await pumpEventQueue();

    repo.release();
    await pumpEventQueue();

    expect(cubit.state.status, DataFetchStatus.success,
        reason: 'superseding a request must not strand the screen on waiting');
    await cubit.close();
  });
}

/// Holds every request open until [release] is called, and counts how many
/// were asked for.
class _SlowCatalog implements CatalogRepository {
  final _gates = <Completer<void>>[];
  int productCalls = 0;

  void release() {
    for (final gate in _gates) {
      if (!gate.isCompleted) gate.complete();
    }
  }

  Future<void> _wait() {
    final gate = Completer<void>();
    _gates.add(gate);
    return gate.future;
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
    bool inStockOnly = false,
    bool has360 = false,
    String sortBy = 'name',
    String sortDirection = 'asc',
    int page = 1,
    int perPage = 24,
  }) async {
    productCalls++;
    await _wait();
    return Paginated<Product>(
      items: [Product.fromJson(const {'id': 1, 'name': 'Samba', 'code': 'S1', 'mrp': 480})],
      currentPage: page,
      lastPage: 3,
      total: 60,
      hasMorePages: page < 3,
    );
  }

  @override
  Future<List<Branch>> branches() async => const [];
  @override
  Future<List<ColorOption>> colors() async => const [];
  @override
  Future<List<CategoryOption>> categories({String? size, int? branchId, bool inStockOnly = true}) async => const [];
  @override
  Future<List<SizeOption>> sizes({int? mainCategoryId, int? brandId, int? branchId}) async => const [];
  @override
  Future<List<BrandOption>> brands({int? mainCategoryId, String? size, bool inStockOnly = true}) async => const [];
  @override
  dynamic noSuchMethod(Invocation i) => super.noSuchMethod(i);
}
