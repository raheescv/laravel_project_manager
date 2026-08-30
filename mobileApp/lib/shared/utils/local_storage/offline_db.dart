import 'package:flutter/foundation.dart' show visibleForTesting;
import 'package:path/path.dart' as p;
import 'package:sqflite/sqflite.dart';

/// The single sqflite database behind offline selling.
///
/// Two concerns share it because both are relational and both must survive a
/// process kill, which the prefs-backed [LocalStorageService] cannot promise:
///
///  * `catalog_*` — a per-branch snapshot of what is sellable, so the New Sale
///    screen keeps browsing, searching and scanning with no network.
///  * `outbox_sales` — sales rung up offline, replayed until the server
///    acknowledges them.
///
/// Everything scalar (theme, print options, remembered stylist) stays in
/// [LocalStorageService]; this is only for rows.
class OfflineDb {
  OfflineDb._();

  static const String _fileName = 'invo_offline.db';
  static const int _version = 4;

  static const String tableProducts = 'catalog_products';
  static const String tableCategories = 'catalog_categories';
  static const String tableMeta = 'catalog_meta';
  static const String tableOutbox = 'outbox_sales';

  /// The small reference lists the New Sale flow needs but that are not the
  /// catalog: payment methods, staff, customers. One table with a `kind`
  /// discriminator rather than three near-identical ones — they are all
  /// "id + name + something to search on + the server's own JSON".
  static const String tableLookups = 'snapshot_lookups';

  static Database? _db;

  /// Test seam: where the database file lives. Set to `inMemoryDatabasePath`
  /// (with `databaseFactory = databaseFactoryFfi`) so the outbox and snapshot
  /// can be exercised in-process, with no device and no file to clean up.
  @visibleForTesting
  static String? debugPathOverride;

  /// Test seam: drop the open handle so the next [instance] call reopens.
  @visibleForTesting
  static Future<void> resetForTests() async {
    await _db?.close();
    _db = null;
  }

  /// Opens (and migrates) the database, reusing the handle on later calls.
  static Future<Database> instance() async {
    final open = _db;
    if (open != null && open.isOpen) return open;
    final path = debugPathOverride ?? p.join(await getDatabasesPath(), _fileName);
    return _db = await openDatabase(path, version: _version, onCreate: _create, onUpgrade: _upgrade);
  }

  static Future<void> _create(Database db, int version) async {
    // The catalog rows keep the server's own JSON in `payload` and lift out only
    // what has to be queryable. Product.fromJson then parses the payload exactly
    // as it would parse a live response, so a snapshot can never drift from the
    // wire format the way a column-per-field mirror would.
    await db.execute('''
      CREATE TABLE $tableProducts (
        branch_id INTEGER NOT NULL,
        product_id INTEGER NOT NULL,
        name TEXT NOT NULL,
        code TEXT NOT NULL,
        barcode TEXT NOT NULL,
        type TEXT NOT NULL,
        category_id INTEGER,
        total_stock REAL NOT NULL DEFAULT 0,
        priority INTEGER NOT NULL DEFAULT 0,
        thumbnail TEXT NOT NULL DEFAULT '',
        payload TEXT NOT NULL,
        PRIMARY KEY (branch_id, product_id)
      )
    ''');
    // Search hits name/code/barcode; the grid filters on type + category. One
    // index per access path — the table is read on every keystroke.
    await db.execute('CREATE INDEX idx_products_search ON $tableProducts (branch_id, name, code)');
    await db.execute('CREATE INDEX idx_products_barcode ON $tableProducts (branch_id, barcode)');
    await db.execute('CREATE INDEX idx_products_filter ON $tableProducts (branch_id, type, category_id)');

    await db.execute('''
      CREATE TABLE $tableCategories (
        branch_id INTEGER NOT NULL,
        category_id INTEGER NOT NULL,
        type TEXT NOT NULL,
        name TEXT NOT NULL,
        product_count INTEGER NOT NULL DEFAULT 0,
        image_url TEXT NOT NULL DEFAULT '',
        PRIMARY KEY (branch_id, category_id, type)
      )
    ''');

    // When each branch's snapshot was taken, so the UI can say how stale it is.
    await db.execute('''
      CREATE TABLE $tableMeta (
        branch_id INTEGER PRIMARY KEY,
        synced_at INTEGER NOT NULL,
        product_count INTEGER NOT NULL DEFAULT 0
      )
    ''');

    await db.execute('''
      CREATE TABLE $tableOutbox (
        client_uuid TEXT PRIMARY KEY,
        payload TEXT NOT NULL,
        snapshot TEXT NOT NULL,
        provisional_ref TEXT NOT NULL,
        branch_id INTEGER,
        user_id TEXT NOT NULL,
        created_at INTEGER NOT NULL,
        status TEXT NOT NULL,
        attempts INTEGER NOT NULL DEFAULT 0,
        last_attempt_at INTEGER,
        last_error TEXT,
        server_sale_id TEXT,
        invoice_no TEXT
      )
    ''');
    // The drain reads "oldest pending first" on every tick.
    await db.execute('CREATE INDEX idx_outbox_status ON $tableOutbox (status, created_at)');

    await _createLookups(db);
  }

