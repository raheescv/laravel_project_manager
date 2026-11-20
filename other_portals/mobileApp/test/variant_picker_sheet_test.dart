import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:google_fonts/google_fonts.dart';

import 'package:invo/features/sale/widgets/v3/variant_picker_sheet.dart';
import 'package:invo/shared/domain/models/index.dart';

import 'support/test_harness.dart';

/// The family picker on New Sale follows the web POS "Studio" design: a phone
/// gets one column under a pinned header with the colours as a swipe strip; a
/// tablet gets two columns. One tap on a size returns that item.
void main() {
  setUpAll(() => GoogleFonts.config.allowRuntimeFetching = false);

  Product item(int id, String size, String colour, String hex, num stock) => Product(
        id: id, code: 'RS', name: 'Running Shoe', barcode: '77$id', mrp: id == 3 ? 160 : 149, tax: 0, type: 'product',
        categoryName: 'Shoes', duration: '', totalStock: stock, thumbnail: '', parentId: id == 1 ? null : 1,
        size: size, color: colour, colorName: colour, colorHex: hex,
      );

  final family = Product.family([
    item(1, '41', 'Black', '#1f2328', 2),
    item(2, '42', 'Black', '#1f2328', 0),
    item(3, '42', 'Blue', '#2563eb', 3),
  ]);

  Future<Product?> open(WidgetTester tester, Size screen, {required Future<void> Function() act}) async {
    tester.view.physicalSize = screen;
    tester.view.devicePixelRatio = 1;
    addTearDown(tester.view.reset);
    final d = TestHarness();
    await d.init();
    addTearDown(d.dispose);
    Product? picked;
    await tester.pumpWidget(d.wrap(Scaffold(
      body: Builder(builder: (context) => Center(
            child: TextButton(onPressed: () async => picked = await pickVariant(context, family), child: const Text('open')),
          )),
    )));
    await tester.tap(find.text('open'));
    await tester.pumpAndSettle();
    await act();
    await tester.pumpAndSettle();
    return picked;
  }

  testWidgets('phone: pinned header, colour strip, and a size key adds that item', (tester) async {
    final picked = await open(tester, const Size(390, 844), act: () async {
      expect(find.text('Running Shoe'), findsOneWidget);
      expect(find.text('SIZE IN BLACK'), findsOneWidget);
      expect(find.byType(ListView), findsOneWidget, reason: 'colours swipe sideways on a phone');
      expect(find.text('Out of stock'), findsOneWidget, reason: '42 / Black has none left');

      await tester.tap(find.text('Blue'));
      await tester.pumpAndSettle();
      expect(find.text('SIZE IN BLUE'), findsOneWidget);
      expect(find.text('Not in Blue'), findsOneWidget, reason: 'Blue has no 41: a ghosted key');

      await tester.tap(find.text('42'));
    });
    expect(picked?.id, 3);
  });

  testWidgets('tablet: two columns with the colours as a grid, close returns nothing', (tester) async {
    final picked = await open(tester, const Size(1024, 768), act: () async {
      expect(find.byType(ListView), findsNothing, reason: 'colours sit in a grid beside the keypad');
      expect(find.text('SIZE IN BLACK'), findsOneWidget);
      await tester.tap(find.byIcon(Icons.close_rounded));
    });
    expect(picked, isNull);
  });

  testWidgets('a colour with no photo of its own shows the family photo, not a blank stage', (tester) async {
    Product shot(int id, String colour, String hex, num stock, String photo) => Product(
          id: id, code: 'RS', name: 'Running Shoe', barcode: '88$id', mrp: 149, tax: 0, type: 'product',
          categoryName: 'Shoes', duration: '', totalStock: stock, thumbnail: photo, parentId: id == 10 ? null : 10,
          size: '42', color: colour, colorName: colour, colorHex: hex,
        );
    final photographed = Product.family([
      shot(10, 'Black', '#1f2328', 0, 'https://example.test/storage/products/10/black.png'),
      shot(11, 'Blue', '#2563eb', 2, ''),
    ]);
    tester.view.physicalSize = const Size(390, 844);
    tester.view.devicePixelRatio = 1;
    addTearDown(tester.view.reset);
    final d = TestHarness();
    await d.init();
    addTearDown(d.dispose);
    await tester.pumpWidget(d.wrap(Scaffold(
      body: Builder(builder: (context) => Center(
            child: TextButton(onPressed: () => pickVariant(context, photographed), child: const Text('open')),
          )),
    )));
    await tester.tap(find.text('open'));
    await tester.pumpAndSettle();

    expect(find.text('SIZE IN BLUE'), findsOneWidget, reason: 'opens on the colour in stock');
    expect(find.byType(Image), findsNWidgets(2),
        reason: "Black's thumbnail, plus the family photo standing in on the stage for Blue, which was never photographed");
  });

  test('sizes sort numbers first, by value', () {
    expect(sortedSizes(['10', '9.5', 'M', '42', 'L']), ['9.5', '10', '42', 'L', 'M']);
  });
}
