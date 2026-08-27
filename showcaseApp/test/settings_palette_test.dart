import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:shared_preferences/shared_preferences.dart';
import 'package:showcase/features/catalog/logic/funnel_cubit/funnel_cubit.dart';
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
import 'package:showcase/shared/utils/components/theme/theme_presets.dart';
import 'package:showcase/shared/utils/local_storage/local_storage_service.dart';
import 'package:showcase/shared/utils/router/http_utils/http_service.dart';

/// The palette control is one block for two slots, which is only an
/// improvement if the strip still fits and the tab still aims the tap at the
/// slot it is showing.
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

  Future<void> pumpSettings(WidgetTester tester, Size size) async {
    tester.view
      ..physicalSize = size * 3
      ..devicePixelRatio = 3;
    addTearDown(tester.view.reset);

    await tester.pumpWidget(
      MaterialApp(
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
      ),
    );
    await tester.pump();
  }

  /// Every preset is named on screen once — one strip, not two.
  void expectOneStrip() {
    for (final preset in ThemePreset.values) {
      expect(find.text(preset.label.toUpperCase()), findsOneWidget,
          reason: '${preset.label} should appear exactly once');
    }
  }

  const sizes = <String, Size>{
    'tablet': Size(1180, 820),
    'phone': Size(402, 874),
    'narrowest supported': Size(320, 568),
  };

  for (final entry in sizes.entries) {
    testWidgets('the palette block fits a ${entry.key}', (tester) async {
      await pumpSettings(tester, entry.value);
      expectOneStrip();
    });
  }

  testWidgets('the mode tab aims the tap at the slot it is showing',
      (tester) async {
    final theme = serviceLocator<ThemeCubit>();
    await pumpSettings(tester, const Size(1180, 820));

    // Landed on the light tab, because the screen is painted light.
    await tester.tap(find.text('SIZERUN'));
    await tester.pumpAndSettle();
    expect(theme.state.light, ThemePreset.sizerun);
    expect(theme.state.dark, isNot(ThemePreset.sizerun));

    // "DARK" is also a Mode segment at the top of the screen; the palette tab
    // is the later of the two, since its block is further down.
    await tester.tap(find.text('DARK').last);
    await tester.pumpAndSettle();
    await tester.tap(find.text('PEARL'));
    await tester.pumpAndSettle();
    expect(theme.state.dark, ThemePreset.pearl);
    expect(theme.state.light, ThemePreset.sizerun);
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
