import 'package:flutter_test/flutter_test.dart';
import 'package:shared_preferences/shared_preferences.dart';
import 'package:showcase/features/catalog/logic/funnel_cubit/funnel_cubit.dart';
import 'package:showcase/shared/domain/constants/app_config.dart';
import 'package:showcase/shared/domain/constants/global_variables.dart';
import 'package:showcase/shared/domain/models/index.dart';
import 'package:showcase/shared/domain/repository/catalog_repository.dart';
import 'package:showcase/shared/logic/branch_cubit/branch_cubit.dart';
import 'package:showcase/shared/utils/local_storage/local_storage_service.dart';
import 'package:showcase/shared/utils/router/http_utils/http_service.dart';

/// What the tablet looks like when the next customer picks it up.
///
/// The whole point of the idle reset is that none of the last person's answers
/// survive it, so each one is asserted rather than assumed — a reset that quietly
/// leaves the branch on one shop is worse than no reset, because the next
/// customer is then shown one shop's stock without having chosen it.
void main() {
  late LocalStorageService storage;

  setUp(() async {
    SharedPreferences.setMockInitialValues({});
    storage = await LocalStorageService.create();
    final config = AppConfig.resolve(savedBaseUrl: null, savedTenant: null);
    serviceLocator
      ..registerSingleton<LocalStorageService>(storage)
      ..registerSingleton<AppConfig>(config)
      ..registerSingleton<HttpService>(HttpService(config: config))
      ..registerSingleton<CatalogRepository>(_Stub())
      ..registerSingleton<BranchCubit>(BranchCubit());
  });

  tearDown(() => serviceLocator.reset());

  test('the size and brand the last customer chose are gone', () async {
    final funnel = FunnelCubit();
    await funnel.chooseSize('42');
    funnel.chooseBrand(const BrandOption(id: 7, name: 'Hoka', productCount: 13));
    expect(funnel.state.size, '42');
    expect(funnel.state.brand, isNotNull);

    await funnel.resetForNextCustomer();

    expect(funnel.state.size, isNull);
    expect(funnel.state.brand, isNull);
    expect(funnel.state.brands, isEmpty);
  });

  test('the stock filter goes back on even if it was turned off', () async {
    final funnel = FunnelCubit();
    await funnel.setInStockOnly(false);
    expect(funnel.state.inStockOnly, isFalse);

    await funnel.resetForNextCustomer();

    expect(funnel.state.inStockOnly, isTrue);
  });

  test('the shop goes back to all branches', () async {
    final branch = serviceLocator<BranchCubit>();
    await branch.ready;
    await branch.select(const Branch(
        id: 2, name: 'Galleria', code: 'MG', location: 'Galleria', mobile: ''));
    expect(branch.state.showingAll, isFalse);
    expect(branch.selectedId, 2);

    await branch.selectAll();

    expect(branch.state.showingAll, isTrue);
    // Null is what makes the server answer for the whole chain.
    expect(branch.selectedId, isNull);
    expect(serviceLocator<HttpService>().activeBranchId, isNull);
  });

  test('all branches survives a restart', () async {
    await serviceLocator<BranchCubit>().selectAll();
    expect(storage.branchId, BranchCubit.allBranches);

    // A fresh cubit is what the next launch builds.
    serviceLocator.unregister<BranchCubit>();
    final relaunched = BranchCubit();
    await relaunched.ready;
    expect(relaunched.state.showingAll, isTrue,
        reason: 'a stored 0 is a choice, not an absent branch');
  });
}

class _Stub implements CatalogRepository {
  @override
  Future<List<Branch>> branches() async => const [
        Branch(id: 1, name: 'Mall of Qatar', code: 'MOQ', location: 'Mall of Qatar', mobile: ''),
        Branch(id: 2, name: 'Galleria', code: 'MG', location: 'Galleria', mobile: ''),
      ];
  @override
  Future<List<SizeOption>> sizes({int? mainCategoryId, int? brandId, int? branchId}) async =>
      const [SizeOption(size: '42', group: SizeGroup.adult, stockTotal: 4, inStock: true)];
  @override
  Future<List<CategoryOption>> categories({String? size, int? branchId, bool inStockOnly = true}) async => const [];
  @override
  Future<List<BrandOption>> brands({int? mainCategoryId, String? size, bool inStockOnly = true}) async =>
      const [BrandOption(id: 7, name: 'Hoka', productCount: 13)];
  @override
  dynamic noSuchMethod(Invocation i) => super.noSuchMethod(i);
}
