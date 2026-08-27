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

/// Appearance has to survive its own settings.
///
/// It is the one screen where every control is a box of text, and it is also
/// where the text-size steps are chosen — so it is the first thing the largest
/// step breaks, and the last place anyone thinks to check.
void main() {
  setUp(() async {
    SharedPreferences.setMockInitialValues({});
    final storage = await LocalStorageService.create();
    final config = AppConfig.resolve(savedBaseUrl: null, savedTenant: null);
    serviceLocator
      ..registerSingleton<LocalStorageService>(storage)
      ..registerSingleton<AppConfig>(config)
      ..registerSingleton<HttpService>(HttpService(config: config))
      ..registerSingleton<ConnectivityCubit>(ConnectivityCubit())
      ..registerSingleton<CatalogRepository>(_Stub())
      ..registerSingleton<ThemeCubit>(ThemeCubit())
      ..registerSingleton<BranchCubit>(BranchCubit());
  });

  tearDown(() => serviceLocator.reset());

  Future<void> pump(WidgetTester tester, Size size, double scale, Locale locale) async {
    tester.view
      ..physicalSize = size * 2
      ..devicePixelRatio = 2;
    addTearDown(tester.view.reset);
    await tester.pumpWidget(MultiBlocProvider(
      providers: [
        BlocProvider<ThemeCubit>.value(value: serviceLocator<ThemeCubit>()),
        BlocProvider<BranchCubit>.value(value: serviceLocator<BranchCubit>()),
        BlocProvider<ConnectivityCubit>.value(value: serviceLocator<ConnectivityCubit>()),
        BlocProvider<LocaleCubit>(create: (_) => LocaleCubit()),
        BlocProvider<FunnelCubit>(create: (_) => FunnelCubit()),
      ],
      child: MaterialApp(
        locale: locale,
        supportedLocales: L.supportedLocales,
        localizationsDelegates: L.localizationsDelegates,
        theme: buildPearlTheme(PearlPalette.light),
        home: MediaQuery.withClampedTextScaling(
          minScaleFactor: scale,
          maxScaleFactor: scale,
          child: const SettingsScreen(),
        ),
      ),
    ));
    await tester.pumpAndSettle();
  }

  const sizes = <String, Size>{
    'phone': Size(375, 812),
    'narrowest': Size(320, 568),
    'tablet': Size(1024, 768),
  };

  for (final entry in sizes.entries) {
    for (final locale in const [Locale('en'), Locale('ar')]) {
      testWidgets(
          'appearance fits a ${entry.key} at every text size in ${locale.languageCode}',
          (tester) async {
        for (final scale in ThemeCubit.textScales) {
          await pump(tester, entry.value, scale, locale);
          expect(tester.takeException(), isNull,
              reason: 'overflowed at ${scale}x on ${entry.key}');
        }
      });
    }
  }
}

class _Stub implements CatalogRepository {
  @override
  Future<List<Branch>> branches() async => const [];
  @override
  Future<List<SizeOption>> sizes({int? mainCategoryId, int? brandId, int? branchId}) async => const [];
  @override
  Future<List<CategoryOption>> categories({String? size, int? branchId, bool inStockOnly = true}) async => const [];
  @override
  Future<List<BrandOption>> brands({int? mainCategoryId, String? size, bool inStockOnly = true}) async => const [];
  @override
  dynamic noSuchMethod(Invocation i) => super.noSuchMethod(i);
}
