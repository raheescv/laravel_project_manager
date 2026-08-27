import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:shared_preferences/shared_preferences.dart';
import 'package:showcase/shared/logic/funnel_cubit/funnel_cubit.dart';
import 'package:showcase/features/catalog/screens/size_screen.dart';
import 'package:showcase/l10n/app_localizations.dart';
import 'package:showcase/l10n/app_localizations_ar.dart';
import 'package:showcase/l10n/app_localizations_en.dart';
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

/// The size question is the one line on the tablet that is asked in both
/// languages at once, because it is read before anybody has found the language
/// setting. These check that both wordings are actually on the screen, that the
/// app's own language leads, and that neither is left set in a face that cannot
/// draw it.
void main() {
  // As drawn, not as written: the heading sets the Latin half in capitals.
  // Arabic has no case, so that half is on screen exactly as it is in the
  // string table.
  final en = LEn().whichSize.toUpperCase();
  final ar = LAr().whichSize;

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

  tearDown(() {
    PearlText.useArabic(false);
    serviceLocator.reset();
  });

  Future<void> pumpSizes(WidgetTester tester, Locale locale, Size size) async {
    await registerServices();
    // LocaleCubit does this at runtime before the frame that changes language;
    // driving MaterialApp's locale directly here leaves the static behind.
    PearlText.useArabic(locale.languageCode == 'ar');
    tester.view
      ..physicalSize = size * 3
      ..devicePixelRatio = 3;
    addTearDown(tester.view.reset);

    await tester.pumpWidget(
      MaterialApp(
        locale: locale,
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

  const kiosk = Size(1180, 820);

  /// English first: to the left of the Arabic when the two share a row, above
  /// it when the pair is too wide and stacks.
  ///
  /// It shares one on a real screen, but not under the test font — every glyph
  /// there is a full em wide, so the same question measures nearly twice what
  /// Jost draws. Asserting on the row would be asserting on the fixture.
  void expectEnglishFirst(WidgetTester tester) {
    final first = tester.getRect(find.text(en));
    final second = tester.getRect(find.text(ar));
    // Sharing a line is an overlap of the two boxes, not equal tops: Arabic is
    // set with a taller line box than the Latin face, so two halves centred on
    // the same row still start a couple of points apart.
    final sameRow = first.top < second.bottom && second.top < first.bottom;
    if (sameRow) {
      expect(first.left, lessThan(second.left), reason: 'English on the left');
    } else {
      expect(first.top, lessThan(second.top), reason: 'English on the first line');
    }
  }

  /// The direction the wording is actually laid out in, off its own paragraph
  /// rather than off the page it sits on.
  TextDirection directionOf(WidgetTester tester, String wording) =>
      Directionality.of(tester.element(find.text(wording)));

  testWidgets('an English kiosk asks in Arabic as well', (tester) async {
    await pumpSizes(tester, const Locale('en'), kiosk);
    expect(find.text(en), findsOneWidget);
    expect(find.text(ar), findsOneWidget);
    expectEnglishFirst(tester);
    // Arabic reads right to left even on a page that runs the other way.
    expect(directionOf(tester, ar), TextDirection.rtl);
    expect(directionOf(tester, en), TextDirection.ltr);
  });

  testWidgets('an Arabic kiosk still asks in English', (tester) async {
    await pumpSizes(tester, const Locale('ar'), kiosk);
    expect(find.text(ar), findsOneWidget);
    expect(find.text(en), findsOneWidget);
    // The pair does not swap ends with the language: the English half is in the
    // same place on every screen, and only where the block starts follows the
    // page. A heading that moved would read as a different heading.
    expectEnglishFirst(tester);
    expect(directionOf(tester, ar), TextDirection.rtl);
    expect(directionOf(tester, en), TextDirection.ltr);
  });

  testWidgets('the heading starts where the rest of an Arabic screen does',
      (tester) async {
    await pumpSizes(tester, const Locale('ar'), kiosk);
    // Against the size run below it, which fills the body: on an Arabic page
    // both should begin at the right-hand edge.
    final bodyRight = tester
        .widgetList<PearlChip>(find.byType(PearlChip))
        .map((chip) => tester.getRect(find.byWidget(chip)).right)
        .reduce((a, b) => a > b ? a : b);
    expect(tester.getRect(find.text(ar)).right, closeTo(bodyRight, 1));
  });

  testWidgets('both wordings survive the narrowest phone', (tester) async {
    // Where the pair cannot share a line it breaks onto two rather than losing
    // one of them — the whole point is that neither language is dropped.
    await pumpSizes(tester, const Locale('en'), const Size(320, 568));
    expect(find.text(en), findsOneWidget);
    expect(find.text(ar), findsOneWidget);
  });

  test('a wording is set in its own script, not the app\'s', () {
    // Checked on the decision rather than the resolved style, because
    // resolving one reaches for a font this test cannot fetch. Without the
    // override the Arabic half of an English heading would keep Pearl's
    // tracking, which separates letters that are supposed to join.
    PearlText.useArabic(false);
    expect(PearlText.trackingFor(3.4, arabic: true), 0);
    expect(PearlText.leadingFor(1.08, arabic: true), greaterThan(1.08));

    PearlText.useArabic(true);
    expect(PearlText.trackingFor(3.4, arabic: false), 3.4);
    expect(PearlText.leadingFor(1.08, arabic: false), 1.08);
  });
}

/// Enough of a size run to fill the screen; nothing else is read.
class _StubCatalog implements CatalogRepository {
  @override
  Future<List<Branch>> branches() async => const [
        Branch(id: 1, name: 'Doha', code: 'DOH', location: 'Doha', mobile: ''),
      ];

  @override
  Future<List<SizeOption>> sizes({int? mainCategoryId, int? brandId, int? branchId}) async => [
        for (final label in ['38', '39', '40', '41'])
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
