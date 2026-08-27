import 'dart:async';

import 'package:fake_async/fake_async.dart';
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

/// A grid somebody is standing in front of has to give up before they do.
///
/// The transport allows twenty-five seconds. A spinner running that long is
/// indistinguishable from a broken app, and there is no way out of it because
/// nothing has failed yet — no message, no Try again, just a spinner. The list
/// gives up at ten and says so.
void main() {
  setUp(() async {
    SharedPreferences.setMockInitialValues({});
    final storage = await LocalStorageService.create();
    final config = AppConfig.resolve(savedBaseUrl: null, savedTenant: null);
    serviceLocator
      ..registerSingleton<LocalStorageService>(storage)
      ..registerSingleton<AppConfig>(config)
      ..registerSingleton<HttpService>(HttpService(config: config))
      ..registerSingleton<CatalogRepository>(_NeverAnswers())
      ..registerSingleton<BranchCubit>(BranchCubit());
  });

  tearDown(() => serviceLocator.reset());

  test('a request that never answers ends in a visible failure', () {
    fakeAsync((async) {
      final cubit = ProductListCubit(filters: const ProductFilters(size: '42'))..load();
      async.flushMicrotasks();
      expect(cubit.state.status, DataFetchStatus.waiting);

      async.elapse(const Duration(seconds: 9));
      expect(cubit.state.status, DataFetchStatus.waiting,
          reason: 'still inside the window');

      async.elapse(const Duration(seconds: 2));
      expect(cubit.state.status, DataFetchStatus.failed);
      expect(cubit.state.errorMessage, isNotNull,
          reason: 'a failure with nothing to read is still a dead end');
    });
  });

  test('the screen can be retried after it gives up', () {
    fakeAsync((async) {
      final cubit = ProductListCubit(filters: const ProductFilters(size: '42'))..load();
      async.elapse(const Duration(seconds: 11));
      expect(cubit.state.status, DataFetchStatus.failed);

      // The in-flight guard must not have latched, or Try again does nothing.
      cubit.load();
      async.flushMicrotasks();
      expect(cubit.state.status, DataFetchStatus.waiting);
    });
  });
}

/// Accepts every request and answers none of them.
class _NeverAnswers implements CatalogRepository {
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
  }) =>
      Completer<Paginated<Product>>().future;

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
