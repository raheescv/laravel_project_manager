import 'dart:convert';

import 'package:sqflite/sqflite.dart';

import '../../utils/local_storage/offline_db.dart';
import '../helpers/formatters.dart';
import '../models/index.dart';
import '../repository/catalog_snapshot_repository.dart';

/// sqflite-backed [CatalogSnapshotRepository].
///
/// Rows keep the server's own product JSON in `payload` and lift out only the
/// columns that have to be queryable, so [Product.fromJson] parses a cached row
/// exactly as it parses a live response — a snapshot cannot drift from the wire
/// format the way a column-per-field mirror would.
class CatalogSnapshotService implements CatalogSnapshotRepository {
  Future<Database> get _db => OfflineDb.instance();

  /// The key a null (All Types) filter is stored under — a SQLite primary key
  /// column cannot be null.
  static const String _allTypes = '';

  @override
  Future<void> replace({
    required int branchId,
    required List<Map<String, dynamic>> products,
    required Map<String, List<Category>> categoriesByType,
  }) async {
    final db = await _db;
    await db.transaction((txn) async {
      await txn.delete(OfflineDb.tableProducts, where: 'branch_id = ?', whereArgs: [branchId]);
      await txn.delete(OfflineDb.tableCategories, where: 'branch_id = ?', whereArgs: [branchId]);

      final batch = txn.batch();
      for (final json in products) {
        final main = json['main_category'];
        batch.insert(OfflineDb.tableProducts, {
          'branch_id': branchId,
          'product_id': asNum(json['id']).toInt(),
          'name': asStr(json['name']),
          'code': asStr(json['code']),
          'barcode': asStr(json['barcode']),
          'type': asStr(json['type']).isEmpty ? 'service' : asStr(json['type']),
          'category_id': main is Map ? asNum(main['id']).toInt() : null,
          'total_stock': asNum(json['total_stock']).toDouble(),
          'priority': asNum(json['priority']).toInt(),
          'payload': jsonEncode(json),
        }, conflictAlgorithm: ConflictAlgorithm.replace);
      }
      for (final entry in categoriesByType.entries) {
        for (final category in entry.value) {
          batch.insert(OfflineDb.tableCategories, {
            'branch_id': branchId,
            'category_id': category.id,
            'type': entry.key,
            'name': category.name,
            'product_count': category.productCount,
          }, conflictAlgorithm: ConflictAlgorithm.replace);
        }
      }
      await batch.commit(noResult: true);

      await txn.insert(
        OfflineDb.tableMeta,
        {
          'branch_id': branchId,
          'synced_at': DateTime.now().millisecondsSinceEpoch,
          'product_count': products.length,
        },
        conflictAlgorithm: ConflictAlgorithm.replace,
      );
    });
  }

  @override
  Future<CatalogSnapshotMeta?> meta(int branchId) async {
    final db = await _db;
    final rows = await db.query(OfflineDb.tableMeta,
        where: 'branch_id = ?', whereArgs: [branchId], limit: 1);
    if (rows.isEmpty) return null;
    return CatalogSnapshotMeta(
      syncedAt: DateTime.fromMillisecondsSinceEpoch(asNum(rows.first['synced_at']).toInt()),
      productCount: asNum(rows.first['product_count']).toInt(),
    );
  }

