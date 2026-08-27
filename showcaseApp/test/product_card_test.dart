import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:showcase/l10n/app_localizations.dart';
import 'package:showcase/shared/domain/models/index.dart';
import 'package:showcase/shared/utils/components/theme/pearl_theme.dart';
import 'package:showcase/shared/widgets/product_card.dart';

/// The grid gives every tile its height before the words inside it are laid
/// out, so the card and the grid have to agree on how tall those words are. In
/// Arabic they are taller, at a large text size taller again — and when the two
/// disagreed the tile overflowed and the price was painted over stripes.
void main() {
  tearDown(() => PearlText.useArabic(false));

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

  Future<void> pumpGrid(
    WidgetTester tester, {
    required Size size,
    Locale locale = const Locale('en'),
    double textScale = 1,
  }) async {
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
        home: MediaQuery.withClampedTextScaling(
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
    );
    await tester.pumpAndSettle();
  }

  /// Every column count the grid can choose, including the 3-up tile at ~119
  /// logical pixels that the overflow was first reported on.
  const sizes = <String, Size>{
    'phone, 2-up': Size(375, 812),
    'wide phone, 3-up': Size(430, 932),
    'tablet, 4-up': Size(1024, 1366),
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
