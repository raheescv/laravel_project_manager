import 'package:dio/dio.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:invo/features/sale/domain/models/pending_sale.dart';
import 'package:invo/features/sale/domain/repository/outbox_repository.dart';
import 'package:invo/features/sale/domain/services/offline_sale_service.dart';
import 'package:invo/features/sale/domain/services/outbox_service.dart';
import 'package:invo/features/sale/logic/offline_sync_cubit/offline_sync_cubit.dart';
import 'package:invo/features/sales/logic/sales_cubit/sales_cubit.dart';
import 'package:invo/shared/domain/constants/global_variables.dart';
import 'package:invo/shared/domain/models/index.dart';
import 'package:invo/shared/domain/repository/catalog_snapshot_repository.dart';
import 'package:invo/shared/domain/services/catalog_snapshot_service.dart';

import 'support/fake_lookup_repository.dart';
import 'support/fake_repositories.dart';
import 'support/offline_harness.dart';

/// A sale rung up offline is receipted immediately, under a reference the device
/// invented: `OFF-7K2-0042`. The customer walks out with that number and nothing
/// else.
///
/// The moment the sale syncs, two things happen at once — the server gives it a
/// real invoice number, and the device deletes its queued copy. So unless the
/// reference travels with the sale on that final POST, it is destroyed by the very
/// event that makes the sale real, and the receipt in the customer's hand refers to
/// nothing. These tests pin that it travels, that a correction cannot lose it, and
/// that it is never claimed for a sale that was never offline.
void main() {
  late OutboxService outbox;
  late CatalogSnapshotService snapshot;
  late FakeSaleRepository server;
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
    server = FakeSaleRepository();
    sync = OfflineSyncCubit(server);
  });

  tearDown(() async {
    await sync.close();
    await tearDownOfflineHarness();
  });

  Map<String, dynamic> payloadFor(String uuid, {double qty = 1}) => {
        'customerName': 'Walk-in',
        'items': [
          {'productId': 1, 'quantity': qty, 'unitPrice': 10.0, 'discount': 0, 'tax': 0},
        ],
        'paymentMethod': 'Cash',
        'totalPayment': 10.0 * qty,
        'clientUuid': uuid,
      };

  Map<String, dynamic> saleJsonFor(String uuid, {double qty = 1}) => {
        'id': '',
        'invoice_no': '',
        'client_uuid': uuid,
        'pending': true,
        'status': 'completed',
        'customer': {'name': 'Walk-in', 'mobile': ''},
        'items': [
          {'product_id': 1, 'name': 'Shirt 1', 'type': 'product', 'quantity': qty, 'unit_price': 10.0, 'tax': 0, 'total': 10.0 * qty},
        ],
        'payments': const [],
        'summary': {'grand_total': 10.0 * qty, 'paid': 10.0 * qty, 'balance': 0},
      };

  Future<PendingSale> queue(String uuid, {double qty = 1}) => outbox.enqueue(
        clientUuid: uuid,
        payload: payloadFor(uuid, qty: qty),
        saleJson: saleJsonFor(uuid, qty: qty),
        userId: '7',
        branchId: branchId,
      );

  group('the reference reaches the server', () {
    test('a queued sale posts the reference its receipt was printed under', () async {
      final row = await queue(a);

      await sync.drain(ignoreBackoff: true);

      expect(server.lastPayload!['offlineRef'], row.provisionalRef);
      expect(row.provisionalRef, startsWith('OFF-'));
    });

    test('it is sent on the same POST that then destroys the local copy', () async {
      await queue(a);

      await sync.drain(ignoreBackoff: true);

      // Purge-on-sync: the outbox holds only what the server does not have. So the
      // assertion above is not a nicety — after this line the server's copy is the
      // ONLY place the printed reference exists.
      expect(await outbox.byUuid(a), isNull);
      expect(server.lastPayload!['offlineRef'], isNotNull);
    });

    test('a corrected sale still posts the number already in the customer hand',
        () async {
      final original = await queue(a, qty: 1);
      // Correcting a queued sale rebuilds its payload from the cart, which is exactly
      // how the reference would get dropped — so it is read off the row, not the
      // payload.
      await sync.editPending(
        a,
        payload: payloadFor(a, qty: 3),
        saleJson: saleJsonFor(a, qty: 3),
        soldBefore: original.soldQuantities,
      );

      // A correction fires its own immediate retry — it should not sit out the
      // backoff the version it replaced had earned — so wait for that to land
      // rather than starting a second drain, which the first would refuse.
      await _pumpUntil(() => server.lastPayload != null);

      expect(server.lastPayload!['offlineRef'], original.provisionalRef);
      // …and it really is the corrected sale that posted.
      final posted = Map<String, dynamic>.from((server.lastPayload!['items'] as List).first as Map);
      expect(posted['quantity'], 3);
    });

    test('a sale that was never offline claims no reference', () async {
      // Straight through the decorator to a reachable server: nothing was printed
      // provisionally, so the sale's reference field is the back office's to use.
      final service = OfflineFirstSaleService(server);

      await service.createSale(payloadFor(a), offlineSale: saleJsonFor(a));

      expect(server.lastPayload!.containsKey('offlineRef'), isFalse);
    });
  });

  group('what the cashier and the customer see', () {
    test('a captured sale is receipted under the provisional reference', () async {
      final offline = _UnreachableSaleRepository();
      final service = OfflineFirstSaleService(offline);

      final sale = await service.createSale(payloadFor(a), offlineSale: saleJsonFor(a));

      // Both, and equal: while the sale is held there is no invoice number to be
      // different from, and the receipt prints this as the invoice line.
      expect(sale.invoiceNo, startsWith('OFF-'));
      expect(sale.offlineRef, sale.invoiceNo);
    });

    test('a synced sale carries both numbers, so the two can be reconciled', () {
      final sale = Sale.fromJson({
        'id': '9001',
        'invoice_no': 'INV-1182',
        'reference_no': 'OFF-7K2-0042',
        'client_uuid': a,
        'date': '2026-08-10',
        'status': 'completed',
        'branch': 'Main',
        'customer': {'name': 'Walk-in', 'mobile': ''},
        'items': const [],
        'payments': const [],
        'summary': {'grand_total': 10, 'paid': 10, 'balance': 0},
      });

      expect(sale.invoiceNo, 'INV-1182');
      // This is the number on the piece of paper the customer brought back.
      expect(sale.offlineRef, 'OFF-7K2-0042');
    });

    test('a reference typed in the back office is not an offline receipt number', () {
      // `reference_no` is free text on any sale entered on the web. Reading it as a
      // printed offline reference would put a stranger's note on a customer receipt
      // and label an ordinary sale as one that came out of an outage.
      final sale = Sale.fromJson({
        'id': '9002',
        'invoice_no': 'INV-1183',
        'reference_no': 'PO-4471 (Mr Khan, phone order)',
        'date': '2026-08-10',
        'status': 'completed',
        'branch': 'Main',
        'customer': {'name': 'Walk-in', 'mobile': ''},
        'items': const [],
        'payments': const [],
        'summary': {'grand_total': 10, 'paid': 10, 'balance': 0},
      });

      expect(sale.referenceNo, 'PO-4471 (Mr Khan, phone order)');
      expect(sale.offlineRef, isEmpty);
    });

    test('a held row in the sales list describes itself the same way', () async {
      final row = await queue(a);
      final sales = SalesCubit(sales: server, lookup: FakeLookupRepository());

      final page = await sales.fetchPage(page: 1);

      final held = page.rows.firstWhere((r) => r['pending'] == true);
      // The row a held sale renders from and the row it becomes after syncing carry
      // the reference in the same field, so nothing downstream has to special-case
      // which of the two it is looking at.
      expect(held['reference_no'], row.provisionalRef);
      expect(Sale.fromJson(held).offlineRef, row.provisionalRef);
    });

    test('the reference is what a cashier can search a held sale by', () async {
      final row = await queue(a);
      final sales = SalesCubit(sales: server, lookup: FakeLookupRepository());

      final page = await sales.fetchPage(page: 1, search: row.provisionalRef);

      expect(page.rows.where((r) => r['pending'] == true), hasLength(1));
    });
  });
}

/// Wait for a fire-and-forget drain to finish, rather than guessing at a delay.
Future<void> _pumpUntil(bool Function() done, {Duration limit = const Duration(seconds: 5)}) async {
  final deadline = DateTime.now().add(limit);
  while (!done() && DateTime.now().isBefore(deadline)) {
    await Future<void>.delayed(const Duration(milliseconds: 10));
  }
}

/// A server that cannot be reached, so the decorator captures instead of posting.
class _UnreachableSaleRepository extends FakeSaleRepository {
  @override
  Future<Sale> createSale(Map<String, dynamic> payload, {Map<String, dynamic>? offlineSale}) async {
    throw DioException.connectionError(requestOptions: RequestOptions(), reason: 'no route');
  }
}