  @override
  Future<Paginated<Product>> products({
    required int branchId,
    String? search,
    int? mainCategoryId,
    String? type,
    int page = 1,
    int perPage = 20,
  }) async {
    final db = await _db;
    final where = StringBuffer('branch_id = ?');
    final args = <Object?>[branchId];

    if (type != null && type.isNotEmpty) {
      where.write(' AND type = ?');
      args.add(type);
    }
    if (mainCategoryId != null) {
      where.write(' AND category_id = ?');
      args.add(mainCategoryId);
    }
    final term = search?.trim() ?? '';
    if (term.isNotEmpty) {
      // Mirrors what the server's search does across the three fields the
      // cashier actually types into. LIKE is case-insensitive for ASCII in
      // SQLite; `escape` keeps a typed % or _ literal instead of turning the
      // search into a wildcard.
      where.write(' AND (name LIKE ? ESCAPE ? OR code LIKE ? ESCAPE ? OR barcode LIKE ? ESCAPE ?)');
      final like = '%${_escapeLike(term)}%';
      args.addAll([like, r'\', like, r'\', like, r'\']);
    }

    final counted = await db.rawQuery(
      'SELECT COUNT(*) AS c FROM ${OfflineDb.tableProducts} WHERE $where',
      args,
    );
    final total = asNum(counted.first['c']).toInt();

    final rows = await db.query(
      OfflineDb.tableProducts,
      columns: ['payload', 'total_stock'],
      where: where.toString(),
      whereArgs: args,
      orderBy: 'priority DESC, name COLLATE NOCASE ASC',
      limit: perPage,
      offset: (page - 1) * perPage,
    );

    return Paginated(
      items: rows.map(_toProduct).toList(),
      currentPage: page,
      lastPage: total == 0 ? 1 : (total / perPage).ceil(),
      total: total,
    );
  }

  @override
  Future<Product?> productByBarcode({required int branchId, required String barcode}) async {
    final code = barcode.trim();
    if (code.isEmpty) return null;
    final db = await _db;
    // Scanners produce the barcode, but staff also key in the product code for
    // items whose label has rubbed off — the online endpoint accepts either.
    final rows = await db.query(
      OfflineDb.tableProducts,
      columns: ['payload', 'total_stock'],
      where: 'branch_id = ? AND (barcode = ? OR code = ?)',
      whereArgs: [branchId, code, code],
      limit: 1,
    );
    if (rows.isEmpty) return null;
    return _toProduct(rows.first);
  }

  @override
  Future<List<Category>> categories({required int branchId, String? type}) async {
    final db = await _db;
    final rows = await db.query(
      OfflineDb.tableCategories,
      where: 'branch_id = ? AND type = ?',
      whereArgs: [branchId, type ?? _allTypes],
      orderBy: 'name COLLATE NOCASE ASC',
    );
    return rows
        .map((r) => Category(
              id: asNum(r['category_id']).toInt(),
              name: asStr(r['name']),
              productCount: asNum(r['product_count']).toInt(),
            ))
        .toList();
  }

  @override
  Future<void> reduceStock({required int branchId, required Map<int, double> soldByProductId}) async {
    if (soldByProductId.isEmpty) return;
    final db = await _db;
    final batch = db.batch();
    soldByProductId.forEach((productId, qty) {
      // Clamped at zero: a negative cached figure would read as a data bug on
      // the tile rather than as "none left", which is all it can mean here.
      batch.rawUpdate(
        'UPDATE ${OfflineDb.tableProducts} SET total_stock = MAX(0, total_stock - ?) '
        'WHERE branch_id = ? AND product_id = ?',
        [qty, branchId, productId],
      );
    });
    await batch.commit(noResult: true);
  }

  @override
  Future<void> restoreStock({required int branchId, required Map<int, double> byProductId}) async {
    if (byProductId.isEmpty) return;
    final db = await _db;
    final batch = db.batch();
    byProductId.forEach((productId, qty) {
      // No clamp on the way up: the corrected quantity is taken straight after, and
      // the next refresh overwrites the figure from the server anyway.
      batch.rawUpdate(
        'UPDATE ${OfflineDb.tableProducts} SET total_stock = total_stock + ? '
        'WHERE branch_id = ? AND product_id = ?',
        [qty, branchId, productId],
      );
    });
    await batch.commit(noResult: true);
  }

  // ---- reference lists ----

  @override
  Future<void> replaceLookups({
    required int branchId,
    required SnapshotLookup kind,
    required List<Map<String, dynamic>> rows,
  }) async {
    final db = await _db;
    if (rows.isEmpty && await lookupCount(branchId: branchId, kind: kind) > 0) return;

    await db.transaction((txn) async {
      await txn.delete(OfflineDb.tableLookups,
          where: 'branch_id = ? AND kind = ?', whereArgs: [branchId, kind.name]);
      final batch = txn.batch();
      for (var i = 0; i < rows.length; i++) {
        final row = rows[i];
        final name = asStr(row['name']);
        // Everything a cashier might type, lowercased once here rather than on
        // every keystroke.
        final search = [name, asStr(row['mobile']), asStr(row['code'])]
            .where((s) => s.isNotEmpty)
            .join(' ')
            .toLowerCase();
        batch.insert(OfflineDb.tableLookups, {
          'branch_id': branchId,
          'kind': kind.name,
          'entity_id': asNum(row['id']).toInt(),
          'name': name,
          'search': search,
          'sort_order': i,
          'payload': jsonEncode(row),
        }, conflictAlgorithm: ConflictAlgorithm.replace);
      }
      await batch.commit(noResult: true);
    });
  }

  @override
  Future<List<PaymentMethod>> paymentMethods({required int branchId}) async =>
      (await _lookupRows(branchId: branchId, kind: SnapshotLookup.paymentMethod))
          .map(PaymentMethod.fromJson)
          .toList();

  @override
  Future<List<Employee>> employees({required int branchId, String? search}) async =>
      (await _lookupRows(branchId: branchId, kind: SnapshotLookup.employee, search: search))
          .map(Employee.fromJson)
          .toList();

  @override
  Future<List<Customer>> customers({
    required int branchId,
    String? mobile,
    String? search,
  }) async {
    // The client sheet searches by either; locally one field holds both.
    final term = (mobile?.trim().isNotEmpty == true) ? mobile : search;
    return (await _lookupRows(branchId: branchId, kind: SnapshotLookup.customer, search: term, limit: 20))
        .map(Customer.fromJson)
        .toList();
  }

  @override
  Future<int> lookupCount({required int branchId, required SnapshotLookup kind}) async {
    final db = await _db;
    final rows = await db.rawQuery(
      'SELECT COUNT(*) AS c FROM ${OfflineDb.tableLookups} WHERE branch_id = ? AND kind = ?',
      [branchId, kind.name],
    );
    return asNum(rows.first['c']).toInt();
  }

  Future<List<Map<String, dynamic>>> _lookupRows({
    required int branchId,
    required SnapshotLookup kind,
    String? search,
    int? limit,
  }) async {
    final db = await _db;
    final where = StringBuffer('branch_id = ? AND kind = ?');
    final args = <Object?>[branchId, kind.name];
    final term = search?.trim() ?? '';
    if (term.isNotEmpty) {
      where.write(' AND search LIKE ? ESCAPE ?');
      args.addAll(['%${_escapeLike(term.toLowerCase())}%', r'\']);
    }
    final rows = await db.query(
      OfflineDb.tableLookups,
      columns: ['payload'],
      where: where.toString(),
      whereArgs: args,
      orderBy: 'sort_order ASC',
      limit: limit,
    );
    return rows
        .map((r) => Map<String, dynamic>.from(jsonDecode(r['payload'] as String) as Map))
        .toList();
  }

  @override
  Future<void> clear() => OfflineDb.clearCatalog();

  /// Rebuild a [Product] from a stored row — the same parse the live response
  /// goes through, which is the point of storing the raw JSON.
  ///
  /// `total_stock` is taken from the column rather than the payload: it is the
  /// one field this device writes back (see [reduceStock]), so reading it out of
  /// the frozen JSON would show the figure from the last refresh and every
  /// queued sale would be invisible to the low-stock warning.
  Product _toProduct(Map<String, Object?> row) {
    final json = Map<String, dynamic>.from(jsonDecode(row['payload'] as String) as Map);
    json['total_stock'] = asNum(row['total_stock']);
    return Product.fromJson(json);
  }

  /// Neutralise LIKE's own wildcards so a typed `%` matches a literal `%`.
  String _escapeLike(String value) => value
      .replaceAll(r'\', r'\\')
      .replaceAll('%', r'\%')
      .replaceAll('_', r'\_');
}
