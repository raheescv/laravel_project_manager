import 'package:flutter_test/flutter_test.dart';
import 'package:invo/features/sale/domain/models/pending_sale.dart';
import 'package:invo/features/sale/domain/services/outbox_service.dart';

import 'support/offline_harness.dart';

/// The outbox holds money that has already changed hands, so these tests are
/// about one thing: a queued sale is never lost and never queued twice.
void main() {
  late OutboxService outbox;

  setUp(() async {
    await setUpOfflineHarness();
    outbox = OutboxService();
  });

  tearDown(tearDownOfflineHarness);

  Future<PendingSale> queue(String uuid, {double total = 50, int productId = 1}) => outbox.enqueue(
        clientUuid: uuid,
        payload: {
          'customerName': 'Walk-in',
          'items': [
            {'productId': productId, 'quantity': 2, 'unitPrice': total / 2, 'discount': 0},
          ],
          'paymentMethod': 'Cash',
          'totalPayment': total,
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
          'summary': {'grand_total': total, 'paid': total, 'balance': 0},
        },
        userId: '7',
        branchId: 3,
      );

  test('a queued sale survives being read back', () async {
    final queued = await queue('11111111-1111-4111-8111-111111111111');

    final rows = await outbox.all();
    expect(rows, hasLength(1));
    expect(rows.single.clientUuid, queued.clientUuid);
    expect(rows.single.status, PendingSaleStatus.pending);
    expect(rows.single.userId, '7');
    expect(rows.single.branchId, 3);
    expect(rows.single.total, 50);
    expect(rows.single.customerName, 'Walk-in');
  });

  test('enqueueing the same key twice returns the first row rather than throwing', () async {
    // The charge key is stable while the ticket is unchanged, so a second press
    // after a failed attempt arrives with the same uuid. That is the same sale.
    const uuid = '22222222-2222-4222-8222-222222222222';
    final first = await queue(uuid);
    final second = await queue(uuid);

    expect(second.provisionalRef, first.provisionalRef);
    expect(await outbox.all(), hasLength(1));
  });

  test('provisional references are unique and sequential on one device', () async {
    final a = await queue('33333333-3333-4333-8333-333333333333');
    final b = await queue('44444444-4444-4444-8444-444444444444');

    expect(a.provisionalRef, isNot(b.provisionalRef));
    expect(a.provisionalRef, startsWith('OFF-'));
    expect(a.provisionalRef, endsWith('0001'));
    expect(b.provisionalRef, endsWith('0002'));
  });

  test('unsynced returns owed rows oldest first and excludes synced ones', () async {
    final a = await queue('55555555-5555-4555-8555-555555555555');
    final b = await queue('66666666-6666-4666-8666-666666666666');
    await outbox.save(a.copyWith(status: PendingSaleStatus.synced, invoiceNo: 'INV-1'));

    final owed = await outbox.unsynced();
    expect(owed.map((r) => r.clientUuid), [b.clientUuid]);
    expect(await outbox.unsyncedCount(), 1);
  });

  test('a row left mid-post is recovered as pending, not stranded', () async {
    // The app died between marking the row `syncing` and getting an answer. The
    // idempotency key makes re-posting safe; leaving it stuck does not.
    final row = await queue('77777777-7777-4777-8777-777777777777');
    await outbox.save(row.copyWith(status: PendingSaleStatus.syncing));

    await outbox.resetStuckSyncing();

    expect((await outbox.byUuid(row.clientUuid))!.status, PendingSaleStatus.pending);
  });

  test('purgeSynced keeps unsynced rows whatever their age', () async {
    final synced = await queue('88888888-8888-4888-8888-888888888888');
    final pending = await queue('99999999-9999-4999-8999-999999999999');
    await outbox.save(synced.copyWith(status: PendingSaleStatus.synced));

    // Nothing is old enough yet, so the first pass must remove nothing at all.
    expect(await outbox.purgeSynced(keepFor: const Duration(days: 7)), 0);
    // With a zero window the synced row goes and the owed one stays.
    expect(await outbox.purgeSynced(keepFor: Duration.zero), 1);

    final remaining = await outbox.all();
    expect(remaining.map((r) => r.clientUuid), [pending.clientUuid]);
  });

  test('soldQuantities totals the payload lines per product', () async {
    final row = await queue('aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa', productId: 12);

    expect(row.soldQuantities, {12: 2.0});
  });

  test('displayRef prefers the real invoice number once it is known', () async {
    final row = await queue('bbbbbbbb-bbbb-4bbb-8bbb-bbbbbbbbbbbb');
    expect(row.displayRef, row.provisionalRef);

    await outbox.save(row.copyWith(status: PendingSaleStatus.synced, invoiceNo: 'INV-M-25-0007'));

    expect((await outbox.byUuid(row.clientUuid))!.displayRef, 'INV-M-25-0007');
  });

  test('the queued sale rebuilds into the Sale the receipt renders', () async {
    final row = await queue('cccccccc-cccc-4ccc-8ccc-cccccccccccc');

    final sale = row.sale;
    expect(sale.pending, isTrue);
    expect(sale.id, isEmpty);
    expect(sale.grandTotal, 50);
  });
}
