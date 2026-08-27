import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:go_router/go_router.dart';
import 'package:shared_preferences/shared_preferences.dart';
import 'package:showcase/shared/logic/funnel_cubit/funnel_cubit.dart';
import 'package:showcase/shared/utils/router/funnel_navigation.dart';
import 'package:showcase/l10n/app_localizations.dart';
import 'package:showcase/shared/domain/constants/app_config.dart';
import 'package:showcase/shared/domain/constants/global_variables.dart';
import 'package:showcase/shared/domain/models/index.dart';
import 'package:showcase/shared/domain/repository/catalog_repository.dart';
import 'package:showcase/shared/logic/branch_cubit/branch_cubit.dart';
import 'package:showcase/shared/logic/connectivity_cubit/connectivity_cubit.dart';
import 'package:showcase/shared/logic/locale_cubit/locale_cubit.dart';
import 'package:showcase/shared/logic/theme_cubit/theme_cubit.dart';
import 'package:showcase/shared/utils/components/theme/pearl_theme.dart';
import 'package:showcase/shared/utils/local_storage/local_storage_service.dart';
import 'package:showcase/shared/utils/router/app_router.dart';
import 'package:showcase/shared/utils/router/http_utils/http_service.dart';
import 'package:showcase/shared/utils/router/routes.dart';
import 'package:showcase/shared/widgets/branch_picker.dart';
import 'package:showcase/shared/widgets/chrome/idle_reset.dart';

