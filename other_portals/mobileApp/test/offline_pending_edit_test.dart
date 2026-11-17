import 'package:dio/dio.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:invo/features/sale/domain/models/pending_sale.dart';
import 'package:invo/features/sale/domain/repository/outbox_repository.dart';
import 'package:invo/features/sale/domain/services/offline_sale_service.dart';
import 'package:invo/features/sale/domain/services/outbox_service.dart';
import 'package:invo/features/sale/logic/cart_cubit/cart_cubit.dart';
import 'package:invo/features/sale/logic/offline_sync_cubit/offline_sync_cubit.dart';
import 'package:invo/shared/domain/constants/global_variables.dart';
import 'package:invo/shared/domain/models/index.dart';
import 'package:invo/shared/domain/repository/catalog_snapshot_repository.dart';
import 'package:invo/shared/domain/services/catalog_snapshot_service.dart';

import 'support/fake_repositories.dart';
import 'support/offline_harness.dart';

/// Two things a till has to be able to do to a sale it is still holding: correct it,
/// and park it as a draft. Both are money-adjacent in different ways — a correction
/// must replace one captured sale rather than create a second, and a draft must not
/// be mistaken for takings.
void main() {
  late OutboxService outbox;
  late CatalogSnapshotService snapshot;
  late OfflineSyncCubit sync;
  late int branchId;

  const a = 'aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa';

  setUp(() async {
    await setUpOfflineHarness();
    branchId = await registerBranchContext();
    outbox = OutboxService();
    snapshot = CatalogSnapshotService();
    serviceLocator
      ..registerSingleton<OutboxRepository>(outbox)
      ..registerSingleton<CatalogSnapshotRepository>(snapshot);
    sync = OfflineSyncCubit(FakeSaleRepository());
  });

  tearDown(() async {
    await sync.close();
    await tearDownOfflineHarness();
  });

  Map<String, dynamic> payloadFor(String uuid, {double qty = 1, String? status}) => {
        'customerName': 'Walk-in',
        'items': [
          {'productId': 1, 'quantity': qty, 'unitPrice': 10.0, 'discount': 0, 'tax': 0},
        ],
        'paymentMethod': 'Cash',
        'totalPayment': 10.0 * qty,
        'clientUuid': uuid,
        if (status != null) 'status': status,
      };

  Map<String, dynamic> saleJsonFor(String uuid, {double qty = 1, String? status}) => {
        'id': '',
        'invoice_no': '',
        'client_uuid': uuid,
        'pending': true,
        'status': status ?? 'completed',
        'customer': {'name': 'Walk-in', 'mobile': ''},
        'items': [
          {'product_id': 1, 'name': 'Shirt 1', 'type': 'product', 'quantity': qty, 'unit_price': 10.0, 'tax': 0, 'total': 10.0 * qty},
        ],
        'payments': const [],
        'summary': {'grand_total': 10.0 * qty, 'paid': 10.0 * qty, 'balance': 0},
      };

  Future<PendingSale> queue(String uuid, {double qty = 1, String? status}) => outbox.enqueue(
        clientUuid: uuid,
        payload: payloadFor(uuid, qty: qty, status: status),
        saleJson: saleJsonFor(uuid, qty: qty, status: status),
        userId: '7',
        branchId: branchId,
      );

  group('correcting a queued sale', () {
    test('replaces what will post, and keeps the sale its own identity', () async {
      final original = await queue(a, qty: 1);

      final applied = await sync.editPending(
        a,
        payload: payloadFor(a, qty: 3),
        saleJson: saleJsonFor(a, qty: 3),
        soldBefore: original.soldQuantities,
      );

      final row = (await outbox.byUuid(a))!;
      expect(applied, isTrue);
      // The corrected figures are what will post…
      expect(row.total, 30.0);
      // …but this is still ONE captured sale. A new key would let the server commit
      // both the version being replaced and its replacement, and a new provisional
      // reference would orphan the receipt already in the customer's hand.
      expect(row.clientUuid, a);
      expect(row.provisionalRef, original.provisionalRef);
      // Compared at millisecond precision, which is what the row is stored at — the
      // point is that the capture time is preserved, so the queue keeps posting in
      // the order the customers were actually served.
      expect(row.createdAt.millisecondsSinceEpoch, original.createdAt.millisecondsSinceEpoch);
    });

    test('a corrected sale is retried at once, not left in its old backoff', () async {
      final original = await queue(a);
      await outbox.save(original.copyWith(
        status: PendingSaleStatus.failed,
        attempts: 5,
        lastError: 'Insufficient stock',
      ));

      await sync.editPending(
        a,
        payload: payloadFor(a, qty: 2),
        saleJson: saleJsonFor(a, qty: 2),
        soldBefore: original.soldQuantities,
      );

      final row = (await outbox.byUuid(a))!;
      expect(row.status, PendingSaleStatus.pending);
      expect(row.attempts, 0);
      expect(row.lastError, isNull);
    });

    test('refuses a sale that has a request in flight', () async {
      final original = await queue(a);
      await outbox.save(original.copyWith(status: PendingSaleStatus.syncing));

      final applied = await sync.editPending(
        a,
        payload: payloadFor(a, qty: 9),
        saleJson: saleJsonFor(a, qty: 9),
        soldBefore: original.soldQuantities,
      );

      // The server may be committing the version being replaced right now; the edit
      // would then describe a sale that is already recorded differently.
      expect(applied, isFalse);
      expect((await outbox.byUuid(a))!.total, 10.0);
    });

    test('refuses a sale the server already has', () async {
      final original = await queue(a);
      await outbox.save(original.copyWith(status: PendingSaleStatus.synced));

      final applied = await sync.editPending(
        a,
        payload: payloadFor(a, qty: 9),
        saleJson: saleJsonFor(a, qty: 9),
        soldBefore: original.soldQuantities,
      );

      // That is an ordinary edit of a committed sale, done through the server.
      expect(applied, isFalse);
    });

    test('refuses an unknown key rather than inventing a row for it', () async {
      expect(
        await sync.editPending(
          a,
          payload: payloadFor(a),
          saleJson: saleJsonFor(a),
          soldBefore: const {},
        ),
        isFalse,
      );
    });

    test('hands back the old quantities before taking the new ones', () async {
      await snapshot.replace(
        branchId: branchId,
        products: [_product(id: 1, stock: 10)],
        categoriesByType: const {},
      );
      final original = await queue(a, qty: 4);
      await snapshot.reduceStock(branchId: branchId, soldByProductId: original.soldQuantities);

      await sync.editPending(
        a,
        payload: payloadFor(a, qty: 1),
        saleJson: saleJsonFor(a, qty: 1),
        soldBefore: original.soldQuantities,
      );

      // 10 − 4 (captured) + 4 (given back) − 1 (corrected) = 9. Without the give-back
      // every edit would walk the cached figure further down.
      final page = await snapshot.products(branchId: branchId);
      expect(page.items.single.totalStock, 9);
    });
  });

  group('the ticket holding a correction', () {
    late CartCubit cart;

    setUp(() => cart = CartCubit());
    tearDown(() => cart.close());

    test('seeding pins the queued sale key so an edit cannot become a second sale',
        () async {
      final row = await queue(a);

      cart.seedFromPendingSale(row);
      // Edit it — this is the whole point of the screen.
      cart.setQty(cart.lines.first, 5);
      final ticket = cart.beginCharge();

      // Normally ANY change to the ticket retires the key. A correction is the
      // exception: it must post under the sale's existing key.
      expect(ticket.clientUuid, a);
      expect(cart.state.editingPendingUuid, a);
    });

    test('a correction is not an edit of a server record', () async {
      final row = await queue(a);

      cart.seedFromPendingSale(row);

      // The snapshot's `id` is blank, and treating that as an edit id would make
      // checkout PUT to nothing.
      expect(cart.editingSaleId, isNull);
      expect(cart.isEditing, isFalse);
      expect(cart.state.isEditingPending, isTrue);
    });

    test('carries what the queued version took off the shelf', () async {
      final row = await queue(a, qty: 3);

      cart.seedFromPendingSale(row);

      expect(cart.state.editingPendingSold, {1: 3.0});
    });

    test('a fresh ticket still retires its key on every change', () {
      cart.add(_shirt());
      final first = cart.beginCharge().clientUuid;
      cart.setQty(cart.lines.first, 2);

      // The double-charge guard for ordinary sales, unchanged by the correction path.
      expect(cart.beginCharge().clientUuid, isNot(first));
    });
  });

  group('parking a draft offline', () {
    test('a draft is captured with a key, like a sale', () {
      final cart = CartCubit()..add(_shirt());
      addTearDown(cart.close);

      final ticket = cart.beginCharge(status: 'draft');

      // It still must not be replayed into two parked rows.
      expect(ticket.payload['status'], 'draft');
      expect(ticket.offlineSale['status'], 'draft');
      expect(ticket.clientUuid, isNotEmpty);
    });

    test('a queued draft moves no stock', () async {
      await snapshot.replace(
        branchId: branchId,
        products: [_product(id: 1, stock: 6)],
        categoriesByType: const {},
      );
      final online = _UnreachableSaleRepository();
      final service = OfflineFirstSaleService(online);

      await service.createSale(
        payloadFor(a, qty: 2, status: 'draft'),
        offlineSale: saleJsonFor(a, qty: 2, status: 'draft'),
      );

      // Nothing has left the shop — decrementing would hide stock that is still on
      // the shelf and sellable to the next customer.
      final page = await snapshot.products(branchId: branchId);
      expect(page.items.single.totalStock, 6);
    });

    test('a queued draft is visible as held but does not block the day close', () async {
      await queue(a, status: 'draft');

      await sync.refresh();

      // A draft is not unbanked takings. Blocking a close over one teaches people to
      // force past the guard that protects the money.
      expect(sync.state.pendingCount, 1);
      expect(sync.state.unbankedTakings, isEmpty);
      expect(sync.state.hasUnbankedTakings, isFalse);
    });

    test('a queued completed sale does block the day close', () async {
      await queue(a);

      await sync.refresh();

      expect(sync.state.unbankedTakings, hasLength(1));
      expect(sync.state.hasUnbankedTakings, isTrue);
    });
  });
}

Product _shirt() => Product.fromJson(_product(id: 1, stock: 5));

Map<String, dynamic> _product({required int id, required double stock}) => {
      'id': id,
      'name': 'Shirt $id',
      'code': 'C$id',
      'barcode': 'B$id',
      'type': 'product',
      'mrp': 10.0,
      'tax': 0,
      'total_stock': stock,
    };

/// A sale repository that can never reach the server, which is the only condition
/// under which a sale is allowed to queue.
class _UnreachableSaleRepository extends FakeSaleRepository {
  @override
  Future<Sale> createSale(Map<String, dynamic> payload, {Map<String, dynamic>? offlineSale}) async {
    throw DioException.connectionError(requestOptions: RequestOptions(), reason: 'no route');
  }
}
