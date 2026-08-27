import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:shared_preferences/shared_preferences.dart';
import 'package:showcase/shared/logic/funnel_cubit/funnel_cubit.dart';
import 'package:showcase/features/catalog/screens/brand_screen.dart';
import 'package:showcase/l10n/app_localizations.dart';
import 'package:showcase/l10n/app_localizations_en.dart';
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
import 'package:showcase/shared/utils/router/http_utils/http_service.dart';
import 'package:showcase/shared/widgets/chrome/showcase_scaffold.dart';
import 'package:showcase/shared/widgets/pearl_widgets.dart';

/// The brand step is a wall of marks drawn on the results grid: a square stage
/// holding a logo, the count badged on its corner, and the brand's name
/// captioned underneath it the way a product tile captions its own.
///
/// What makes it that rather than a table of brands — the stage is square, the
/// name is outside it and set like a product's, the wall takes its columns from
/// the same Appearance setting the results do — is invisible to a compiler, and
/// so is the one thing the stage carries: the count, in the customer's size
/// rather than in the catalogue. All of it is asserted here, along with the one
/// thing that is not visible at rest: the plinths are ink, and ink escapes the
/// list it is drawn in unless the tile owns a Material of its own.
void main() {
  setUp(() async {
    SharedPreferences.setMockInitialValues({});
    final config = AppConfig.resolve(savedBaseUrl: null, savedTenant: null);
    serviceLocator
      ..registerSingleton<LocalStorageService>(await LocalStorageService.create())
      ..registerSingleton<AppConfig>(config)
      ..registerSingleton<HttpService>(HttpService(config: config))
      ..registerSingleton<CatalogRepository>(_StubCatalog())
      ..registerSingleton<ConnectivityCubit>(ConnectivityCubit())
      ..registerSingleton<ThemeCubit>(ThemeCubit())
      ..registerSingleton<BranchCubit>(BranchCubit());
  });

  tearDown(() => serviceLocator.reset());

  /// The brand step, with the brands already landed.
  Future<void> pumpWall(WidgetTester tester, {Size size = const Size(720, 1280)}) async {
    tester.view
      ..physicalSize = size * 3
      ..devicePixelRatio = 3;
    addTearDown(tester.view.reset);

    final funnel = FunnelCubit();
    addTearDown(funnel.close);
    await funnel.loadBrands();

    await tester.pumpWidget(MaterialApp(
      theme: buildPearlTheme(PearlPalette.light),
      supportedLocales: L.supportedLocales,
      localizationsDelegates: L.localizationsDelegates,
      home: MultiBlocProvider(
        providers: [
          BlocProvider<LocaleCubit>(create: (_) => LocaleCubit()),
          BlocProvider<FunnelCubit>.value(value: funnel),
          BlocProvider<BranchCubit>.value(value: serviceLocator<BranchCubit>()),
          BlocProvider<ThemeCubit>.value(value: serviceLocator<ThemeCubit>()),
          BlocProvider<ConnectivityCubit>.value(
              value: serviceLocator<ConnectivityCubit>()),
        ],
        child: const BrandScreen(),
      ),
    ));
    await tester.pump();
  }

  /// The cards. `Ink` is the card's ground and nothing else on this screen
  /// uses one.
  final cards = find.byType(Ink);

  /// Where every card on screen is, in order.
  List<Rect> cardRects(WidgetTester tester) => [
        for (var i = 0; i < tester.widgetList(cards).length; i++)
          tester.getRect(cards.at(i)),
      ];

  testWidgets('every card is square', (tester) async {
    await pumpWall(tester);
    expect(cards, findsWidgets);
    for (final (i, box) in cardRects(tester).indexed) {
      expect(box.width, closeTo(box.height, .5),
          reason: 'card $i should be square, got $box');
    }
  });

  testWidgets('the name sits under the card, not inside it', (tester) async {
    await pumpWall(tester);
    // Whichever row Nike lands in, its name must clear the bottom of the stage
    // it belongs to. Set like a product caption — raised, and hard against the
    // reading edge of the tile rather than centred under it.
    final name = tester.getRect(find.text('NIKE'));
    expect(find.text('Nike'), findsNothing);
    final column = cardRects(tester)
        .where((r) => r.left <= name.left + 1 && r.right >= name.right - 1)
        .toList();
    expect(column, isNotEmpty, reason: 'no card sits in the name\'s column');
    expect(column.any((card) => card.bottom <= name.top), isTrue,
        reason: 'the name overlaps every card in its column');
    expect(column.any((card) => (card.left - name.left).abs() < 1), isTrue,
        reason: 'the name should start where its stage does');
  });

  testWidgets('the caption is set like a product caption', (tester) async {
    await pumpWall(tester);
    // The same style object the results grid names its products with, at the
    // same fraction of the tile — so a wall of brands and a wall of products
    // read as one grid rather than two.
    final card = cardRects(tester).first;
    final name = tester.widget<Text>(find.text('NIKE'));
    expect(name.style?.fontSize,
        closeTo((card.width * .034).clamp(10.5, 17.0), .01));
    expect(name.maxLines, 2);
  });

  testWidgets('every card badges its count', (tester) async {
    await pumpWall(tester);
    // Each brand's own figure — already scoped to the size and the store by the
    // server — and the wall added up on the tile that leads it.
    for (final count in ['38', '12', '7', '57']) {
      expect(find.text(count), findsOneWidget,
          reason: '$count should be badged on the brand wall');
    }
  });

  testWidgets('the count rides the top corner of its own card', (tester) async {
    await pumpWall(tester);
    // Inside the card and clear of the mark: over it, at the top, and at the
    // end of the row the page reads towards.
    final badge = tester.getRect(find.text('38'));
    final card = cardRects(tester).where((r) => r.overlaps(badge)).toList();
    expect(card, hasLength(1), reason: 'the badge should sit on exactly one card');
    expect(card.single.contains(badge.topLeft), isTrue);
    expect(card.single.contains(badge.bottomRight), isTrue);
    expect(badge.center.dy, lessThan(card.single.center.dy));
    expect(badge.center.dx, greaterThan(card.single.center.dx));
  });

  testWidgets('the wall leads with All brands', (tester) async {
    await pumpWall(tester);
    final all = tester.getRect(find.text('ALL BRANDS'));
    final nike = tester.getRect(find.text('NIKE'));
    expect(all.top, lessThanOrEqualTo(nike.top));
    expect(find.byIcon(Icons.sell_outlined), findsOneWidget);
  });

  /// How many cards share the top row.
  int acrossTheTop(WidgetTester tester) {
    final tops = cardRects(tester).map((r) => r.top).toList();
    return tops.where((t) => (t - tops.first).abs() < 1).length;
  }

  testWidgets('the wall is as dense as the results grid', (tester) async {
    // Appearance's "products per row", not a rule of the brand screen's own.
    // The wall used to be three across on a width rule while the results behind
    // it were two, so stepping from one to the other changed the grid under the
    // customer.
    await pumpWall(tester);
    expect(acrossTheTop(tester), ThemeCubit.defaultProductColumns);
  });

  testWidgets('a denser results grid makes a denser wall', (tester) async {
    await serviceLocator<ThemeCubit>().setProductColumns(4);
    await pumpWall(tester);
    expect(acrossTheTop(tester), 4);
  });

  testWidgets('the cards cannot paint outside the list', (tester) async {
    await pumpWall(tester);
    // `Ink` paints into the nearest Material *ancestor*, and a Material paints
    // its ink features itself — outside the clip the list applies to its
    // children. With the Scaffold's Material as the nearest one, every plinth
    // on this wall was drawn across the top bar as the grid scrolled under it.
    // Each tile owning a Material puts that ink layer inside the viewport.
    final nearestInkLayer = find
        .ancestor(of: cards.first, matching: find.byType(Material))
        .first;
    final list =
        find.ancestor(of: cards.first, matching: find.byType(ListView)).first;
    expect(
      tester.getSize(nearestInkLayer).height,
      lessThan(tester.getSize(list).height),
      reason: 'the ink layer nearest a card should be the card, not the page',
    );
  });

  testWidgets('the question is asked in capitals', (tester) async {
    await pumpWall(tester);
    // Raised as it is drawn, not as it is written: the string table keeps
    // ordinary sentence case for whoever edits the translations. The weight
    // goes with the case — a light capital at this size reads thin rather than
    // quiet — and in Arabic, which has no case to raise, the weight is the
    // whole of the difference the script allows.
    final asked = LEn().whichBrand.toUpperCase();
    expect(find.text(asked), findsOneWidget);
    expect(find.text(LEn().whichBrand), findsNothing);
    expect(tester.widget<Text>(find.text(asked)).style?.fontWeight,
        FontWeight.w700);
  });

  testWidgets('the way out is pinned under the wall', (tester) async {
    await pumpWall(tester);
    // A customer who walks up to a panel left on this step can start their own
    // visit without pressing Back through a stranger's answer. Below every
    // card, in the bar, where the results screen keeps the same control.
    final label = LEn().backToHome.toUpperCase();
    final home = find.text(label);
    expect(home, findsOneWidget);
    final lowestCard =
        cardRects(tester).map((r) => r.bottom).reduce((a, b) => a > b ? a : b);
    expect(tester.getRect(home).top, greaterThan(lowestCard));

    // The whole width of the bar. It shares the row with Filter and sort on the
    // results screen; here it has nothing to share with, and a share sized for
    // that pair ellipsised the label down to "BAC…" — the one control on the
    // panel that must not need working out.
    final button = find.ancestor(of: home, matching: find.byType(PearlButton));
    final bar = tester.getRect(find.byType(PinnedBar));
    expect(tester.getRect(button).width,
        closeTo(bar.width - PearlMetrics.pad * 2, 1));
    expect(tester.widget<Text>(home).overflow, TextOverflow.ellipsis);
    expect(find.textContaining('\u2026'), findsNothing,
        reason: 'nothing on the wall should be ellipsised');
  });
}

/// Three brands with counts that would show if anything still drew them, plus
/// the leading "All brands" tile the screen adds itself.
class _StubCatalog implements CatalogRepository {
  @override
  Future<List<BrandOption>> brands({
    int? mainCategoryId,
    String? size,
    bool inStockOnly = true,
  }) async =>
      const [
        BrandOption(id: 1, name: 'Adidas', productCount: 38),
        BrandOption(id: 2, name: 'Asics', productCount: 12),
        BrandOption(id: 3, name: 'Nike', productCount: 7),
      ];

  @override
  Future<List<Branch>> branches() async => const [
        Branch(id: 1, name: 'Doha', code: 'DOH', location: 'Doha', mobile: ''),
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
  dynamic noSuchMethod(Invocation invocation) => super.noSuchMethod(invocation);
}
