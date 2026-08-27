import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:package_info_plus/package_info_plus.dart';
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

/// A tablet on a shop floor is updated by whoever is standing in front of it,
/// so Settings has to be able to answer "which build is this one on?".
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

  Future<void> pump(WidgetTester tester) async {
    tester.view
      ..physicalSize = const Size(1180, 1600) * 3
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
        child: const SettingsScreen(),
      ),
    ));
    await tester.pumpAndSettle();
  }

  testWidgets('appearance names the installed build', (tester) async {
    PackageInfo.setMockInitialValues(
      appName: 'Showcase',
      packageName: 'com.sizerun.showcase',
      version: '2.4.1',
      buildNumber: '37',
      buildSignature: '',
    );

    await pump(tester);

    // Version and build both: two tablets on the same version can be on
    // different builds, and that is exactly the case worth telling apart.
    expect(find.text('Version 2.4.1 (37)'), findsOneWidget);
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
