import 'package:flutter_test/flutter_test.dart';
import 'package:invo/shared/domain/models/index.dart';
import 'package:invo/shared/domain/services/catalog_snapshot_service.dart';

import 'support/offline_harness.dart';

/// The snapshot is what lets the New Sale screen keep working with no network,
/// so it has to answer the same three questions the live endpoints answer:
/// paged products, categories, and a barcode lookup.
void main() {
  late CatalogSnapshotService snapshot;

  const branch = 1;
  const otherBranch = 2;

  setUp(() async {
    await setUpOfflineHarness();
    snapshot = CatalogSnapshotService();
  });

  tearDown(tearDownOfflineHarness);

  Map<String, dynamic> product({
    required int id,
    required String name,
    String code = '',
    String barcode = '',
    String type = 'product',
    int? categoryId,
    double stock = 10,
    int priority = 0,
  }) =>
      {
        'id': id,
        'name': name,
        'code': code.isEmpty ? 'C$id' : code,
        'barcode': barcode,
        'type': type,
        'mrp': 25.0,
        'tax': 0,
        'total_stock': stock,
        'priority': priority,
        'thumbnail': null,
        if (categoryId != null) 'main_category': {'id': categoryId, 'name': 'Cat $categoryId'},
      };

  Future<void> seed({int branchId = branch}) => snapshot.replace(
        branchId: branchId,
        products: [
          product(id: 1, name: 'Shampoo', barcode: '1000001', categoryId: 5),
          product(id: 2, name: 'Conditioner', barcode: '1000002', categoryId: 5),
          product(id: 3, name: 'Haircut', type: 'service', categoryId: 6, stock: 0),
        ],
        categoriesByType: {
          '': const [Category(id: 5, name: 'Hair', productCount: 2)],
          'product': const [Category(id: 5, name: 'Hair', productCount: 2)],
          'service': const [Category(id: 6, name: 'Salon', productCount: 1)],
        },
      );

  test('a replaced snapshot reads back with its meta', () async {
    await seed();

    final meta = await snapshot.meta(branch);
    expect(meta, isNotNull);
    expect(meta!.productCount, 3);
    expect(meta.age.inSeconds, lessThan(5));
  });

  test('replace swaps the whole branch catalog rather than merging into it', () async {
    await seed();
    await snapshot.replace(
      branchId: branch,
      products: [product(id: 9, name: 'Only Product')],
      categoriesByType: const {},
    );

    final page = await snapshot.products(branchId: branch);
    expect(page.items.map((p) => p.id), [9]);
    // A stale row from the previous catalog would still be sellable at a price
    // the shop no longer charges.
    expect(page.total, 1);
  });

  test('one branch snapshot never leaks into another', () async {
    await seed();
    await snapshot.replace(
      branchId: otherBranch,
      products: [product(id: 42, name: 'Other Branch Only')],
      categoriesByType: const {},
    );

    expect((await snapshot.products(branchId: branch)).total, 3);
    expect((await snapshot.products(branchId: otherBranch)).items.single.id, 42);
  });

  test('search matches name, code and barcode', () async {
    await seed();

    expect((await snapshot.products(branchId: branch, search: 'sham')).items.single.name, 'Shampoo');
    expect((await snapshot.products(branchId: branch, search: 'C2')).items.single.id, 2);
    expect((await snapshot.products(branchId: branch, search: '1000002')).items.single.id, 2);
  });

  test('a typed wildcard is matched literally, not as a wildcard', () async {
    await snapshot.replace(
      branchId: branch,
      products: [product(id: 1, name: '50% Off Bundle'), product(id: 2, name: 'Plain')],
      categoriesByType: const {},
    );

    // Without escaping, '%' would match every row.
    final hits = await snapshot.products(branchId: branch, search: '%');
    expect(hits.items.map((p) => p.id), [1]);
  });

  test('type and category filter the same way the grid does', () async {
    await seed();

    expect((await snapshot.products(branchId: branch, type: 'service')).items.single.id, 3);
    expect((await snapshot.products(branchId: branch, mainCategoryId: 5)).total, 2);
    expect((await snapshot.categories(branchId: branch, type: 'service')).single.name, 'Salon');
    expect((await snapshot.categories(branchId: branch)).single.name, 'Hair');
  });

  test('paging reports a last page the grid can stop on', () async {
    await seed();

    final first = await snapshot.products(branchId: branch, perPage: 2);
    expect(first.items, hasLength(2));
    expect(first.currentPage, 1);
    expect(first.lastPage, 2);
    expect(first.hasMore, isTrue);

    final second = await snapshot.products(branchId: branch, page: 2, perPage: 2);
    expect(second.items, hasLength(1));
    expect(second.hasMore, isFalse);
    // No row appears on both pages.
    expect({...first.items.map((p) => p.id), ...second.items.map((p) => p.id)}, hasLength(3));
  });

  test('barcode lookup resolves by barcode or by product code', () async {
    await seed();

    expect((await snapshot.productByBarcode(branchId: branch, barcode: '1000001'))!.id, 1);
    expect((await snapshot.productByBarcode(branchId: branch, barcode: 'C3'))!.id, 3);
    expect(await snapshot.productByBarcode(branchId: branch, barcode: 'nope'), isNull);
    expect(await snapshot.productByBarcode(branchId: branch, barcode: '  '), isNull);
  });

  test('reduceStock takes queued sales off the shelf and stops at zero', () async {
    await seed();

    await snapshot.reduceStock(branchId: branch, soldByProductId: {1: 4, 2: 999});

    final rows = await snapshot.products(branchId: branch, type: 'product');
    final byId = {for (final p in rows.items) p.id: p.totalStock};
    expect(byId[1], 6);
    // Clamped: a negative figure would read as a data bug rather than "none left".
    expect(byId[2], 0);
  });

  test('clear empties every branch', () async {
    await seed();
    await snapshot.clear();

    expect(await snapshot.meta(branch), isNull);
    expect((await snapshot.products(branchId: branch)).total, 0);
  });
}
