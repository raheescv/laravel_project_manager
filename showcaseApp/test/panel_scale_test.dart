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
import 'package:showcase/shared/utils/components/theme/panel_scale.dart';
import 'package:showcase/shared/utils/components/theme/pearl_theme.dart';
import 'package:showcase/shared/utils/local_storage/local_storage_service.dart';
import 'package:showcase/shared/utils/router/http_utils/http_service.dart';
import 'package:showcase/shared/widgets/pearl_widgets.dart';

/// The app is drawn for a panel you stand in front of, not one you hold.
///
/// Flutter's logical pixel says nothing about how far away the glass is, so a
/// kiosk reporting a thousand of them across painted this design system's 38pt
/// controls and 9.5pt eyebrows at exactly the size a phone does. These check
/// the two halves of the fix: that the app is drawn larger on a large panel at
/// all, and that a size plate on the panel is a target you can hit from where
/// the customer is standing.
void main() {
  group('how much larger than drawn', () {
    test('a phone and a tablet are left exactly as they were', () {
      expect(PanelScale.scaleFor(const Size(375, 812)), 1);
      expect(PanelScale.scaleFor(const Size(402, 874)), 1);
      // The width every number in the app was drawn against — the last size at
      // which nothing happens.
      expect(PanelScale.scaleFor(const Size(PanelScale.drawnAt, 1000)), 1);
    });

    test('a kiosk panel is drawn a third larger', () {
      final scale = PanelScale.scaleFor(const Size(1080, 1920));
      expect(scale, greaterThan(1.3));
      expect(scale, lessThan(1.4));
    });

    test('the canvas grows as well as the furniture', () {
      // Not a zoom: if the whole of the extra width went into scale the panel
      // would show a tablet's layout larger, and the grids would have nothing
      // new to spend. Some of it stays as canvas.
      const panel = Size(1080, 1920);
      final canvas = panel.width / PanelScale.scaleFor(panel);
      expect(canvas, greaterThan(PanelScale.drawnAt));
    });

    test('it is the shortest side that decides, so landscape still fits', () {
      expect(
        PanelScale.scaleFor(const Size(1920, 1080)),
        PanelScale.scaleFor(const Size(1080, 1920)),
      );
    });

    test('an absurd panel stops growing rather than filling with furniture', () {
      expect(PanelScale.scaleFor(const Size(4000, 6000)), PanelScale.ceiling);
    });
  });

  group('on the panel', () {
    Future<void> pump(WidgetTester tester, Size panel) async {
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

      // Density 1, which is what these panels report and the whole reason the
      // app arrived looking like a stretched phone.
      tester.view
        ..physicalSize = panel
        ..devicePixelRatio = 1;
      addTearDown(tester.view.reset);

      await tester.pumpWidget(MaterialApp(
        theme: buildPearlTheme(PearlPalette.light),
        supportedLocales: L.supportedLocales,
        localizationsDelegates: L.localizationsDelegates,
        // Wired the way `app.dart` wires it, so this tests the frame that ships.
        builder: (context, child) => PanelScale(child: child ?? const SizedBox()),
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
      ));
      await tester.pumpAndSettle();
    }

    /// The plates of the topmost row, measured on the glass rather than on the
    /// canvas they were laid out on — which is the only measurement that says
    /// anything about whether a customer can hit one.
    List<Rect> topRow(WidgetTester tester) {
      final rects = tester
          .widgetList<PearlChip>(find.byType(PearlChip))
          .map((chip) => tester.getRect(find.byWidget(chip)))
          .toList()
        ..sort((a, b) => a.top.compareTo(b.top));
      final top = rects.first.top;
      return rects.where((r) => (r.top - top).abs() < 1).toList();
    }

    testWidgets('the size run fills the panel three across', (tester) async {
      await pump(tester, const Size(1080, 1920));
      final row = topRow(tester);
      expect(row.length, ThemeCubit.defaultSizeColumns);

      // Three plates and two gaps span the page, edge to edge. The run used to
      // cap each plate at 120pt and centre it in its share, which on this panel
      // left three small squares adrift in a third of the width each.
      final spanned = row.last.right - row.first.left;
      expect(spanned, greaterThan(1080 * .85));

      // And every plate is square and large enough to aim at across a floor.
      for (final plate in row) {
        expect(plate.width, greaterThan(280));
        expect(plate.height, closeTo(plate.width, 1));
      }
    });

    testWidgets('a plate grows when the setting asks for fewer', (tester) async {
      await pump(tester, const Size(1080, 1920));
      final threeUp = topRow(tester).first.width;

      await serviceLocator<ThemeCubit>().setSizeColumns(6);
      await tester.pumpAndSettle();
      final sixUp = topRow(tester);

      expect(sixUp.length, 6);
      // Halve the columns and the plate roughly doubles. It used to be capped,
      // so fewer columns bought air around the run instead of a bigger target.
      expect(threeUp, greaterThan(sixUp.first.width * 1.8));
      expect(tester.takeException(), isNull);
    });

    testWidgets('nothing overflows on the panel', (tester) async {
      await pump(tester, const Size(1080, 1920));
      expect(tester.takeException(), isNull);
    });
  });
}

/// Enough sizes to fill more than one row at any column count.
class _Stub implements CatalogRepository {
  @override
  Future<List<Branch>> branches() async => const [
        Branch(id: 1, name: 'Doha', code: 'DOH', location: 'Doha', mobile: ''),
      ];

  @override
  Future<List<SizeOption>> sizes({int? mainCategoryId, int? brandId, int? branchId}) async => [
        for (final label in ['36', '37', '38', '39', '40', '41', '42', '43', '44'])
          SizeOption(size: label, group: SizeGroup.adult, stockTotal: 4, inStock: true),
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