  static Future<void> _createLookups(Database db) async {
    await db.execute('''
      CREATE TABLE $tableLookups (
        branch_id INTEGER NOT NULL,
        kind TEXT NOT NULL,
        entity_id INTEGER NOT NULL,
        name TEXT NOT NULL,
        search TEXT NOT NULL,
        sort_order INTEGER NOT NULL DEFAULT 0,
        payload TEXT NOT NULL,
        PRIMARY KEY (branch_id, kind, entity_id)
      )
    ''');
    // `search` is a pre-lowercased blob of everything a cashier might type
    // (name, mobile, code), so the client sheet's lookup is one indexed LIKE.
    await db.execute('CREATE INDEX idx_lookups_search ON $tableLookups (branch_id, kind, search)');
  }

  /// v1 shipped without [tableLookups]. The upgrade only adds it — the catalog
  /// and, crucially, the outbox are left untouched, because a device upgrading
  /// mid-shift may be holding sales that have not reached the server yet.
  ///
  /// That "only add" rule is deliberate, not a coincidence: an ALTER on
  /// `outbox_sales` that failed halfway would take real takings with it. New
  /// capability gets a new table.
  /// v3 lifts each product's photo URL out of `payload` into its own column, so
  /// the pre-download pass can list every photo the grid will ask for without
  /// decoding a few thousand JSON blobs to find them.
  ///
  /// An ALTER is safe here in a way it would never be on `outbox_sales`: this
  /// table is a disposable copy of the server's catalog. Existing rows get the
  /// default empty string and the next refresh fills them in, so the worst case
  /// is one refresh cycle with nothing to pre-download.
  static Future<void> _upgrade(Database db, int oldVersion, int newVersion) async {
    if (oldVersion < 2) await _createLookups(db);
    if (oldVersion < 3) {
      await db.execute("ALTER TABLE $tableProducts ADD COLUMN thumbnail TEXT NOT NULL DEFAULT ''");
    }
    // v4 keeps each category's photo, for the New Sale rail's image layouts.
    // Same reasoning as v3: a disposable copy of the server's catalog, so an
    // ALTER with a default is safe and the next refresh fills the column in.
    if (oldVersion < 4) {
      await db.execute("ALTER TABLE $tableCategories ADD COLUMN image_url TEXT NOT NULL DEFAULT ''");
    }
  }

  /// Drops every cached row. Called on sign-out so the next user of a shared
  /// till never sees the previous tenant's catalog.
  ///
  /// The outbox is deliberately **not** cleared: an unsynced sale is money that
  /// was taken, and it has to outlive the session that rang it up.
  static Future<void> clearCatalog() async {
    final db = await instance();
    await db.transaction((txn) async {
      await txn.delete(tableProducts);
      await txn.delete(tableCategories);
      await txn.delete(tableMeta);
      await txn.delete(tableLookups);
    });
  }
}
