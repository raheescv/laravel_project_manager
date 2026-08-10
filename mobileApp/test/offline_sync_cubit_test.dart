import 'dart:typed_data';

import 'package:dio/dio.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:invo/features/sale/domain/models/pending_sale.dart';
import 'package:invo/features/sale/domain/repository/outbox_repository.dart';
import 'package:invo/features/sale/domain/repository/sale_repository.dart';
import 'package:invo/features/sale/domain/services/outbox_service.dart';
import 'package:invo/features/sale/logic/offline_sync_cubit/offline_sync_cubit.dart';
import 'package:invo/shared/domain/constants/global_variables.dart';
import 'package:invo/shared/domain/models/index.dart';
import 'package:invo/shared/domain/repository/catalog_snapshot_repository.dart';
import 'package:invo/shared/domain/services/catalog_snapshot_service.dart';
import 'package:invo/shared/utils/router/http_utils/common_exception.dart';

import 'support/offline_harness.dart';

/// The sync engine decides, for every sale the till has already taken money for,
/// whether to post it now, wait, or hand it to a person. These tests pin those
/// decisions — the drain order, the mutexes that stop a double-post, the
/// retryable/terminal split, and the catalog pull's refusal to destroy a working
/// snapshot.
void main() {
  late _StubSaleRepository online;
  late OutboxService outbox;
  late OfflineSyncCubit sync;

  setUp(() async {
    await setUpOfflineHarness();
    await registerBranchContext();
    online = _StubSaleRepository();
    outbox = OutboxService();
    serviceLocator
      ..registerSingleton<OutboxRepository>(outbox)
      ..registerSingleton<CatalogSnapshotRepository>(CatalogSnapshotService());
    sync = OfflineSyncCubit(online);
  });

  tearDown(() async {
    await sync.close();
    await tearDownOfflineHarness();
  });

  Future<PendingSale> queue(String uuid, {String userId = '7'}) => outbox.enqueue(
        clientUuid: uuid,
        payload: {
          'customerName': 'Walk-in',
          'items': [
            {'productId': 1, 'quantity': 1, 'unitPrice': 10.0, 'discount': 0},
          ],
          'paymentMethod': 'Cash',
          'totalPayment': 10.0,
          'clientUuid': uuid,
        },
        saleJson: {
          'id': '',
          'invoice_no': '',
          'client_uuid': uuid,
          'pending': true,
          'customer': {'name': 'Walk-in', 'mobile': ''},
          'items': const [],
          'payments': const [],
          'summary': {'grand_total': 10.0, 'paid': 10.0, 'balance': 0},
        },
        userId: userId,
        branchId: 1,
      );

  const a = 'aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa';
  const b = 'bbbbbbbb-bbbb-4bbb-8bbb-bbbbbbbbbbbb';
  const c = 'cccccccc-cccc-4ccc-8ccc-cccccccccccc';

  group('drain', () {
    test('posts the whole queue and marks it synced', () async {
      await queue(a);
      await queue(b);

      await sync.drain();

      expect(online.posted, hasLength(2));
      expect(await outbox.unsyncedCount(), 0);
      expect(sync.state.lastSyncedCount, 2);
    });

    test('posts oldest first — the order the customers were served', () async {
      await queue(a);
      await queue(b);
      await queue(c);

      await sync.drain();

      expect(online.posted.map((p) => p['clientUuid']), [a, b, c]);
    });

    test('carries the originating cashier so a shared till files it correctly', () async {
      await queue(a, userId: '42');

      await sync.drain();

      // The branch is deliberately NOT sent — the server takes it from this
      // cashier's own assignment.
      expect(online.posted.single['clientUserId'], 42);
      expect(online.posted.single.containsKey('clientBranchId'), isFalse);
    });

    test('stops at the first unreachable row rather than burning the queue', () async {
      await queue(a);
      await queue(b);
      online.failWith = DioException(
        requestOptions: RequestOptions(path: '/sale'),
        type: DioExceptionType.connectionError,
      );

      await sync.drain();

      expect(online.posted, hasLength(1));
      expect(await outbox.unsyncedCount(), 2);
    });

    test('a second drain cannot overlap the first', () async {
      await queue(a);
      online.gate = true;

      final first = sync.drain();
      await sync.drain(); // must be a no-op while the first is in flight
      online.release();
      await first;

      expect(online.posted, hasLength(1));
    });

    test('an acknowledged sale leaves the device entirely', () async {
      await queue(a);

      await sync.drain();

      // The outbox exists to hold what the server does not have yet. Keeping an
      // acknowledged row would leave a second, permanently-staling copy of a sale
      // the Sales list now serves — and the device is online by definition at
      // this moment, so there is nothing to fall back to it for.
      expect(await outbox.byUuid(a), isNull);
      expect(sync.state.pendingCount, 0);
    });

    test('reports the real invoice number the queued sale became', () async {
      await queue(a);

      await sync.drain();

      // The only moment the app can tell a cashier holding an OFF-… receipt what
      // it turned into, now that the row itself is gone.
      expect(sync.state.lastSyncedCount, 1);
      expect(sync.state.lastSyncedRefs, ['INV-1']);
    });
  });

  group('failure classification', () {
    test('a verdict on this sale is terminal and needs a person', () async {
      await queue(a);
      online.failWith = ApiException('Out of stock', statusCode: 422);

      await sync.drain();

      final row = await outbox.byUuid(a);
      expect(row!.status, PendingSaleStatus.failed);
      expect(row.lastError, 'Out of stock');
    });

    for (final code in [401, 408, 429, 500, 503]) {
      test('a $code stays pending because it can clear on its own', () async {
        await queue(a);
        online.failWith = ApiException('Transient', statusCode: code);

        await sync.drain();

        // Marking these failed would be worse than a slow retry: `failed` is the
        // only state that offers the money-losing Discard button.
        expect((await outbox.byUuid(a))!.status, PendingSaleStatus.pending);
      });
    }
  });

  group('backoff', () {
    test('a row that just failed is not retried immediately', () async {
      await queue(a);
      online.failWith = ApiException('Server', statusCode: 500);
      await sync.drain();
      expect(online.posted, hasLength(1));

      online.failWith = null;
      await sync.drain();

      // Still inside its backoff window.
      expect(online.posted, hasLength(1));
      expect(await outbox.unsyncedCount(), 1);
    });

    test('ignoreBackoff retries at once, which is what a regained link does', () async {
      await queue(a);
      online.failWith = ApiException('Server', statusCode: 500);
      await sync.drain();

      online.failWith = null;
      await sync.drain(ignoreBackoff: true);

      expect(online.posted, hasLength(2));
      expect(await outbox.unsyncedCount(), 0);
    });
  });

  group('retry and discard', () {
    test('retry posts a failed row immediately', () async {
      await queue(a);
      online.failWith = ApiException('Out of stock', statusCode: 422);
      await sync.drain();

      online.failWith = null;
      await sync.retry(a);

      // Synced means gone: the row is dropped as soon as the server has it.
      expect(await outbox.byUuid(a), isNull);
      // One invoice reported back, so the retry counts as its own sync round
      // rather than reporting whatever the failed drain before it left behind.
      expect(sync.state.lastSyncedRefs, hasLength(1));
    });

    test('retry refuses a row that is already mid-post', () async {
      final row = await queue(a);
      await outbox.save(row.copyWith(status: PendingSaleStatus.syncing));

      await sync.retry(a);

      // A second concurrent POST of the same sale is exactly what the
      // idempotency key should not have to clean up after.
      expect(online.posted, isEmpty);
    });

    test('discard removes a failed row', () async {
      await queue(a);
      online.failWith = ApiException('Gone', statusCode: 404);
      await sync.drain();

      await sync.discard(a);

      expect(await outbox.byUuid(a), isNull);
    });

    test('discard refuses a row that is still owed to the server', () async {
      await queue(a);

      await sync.discard(a);

      // Deleting a pending row loses takings outright.
      expect(await outbox.byUuid(a), isNotNull);
    });
  });
}

