import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:shared_preferences/shared_preferences.dart';
import 'package:showcase/features/catalog/logic/funnel_cubit/funnel_cubit.dart';
import 'package:showcase/shared/domain/constants/app_config.dart';
import 'package:showcase/shared/domain/constants/global_variables.dart';
import 'package:showcase/shared/domain/models/index.dart';
import 'package:showcase/shared/domain/repository/catalog_repository.dart';
import 'package:showcase/l10n/app_localizations.dart';
import 'package:showcase/shared/logic/branch_cubit/branch_cubit.dart';
import 'package:showcase/shared/logic/locale_cubit/locale_cubit.dart';
import 'package:showcase/shared/logic/connectivity_cubit/connectivity_cubit.dart';
import 'package:showcase/shared/logic/theme_cubit/theme_cubit.dart';
import 'package:showcase/shared/utils/components/theme/pearl_theme.dart';
import 'package:showcase/shared/utils/local_storage/local_storage_service.dart';
import 'package:showcase/shared/utils/router/http_utils/http_service.dart';
import 'package:showcase/shared/widgets/brand_mark.dart';
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
    Locale locale = const Locale('en'),
    double textScale = 1,
  }) async {
    tester.view
      ..physicalSize = size * 3
      ..devicePixelRatio = 3;
    addTearDown(tester.view.reset);

    await tester.pumpWidget(
      MaterialApp(
        theme: buildPearlTheme(PearlPalette.light),
        locale: locale,
        supportedLocales: L.supportedLocales,
        localizationsDelegates: L.localizationsDelegates,
        home: BlocProvider<LocaleCubit>(
          create: (_) => LocaleCubit(),
          child: BlocProvider<FunnelCubit>(
          create: (_) => FunnelCubit(),
          child: BlocProvider<BranchCubit>.value(
            value: serviceLocator<BranchCubit>(),
            child: BlocProvider<ThemeCubit>.value(
              value: serviceLocator<ThemeCubit>(),
              child: BlocProvider<ConnectivityCubit>.value(
                value: serviceLocator<ConnectivityCubit>(),
                child: MediaQuery.withClampedTextScaling(
                  minScaleFactor: textScale,
                  maxScaleFactor: textScale,
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

  testWidgets('the phone bar keeps search inline and the rest in a menu',
      (tester) async {
    await pumpBar(tester, const Size(375, 812), withBreadcrumbs: false);

    // Search earns a field of its own in the control row; what is left over
    // is the pair nobody reaches for mid-browse.
    expect(find.byIcon(Icons.search), findsOneWidget);
    expect(find.byIcon(Icons.more_horiz), findsOneWidget);

    await tester.tap(find.byIcon(Icons.more_horiz));
    await tester.pumpAndSettle();
    expect(find.text('Scan a barcode'), findsOneWidget);
    expect(find.text('Appearance'), findsOneWidget);
  });

  for (final entry in sizes.entries) {
    testWidgets('the top bar survives the largest text size on a ${entry.key}',
        (tester) async {
      // The text-size setting is the thing most likely to break a bar that
      // only just fits: every label grows but the controls around it do not.
      await pumpBar(tester, entry.value, withBreadcrumbs: true, textScale: 1.25);
      expect(tester.takeException(), isNull);
    });
  }

  testWidgets('the language switch sits beside the shop, not in settings',
      (tester) async {
    // It is the first thing a customer needs when the tablet is handed over —
    // a member of staff should not have to go and find a screen for it.
    await pumpBar(tester, const Size(375, 812), withBreadcrumbs: false);

    final lang = tester.getRect(find.byType(LanguagePill));
    final branch = tester.getRect(find.byType(BranchPill));
    expect((lang.center.dy - branch.center.dy).abs(), lessThan(6));

    // Labelled with the language you would get, not the one you are in.
    expect(find.text('العربية'), findsOneWidget);
  });

  testWidgets('the bar keeps its layout in Arabic', (tester) async {
    // Everything below the bar mirrors in Arabic; the chrome does not. The mark
    // stays left of the shop and the back control stays on the left, so the
    // controls do not move out from under a hand mid-tap.
    await pumpBar(tester, const Size(375, 812),
        withBreadcrumbs: true, locale: const Locale('ar'));

    final mark = tester.getRect(find.byType(BrandMark));
    final branch = tester.getRect(find.byType(BranchPill));
    final back = tester.getRect(find.byIcon(Icons.arrow_back));
    final bar = tester.getRect(find.byType(AppTopBar));

    expect(mark.left - bar.left, lessThan(16), reason: 'mark still hard left');
    expect(bar.right - branch.right, lessThan(16), reason: 'shop still hard right');
    expect(back.center.dx, lessThan(bar.center.dx), reason: 'back still on the left');

    // The words themselves are Arabic even though the boxes did not move.
    expect(find.text('English'), findsOneWidget);
  });

  testWidgets('the in-stock control says what it is', (tester) async {
    // A lone checkbox in a bar is a control nobody can name, and this one
    // scopes every count on every screen.
    await pumpBar(tester, const Size(375, 812), withBreadcrumbs: false);
    expect(find.text('In stock'), findsOneWidget);
  });

  testWidgets('the phone bar puts brand and shop above the controls',
      (tester) async {
    // Direction B: row one says who we are and where you are, row two is what
    // you can press. Asserted by geometry, because "two rows" collapses back to
    // one the moment somebody moves a widget between them.
    await pumpBar(tester, const Size(375, 812), withBreadcrumbs: true);

    final mark = tester.getRect(find.byType(BrandMark));
    final branch = tester.getRect(find.byType(BranchPill));
    final stock = tester.getRect(find.byType(StockPill));
    final back = tester.getRect(find.byIcon(Icons.arrow_back));

    // Brand and shop share the top row.
    expect((mark.center.dy - branch.center.dy).abs(), lessThan(6));
    // The controls are strictly below them, not beside them.
    expect(stock.top, greaterThan(mark.bottom - 1));
    expect(back.center.dy, greaterThan(mark.bottom - 1));
    // And the shop name sits to the right of the mark, not under it.
    expect(branch.left, greaterThan(mark.right));

    // Hard against the edges: the mark flush left, the shop flush right. A
    // loose Flexible beside a Spacer left the pill floating mid-row with the
    // slack behind it, which reads as neither aligned nor centred.
    final bar = tester.getRect(find.byType(AppTopBar));
    expect(mark.left - bar.left, lessThan(16));
    expect(bar.right - branch.right, lessThan(16));
  });

  testWidgets('the phone keeps the mark when a back control is present',
      (tester) async {
    // Brand and Results have a back button. The mark used to be the thing that
    // button replaced, so the shop's name vanished for two of the three funnel
    // screens.
    await pumpBar(tester, const Size(375, 812), withBreadcrumbs: true);

    expect(find.byType(BrandMark), findsOneWidget);
    expect(find.byIcon(Icons.arrow_back), findsOneWidget);
    expect(tester.takeException(), isNull);
  });

  testWidgets('the tablet bar leaves the mark to the rail', (tester) async {
    // Two marks on one screen is one too many, and the rail carries it there.
    await pumpBar(tester, const Size(1024, 1366), withBreadcrumbs: true);

    expect(find.byType(BrandMark), findsNothing);
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
