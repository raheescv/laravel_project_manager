import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:shared_preferences/shared_preferences.dart';
import 'package:showcase/features/catalog/logic/funnel_cubit/funnel_cubit.dart';
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
import 'package:showcase/shared/utils/router/http_utils/http_service.dart';
import 'package:showcase/shared/widgets/chrome/app_top_bar.dart';
import 'package:showcase/shared/widgets/pearl_widgets.dart';

/// The bar has to reach both edges of whatever it is drawn on.
///
/// Its elastic controls — the stock toggle, the shop name — used to sit in the
/// row as `Flexible`, which hands a child half the free space and lets it keep
/// what it does not use. They took the width of their label and left the rest
/// as a hole between the last control and the edge: a 1366pt tablet ended its
/// row 378pt short of its own padding, which reads as a bar built for a phone
/// and stretched.
void main() {

  Future<void> pump(WidgetTester tester, Size size) async {
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
    addTearDown(() => serviceLocator.reset());

    tester.view
      ..physicalSize = size * 3
      ..devicePixelRatio = 3;
    addTearDown(tester.view.reset);

    await tester.pumpWidget(MaterialApp(
      theme: buildPearlTheme(PearlPalette.light),
      supportedLocales: L.supportedLocales,
      localizationsDelegates: L.localizationsDelegates,
      home: MultiBlocProvider(
        providers: [
          BlocProvider<LocaleCubit>(create: (_) => LocaleCubit()),
          BlocProvider<FunnelCubit>(create: (_) => FunnelCubit()),
          BlocProvider<BranchCubit>.value(value: serviceLocator<BranchCubit>()),
          BlocProvider<ThemeCubit>.value(value: serviceLocator<ThemeCubit>()),
          BlocProvider<ConnectivityCubit>.value(
              value: serviceLocator<ConnectivityCubit>()),
        ],
        child: const Scaffold(
          body: Column(
            children: [AppTopBar(leading: Icon(Icons.arrow_back, size: 38))],
          ),
        ),
      ),
    ));
    await tester.pumpAndSettle();
  }

  /// The right edge of the control that ends the row — its box, not its icon.
  double lastControlRight(WidgetTester tester, Finder control) =>
      tester.getRect(control.first).right;

  /// The phone row ends in the overflow menu, the tablet row in the scanner.
  final menu = find.byType(PopupMenuButton<String>);
  final scanner = find.ancestor(
    of: find.byIcon(Icons.qr_code_scanner_outlined),
    matching: find.byType(IconSquare),
  );

  const phones = <String, Size>{
    'narrowest': Size(320, 568),
    'iPhone 13 mini': Size(375, 812),
    'iPhone 17 Pro': Size(402, 874),
    'Pro Max': Size(430, 932),
    'widest phone-class': Size(590, 900),
  };

  for (final entry in phones.entries) {
    testWidgets('the ${entry.key} control row reaches the edge', (tester) async {
      await pump(tester, entry.value);
      // 14pt of padding each side, from `_phoneBar`.
      expect(lastControlRight(tester, menu),
          closeTo(entry.value.width - 14, .5));
    });
  }

  const tablets = <String, Size>{
    'iPad mini': Size(744, 1133),
    'iPad': Size(820, 1180),
    'iPad Pro portrait': Size(1024, 1366),
    'iPad Pro landscape': Size(1366, 1024),
  };

  for (final entry in tablets.entries) {
    testWidgets('the ${entry.key} bar reaches the edge', (tester) async {
      await pump(tester, entry.value);
      // 20pt of padding each side, from `_tabletBar`.
      expect(lastControlRight(tester, scanner),
          closeTo(entry.value.width - 20, .5));
    });
  }

  testWidgets('the narrowest phone keeps the word in its search field',
      (tester) async {
    await pump(tester, const Size(320, 568));
    // Capping the toggle must not push the field down to its icon: 320pt is
    // exactly where the two are closest to trading places.
    expect(find.text('Search'), findsOneWidget);
  });
}

class _Stub implements CatalogRepository {
  @override
  Future<List<Branch>> branches() async =>
      const [Branch(id: 1, name: 'Doha Festival City', code: 'DFC', location: 'Doha', mobile: '')];
  @override
  Future<List<SizeOption>> sizes({int? mainCategoryId, int? brandId, int? branchId}) async => const [];
  @override
  Future<List<CategoryOption>> categories({String? size, int? branchId, bool inStockOnly = true}) async => const [];
  @override
  Future<List<BrandOption>> brands({int? mainCategoryId, String? size, bool inStockOnly = true}) async => const [];
  @override
  dynamic noSuchMethod(Invocation i) => super.noSuchMethod(i);
}
