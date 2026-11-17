import '../models/pending_sale.dart';

/// Durable queue of sales taken on this device that the server has not
/// acknowledged.
///
/// Every row here is money that has already changed hands, so nothing in this
/// contract silently drops one: rows leave only by syncing, or by a person
/// discarding a failure they have resolved another way.
abstract class OutboxRepository {
  /// Queue a sale and return the stored row, with its device-local provisional
  /// reference assigned.
  Future<PendingSale> enqueue({
    required String clientUuid,
    required Map<String, dynamic> payload,
    required Map<String, dynamic> saleJson,
    required String userId,
    int? branchId,
  });

  /// Every row, newest first — what the pending-sales screen lists.
  Future<List<PendingSale>> all();

  /// Rows still owed to the server (`pending` or `failed`), oldest first, which
  /// is the order the drain must post them in.
  Future<List<PendingSale>> unsynced();

  /// How many sales are still owed to the server. Drives the badge and the
  /// day-close guard.
  Future<int> unsyncedCount();

  Future<PendingSale?> byUuid(String clientUuid);

  Future<void> save(PendingSale row);

  /// Replace what a still-queued sale will post, keeping its identity.
  ///
  /// The `clientUuid`, the provisional reference and the capture time all stay
  /// put, because this is a correction to one captured sale and not a second one:
  /// a new key would post both versions, and a new provisional reference would
  /// orphan the receipt already in the customer's hand. Attempt counters and the
  /// last error reset, so a corrected sale is retried at once rather than sitting
  /// out the backoff its rejected version earned.
  ///
  /// Returns the updated row, or null when there is nothing editable under that
  /// key — see [OfflineSyncCubit.editPending] for which statuses qualify.
  Future<PendingSale?> replaceContent({
    required String clientUuid,
    required Map<String, dynamic> payload,
    required Map<String, dynamic> saleJson,
  });

  /// Drop a row a person has explicitly resolved. Reserved for `failed` and
  /// `synced` rows — discarding a pending sale would lose the takings.
  Future<void> discard(String clientUuid);

  /// Remove `synced` rows older than [keepFor]; they only exist so the cashier
  /// can reprint with the real invoice number.
  Future<int> purgeSynced({Duration keepFor});

  /// Recover rows left mid-flight by a process that died while posting. Called
  /// once at boot, before any drain.
  Future<void> resetStuckSyncing();
}
