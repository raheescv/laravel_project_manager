import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:shared_preferences/shared_preferences.dart';
import 'package:showcase/features/catalog/logic/funnel_cubit/funnel_cubit.dart';
import 'package:showcase/shared/domain/constants/app_config.dart';
import 'package:showcase/shared/domain/constants/global_variables.dart';
import 'package:showcase/shared/domain/models/index.dart';
import 'package:showcase/shared/domain/repository/catalog_repository.dart';
import 'package:showcase/shared/logic/branch_cubit/branch_cubit.dart';
import 'package:showcase/shared/logic/theme_cubit/theme_cubit.dart';
import 'package:showcase/shared/utils/components/theme/theme_presets.dart';
import 'package:showcase/shared/utils/local_storage/local_storage_service.dart';
import 'package:showcase/shared/utils/router/http_utils/http_service.dart';

/// What a tablet out of the box looks like. These are the three the shop never
/// sets, so they are the three most worth pinning.
void main() {
  setUp(() async {
    SharedPreferences.setMockInitialValues({});
    final storage = await LocalStorageService.create();
    final config = AppConfig.resolve(savedBaseUrl: null, savedTenant: null);
    serviceLocator
      ..registerSingleton<LocalStorageService>(storage)
      ..registerSingleton<AppConfig>(config)
      ..registerSingleton<HttpService>(HttpService(config: config))
      ..registerSingleton<CatalogRepository>(_Stub());
  });

  tearDown(() => serviceLocator.reset());

  test('an unconfigured tablet wears Aurora Glass in both modes', () {
    final theme = ThemeCubit();
    expect(theme.state.light, ThemePreset.aurora);
    expect(theme.state.dark, ThemePreset.aurora);
  });

  test('and follows the device for when to go dark', () {
    expect(ThemeCubit().state.mode, ThemeMode.system);
  });

  test('a saved choice still wins over the default', () async {
    SharedPreferences.setMockInitialValues({
      'theme_preset_light': 'noir',
      'theme_mode': 'dark',
    });
    serviceLocator.unregister<LocalStorageService>();
    serviceLocator.registerSingleton<LocalStorageService>(
      await LocalStorageService.create(),
    );

    final theme = ThemeCubit();
    expect(theme.state.light, ThemePreset.noir);
    expect(theme.state.mode, ThemeMode.dark);
    // Untouched slots keep the default rather than following the other one.
    expect(theme.state.dark, ThemePreset.aurora);
  });

  test('in stock is on before anyone touches it', () {
    serviceLocator.registerSingleton<BranchCubit>(BranchCubit());
    expect(FunnelCubit().state.inStockOnly, isTrue);
  });
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
