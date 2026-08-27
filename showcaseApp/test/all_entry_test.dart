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
import 'package:showcase/shared/widgets/chrome/showcase_scaffold.dart';
import 'package:showcase/shared/widgets/pearl_widgets.dart';

/// Both funnel questions offer "all of them", and both offer it as the first
/// answer in the grid rather than as a button pinned underneath.
///
/// A screen that asks its question with a wall of targets and then answers it
/// again with a differently-shaped control somewhere else is asking twice. It
/// also made "I don't know my size" look like the way *out* of the screen
/// rather than one of the answers on it.
void main() {
  const panel = Size(1080, 1920);

  late FunnelCubit funnel;
  late GoRouter router;

  /// The real router, so tapping an answer navigates the way it does in the
  /// app: both of these tiles are a funnel step, not a local selection.
  Future<void> pump(WidgetTester tester) async {
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
      ..physicalSize = panel
      ..devicePixelRatio = 1;
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
        supportedLocales: L.supportedLocales,
        localizationsDelegates: L.localizationsDelegates,
        routerConfig: router,
        builder: (context, child) => PanelScale(child: child ?? const SizedBox()),
      ),
    ));
    await tester.pumpAndSettle();
  }

  group('the size run', () {
    testWidgets('leads with All, and All is the answer in force', (tester) async {
      await pump(tester);

      final plates = tester.widgetList<PearlChip>(find.byType(PearlChip)).toList();
      expect(plates.first.label, 'All');
      // Nothing chosen yet is not "unanswered" — it is every size, and the run
      // shows the answer currently in force the same way it does for a size a
      // customer came back to change.
      expect(plates.first.selected, isTrue);
      expect(plates.where((c) => c.selected).length, 1);

      // Same plate as the answer beside it, not a button in a different shape.
      // Against its neighbour rather than against a named size: the run is
      // ordered by the catalogue, and this is about the shape of the tile.
      final all = tester.getRect(find.byWidget(plates.first));
      final next = tester.getRect(find.byWidget(plates[1]));
      expect(all.width, closeTo(next.width, .5));
      expect(all.height, closeTo(next.height, .5));
      expect(all.top, closeTo(next.top, .5));
      expect(all.left, lessThan(next.left));
    });

    testWidgets('the question is asked once — no pinned bar under it',
        (tester) async {
      await pump(tester);
      expect(find.byType(PinnedBar), findsNothing);
    });

    testWidgets('tapping All skips the size and moves on', (tester) async {
      await pump(tester);
      await tester.tap(find.widgetWithText(PearlChip, 'All'));
      await tester.pumpAndSettle();

      // Skipped, not chosen — and it is a funnel step like any other answer.
      expect(funnel.state.size, isNull);
      expect(router.state.uri.path, Routes.brand);
    });

    testWidgets('a chosen size takes the mark off All', (tester) async {
      await pump(tester);
      await funnel.chooseSize('38');
      await tester.pumpAndSettle();

      final plates = tester.widgetList<PearlChip>(find.byType(PearlChip)).toList();
      expect(plates.first.label, 'All');
      expect(plates.first.selected, isFalse, reason: 'All is no longer in force');
      expect(plates.firstWhere((c) => c.label == '38').selected, isTrue);
    });
  });

  group('the brand grid', () {
    /// Brands are only loaded by answering the size question, so this walks in
    /// rather than mounting the screen cold.
    ///
    /// Pushed rather than tapped: `goToFunnelStep` swallows a second push of
    /// the same path inside 900ms of real time, which is a double-tap guard on
    /// the shop floor and a shared static between tests in here.
    Future<void> openBrands(WidgetTester tester) async {
      await pump(tester);
      await funnel.skipSize();
      router.push(Routes.brand);
      await tester.pumpAndSettle();
    }

    testWidgets('leads with All brands, counted like every other tile',
        (tester) async {
      await openBrands(tester);

      // Captioned the way the results grid captions a product — raised, under
      // the stage, in the same style. The wall is that grid now, and a tile
      // that set its name differently would be announcing it is not one of
      // them.
      expect(find.text('ALL BRANDS'), findsOneWidget);
      // The wall added up, so "All brands, 60" reads against "Nike, 38" — which
      // is the comparison a customer is actually making.
      expect(find.text('60'), findsOneWidget);
      // The bar under the wall is the way out and nothing else. "Show every
      // brand" used to be pinned down there, which asked the screen's question
      // a second time in a different shape; the one control left is Home.
      final pinned = find.descendant(
          of: find.byType(PinnedBar), matching: find.byType(PearlButton));
      expect(pinned, findsOneWidget);
      expect(
          find.descendant(of: pinned, matching: find.byIcon(Icons.home_outlined)),
          findsOneWidget);
    });

    testWidgets('the leading tile sits first and matches the others',
        (tester) async {
      await openBrands(tester);

      final all = tester.getRect(find.text('ALL BRANDS'));
      final nike = tester.getRect(find.text('NIKE'));
      expect(all.left, lessThan(nike.left));
      expect(all.top, closeTo(nike.top, 1));
    });

    testWidgets('tapping it takes every brand through to the results',
        (tester) async {
      await openBrands(tester);
      await tester.tap(find.text('ALL BRANDS'));
      await tester.pumpAndSettle();

      expect(funnel.state.brand, isNull);
      expect(router.state.uri.path, Routes.results);
    });
  });

  group('the results bar', () {
    testWidgets('the controls that move you are the ones drawn in the accent',
        (tester) async {
      await pump(tester);
      await funnel.chooseSize('38');
      router.push(Routes.results);
      await tester.pumpAndSettle();

      IconSquare square(IconData icon) =>
          tester.widget<IconSquare>(find.widgetWithIcon(IconSquare, icon));

      // Back takes a customer somewhere; on a panel with the system bars
      // hidden it is one of only two things that can, so it is emphasised.
      expect(square(Icons.arrow_back).prominent, isTrue);
      // The other is Home, which is no longer a square at all — it carries its
      // name, and it is the one filled block in the bar.
      final home = tester.widget<PearlButton>(
          find.widgetWithIcon(PearlButton, Icons.home_outlined));
      expect(home.label, isNotEmpty);
      expect(home.ghost, isFalse);
      // The sort direction changes what you are looking at. If every control in
      // the frame is emphasised then none of them is.
      // Ascending by default, so the toggle is showing its up arrow.
      expect(square(Icons.arrow_upward).prominent, isFalse);
      expect(
          tester
              .widget<PearlButton>(find.widgetWithIcon(PearlButton, Icons.tune))
              .ghost,
          isTrue);
    });

    testWidgets('Home sits beside the filter and clears the visit',
        (tester) async {
      await pump(tester);
      await funnel.chooseSize('38');
      router.push(Routes.results);
      await tester.pumpAndSettle();

      // Same height as the control it shares the bar with, and to its left.
      // By its icon: the empty results state carries a ghost button too.
      final home =
          tester.getRect(find.widgetWithIcon(PearlButton, Icons.home_outlined));
      final filter =
          tester.getRect(find.widgetWithIcon(PearlButton, Icons.tune));
      expect(home.height, closeTo(filter.height, .5));
      expect(home.left, lessThan(filter.left));
      // Narrower, still: narrowing down is what a customer does here over and
      // over, leaving is what they do once.
      expect(home.width, lessThan(filter.width));

      await tester.tap(find.widgetWithIcon(PearlButton, Icons.home_outlined));
      await tester.pumpAndSettle();

      expect(router.state.uri.path, Routes.size);
      expect(router.canPop(), isFalse);
      expect(funnel.state.size, isNull);
      expect(funnel.state.inStockOnly, isTrue);
    });
  });
}

class _Stub implements CatalogRepository {
  @override
  Future<List<Branch>> branches() async => const [
        Branch(id: 1, name: 'Doha', code: 'DOH', location: 'Doha', mobile: ''),
      ];

  @override
  Future<List<SizeOption>> sizes({int? mainCategoryId, int? brandId, int? branchId}) async => [
        for (final label in ['36', '37', '38', '39', '40', '41'])
          SizeOption(size: label, group: SizeGroup.adult, stockTotal: 4, inStock: true),
      ];

  @override
  Future<List<BrandOption>> brands({
    int? mainCategoryId,
    String? size,
    bool inStockOnly = true,
  }) async =>
      const [
        BrandOption(id: 1, name: 'Nike', productCount: 38),
        BrandOption(id: 2, name: 'Asics', productCount: 22),
      ];

  @override
  Future<List<CategoryOption>> categories({
    String? size,
    int? branchId,
    bool inStockOnly = true,
  }) async =>
      const [];

  // Reached only by walking through to the results, which the last test does.
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
      Paginated.empty<Product>();

  @override
  dynamic noSuchMethod(Invocation invocation) => super.noSuchMethod(invocation);
}
