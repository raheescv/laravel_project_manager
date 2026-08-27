import 'package:flutter_test/flutter_test.dart';
import 'package:shared_preferences/shared_preferences.dart';
import 'package:showcase/shared/logic/product_list_cubit/product_list_cubit.dart';
import 'package:showcase/shared/domain/constants/app_config.dart';
import 'package:showcase/shared/domain/constants/global_variables.dart';
import 'package:showcase/shared/domain/models/index.dart';
import 'package:showcase/shared/domain/repository/catalog_repository.dart';
import 'package:showcase/shared/logic/branch_cubit/branch_cubit.dart';
import 'package:showcase/shared/utils/local_storage/local_storage_service.dart';
import 'package:showcase/shared/utils/router/http_utils/http_service.dart';

/// "Has a 360° view" is a question only the server can answer.
///
/// The spin frames are a detail-view payload — a list row has never carried
/// them — so the filter used to keep the rows whose frames it could count, of
/// which there were none, and every product in the catalogue disappeared the
/// moment the toggle went on. Same reason the 360° badge never appeared on a
/// card. `has_360` is the server's own answer and rides on every row.
void main() {
  late _Spy repo;

  setUp(() async {
    SharedPreferences.setMockInitialValues({});
    final config = AppConfig.resolve(savedBaseUrl: null, savedTenant: null);
    repo = _Spy();
    serviceLocator
      ..registerSingleton<LocalStorageService>(await LocalStorageService.create())
      ..registerSingleton<AppConfig>(config)
      ..registerSingleton<HttpService>(HttpService(config: config))
      ..registerSingleton<CatalogRepository>(repo)
      ..registerSingleton<BranchCubit>(BranchCubit());
  });

  tearDown(() => serviceLocator.reset());

  test('a list row knows it can be spun without carrying the frames', () {
    // What the list endpoint sends: no images360, one boolean.
    final row = Product.fromJson({'id': 1, 'name': 'Samba', 'has_360': true});
    expect(row.images360, isEmpty);
    expect(row.hasSpin, isTrue);

    expect(Product.fromJson({'id': 2, 'name': 'Gazelle', 'has_360': false}).hasSpin, isFalse);
  });

  test('a server that has never heard of the flag is not read as "no spin"', () {
    expect(Product.fromJson({'id': 3, 'name': 'Campus'}).has360, isNull);
  });

  test('the frames win wherever there are frames', () {
    // The product page must never offer a viewer it has nothing to put in, so
    // a detail payload is judged on what it actually holds.
    final one = Product.fromJson({
      'id': 4,
      'name': 'Forum',
      'has_360': true,
      'images360': [
        {'id': 1, 'url': 'a.png', 'degree': 0},
      ],
    });
    expect(one.hasSpin, isFalse, reason: 'one frame is not a sequence');
  });

  test('the toggle is asked of the server, not applied to what came back',
      () async {
    final cubit = ProductListCubit(
      filters: const ProductFilters(size: '42', spinOnly: true),
    );
    await cubit.load();
    addTearDown(cubit.close);

    expect(repo.lastHas360, isTrue);
    // Every row the server sent survives: it already answered the question.
    expect(cubit.state.items, hasLength(2));
    expect(cubit.state.total, 2);
  });

  test('off, it is not asked at all', () async {
    final cubit = ProductListCubit(filters: const ProductFilters(size: '42'));
    await cubit.load();
    addTearDown(cubit.close);

    expect(repo.lastHas360, isFalse);
  });
}

/// Answers two rows and remembers what it was asked.
class _Spy implements CatalogRepository {
  bool? lastHas360;

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
    lastHas360 = has360;
    return Paginated<Product>(
      items: [
        // Deliberately without `has_360`, the way an older server answers: the
        // rows must still reach the grid rather than being filtered out here.
        Product.fromJson({'id': 1, 'name': 'Samba'}),
        Product.fromJson({'id': 2, 'name': 'Gazelle'}),
      ],
      currentPage: 1,
      lastPage: 1,
      total: 2,
      hasMorePages: false,
    );
  }

  @override
  Future<List<Branch>> branches() async => const [];

  @override
  dynamic noSuchMethod(Invocation invocation) => super.noSuchMethod(invocation);
}
