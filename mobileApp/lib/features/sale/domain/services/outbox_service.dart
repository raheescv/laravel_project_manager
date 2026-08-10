import 'dart:convert';
import 'dart:math';

import 'package:invo/shared/domain/constants/global_variables.dart';
import 'package:invo/shared/domain/helpers/formatters.dart';
import 'package:invo/shared/utils/local_storage/local_storage_service.dart';
import 'package:invo/shared/utils/local_storage/offline_db.dart';
import 'package:sqflite/sqflite.dart';

import '../models/pending_sale.dart';
import '../repository/outbox_repository.dart';

/// sqflite-backed [OutboxRepository].
class OutboxService implements OutboxRepository {
  Future<Database> get _db => OfflineDb.instance();

  LocalStorageService get _storage => serviceLocator<LocalStorageService>();

  @override
  Future<PendingSale> enqueue({
    required String clientUuid,
    required Map<String, dynamic> payload,
    required Map<String, dynamic> saleJson,
    required String userId,
    int? branchId,
  }) async {
    // The key is stable for as long as the ticket is unchanged, so a second
    // press of Charge after a failed attempt arrives with the same one. That is
    // the same sale, already captured — hand back the existing row rather than
    // letting the primary key throw and report a queued sale as failed.
    if (await byUuid(clientUuid) case final existing?) return existing;

    final row = PendingSale(
      clientUuid: clientUuid,
      payload: payload,
      saleJson: saleJson,
      provisionalRef: await _nextProvisionalRef(),
      userId: userId,
      branchId: branchId,
      createdAt: DateTime.now(),
      status: PendingSaleStatus.pending,
    );
    final db = await _db;
    // `insert`, not `upsert`: a uuid is minted per press of Charge, so a clash
    // would mean two different tickets sharing an identity — better to fail
    // loudly here than to overwrite an unsynced sale.
    await db.insert(OfflineDb.tableOutbox, _toRow(row));
    return row;
  }

  @override
  Future<List<PendingSale>> all() async {
    final db = await _db;
    final rows = await db.query(OfflineDb.tableOutbox, orderBy: 'created_at DESC');
    return rows.map(_fromRow).toList();
  }

  @override
  Future<List<PendingSale>> unsynced() async {
    final db = await _db;
    final rows = await db.query(
      OfflineDb.tableOutbox,
      where: 'status IN (?, ?)',
      whereArgs: [PendingSaleStatus.pending.name, PendingSaleStatus.failed.name],
      orderBy: 'created_at ASC',
    );
    return rows.map(_fromRow).toList();
  }

  @override
  Future<int> unsyncedCount() async {
    final db = await _db;
    final rows = await db.rawQuery(
      'SELECT COUNT(*) AS c FROM ${OfflineDb.tableOutbox} WHERE status IN (?, ?, ?)',
      [PendingSaleStatus.pending.name, PendingSaleStatus.failed.name, PendingSaleStatus.syncing.name],
    );
    return asNum(rows.first['c']).toInt();
  }

  @override
  Future<PendingSale?> byUuid(String clientUuid) async {
    final db = await _db;
    final rows = await db.query(OfflineDb.tableOutbox,
        where: 'client_uuid = ?', whereArgs: [clientUuid], limit: 1);
    return rows.isEmpty ? null : _fromRow(rows.first);
  }

  @override
  Future<void> save(PendingSale row) async {
    final db = await _db;
    await db.update(OfflineDb.tableOutbox, _toRow(row),
        where: 'client_uuid = ?', whereArgs: [row.clientUuid]);
  }

  @override
  Future<PendingSale?> replaceContent({
    required String clientUuid,
    required Map<String, dynamic> payload,
    required Map<String, dynamic> saleJson,
  }) async {
    final existing = await byUuid(clientUuid);
    // `syncing` is excluded because a request is in flight: the server may be
    // committing the version being replaced right now, and the edit would then
    // describe a sale that is already recorded differently. `synced` is excluded
    // because the server owns it — that is an ordinary online edit, not this.
    if (existing == null ||
        existing.status == PendingSaleStatus.syncing ||
        existing.status == PendingSaleStatus.synced) {
      return null;
    }

    final updated = existing.replacing(payload: payload, saleJson: saleJson);
    final db = await _db;
    await db.update(OfflineDb.tableOutbox, _toRow(updated),
        where: 'client_uuid = ?', whereArgs: [clientUuid]);
    return updated;
  }