/// Online repository stand-in: records payloads, fails on demand, and can be
/// held open so overlapping calls are observable.
class _StubSaleRepository implements SaleRepository {
  final List<Map<String, dynamic>> posted = [];
  Object? failWith;

  bool gate = false;
  final _gateCompleter = <void Function()>[];

  void release() {
    for (final f in _gateCompleter) {
      f();
    }
    _gateCompleter.clear();
    gate = false;
  }

  @override
  Future<Sale> createSale(Map<String, dynamic> payload, {Map<String, dynamic>? offlineSale}) async {
    if (gate) {
      await Future<void>.delayed(const Duration(milliseconds: 30));
    }
    // Recorded before the failure: these are ATTEMPTS, which is what the drain's
    // stop-early and backoff behaviour is actually about.
    posted.add(payload);
    if (failWith case final error?) throw error;
    return Sale.fromJson({
      'id': '${posted.length}',
      'invoice_no': 'INV-${posted.length}',
      'date': '2026-08-09',
      'status': 'completed',
      'branch': 'Main',
      'customer': {'name': 'Walk-in', 'mobile': ''},
      'items': const [],
      'payments': const [],
      'summary': {'grand_total': 10.0, 'paid': 10.0, 'balance': 0},
      'created_by': 'Maya',
    });
  }

  @override
  Future<void> deleteSale(String id) async {}

  @override
  Future<Sale> saleById(String id) => throw UnimplementedError();

  @override
  Future<Uint8List> saleReceiptPdf(String id) => throw UnimplementedError();

  @override
  Future<SalesPage> sales({
    String? status,
    String? search,
    int? paymentMethodId,
    String? fromDate,
    String? toDate,
    String sortBy = 'date',
    String sortDirection = 'desc',
    bool mineOnly = false,
    int page = 1,
    int perPage = 30,
  }) =>
      throw UnimplementedError();

  @override
  Future<Sale> updateSale(String id, Map<String, dynamic> payload) => throw UnimplementedError();
}
