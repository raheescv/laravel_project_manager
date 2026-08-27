import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:shared_preferences/shared_preferences.dart';
import 'package:showcase/shared/logic/funnel_cubit/funnel_cubit.dart';
import 'package:showcase/features/settings/screens/settings_screen.dart';
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
import 'package:showcase/shared/widgets/chrome/showcase_scaffold.dart';
import 'package:showcase/shared/widgets/pearl_widgets.dart';

/// The bar's Settings square is the same widget on every screen, including
/// Settings — where tapping it pushed a second copy of the screen already on
/// show, and the back arrow then had to be pressed twice to leave.
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

  Future<void> pump(WidgetTester tester, Widget screen) async {
    tester.view
      ..physicalSize = const Size(1180, 820) * 3
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
        child: screen,
      ),
    ));
    await tester.pump();
  }

  /// The control that ends the control row.
  IconSquare settingsSquare(WidgetTester tester) => tester.widget<IconSquare>(
        find.ancestor(
          of: find.byIcon(Icons.tune),
          matching: find.byType(IconSquare),
        ),
      );

  testWidgets('the Settings square is dead on Settings', (tester) async {
    await pump(tester, const SettingsScreen());
    expect(settingsSquare(tester).onTap, isNull);

    // And pressing it is a no-op rather than a navigation. There is no router
    // over this harness, so had the tap still been wired it would throw here.
    await tester.tap(find.byIcon(Icons.tune));
    await tester.pump();
    expect(tester.takeException(), isNull);
  });

  testWidgets('and still live on every other screen', (tester) async {
    await pump(
      tester,
      const ShowcaseScaffold(topBar: AppTopBar(), body: SizedBox.shrink()),
    );
    expect(settingsSquare(tester).onTap, isNotNull);
  });
}

/// The screen never reads the catalogue; the top bar's cubits only have to be
/// able to come up empty.
class _StubCatalog implements CatalogRepository {
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
  Future<List<BrandOption>> brands({
    int? mainCategoryId,
    String? size,
    bool inStockOnly = true,
  }) async =>
      const [];

  @override
  dynamic noSuchMethod(Invocation invocation) => super.noSuchMethod(invocation);
}
