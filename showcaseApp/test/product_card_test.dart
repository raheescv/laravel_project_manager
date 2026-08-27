import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:shared_preferences/shared_preferences.dart';
import 'package:showcase/l10n/app_localizations.dart';
import 'package:showcase/shared/domain/constants/global_variables.dart';
import 'package:showcase/shared/domain/models/index.dart';
import 'package:showcase/shared/logic/theme_cubit/theme_cubit.dart';
import 'package:showcase/shared/utils/components/theme/pearl_theme.dart';
import 'package:showcase/shared/utils/local_storage/local_storage_service.dart';
import 'package:showcase/shared/widgets/product_card.dart';

/// The grid gives every tile its height before the words inside it are laid
/// out, so the card and the grid have to agree on how tall those words are. In
/// Arabic they are taller, at a large text size taller again — and when the two
/// disagreed the tile overflowed and the price was painted over stripes.
void main() {
  tearDown(() {
    PearlText.useArabic(false);
    serviceLocator.reset();
  });

  /// The grid reads its column count from Appearance, so it needs the cubit
  /// that holds it — and the cubit needs somewhere to have read it from.
  Future<ThemeCubit> theme() async {
    SharedPreferences.setMockInitialValues({});
    serviceLocator
        .registerSingleton<LocalStorageService>(await LocalStorageService.create());
    final cubit = ThemeCubit();
    serviceLocator.registerSingleton<ThemeCubit>(cubit);
    return cubit;
  }

  final products = List.generate(
    9,
    (i) => Product.fromJson({
      'id': i + 1,
      // Long enough to need both name lines — a short name would hide the
      // overflow this test exists to catch.
      'name': 'Fresh Foam X 1080 v13 Running Shoe',
      'brand': {'id': 1, 'name': 'New Balance'},
      'mrp': 1249.5,
      'total_stock': 2,
      'availability_status': 'in_stock',
    }),
  );

  Future<ThemeCubit> pumpGrid(
    WidgetTester tester, {
    required Size size,
    Locale locale = const Locale('en'),
    double textScale = 1,
  }) async {
    final cubit = await theme();
    tester.view
      ..physicalSize = size * 3
      ..devicePixelRatio = 3;
    addTearDown(tester.view.reset);
    PearlText.useArabic(locale.languageCode == 'ar');

    await tester.pumpWidget(
      MaterialApp(
        theme: buildPearlTheme(PearlPalette.light),
        locale: locale,
        supportedLocales: L.supportedLocales,
        localizationsDelegates: L.localizationsDelegates,
        home: BlocProvider<ThemeCubit>.value(
          value: cubit,
          child: MediaQuery.withClampedTextScaling(
            minScaleFactor: textScale,
            maxScaleFactor: textScale,
            child: Scaffold(
              body: ProductGrid(
                products: products,
                onTap: (_) {},
                padding: const EdgeInsets.all(PearlMetrics.pad),
              ),
            ),
          ),
        ),
      ),
    );
    await tester.pumpAndSettle();
    return cubit;
  }

  /// How many cards share the topmost row.
  int firstRow(WidgetTester tester) {
    final rects = tester
        .widgetList<ProductCard>(find.byType(ProductCard))
        .map((card) => tester.getRect(find.byWidget(card)))
        .toList()
      ..sort((a, b) => a.top.compareTo(b.top));
    final top = rects.first.top;
    return rects.where((r) => (r.top - top).abs() < 1).length;
  }

  /// A spread of screens, including the narrow tile the caption overflow was
  /// first reported on and the ~800pt canvas `PanelScale` hands this grid on
  /// the kiosk. The column count no longer changes with any of them — the
  /// setting decides — but the words still have to fit at every width.
  const sizes = <String, Size>{
    'phone': Size(375, 812),
    'kiosk canvas': Size(813, 1422),
    'wide display': Size(1180, 900),
    'very wide display': Size(1520, 980),
  };

  for (final entry in sizes.entries) {
    for (final locale in const [Locale('en'), Locale('ar')]) {
      testWidgets(
          'the ${entry.key} grid fits its words in ${locale.languageCode}',
          (tester) async {
        await pumpGrid(tester, size: entry.value, locale: locale);
        expect(tester.takeException(), isNull);
      });
    }
  }

  testWidgets('the panel opens on the big tile, not the smallest one the grid '
      'can draw', (tester) async {
    // The count used to be a rule read off the width, and it reached four
    // columns at 1000pt — which is roughly what a kiosk panel reports, so the
    // one screen this app runs on was showing the narrowest tile available. A
    // customer standing in front of the panel is choosing a shoe from a
    // photograph. Two is what an unconfigured tablet shows now, at every width.
    for (final size in sizes.values) {
      await pumpGrid(tester, size: size);
      expect(firstRow(tester), ThemeCubit.defaultProductColumns);
      await serviceLocator.reset();
    }
  });

  testWidgets('the row is however many Appearance was set to', (tester) async {
    final cubit = await pumpGrid(tester, size: const Size(813, 1422));
    for (final columns in ThemeCubit.productColumnOptions) {
      await cubit.setProductColumns(columns);
      await tester.pumpAndSettle();
      expect(firstRow(tester), columns);
    }
  });

  testWidgets('the densest setting survives the narrowest screen',
      (tester) async {
    // The worst case Appearance can ask for: five tiles across a phone, in the
    // taller script. The setting is the shop's to make, so the grid has to
    // draw it rather than overflow — the card gives up photo, not caption.
    final cubit = await pumpGrid(tester,
        size: const Size(320, 568), locale: const Locale('ar'));
    await cubit.setProductColumns(ThemeCubit.productColumnOptions.last);
    await tester.pumpAndSettle();
    expect(tester.takeException(), isNull);
  });

  testWidgets('the grid survives the largest text size in Arabic',
      (tester) async {
    // The worst case the settings can produce: the taller script and every
    // label grown by the OS, in the tile that has the least room to give.
    await pumpGrid(tester,
        size: const Size(430, 932), locale: const Locale('ar'), textScale: 1.3);
    expect(tester.takeException(), isNull);
  });

  testWidgets('the card yields the photo when the box is short', (tester) async {
    // What a late-arriving font does: the box was measured and handed out
    // before the real face downloaded, so the words turn up taller than the
    // room reserved for them. The stage absorbs it; nothing overflows.
    late double caption;
    await tester.pumpWidget(MaterialApp(
      theme: buildPearlTheme(PearlPalette.light),
      supportedLocales: L.supportedLocales,
      localizationsDelegates: L.localizationsDelegates,
      home: Scaffold(
        body: Center(
          child: Builder(builder: (context) {
            caption = ProductCard.captionHeight(context);
            return SizedBox(
              width: 160,
              // Ten pixels less than the card was told it would get.
              height: 160 + caption - 10,
              child: ProductCard(product: products.first, width: 160),
            );
          }),
        ),
      ),
    ));
    await tester.pumpAndSettle();

    expect(tester.takeException(), isNull);
    expect(caption, greaterThan(0));
  });

  testWidgets('the card asks for more room in Arabic than in English',
      (tester) async {
    late double english;
    late double arabic;

    Future<double> measure(bool arabicScript) async {
      PearlText.useArabic(arabicScript);
      late double height;
      await tester.pumpWidget(MaterialApp(
        home: Builder(builder: (context) {
          height = ProductCard.captionHeight(context);
          return const SizedBox.shrink();
        }),
      ));
      return height;
    }

    english = await measure(false);
    arabic = await measure(true);

    // Not a magic number: Arabic is given extra leading by the type scale, so
    // whatever the caption costs in English it costs more here.
    expect(arabic, greaterThan(english));
  });
}
