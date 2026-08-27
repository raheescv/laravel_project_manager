import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:shared_preferences/shared_preferences.dart';
import 'package:showcase/shared/logic/funnel_cubit/funnel_cubit.dart';
import 'package:showcase/features/catalog/screens/size_screen.dart';
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
import 'package:showcase/shared/widgets/pearl_widgets.dart';

/// The size run is the first thing anyone touches, and how many of it fit a
/// row is a setting rather than something read off the width: the same tablet
/// is a counter-top display in one branch and a handheld in another. These
/// check the default a tablet arrives on and that the setting is obeyed.
void main() {
  /// Registered from inside the test body rather than `setUp`, because the
  /// futures these cubits hand out have to belong to the test's fake-async
  /// zone — a BranchCubit built in `setUp` completes `ready` in the outer
  /// zone, and the funnel's `await _branch.ready` then never resumes here.
  Future<void> registerServices() async {
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
    await serviceLocator<BranchCubit>().load();
  }

  tearDown(() => serviceLocator.reset());

  /// How many chips share the topmost row, and how wide each one is.
  ({int columns, double width}) firstRow(WidgetTester tester) {
    final rects = tester
        .widgetList<PearlChip>(find.byType(PearlChip))
        .map((chip) => tester.getRect(find.byWidget(chip)))
        .toList()
      ..sort((a, b) => a.top.compareTo(b.top));
    final top = rects.first.top;
    final row = rects.where((r) => (r.top - top).abs() < 1).toList();
    return (columns: row.length, width: row.first.width);
  }

  Future<void> pumpSizes(WidgetTester tester, Size size) async {
    await registerServices();
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
          child: const SizeScreen(),
        ),
      ),
    );
    await tester.pumpAndSettle();
  }

  /// A spread of screens: the layout is one capped column now, so a kiosk
  /// panel lands on the same chip count as a phone.
  const narrow = <String, Size>{
    'iPhone 17 Pro': Size(402, 874),
    'iPhone 13 mini': Size(375, 812),
    'narrowest supported': Size(320, 568),
    'iPad mini portrait': Size(744, 1133),
    'iPad portrait': Size(820, 1180),
  };

  for (final entry in narrow.entries) {
    testWidgets('the ${entry.key} opens on three across', (tester) async {
      await pumpSizes(tester, entry.value);
      final row = firstRow(tester);
      expect(row.columns, ThemeCubit.defaultSizeColumns);
      // Three is the default because it is the count that leaves a chip you
      // can hit on the narrowest screen the app runs on.
      expect(row.width, greaterThan(80));
    });
  }

  testWidgets('the row is however many Appearance was set to', (tester) async {
    await pumpSizes(tester, const Size(1180, 820));
    for (final columns in ThemeCubit.sizeColumnOptions) {
      await serviceLocator<ThemeCubit>().setSizeColumns(columns);
      await tester.pumpAndSettle();
      expect(firstRow(tester).columns, columns);
    }
  });

  testWidgets('a phone honours the setting too', (tester) async {
    await pumpSizes(tester, const Size(402, 874));
    await serviceLocator<ThemeCubit>().setSizeColumns(6);
    await tester.pumpAndSettle();
    expect(firstRow(tester).columns, 6);
  });
}

/// Sizes enough to fill more than one row on any screen; nothing else is read.
class _StubCatalog implements CatalogRepository {
  @override
  Future<List<Branch>> branches() async => const [
        Branch(id: 1, name: 'Doha', code: 'DOH', location: 'Doha', mobile: ''),
      ];

  @override
  Future<List<SizeOption>> sizes({int? mainCategoryId, int? brandId, int? branchId}) async => [
        for (final label in ['36', '37', '38', '39', '40', '41', '42', '43', '44'])
          SizeOption(
            size: label,
            group: SizeGroup.adult,
            stockTotal: 4,
            inStock: true,
          ),
      ];

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
