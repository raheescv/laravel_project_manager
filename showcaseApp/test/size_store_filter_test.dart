import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import 'package:flutter_test/flutter_test.dart';
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
import 'package:showcase/shared/widgets/pearl_widgets.dart';

/// The size run is a filter, not a label.
///
/// A customer standing at the panel asks one question of the availability
/// strip: where can I get *this size*. Listing every shop that has the shoe in
/// anything at all is the wrong answer to it — it sends someone across a mall
/// for a 38. So the strip answers for whatever the size run is set to, and for
/// the style as a whole only when nothing is set.
Product _product() => Product.fromJson({
      'id': 1,
      'name': 'Samba OG',
      'code': 'S1',
      'mrp': 480,
      'size': '42',
      // This row is the 42, so its own inventory rows only ever describe 42s.
      'inventories': [
        {
          'branch': {'id': 1, 'name': 'Galleria', 'code': 'GAL'},
          'quantity': 3,
        },
      ],
      'related_sizes': [
        {
          'size': '41',
          'total_stock': 5,
          'is_out_of_stock': false,
          'branches': [
            {'id': 2, 'name': 'Lusail', 'quantity': 5},
          ],
        },
        {
          'size': '42',
          'total_stock': 3,
          'is_out_of_stock': false,
          'branches': [
            {'id': 1, 'name': 'Galleria', 'quantity': 3},
          ],
        },
        {
          'size': '43',
          'total_stock': 0,
          'is_out_of_stock': true,
          'branches': [
            {'id': 1, 'name': 'Galleria', 'quantity': 0},
          ],
        },
      ],
    });

void main() {
  group('the model', () {
    test('a chosen size lists only the shops holding that size', () {
      final lines = _product().inventoryForSize('41');
      expect(lines.map((l) => l.branchName), ['Lusail']);
      expect(lines.single.quantity, 5);
    });

    test('no size chosen is every shop that has the style, summed', () {
      final lines = _product().inventoryForSize(null);
      // Galleria appears in two size rows and must not be listed twice.
      expect(lines.map((l) => l.branchName).toSet(), {'Galleria', 'Lusail'});
      expect(lines.length, 2);
      expect(lines.firstWhere((l) => l.branchId == 1).quantity, 3);
    });

    test('a size nobody stocks lists nobody', () {
      final stocked =
          _product().inventoryForSize('43').where((l) => l.hasStock).toList();
      expect(stocked, isEmpty);
    });

    test('the badge follows the size run', () {
      final p = _product();
      expect(p.stockAtForSize(1, '42'), 3);
      expect(p.stockAtForSize(1, '41'), 0, reason: 'the 41s are in Lusail');
      expect(p.stockAtForSize(2, '41'), 5);
      // Nothing chosen: this shop's whole shelf for the style.
      expect(p.stockAtForSize(1, null), 3);
    });

    test('with no per-size breakdown there is nothing to filter on', () {
      // The catalogue can answer with size labels and no stock behind them —
      // then filtering would hide shops that do have the shoe, which is worse
      // than not filtering at all.
      final p = Product.fromJson({
        'id': 1,
        'name': 'Samba OG',
        'size': '42',
        'available_sizes': ['41', '42'],
        'inventories': [
          {
            'branch': {'id': 1, 'name': 'Galleria', 'code': 'GAL'},
            'quantity': 3,
          },
        ],
      });
      expect(p.inventoryForSize('41').map((l) => l.branchName), ['Galleria']);
      expect(p.inventoryForSize(null).map((l) => l.branchName), ['Galleria']);
    });
  });

  group('the panel', () {
    const panel = Size(1080, 1920);

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

      tester.view
        ..physicalSize = panel
        ..devicePixelRatio = 1;
      addTearDown(tester.view.reset);

      final router = createRouter();
      addTearDown(router.dispose);
      final funnel = FunnelCubit();
      addTearDown(funnel.close);

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

      router.push('/product/1');
      await tester.pumpAndSettle();
    }

    /// The shops named in the availability strip. Branch names reach the tree
    /// nowhere else on this page — the top bar carries the product's name.
    List<String> shops(WidgetTester tester) => tester
        .widgetList<Text>(find.byType(Text))
        .map((t) => t.data ?? '')
        .where((s) => s == 'GALLERIA' || s == 'LUSAIL')
        .toList();

    Future<void> tapSize(WidgetTester tester, String size) async {
      final chip = find.widgetWithText(PearlChip, size);
      await tester.ensureVisible(chip);
      await tester.pumpAndSettle();
      await tester.tap(chip);
      await tester.pumpAndSettle();
    }

    testWidgets('opens on its own size and lists only that shop',
        (tester) async {
      await pump(tester);
      await tester.drag(find.byType(ListView).first, const Offset(0, -700));
      await tester.pumpAndSettle();

      expect(shops(tester), ['GALLERIA'], reason: 'Lusail has 41s, not 42s');
    });

    testWidgets('another size swaps the strip for that size’s shops',
        (tester) async {
      await pump(tester);
      await tapSize(tester, '41');

      expect(shops(tester), ['LUSAIL']);
    });

    testWidgets('a size nobody has cannot be chosen at all', (tester) async {
      // The chip for a sold-out size is struck through and dead to touch, so
      // the strip can never be filtered down to nothing — a customer is never
      // shown an empty answer they have no way back out of.
      await pump(tester);
      await tapSize(tester, '43');

      expect(shops(tester), ['GALLERIA'], reason: 'still the 42 they arrived on');
    });

    testWidgets('letting the size go lists every shop again', (tester) async {
      await pump(tester);
      await tapSize(tester, '41');
      expect(shops(tester), ['LUSAIL']);

      // The same chip again clears it — the only way back to "all sizes" on a
      // panel with no keyboard and no back gesture.
      await tapSize(tester, '41');
      expect(shops(tester).toSet(), {'GALLERIA', 'LUSAIL'});
    });
  });
}

/// One product, no photographs, so nothing here reaches the network.
class _Stub implements CatalogRepository {
  @override
  Future<List<Branch>> branches() async => const [
        Branch(id: 1, name: 'Galleria', code: 'GAL', location: 'Galleria', mobile: ''),
        Branch(id: 2, name: 'Lusail', code: 'LUS', location: 'Lusail', mobile: ''),
      ];

  @override
  Future<Product> product(int id) async => _product();

  @override
  Future<List<Product>> related(Product product,
          {int limit = 12, bool inStockOnly = true}) async =>
      const [];

  @override
  dynamic noSuchMethod(Invocation invocation) => super.noSuchMethod(invocation);
}
