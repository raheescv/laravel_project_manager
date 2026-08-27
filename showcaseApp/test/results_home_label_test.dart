import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:go_router/go_router.dart';
import 'package:shared_preferences/shared_preferences.dart';
import 'package:showcase/shared/logic/funnel_cubit/funnel_cubit.dart';
import 'package:showcase/l10n/app_localizations.dart';
import 'package:showcase/shared/domain/constants/app_config.dart';
import 'package:showcase/shared/domain/constants/global_variables.dart';
import 'package:showcase/shared/domain/models/index.dart';
import 'package:showcase/shared/domain/repository/catalog_repository.dart';
import 'package:showcase/shared/logic/branch_cubit/branch_cubit.dart';
import 'package:showcase/shared/logic/connectivity_cubit/connectivity_cubit.dart';
import 'package:showcase/shared/logic/locale_cubit/locale_cubit.dart';
import 'package:showcase/shared/logic/theme_cubit/theme_cubit.dart';
import 'package:showcase/shared/utils/components/theme/panel_scale.dart';
import 'package:showcase/shared/utils/components/theme/pearl_theme.dart';
import 'package:showcase/shared/utils/local_storage/local_storage_service.dart';
import 'package:showcase/shared/utils/router/app_router.dart';
import 'package:showcase/shared/utils/router/http_utils/http_service.dart';
import 'package:showcase/shared/utils/router/routes.dart';
import 'package:showcase/shared/widgets/pearl_widgets.dart';

/// The way out of the results says what it is.
///
/// A house glyph on its own is a guess — the shop's own front page, a filter
/// for homeware — and this is the one control a customer standing in somebody
/// else's results must not have to work out. It is also the widest label in
/// that bar, so it is the one that decides whether the bar fits.
void main() {
  const panel = Size(1080, 1920);

  late FunnelCubit funnel;
  late GoRouter router;

  Future<void> pump(
    WidgetTester tester, {
    Size size = panel,
    double pixelRatio = 1,
    Locale locale = const Locale('en'),
    double textScale = 1,
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

    tester.view
      ..physicalSize = size * pixelRatio
      ..devicePixelRatio = pixelRatio;
    addTearDown(tester.view.reset);

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
        locale: locale,
        supportedLocales: L.supportedLocales,
        localizationsDelegates: L.localizationsDelegates,
        routerConfig: router,
        builder: (context, child) => PanelScale(
          child: MediaQuery.withClampedTextScaling(
            minScaleFactor: textScale,
            maxScaleFactor: textScale,
            child: child ?? const SizedBox.shrink(),
          ),
        ),
      ),
    ));
    await tester.pumpAndSettle();

    await funnel.chooseSize('42');
    funnel.chooseBrand(const BrandOption(id: 7, name: 'Hoka', productCount: 13));
    router.push(Routes.results);
    await tester.pumpAndSettle();
  }

  Finder homeButton() => find.widgetWithIcon(PearlButton, Icons.home_outlined);

  testWidgets('the way out is named, not just drawn', (tester) async {
    await pump(tester);

    expect(homeButton(), findsOneWidget);
    final button = tester.widget<PearlButton>(homeButton());
    expect(button.label, 'Back to home');
    // Filled in the accent, not a ghost outline: it has to read as the one
    // control in the bar that takes you somewhere.
    expect(button.ghost, isFalse);
    expect(tester.widget<PearlButton>(find.widgetWithIcon(PearlButton, Icons.tune)).ghost,
        isTrue, reason: 'and the one beside it must not compete with it');
  });

  testWidgets('it clears the visit and goes to step one', (tester) async {
    await pump(tester);
    expect(router.state.uri.path, Routes.results);

    await tester.tap(homeButton());
    await tester.pumpAndSettle();

    expect(router.state.uri.path, Routes.size);
    expect(funnel.state.size, isNull);
    expect(funnel.state.brand, isNull);
    expect(router.canPop(), isFalse);
  });

  testWidgets('the filters control is still the one that takes the room',
      (tester) async {
    // The label must not turn the bar into two equal halves: narrowing down is
    // what a customer does here over and over, leaving is what they do once.
    await pump(tester);

    final home = tester.getRect(homeButton());
    final filters = tester.getRect(find.widgetWithIcon(PearlButton, Icons.tune));
    expect(filters.width, greaterThan(home.width));
    expect(home.height, closeTo(filters.height, .5));
    expect(home.top, closeTo(filters.top, .5));
  });

  /// The panel it was designed on, a phone, and the narrowest phone still in
  /// service — each at the largest text size Settings offers, in both
  /// languages. A RenderFlex overflow is a test failure, so this is a harder
  /// check than looking at it.
  const sizes = <String, ({Size size, double ratio})>{
    'the kiosk panel': (size: panel, ratio: 1),
    'an iPhone 17 Pro': (size: Size(402, 874), ratio: 3),
    'the narrowest supported phone': (size: Size(320, 568), ratio: 3),
  };

  for (final entry in sizes.entries) {
    for (final locale in const [Locale('en'), Locale('ar')]) {
      testWidgets(
          'the bar fits ${entry.key} in ${locale.languageCode} at the largest text size',
          (tester) async {
        await pump(
          tester,
          size: entry.value.size,
          pixelRatio: entry.value.ratio,
          locale: locale,
          textScale: ThemeCubit.textScales.last,
        );

        expect(tester.takeException(), isNull);
        expect(homeButton(), findsOneWidget);
      });
    }
  }
}

/// A page of results, so the bar is measured on the screen a customer actually
/// sees rather than on the empty state.
final _rows = List<Product>.generate(
  6,
  (i) => Product.fromJson({
    'id': i + 1,
    'code': 'HK${i + 1}',
    'name': 'Hoka Clifton ${i + 1}',
    'brand': {'id': 7, 'name': 'Hoka'},
    'main_category': {'id': 1, 'name': 'Running'},
    'size': '42',
    'mrp': 549,
    'total_stock': 3,
  }),
);

class _Stub implements CatalogRepository {
  @override
  Future<List<Branch>> branches() async => const [
        Branch(id: 1, name: 'Mall of Qatar', code: 'MOQ', location: 'Mall of Qatar', mobile: ''),
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
      Paginated<Product>(
          items: _rows, currentPage: 1, lastPage: 1, total: _rows.length, hasMorePages: false);
  @override
  dynamic noSuchMethod(Invocation i) => super.noSuchMethod(i);
}
