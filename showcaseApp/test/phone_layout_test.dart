import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:shared_preferences/shared_preferences.dart';
import 'package:showcase/features/catalog/logic/funnel_cubit/funnel_cubit.dart';
import 'package:showcase/shared/domain/constants/app_config.dart';
import 'package:showcase/shared/domain/constants/global_variables.dart';
import 'package:showcase/shared/domain/models/index.dart';
import 'package:showcase/shared/domain/repository/catalog_repository.dart';
import 'package:showcase/shared/logic/branch_cubit/branch_cubit.dart';
import 'package:showcase/shared/logic/connectivity_cubit/connectivity_cubit.dart';
import 'package:showcase/shared/logic/theme_cubit/theme_cubit.dart';
import 'package:showcase/shared/utils/components/theme/pearl_theme.dart';
import 'package:showcase/shared/utils/local_storage/local_storage_service.dart';
import 'package:showcase/shared/utils/router/http_utils/http_service.dart';
import 'package:showcase/shared/widgets/chrome/app_top_bar.dart';
import 'package:showcase/shared/widgets/chrome/funnel_column.dart';

/// The chrome has to survive a phone, not just the tablet it was designed on.
///
/// A RenderFlex overflow is a test failure in Flutter, so pumping the real
/// widgets at a real iPhone's logical size is a harder check than looking at a
/// screenshot — it catches the case nobody thought to open, like a long shop
/// name on the narrowest device still in service.
void main() {
  /// iPhone 17 Pro, iPhone 13 mini, and the narrowest phone worth supporting.
  const sizes = <String, Size>{
    'iPhone 17 Pro': Size(402, 874),
    'iPhone 13 mini': Size(375, 812),
    'narrowest supported': Size(320, 568),
  };

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

  Future<void> pumpBar(
    WidgetTester tester,
    Size size, {
    required bool withBreadcrumbs,
  }) async {
    tester.view
      ..physicalSize = size * 3
      ..devicePixelRatio = 3;
    addTearDown(tester.view.reset);

    await tester.pumpWidget(
      MaterialApp(
        theme: buildPearlTheme(PearlPalette.light),
        home: BlocProvider<FunnelCubit>(
          create: (_) => FunnelCubit(),
          child: BlocProvider<BranchCubit>.value(
            value: serviceLocator<BranchCubit>(),
            child: BlocProvider<ThemeCubit>.value(
              value: serviceLocator<ThemeCubit>(),
              child: BlocProvider<ConnectivityCubit>.value(
                value: serviceLocator<ConnectivityCubit>(),
                child: Scaffold(
                  body: Builder(
                    builder: (context) => AppTopBar(
                      leading: withBreadcrumbs
                          ? const Icon(Icons.arrow_back, size: 38)
                          : null,
                      title: withBreadcrumbs
                          ? FunnelBreadcrumbs(
                              state: const FunnelState(
                                size: '43.5',
                                brand: BrandOption(
                                    id: 1, name: 'New Balance', productCount: 9),
                              ),
                              current: FunnelStep.results,
                              onReopen: (_) {},
                            )
                          : null,
                    ),
                  ),
                ),
              ),
            ),
          ),
        ),
      ),
    );
    await tester.pump();
  }

  for (final entry in sizes.entries) {
    testWidgets('the top bar fits a ${entry.key} on the funnel root', (tester) async {
      await pumpBar(tester, entry.value, withBreadcrumbs: false);
      expect(tester.takeException(), isNull);
    });

    testWidgets('the top bar fits a ${entry.key} with breadcrumbs', (tester) async {
      // The worst case the funnel can produce: a back control, every crumb and
      // the full set of right-hand controls on the narrowest screen.
      await pumpBar(tester, entry.value, withBreadcrumbs: true);
      expect(tester.takeException(), isNull);
    });
  }

  testWidgets('the phone bar folds search, scan and appearance into one menu',
      (tester) async {
    await pumpBar(tester, const Size(375, 812), withBreadcrumbs: false);

    // Three separate icons would not fit beside the branch name; one would.
    expect(find.byIcon(Icons.more_horiz), findsOneWidget);
    expect(find.byIcon(Icons.search), findsNothing);

    await tester.tap(find.byIcon(Icons.more_horiz));
    await tester.pumpAndSettle();
    expect(find.text('Search'), findsOneWidget);
    expect(find.text('Scan a barcode'), findsOneWidget);
    expect(find.text('Appearance'), findsOneWidget);
  });

  testWidgets('the tablet bar keeps its inline search and scanner', (tester) async {
    await pumpBar(tester, const Size(1024, 1366), withBreadcrumbs: false);

    expect(find.byIcon(Icons.qr_code_scanner_outlined), findsOneWidget);
    expect(find.byIcon(Icons.more_horiz), findsNothing);
    expect(tester.takeException(), isNull);
  });
}

/// Every catalog read answers empty: these tests are about layout, and a real
/// request would make them depend on a server being up.
class _StubCatalog implements CatalogRepository {
  @override
  Future<List<Branch>> branches() async => const [
        Branch(
          id: 1,
          // Long on purpose — a short shop name would hide the overflow this
          // test exists to catch.
          name: 'Doha Festival City Mall — Ground Floor',
          code: 'DFC',
          location: 'Doha Festival City Mall — Ground Floor',
          mobile: '',
        ),
      ];

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
  dynamic noSuchMethod(Invocation invocation) => super.noSuchMethod(invocation);
}