  @override
  Future<void> discard(String clientUuid) async {
    final db = await _db;
    await db.delete(OfflineDb.tableOutbox, where: 'client_uuid = ?', whereArgs: [clientUuid]);
  }

  @override
  Future<int> purgeSynced({Duration keepFor = const Duration(days: 7)}) async {
    final db = await _db;
    final cutoff = DateTime.now().subtract(keepFor).millisecondsSinceEpoch;
    return db.delete(
      OfflineDb.tableOutbox,
      where: 'status = ? AND created_at < ?',
      whereArgs: [PendingSaleStatus.synced.name, cutoff],
    );
  }

  @override
  Future<void> resetStuckSyncing() async {
    final db = await _db;
    // A row left as `syncing` means the app died mid-post. The request may well
    // have reached the server, but that is precisely what the idempotency key
    // settles — re-posting is safe, and the alternative (leaving it stuck) is
    // a sale that never syncs at all.
    await db.update(
      OfflineDb.tableOutbox,
      {'status': PendingSaleStatus.pending.name},
      where: 'status = ?',
      whereArgs: [PendingSaleStatus.syncing.name],
    );
  }

  // ---- provisional reference ----

  /// `OFF-<device>-<seq>`: unmistakably not an invoice number, and unique per
  /// device so two tills' queues can't print the same reference.
  Future<String> _nextProvisionalRef() async {
    final tag = _storage.offlineDeviceTag ?? await _mintDeviceTag();
    final seq = (_storage.offlineSequence ?? 0) + 1;
    await _storage.setOfflineSequence(seq);
    return 'OFF-$tag-${seq.toString().padLeft(4, '0')}';
  }

  Future<String> _mintDeviceTag() async {
    const alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789'; // no I/O/0/1 — these get read aloud
    final random = Random.secure();
    final tag = List.generate(3, (_) => alphabet[random.nextInt(alphabet.length)]).join();
    await _storage.setOfflineDeviceTag(tag);
    return tag;
  }

  // ---- row mapping ----

  Map<String, Object?> _toRow(PendingSale row) => {
        'client_uuid': row.clientUuid,
        'payload': jsonEncode(row.payload),
        'snapshot': jsonEncode(row.saleJson),
        'provisional_ref': row.provisionalRef,
        'branch_id': row.branchId,
        'user_id': row.userId,
        'created_at': row.createdAt.millisecondsSinceEpoch,
        'status': row.status.name,
        'attempts': row.attempts,
        'last_attempt_at': row.lastAttemptAt?.millisecondsSinceEpoch,
        'last_error': row.lastError,
        'server_sale_id': row.serverSaleId,
        'invoice_no': row.invoiceNo,
      };

  PendingSale _fromRow(Map<String, Object?> r) => PendingSale(
        clientUuid: asStr(r['client_uuid']),
        payload: Map<String, dynamic>.from(jsonDecode(r['payload'] as String) as Map),
        saleJson: Map<String, dynamic>.from(jsonDecode(r['snapshot'] as String) as Map),
        provisionalRef: asStr(r['provisional_ref']),
        userId: asStr(r['user_id']),
        branchId: r['branch_id'] == null ? null : asNum(r['branch_id']).toInt(),
        createdAt: DateTime.fromMillisecondsSinceEpoch(asNum(r['created_at']).toInt()),
        status: PendingSaleStatusX.parse(r['status'] as String?),
        attempts: asNum(r['attempts']).toInt(),
        lastAttemptAt: r['last_attempt_at'] == null
            ? null
            : DateTime.fromMillisecondsSinceEpoch(asNum(r['last_attempt_at']).toInt()),
        lastError: r['last_error'] as String?,
        serverSaleId: r['server_sale_id'] as String?,
        invoiceNo: r['invoice_no'] as String?,
      );
}