/// The wait actually returning the panel to the start.
///
/// `idle_reset_test` proves the clock, `idle_defaults_test` proves what a reset
/// clears. Neither proves the two are wired to each other, and the wiring is
/// the part a customer sees: the timer firing has to land the next person on
/// step one of an empty funnel, from wherever the last one left off, with the
/// last one's sheet closed and no way to walk back into their visit.
///
/// This runs the real router and the real `onIdle` the app installs, over a
/// stub catalogue.
void main() {
  late GoRouter router;
  late FunnelCubit funnel;

  /// The app's own shape: `createRouter` under an `IdleReset` whose `onIdle`
  /// clears the visit and goes to the size run — `app.dart`'s `_returnHome`.
  Future<void> pump(
    WidgetTester tester, {
    Duration after = const Duration(minutes: 10),
  }) async {
    SharedPreferences.setMockInitialValues({});
    final config = AppConfig.resolve(savedBaseUrl: null, savedTenant: null);
    serviceLocator
      ..registerSingleton<LocalStorageService>(await LocalStorageService.create())
      ..registerSingleton<AppConfig>(config)
      ..registerSingleton<HttpService>(HttpService(config: config))
      ..registerSingleton<CatalogRepository>(_Stub())
      ..registerSingleton<ConnectivityCubit>(ConnectivityCubit())
      ..registerSingleton<ThemeCubit>(ThemeCubit())
      ..registerSingleton<BranchCubit>(BranchCubit());
    await serviceLocator<BranchCubit>().load();
    addTearDown(() => serviceLocator.reset());

    funnel = FunnelCubit();
    addTearDown(funnel.close);
    router = createRouter();
    addTearDown(router.dispose);

    await tester.pumpWidget(MultiBlocProvider(
      providers: [
        BlocProvider<LocaleCubit>(create: (_) => LocaleCubit()),
        BlocProvider<FunnelCubit>.value(value: funnel),
        BlocProvider<BranchCubit>.value(value: serviceLocator<BranchCubit>()),
        BlocProvider<ThemeCubit>.value(value: serviceLocator<ThemeCubit>()),
        BlocProvider<ConnectivityCubit>.value(
            value: serviceLocator<ConnectivityCubit>()),
      ],
      child: MaterialApp.router(
        theme: buildPearlTheme(PearlPalette.light),
        supportedLocales: L.supportedLocales,
        localizationsDelegates: L.localizationsDelegates,
        routerConfig: router,
        builder: (context, child) => IdleReset(
          after: after,
          onIdle: () async {
            await clearForNextCustomer(context);
            router.go(Routes.size);
          },
          child: child ?? const SizedBox.shrink(),
        ),
      ),
    ));
    await tester.pumpAndSettle();
  }

  /// Three screens deep with a size and a brand chosen, which is where a
  /// customer who walks off actually leaves it.
  Future<void> walkIn(WidgetTester tester) async {
    await funnel.chooseSize('42');
    router.push(Routes.brand);
    await tester.pumpAndSettle();
    funnel.chooseBrand(const BrandOption(id: 7, name: 'Hoka', productCount: 13));
    router.push(Routes.results);
    await tester.pumpAndSettle();
  }

  testWidgets('the wait lands the panel back on the size run', (tester) async {
    await pump(tester);
    await walkIn(tester);
    expect(router.state.uri.path, Routes.results);

    // Short of the wait by a margin: settling the screens above spends a
    // little of the test clock too, and this is about the wait, not the frame
    // it lands on.
    await tester.pump(const Duration(minutes: 8));
    expect(router.state.uri.path, Routes.results,
        reason: 'still inside the wait');

    await tester.pump(const Duration(minutes: 3));
    await tester.pumpAndSettle();
    expect(router.state.uri.path, Routes.size);
  });

  testWidgets('nothing the last customer chose is left on it', (tester) async {
    await pump(tester);
    await walkIn(tester);

    await tester.pump(const Duration(minutes: 11));
    await tester.pumpAndSettle();

    expect(funnel.state.size, isNull);
    expect(funnel.state.brand, isNull);
    expect(funnel.state.inStockOnly, isTrue);
    expect(serviceLocator<BranchCubit>().state.showingAll, isTrue);
  });

  testWidgets('there is no walking back into the last visit', (tester) async {
    await pump(tester);
    await walkIn(tester);

    await tester.pump(const Duration(minutes: 11));
    await tester.pumpAndSettle();

    // `go`, not a pop: the screens behind are holding answers just cleared.
    expect(router.canPop(), isFalse);
  });

  testWidgets('a sheet the last customer left open goes with them',
      (tester) async {
    await pump(tester);
    router.push(Routes.brand);
    await tester.pumpAndSettle();
    showBranchPicker(tester.element(find.byType(Navigator).last));
    await tester.pumpAndSettle();
    expect(find.byType(BottomSheet), findsOneWidget);

    await tester.pump(const Duration(minutes: 11));
    await tester.pumpAndSettle();

    expect(router.state.uri.path, Routes.size);
    expect(find.byType(BottomSheet), findsNothing);
  });

  testWidgets('the wait set in Settings is the wait used', (tester) async {
    await pump(tester, after: const Duration(minutes: 2));
    await walkIn(tester);

    await tester.pump(const Duration(minutes: 1));
    expect(router.state.uri.path, Routes.results,
        reason: 'the ten-minute default is not what was asked for');

    await tester.pump(const Duration(minutes: 2));
    await tester.pumpAndSettle();
    expect(router.state.uri.path, Routes.size);
  });

  testWidgets('a customer still typing a search is not sent home',
      (tester) async {
    // The search field is the one place in the app where somebody can be busy
    // for minutes without the panel seeing a single pointer event.
    await pump(tester);
    router.push(Routes.search);
    await tester.pumpAndSettle();

    await tester.pump(const Duration(minutes: 9));
    await tester.enterText(find.byType(TextField), 'hoka');
    await tester.pump(const Duration(minutes: 9));

    expect(router.state.uri.path, Routes.search);

    await tester.pump(const Duration(minutes: 2));
    await tester.pumpAndSettle();
    expect(router.state.uri.path, Routes.size,
        reason: 'and once they really have gone, it still resets');
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
  Future<List<CategoryOption>> categories({String? size, int? branchId, bool inStockOnly = true}) async =>
      const [];
  @override
  Future<List<BrandOption>> brands({int? mainCategoryId, String? size, bool inStockOnly = true}) async =>
      const [BrandOption(id: 7, name: 'Hoka', productCount: 13)];
  @override
  Future<List<ColorOption>> colors() async => const [];
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
  }) async =>
      const Paginated<Product>(
          items: [], currentPage: 1, lastPage: 1, total: 0, hasMorePages: false);
  @override
  dynamic noSuchMethod(Invocation i) => super.noSuchMethod(i);
}
