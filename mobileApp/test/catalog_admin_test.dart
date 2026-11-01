import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:google_fonts/google_fonts.dart';

import 'package:invo/features/catalog/domain/models/catalog_models.dart';
import 'package:invo/features/catalog/logic/catalog_view_cubit/catalog_view_cubit.dart';
import 'package:invo/features/catalog/screens/v3/catalog_form_screen.dart';
import 'package:invo/features/catalog/screens/v3/catalog_list_screen.dart';
import 'package:invo/features/catalog/screens/v3/product_config_screen.dart';
import 'package:invo/features/catalog/widgets/v3/catalog_toolbar.dart';
import 'package:invo/features/shell/screens/v3/home_shell.dart';
import 'package:invo/shared/utils/local_storage/local_storage_service.dart';
import 'package:invo/shared/widgets/astra_bottom_nav.dart';
import 'package:invo/shared/widgets/astra_side_rail.dart';
import 'package:shared_preferences/shared_preferences.dart';

import 'support/test_harness.dart';

/// Settings → Catalog: the products / services table, its view preferences,
/// the full-page form and Product configuration.
void main() {
  setUpAll(() => GoogleFonts.config.allowRuntimeFetching = false);

  group('CatalogViewCubit', () {
    late LocalStorageService storage;

    setUp(() async {
      SharedPreferences.setMockInitialValues({});
      storage = await LocalStorageService.create();
    });

    CatalogViewCubit cubit() => CatalogViewCubit(type: CatalogType.product, storage: storage, userId: '14');

    test('starts on the web defaults: every column but Image, newest first', () {
      final state = cubit().state;
      expect(state.showImages, isFalse);
      expect(state.shownColumns.first.key, 'name');
      expect(state.shownColumns.map((c) => c.key), containsAll(['id', 'department', 'barcode', 'cost', 'mrp']));
      expect(state.query.sortKey, 'id');
      expect(state.query.ascending, isFalse);
    });

    test('a header tap starts descending and the same header flips it', () {
      final c = cubit()..sortBy('mrp');
      expect((c.state.query.sortKey, c.state.query.ascending), ('mrp', false));
      c.sortBy('mrp');
      expect(c.state.query.ascending, isTrue);
      c.sortBy('name');
      expect((c.state.query.sortKey, c.state.query.ascending), ('name', false));
    });

    test('remembers columns, view and sort for the same user', () {
      cubit()
        ..toggleColumn('barcode')
        ..toggleColumn(CatalogColumn.imageKey)
        ..setGallery(true)
        ..sortBy('department');

      final again = cubit().state;
      expect(again.visible.contains('barcode'), isFalse);
      expect(again.showImages, isTrue);
      expect(again.gallery, isTrue);
      expect(again.query.sortKey, 'department');
      // Another user on the same till starts on the defaults.
      expect(CatalogViewCubit(type: CatalogType.product, storage: storage, userId: '99').state.gallery, isFalse);
    });

    test('reset restores the default columns and sort', () {
      final c = cubit()
        ..toggleColumn('barcode')
        ..sortBy('name')
        ..resetColumns();
      expect(c.state.visible.contains('barcode'), isTrue);
      expect(c.state.query.sortKey, 'id');
    });
  });

  group('models', () {
    test('a service payload carries its duration and no product-only fields', () {
      const item = CatalogItem(name: 'Balayage', mrp: 450, time: 150, mainCategory: CatalogRef.create('Colour'));
      final payload = item.toPayload(CatalogType.service);
      expect(payload['time'], 150);
      expect(payload['main_category_id'], 'add Colour');
      expect(payload.containsKey('unit_id'), isFalse);
      expect(payload.containsKey('barcode_number'), isFalse);
    });

    test('a product payload sends picker ids and the selling flag', () {
      const item = CatalogItem(name: 'Lipstick', unit: CatalogRef.existing(5, 'Pcs'), department: CatalogRef.existing(1, 'Beauty'), isSelling: false);
      final payload = item.toPayload(CatalogType.product);
      expect(payload['unit_id'], 5);
      expect(payload['department_id'], 1);
      expect(payload['is_selling'], isFalse);
      expect(payload.containsKey('time'), isFalse);
    });

    test('price breakdown treats prices as tax-inclusive', () {
      final b = PriceBreakdown(cost: 42, mrp: 84, taxPercent: 5);
      expect(b.costWithoutTax, closeTo(40, 0.001));
      expect(b.mrpWithoutTax, closeTo(80, 0.001));
      expect(b.profit, closeTo(40, 0.001));
      expect(b.marginPercent, closeTo(50, 0.001));
    });

    test('product configuration maps the web yes/no keys both ways', () {
      final config = ProductConfig.fromJson(const {
        'barcode_type': 'product_wise',
        'barcode_prefix': 'LS',
        'sync_barcode_to_code': 'yes',
        'hide_out_of_stock_sale_items': 'no',
      });
      expect(config.barcodeTypedPerProduct, isTrue);
      expect(config.barcodeIsSku, isTrue);
      expect(config.toJson()['hide_out_of_stock_sale_items'], 'no');
      expect(ProductConfig.fromJson(const {'barcode_type': ''}).barcodeTypedPerProduct, isFalse);
    });
  });

  group('screens', () {
    late TestHarness d;

    setUp(() async {
      d = TestHarness();
      await d.init();
    });
    tearDown(() => d.dispose());

    Future<void> pumpAt(WidgetTester tester, Size size, Widget child) async {
      tester.view.physicalSize = size;
      tester.view.devicePixelRatio = 1.0;
      addTearDown(tester.view.resetPhysicalSize);
      addTearDown(tester.view.resetDevicePixelRatio);
      await tester.pumpWidget(d.wrap(child));
      await tester.pump();
      await tester.pump(const Duration(milliseconds: 500));
      expect(tester.takeException(), isNull);
    }

    const sizes = {'phone': Size(390, 844), 'small phone': Size(360, 640), 'tablet': Size(1194, 834), 'till': Size(1097, 686)};
    final screens = <String, Widget>{
      'Products table': const CatalogListScreen(type: CatalogType.product),
      'Services table': const CatalogListScreen(type: CatalogType.service),
      'New product': const CatalogFormScreen(type: CatalogType.product),
      'Edit product': const CatalogFormScreen(type: CatalogType.product, id: 124),
      'Edit service': const CatalogFormScreen(type: CatalogType.service, id: 42),
      // Hosted by Settings, which supplies the scrolling.
      'Product configuration': const Scaffold(body: SingleChildScrollView(child: ProductConfigScreen())),
    };

    for (final screen in screens.entries) {
      for (final size in sizes.entries) {
        testWidgets('${screen.key} renders at ${size.key}', (tester) async {
          await pumpAt(tester, size.value, screen.value);
        });
      }
    }

    testWidgets('the phone keeps the bottom nav with Settings active', (tester) async {
      await pumpAt(tester, const Size(390, 844), const CatalogListScreen(type: CatalogType.product));
      final nav = tester.widget<AstraNavBar>(find.byType(AstraNavBar));
      expect(nav.activeIndex, 3);
    });

    testWidgets('tapping a column header re-queries sorted by it', (tester) async {
      await pumpAt(tester, const Size(1194, 834), const CatalogListScreen(type: CatalogType.product));
      // Headers are upper-cased; Unit is on screen without scrolling sideways.
      await tester.tap(find.text('UNIT'));
      await tester.pump(const Duration(milliseconds: 100));
      expect(d.catalog.queries.last.sortKey, 'unit');
      expect(d.catalog.queries.last.ascending, isFalse);
    });

    testWidgets('the gallery shows the same rows as tiles', (tester) async {
      await pumpAt(tester, const Size(390, 844), const CatalogListScreen(type: CatalogType.product));
      await tester.tap(find.descendant(of: find.byType(CatalogToolbar), matching: find.byIcon(Icons.grid_view)));
      await tester.pump(const Duration(milliseconds: 300));
      expect(find.text('Argan Repair Shampoo'), findsOneWidget);
      expect(tester.takeException(), isNull);
    });

    // Products opened from Settings is a shell destination: switching to
    // another module and back to Settings returns to it with its state, the
    // same instant swap as Sales or Returns.
    for (final size in const {'phone': Size(390, 844), 'tablet': Size(1194, 834)}.entries) {
      testWidgets('Products keeps its place across tabs on ${size.key}', (tester) async {
        await pumpAt(tester, size.value, const HomeShell(initialTab: 3));
        final listShown = find.text('Settings › Catalog');

        // Both size classes list a Catalog category holding the links — the
        // tablet's rail, the phone's category list.
        await tester.tap(find.text('Catalog'));
        await tester.pump(const Duration(milliseconds: 300));
        await tester.tap(find.text('Products').first);
        await tester.pump(const Duration(milliseconds: 500));
        expect(listShown, findsOneWidget);

        HomeShell.selectTab(1); // Sales
        await tester.pump(const Duration(milliseconds: 300));
        expect(listShown, findsNothing);

        HomeShell.selectTab(3); // Settings → back on Products
        await tester.pump(const Duration(milliseconds: 300));
        expect(listShown, findsOneWidget);
        expect(d.catalog.queries, hasLength(1), reason: 'kept alive, not reloaded');

        HomeShell.selectTab(3); // Settings again → Settings itself
        await tester.pump(const Duration(milliseconds: 300));
        expect(listShown, findsNothing);
        if (size.key == 'phone') {
          // …on its category list, not the Catalog page under it.
          expect(find.text('Products'), findsNothing);
          expect(find.text('Sale Configuration'), findsOneWidget);
        }
        expect(tester.takeException(), isNull);
      });

      testWidgets('a half-filled New form keeps its place across tabs on ${size.key}', (tester) async {
        await pumpAt(tester, size.value, const HomeShell(initialTab: kProductsTab));
        await tester.tap(find.text('New'));
        await tester.pump(); // builds the form, which then loads
        await tester.pump(const Duration(milliseconds: 500));
        expect(find.text('New product'), findsOneWidget);
        await tester.enterText(find.byType(TextField).first, 'Half-typed lipstick');

        HomeShell.selectTab(1); // Sales
        await tester.pump(const Duration(milliseconds: 300));
        expect(find.text('New product'), findsNothing);

        HomeShell.selectTab(3); // Settings → back on the form, text intact
        await tester.pump(const Duration(milliseconds: 300));
        expect(find.text('New product'), findsOneWidget);
        // In the field and on the live POS preview.
        expect(find.text('Half-typed lipstick'), findsNWidgets(2));

        await tester.tap(find.byIcon(Icons.arrow_back)); // closes to the list
        await tester.pump(const Duration(milliseconds: 300));
        expect(find.text('New product'), findsNothing);
        expect(find.text('Settings › Catalog'), findsOneWidget);
        expect(tester.takeException(), isNull);
      });
    }

    // Product configuration is a Settings category of its own, not a link
    // inside Catalog.
    testWidgets('the phone opens Product configuration from the Settings list', (tester) async {
      await pumpAt(tester, const Size(390, 844), const HomeShell(initialTab: 3));
      await tester.tap(find.text('Catalog'));
      await tester.pump(const Duration(milliseconds: 300));
      expect(find.text('Product configuration'), findsNothing);

      await tester.tap(find.byIcon(Icons.arrow_back)); // back to the list
      await tester.pump(const Duration(milliseconds: 300));
      await tester.tap(find.text('Product configuration'));
      await tester.pump(); // builds the page, which then loads
      await tester.pump(const Duration(milliseconds: 500));
      expect(find.text('Barcode prefix'), findsOneWidget);
      expect(find.text('Hide out-of-stock items'), findsOneWidget);
      expect(tester.takeException(), isNull);
    });

    testWidgets('the tablet rail lists Product configuration beside Catalog', (tester) async {
      await pumpAt(tester, const Size(1194, 834), const HomeShell(initialTab: 3));
      await tester.tap(find.text('Product configuration'));
      await tester.pump(); // builds the page, which then loads
      await tester.pump(const Duration(milliseconds: 500));
      expect(find.text('Barcode prefix'), findsOneWidget);

      await tester.tap(find.text('Catalog'));
      await tester.pump(const Duration(milliseconds: 300));
      expect(find.text('Barcode prefix'), findsNothing);
      expect(tester.takeException(), isNull);
    });

    testWidgets('saving an empty form flags the name instead of calling the server', (tester) async {
      await pumpAt(tester, const Size(1194, 834), const CatalogFormScreen(type: CatalogType.product));
      await tester.tap(find.text('Save'));
      await tester.pump(const Duration(milliseconds: 100));
      expect(find.text('Enter a name'), findsOneWidget);
      expect(d.catalog.saved, isEmpty);
    });
  });
}
