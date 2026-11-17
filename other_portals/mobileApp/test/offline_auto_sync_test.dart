import 'package:flutter_test/flutter_test.dart';
import 'package:invo/features/sale/domain/models/pending_sale.dart';
import 'package:invo/features/sale/domain/repository/outbox_repository.dart';
import 'package:invo/features/sale/domain/services/outbox_service.dart';
import 'package:invo/features/sale/logic/offline_sync_cubit/offline_sync_cubit.dart';
import 'package:invo/shared/domain/constants/global_variables.dart';
import 'package:invo/shared/domain/repository/catalog_snapshot_repository.dart';
import 'package:invo/shared/domain/services/catalog_snapshot_service.dart';
import 'package:invo/shared/logic/connectivity_cubit/connectivity_cubit.dart';

import 'support/fake_repositories.dart';
import 'support/offline_harness.dart';

/// A sale held on a device is money the books do not have yet, so the one thing
/// that must not need a person is getting it to the server once the network is
/// back.
///
/// The OS "a network interface appeared" event is not enough on its own: the case
/// that bites hardest in a shop is a till that never lost its wifi at all while
/// the uplink was down, where that event never fires. `ConnectivityCubit` flips to
/// online only when a request actually got an answer, which is proof of reach —
/// these tests pin that the sync engine acts on it.
void main() {
  late OutboxService outbox;
  late FakeSaleRepository online;
  late ConnectivityCubit connectivity;
  late OfflineSyncCubit sync;

  const a = 'aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa';

  setUp(() async {
    await setUpOfflineHarness();
    await registerBranchContext();
    outbox = OutboxService();
    online = FakeSaleRepository();
    connectivity = ConnectivityCubit();
    serviceLocator
      ..registerSingleton<OutboxRepository>(outbox)
      ..registerSingleton<ConnectivityCubit>(connectivity)
      ..registerSingleton<CatalogSnapshotRepository>(CatalogSnapshotService());
    sync = OfflineSyncCubit(online);
  });

  tearDown(() async {
    await sync.close();
    await connectivity.close();
    await tearDownOfflineHarness();
  });

  Future<PendingSale> queue(String uuid) => outbox.enqueue(
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
        userId: '7',
        branchId: 1,
      );

  /// Lets the drain the listener kicks off actually run.
  ///
  /// Only for asserting that something did NOT happen — you cannot poll for the
  /// absence of an event, so those tests do have to wait out a fixed moment.
  Future<void> settle() => Future<void>.delayed(const Duration(milliseconds: 40));

  /// Wait until the queued sale has actually reached the server.
  ///
  /// A fixed delay was enough on an idle machine and not enough on a busy one: the
  /// drain is fire-and-forget and does real database work, so under load these tests
  /// failed for want of a few more milliseconds rather than for any reason to do
  /// with syncing. Polling the outcome makes them say what they mean.
  Future<void> untilSynced(String uuid, {Duration limit = const Duration(seconds: 5)}) async {
    final deadline = DateTime.now().add(limit);
    while (DateTime.now().isBefore(deadline)) {
      if (await outbox.byUuid(uuid) == null) return;
      await Future<void>.delayed(const Duration(milliseconds: 10));
    }
  }

  test('a request reaching the server drains the queue without waiting for a timer',
      () async {
    await queue(a);
    connectivity.reportOutcome(reachable: false);
    await settle();

    // Something got through — the only proof of reach the app has.
    connectivity.reportOutcome(reachable: true);
    await untilSynced(a);

    expect(await outbox.byUuid(a), isNull);
  });

  test('it drains even when the interface never changed', () async {
    // The shop-wifi case: the OS reported a live interface the whole time, so
    // `onConnectivityChanged` never fires and the timer would be the only recovery.
    await queue(a);
    connectivity.reportOutcome(reachable: false);
    await settle();
    expect(await outbox.byUuid(a), isNotNull);

    connectivity.reportOutcome(reachable: true);
    await untilSynced(a);

    expect(await outbox.byUuid(a), isNull);
  });

  test('it ignores the backoff a row earned while the network was down', () async {
    final row = await queue(a);
    // Five failed attempts would otherwise park this row for fifteen minutes.
    await outbox.save(row.copyWith(
      status: PendingSaleStatus.pending,
      attempts: 5,
      lastAttemptAt: DateTime.now(),
    ));
    connectivity.reportOutcome(reachable: false);
    await settle();

    connectivity.reportOutcome(reachable: true);
    await untilSynced(a);

    // Waiting out a backoff earned while offline strands takings for no reason —
    // the condition that caused every one of those failures has just changed.
    expect(await outbox.byUuid(a), isNull);
  });

  test('only the transition drains, not every successful request', () async {
    await queue(a);
    connectivity.reportOutcome(reachable: true);
    await settle();
    final afterFirst = online.postedCount;

    // A busy till makes requests constantly; each one keeps the status online.
    connectivity.reportOutcome(reachable: true);
    connectivity.reportOutcome(reachable: true);
    await settle();

    expect(online.postedCount, afterFirst);
  });

  test('going offline does not post anything', () async {
    await queue(a);

    connectivity.reportOutcome(reachable: false);
    await settle();

    expect(online.postedCount, 0);
    expect(await outbox.byUuid(a), isNotNull);
  });

  test('the engine still syncs with no connectivity cubit registered', () async {
    // The subscription is guarded on registration, so a build or a test without
    // the app-wide cubit must not lose the ability to sync at all.
    await setUpOfflineHarness();
    await registerBranchContext();
    final freshOutbox = OutboxService();
    serviceLocator
      ..registerSingleton<OutboxRepository>(freshOutbox)
      ..registerSingleton<CatalogSnapshotRepository>(CatalogSnapshotService());
    final standalone = OfflineSyncCubit(FakeSaleRepository());
    addTearDown(standalone.close);

    await queue(a);
    await standalone.drain();

    expect(await freshOutbox.byUuid(a), isNull);
  });
}
