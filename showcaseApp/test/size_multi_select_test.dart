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

/// The size step takes more than one answer: a tap ticks a plate, and the
/// pinned bar that appears once anything is ticked is what moves on.
void main() {
  late FunnelCubit funnel;
  late GoRouter router;
  late _Stub repo;

  Future<void> pump(WidgetTester tester, {Size view = const Size(1080, 1920), Locale? locale}) async {
    SharedPreferences.setMockInitialValues({});
    final config = AppConfig.resolve(savedBaseUrl: null, savedTenant: null);
    repo = _Stub();
    serviceLocator
      ..registerSingleton<LocalStorageService>(await LocalStorageService.create())
      ..registerSingleton<AppConfig>(config)
      ..registerSingleton<HttpService>(HttpService(config: config))
      ..registerSingleton<CatalogRepository>(repo)
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
      ..physicalSize = view
      ..devicePixelRatio = 1;
    addTearDown(tester.view.reset);

    await tester.pumpWidget(MultiBlocProvider(
      providers: [
        BlocProvider<LocaleCubit>(create: (_) => LocaleCubit()),
        BlocProvider<FunnelCubit>.value(value: funnel),
        BlocProvider<BranchCubit>.value(value: serviceLocator<BranchCubit>()),
        BlocProvider<ThemeCubit>.value(value: serviceLocator<ThemeCubit>()),
        BlocProvider<ConnectivityCubit>.value(value: serviceLocator<ConnectivityCubit>()),
      ],
      child: MaterialApp.router(
        theme: buildPearlTheme(PearlPalette.light),
        locale: locale,
        supportedLocales: L.supportedLocales,
        localizationsDelegates: L.localizationsDelegates,
        routerConfig: router,
        builder: (context, child) => PanelScale(child: child ?? const SizedBox()),
      ),
    ));
    await tester.pumpAndSettle();
  }

  PearlChip plate(WidgetTester tester, String label) =>
      tester.widget<PearlChip>(find.widgetWithText(PearlChip, label));

  testWidgets('a tap ticks a size and stays on the run', (tester) async {
    await pump(tester);
    expect(find.byType(PinnedBar), findsNothing);

    await tester.tap(find.widgetWithText(PearlChip, '38'));
    await tester.tap(find.widgetWithText(PearlChip, '40'));
    await tester.pumpAndSettle();

    expect(router.state.uri.path, Routes.size);
    expect(funnel.state.chosenSizes, ['40', '38'], reason: 'kept in the order of the run');
    expect(plate(tester, '38').ticked, isTrue);
    expect(plate(tester, '40').selected, isTrue);
    expect(plate(tester, 'All').selected, isFalse);
    expect(repo.brandAsks, isEmpty, reason: 'nothing is fetched until Continue');

    expect(find.byType(PinnedBar), findsOneWidget);
    expect(find.text('CONTINUE WITH 2 SIZES'), findsOneWidget);
    expect(find.text('40 · 38'), findsOneWidget);
  });

  testWidgets('a second tap takes a size off, and Clear takes them all',
      (tester) async {
    await pump(tester);

    await tester.tap(find.widgetWithText(PearlChip, '38'));
    await tester.tap(find.widgetWithText(PearlChip, '39'));
    await tester.tap(find.widgetWithText(PearlChip, '38'));
    await tester.pumpAndSettle();
    expect(funnel.state.chosenSizes, ['39']);
    expect(find.text('CONTINUE WITH 1 SIZE'), findsOneWidget);

    await tester.tap(find.widgetWithText(PearlButton, 'CLEAR'));
    await tester.pumpAndSettle();
    expect(funnel.state.chosenSizes, isEmpty);
    expect(plate(tester, 'All').selected, isTrue);
    expect(find.byType(PinnedBar), findsNothing);
  });

  testWidgets('Continue asks for brands in every ticked size, and Back keeps the ticks',
      (tester) async {
    await pump(tester);

    await tester.tap(find.widgetWithText(PearlChip, '37'));
    await tester.tap(find.widgetWithText(PearlChip, '41'));
    await tester.pumpAndSettle();
    await tester.tap(find.widgetWithIcon(PearlButton, Icons.arrow_forward));
    await tester.pumpAndSettle();

    expect(router.state.uri.path, Routes.brand);
    expect(repo.brandAsks.last, ['41', '37']);
    // The crumb names both answers under a plural label.
    expect(find.text('SIZES'), findsOneWidget);
    expect(find.text('41 · 37'), findsOneWidget);

    await tester.tap(find.widgetWithIcon(IconSquare, Icons.arrow_back));
    await tester.pumpAndSettle();

    expect(router.state.uri.path, Routes.size);
    expect(funnel.state.chosenSizes, ['41', '37']);
    expect(plate(tester, '41').ticked, isTrue);
  });

  testWidgets('All clears the ticks and moves on', (tester) async {
    await pump(tester);

    await tester.tap(find.widgetWithText(PearlChip, '38'));
    await tester.pumpAndSettle();
    await tester.tap(find.widgetWithText(PearlChip, 'All'));
    await tester.pumpAndSettle();

    expect(router.state.uri.path, Routes.brand);
    expect(funnel.state.chosenSizes, isEmpty);
    expect(repo.brandAsks.last, isEmpty);

    // Leave the step so the next push in this file is not swallowed by the
    // double-tap guard.
    await tester.tap(find.widgetWithIcon(IconSquare, Icons.arrow_back));
    await tester.pumpAndSettle();
  });

  for (final locale in const [Locale('en'), Locale('ar')]) {
    testWidgets('the bar fits the narrowest phone in ${locale.languageCode}',
        (tester) async {
      await pump(tester, view: const Size(320, 640), locale: locale);
      for (final size in ['41', '40', '39', '38', '37']) {
        funnel.toggleSize(size);
      }
      await tester.pumpAndSettle();

      expect(find.byType(PinnedBar), findsOneWidget);
      expect(tester.takeException(), isNull);
    });
  }
}

class _Stub implements CatalogRepository {
  final List<List<String>> brandAsks = [];

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
    List<String> sizes = const [],
    bool inStockOnly = true,
  }) async {
    brandAsks.add(sizes);
    return const [BrandOption(id: 1, name: 'Nike', productCount: 38)];
  }

  @override
  dynamic noSuchMethod(Invocation invocation) => super.noSuchMethod(invocation);
}
