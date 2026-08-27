import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:go_router/go_router.dart';
import 'package:shared_preferences/shared_preferences.dart';
import 'package:showcase/features/catalog/logic/funnel_cubit/funnel_cubit.dart';
import 'package:showcase/shared/domain/constants/app_config.dart';
import 'package:showcase/shared/domain/constants/global_variables.dart';
import 'package:showcase/shared/domain/models/index.dart';
import 'package:showcase/shared/domain/repository/catalog_repository.dart';
import 'package:showcase/shared/logic/branch_cubit/branch_cubit.dart';
import 'package:showcase/shared/logic/connectivity_cubit/connectivity_cubit.dart';
import 'package:showcase/shared/logic/theme_cubit/theme_cubit.dart';
import 'package:showcase/shared/utils/local_storage/local_storage_service.dart';
import 'package:showcase/shared/utils/router/http_utils/http_service.dart';
import 'package:showcase/shared/utils/router/routes.dart';

/// The funnel has to be a stack.
///
/// When every step was a `context.go`, the whole stack was replaced each time,
/// so going back animated as a fresh push in from the right — the same
/// direction as going forward. These assert the shape that makes the movement
/// read correctly: forward leaves something to pop, and back pops it.
void main() {
  setUp(() async {
    SharedPreferences.setMockInitialValues({});
    final storage = await LocalStorageService.create();
    final config = AppConfig.resolve(savedBaseUrl: null, savedTenant: null);
    serviceLocator
      ..registerSingleton<LocalStorageService>(storage)
      ..registerSingleton<AppConfig>(config)
      ..registerSingleton<HttpService>(HttpService(config: config))
      ..registerSingleton<ConnectivityCubit>(ConnectivityCubit())
      ..registerSingleton<CatalogRepository>(_StubCatalog())
      ..registerSingleton<ThemeCubit>(ThemeCubit())
      ..registerSingleton<BranchCubit>(BranchCubit());
  });

  tearDown(() => serviceLocator.reset());

  /// A router over stand-in screens: this is about the navigation shape, not
  /// what the funnel draws.
  ({GoRouter router, Widget app}) harness() {
    Widget stub(String label) => Scaffold(body: Center(child: Text(label)));
    final router = GoRouter(
      initialLocation: Routes.size,
      routes: [
        GoRoute(path: Routes.size, builder: (_, __) => stub('size')),
        GoRoute(path: Routes.brand, builder: (_, __) => stub('brand')),
        GoRoute(path: Routes.results, builder: (_, __) => stub('results')),
      ],
    );
    return (router: router, app: MaterialApp.router(routerConfig: router));
  }

  testWidgets('each step forward leaves a way back', (tester) async {
    final h = harness();
    await tester.pumpWidget(h.app);
    expect(h.router.canPop(), isFalse, reason: 'the size run is the root');

    h.router.push(Routes.brand);
    await tester.pumpAndSettle();
    expect(find.text('brand'), findsOneWidget);
    expect(h.router.canPop(), isTrue);

    h.router.push(Routes.results);
    await tester.pumpAndSettle();
    expect(find.text('results'), findsOneWidget);
    expect(h.router.canPop(), isTrue);
  });

  testWidgets('going back pops rather than replacing', (tester) async {
    final h = harness();
    await tester.pumpWidget(h.app);
    h.router.push(Routes.brand);
    await tester.pumpAndSettle();

    h.router.pop();
    await tester.pumpAndSettle();

    expect(find.text('size'), findsOneWidget);
    // Back at the root: a replace would have left a phantom entry behind.
    expect(h.router.canPop(), isFalse);
  });

  testWidgets('reopening the size run from the results unwinds both steps',
      (tester) async {
    final h = harness();
    await tester.pumpWidget(h.app);
    h.router
      ..push(Routes.brand)
      ..push(Routes.results);
    await tester.pumpAndSettle();

    // What the funnel column and the breadcrumbs do.
    for (var i = FunnelStep.results.index; i > FunnelStep.size.index; i--) {
      if (h.router.canPop()) h.router.pop();
    }
    await tester.pumpAndSettle();

    expect(find.text('size'), findsOneWidget);
    expect(h.router.canPop(), isFalse);
  });

  test('the funnel steps are ordered size, brand, results', () {
    // The pop arithmetic above depends on this order.
    expect(FunnelStep.values, [FunnelStep.size, FunnelStep.brand, FunnelStep.results]);
  });
}

class _StubCatalog implements CatalogRepository {
  @override
  Future<List<Branch>> branches() async => const [];
  @override
  Future<List<SizeOption>> sizes({int? mainCategoryId, int? brandId, int? branchId}) async =>
      const [];
  @override
  Future<List<CategoryOption>> categories({
    String? size,
    int? branchId,
    bool inStockOnly = true,
  }) async =>
      const [];
  @override
  Future<List<BrandOption>> brands({
    int? mainCategoryId,
    String? size,
    bool inStockOnly = true,
  }) async =>
      const [];
  @override
  dynamic noSuchMethod(Invocation i) => super.noSuchMethod(i);
}
