import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:shared_preferences/shared_preferences.dart';
import 'package:showcase/l10n/app_localizations.dart';
import 'package:showcase/shared/domain/constants/app_config.dart';
import 'package:showcase/shared/domain/constants/global_variables.dart';
import 'package:showcase/shared/domain/models/index.dart';
import 'package:showcase/shared/domain/repository/catalog_repository.dart';
import 'package:showcase/shared/logic/branch_cubit/branch_cubit.dart';
import 'package:showcase/shared/logic/connectivity_cubit/connectivity_cubit.dart';
import 'package:showcase/shared/logic/funnel_cubit/funnel_cubit.dart';
import 'package:showcase/shared/logic/locale_cubit/locale_cubit.dart';
import 'package:showcase/shared/logic/size_system_cubit/size_system_cubit.dart';
import 'package:showcase/shared/logic/theme_cubit/theme_cubit.dart';
import 'package:showcase/shared/utils/components/theme/pearl_theme.dart';
import 'package:showcase/shared/utils/local_storage/local_storage_service.dart';
import 'package:showcase/shared/utils/router/http_utils/http_service.dart';
import 'package:showcase/shared/widgets/chrome/app_top_bar.dart';
import 'package:showcase/shared/widgets/chrome/showcase_scaffold.dart';

/// UK / US / EU in the bar: one tap re-reads every size on screen. Stock is
/// stored in EU, so the switch is display only, and a size the chart has no
/// row for stays as stored rather than being guessed.
void main() {
  group('the chart', () {
    test('reads a stored EU size out in UK and US', () {
      expect(SizeSystemCubit.labelFor('42', SizeSystem.uk), '8');
      expect(SizeSystemCubit.labelFor('42', SizeSystem.us), '8.5');
      expect(SizeSystemCubit.labelFor('47', SizeSystem.uk), '12.5');
      expect(SizeSystemCubit.labelFor('38.5', SizeSystem.us), '6.5');
      expect(SizeSystemCubit.labelFor('35', SizeSystem.uk), '2.5');
    });

    test('leaves EU, and anything off the chart, as stored', () {
      expect(SizeSystemCubit.labelFor('42', SizeSystem.eu), '42');
      expect(SizeSystemCubit.labelFor('40.5', SizeSystem.uk), '40.5');
      expect(SizeSystemCubit.labelFor('10C', SizeSystem.uk), '10C');
      expect(SizeSystemCubit.labelFor('M', SizeSystem.us), 'M');
    });

    test('keeps the run in order through the women\'s rows', () {
      const run = ['35', '35.5', '36', '37', '37.5', '38', '38.5', '39', '40'];
      for (final system in [SizeSystem.uk, SizeSystem.us]) {
        final shown = run.map((s) => double.parse(SizeSystemCubit.labelFor(s, system))).toList();
        expect(shown, [...shown]..sort(), reason: system.code);
      }
    });
  });

  Future<void> pump(WidgetTester tester, Size size, {Map<String, Object> prefs = const {}}) async {
    SharedPreferences.setMockInitialValues(prefs);
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
          BlocProvider<SizeSystemCubit>(create: (_) => SizeSystemCubit()),
          BlocProvider<FunnelCubit>(create: (_) => FunnelCubit()),
          BlocProvider<BranchCubit>.value(value: serviceLocator<BranchCubit>()),
          BlocProvider<ThemeCubit>.value(value: serviceLocator<ThemeCubit>()),
          BlocProvider<ConnectivityCubit>.value(value: serviceLocator<ConnectivityCubit>()),
        ],
        child: ShowcaseScaffold(
          topBar: const AppTopBar(),
          body: Builder(builder: (context) => Text('size ${context.sizeLabel('42')}')),
        ),
      ),
    ));
    await tester.pumpAndSettle();
  }

  testWidgets('a tap in the bar re-reads the sizes on screen, and is remembered', (tester) async {
    await pump(tester, const Size(1024, 1366));
    expect(find.text('size 42'), findsOneWidget);

    await tester.tap(find.text('UK'));
    await tester.pumpAndSettle();
    expect(find.text('size 8'), findsOneWidget);

    await tester.tap(find.text('US'));
    await tester.pumpAndSettle();
    expect(find.text('size 8.5'), findsOneWidget);
    expect(serviceLocator<LocalStorageService>().sizeSystem, 'us');
  });

  testWidgets('the tablet opens in the sizing it was left in', (tester) async {
    await pump(tester, const Size(1024, 1366), prefs: {'size_system': 'uk'});
    expect(find.text('size 8'), findsOneWidget);
  });

  testWidgets('a narrow phone steps through the sizings from one cell', (tester) async {
    await pump(tester, const Size(320, 568));
    expect(find.text('UK'), findsNothing);

    await tester.tap(find.text('EU'));
    await tester.pumpAndSettle();
    expect(find.text('size 8'), findsOneWidget);

    await tester.tap(find.text('UK'));
    await tester.pumpAndSettle();
    expect(find.text('size 8.5'), findsOneWidget);
  });

  for (final size in const [Size(320, 568), Size(375, 812), Size(402, 874)]) {
    testWidgets('the ${size.width.toInt()}pt bar still fits with the switch in it', (tester) async {
      await pump(tester, size);
      expect(tester.takeException(), isNull);
      expect(find.text('EU'), findsOneWidget);
    });
  }
}

class _Stub implements CatalogRepository {
  @override
  Future<List<Branch>> branches() async =>
      const [Branch(id: 1, name: 'Doha Festival City', code: 'DFC', location: 'Doha', mobile: '')];
  @override
  Future<List<SizeOption>> sizes({int? mainCategoryId, int? brandId, int? branchId}) async => const [];
  @override
  Future<List<CategoryOption>> categories({List<String> sizes = const [], int? branchId, bool inStockOnly = true}) async => const [];
  @override
  Future<List<BrandOption>> brands({int? mainCategoryId, List<String> sizes = const [], bool inStockOnly = true}) async => const [];
  @override
  dynamic noSuchMethod(Invocation i) => super.noSuchMethod(i);
}
