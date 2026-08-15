import 'dart:typed_data';

import 'package:dio/dio.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:invo/shared/logic/connectivity_cubit/connectivity_cubit.dart';
import 'package:invo/features/sale/domain/repository/outbox_repository.dart';
import 'package:invo/features/sale/domain/repository/sale_repository.dart';
import 'package:invo/features/sale/domain/services/offline_sale_service.dart';
import 'package:invo/features/sale/domain/services/outbox_service.dart';
import 'package:invo/shared/domain/constants/global_variables.dart';
import 'package:invo/shared/domain/models/index.dart';
import 'package:invo/shared/domain/repository/catalog_snapshot_repository.dart';
import 'package:invo/shared/domain/services/catalog_snapshot_service.dart';
import 'package:invo/shared/utils/router/http_utils/common_exception.dart';

import 'support/offline_harness.dart';

/// The single decision this decorator makes: queue the sale, or let the failure
/// through. Getting it wrong in one direction loses a sale the till already took
/// money for; in the other it hides a refusal and replays it forever.
void main() {
  late _RecordingSaleRepository online;
  late OfflineFirstSaleService service;
  late OutboxRepository outbox;

  final payload = <String, dynamic>{
    'customerName': 'Walk-in',
    'items': [
      {'productId': 1, 'quantity': 1, 'unitPrice': 20.0, 'discount': 0},
    ],
    'paymentMethod': 'Cash',
    'totalPayment': 20.0,
    'clientUuid': 'dddddddd-dddd-4ddd-8ddd-dddddddddddd',
  };

  final offlineSale = <String, dynamic>{
    'id': '',
    'invoice_no': '',
    'client_uuid': payload['clientUuid'],
    'pending': true,
    'customer': {'name': 'Walk-in', 'mobile': ''},
    'items': const [],
    'payments': const [],
    'summary': {'grand_total': 20.0, 'paid': 20.0, 'balance': 0},
  };

  DioException dio(DioExceptionType type) =>
      DioException(requestOptions: RequestOptions(path: '/sale'), type: type);

  setUp(() async {
    await setUpOfflineHarness();
    online = _RecordingSaleRepository();
    outbox = OutboxService();
    serviceLocator
      ..registerSingleton<OutboxRepository>(outbox)
      ..registerSingleton<CatalogSnapshotRepository>(CatalogSnapshotService());
    service = OfflineFirstSaleService(online);
  });

  tearDown(tearDownOfflineHarness);

  test('an unreachable server queues the sale and hands back a provisional one', () async {
    online.failWith = dio(DioExceptionType.connectionError);

    final sale = await service.createSale(payload, offlineSale: offlineSale);

    expect(sale.pending, isTrue);
    expect(sale.invoiceNo, startsWith('OFF-'));
    expect(await outbox.unsyncedCount(), 1);
  });

  for (final type in [
    DioExceptionType.connectionTimeout,
    DioExceptionType.sendTimeout,
    DioExceptionType.receiveTimeout,
  ]) {
    test('a $type is treated as unreachable and queued', () async {
      online.failWith = dio(type);

      final sale = await service.createSale(payload, offlineSale: offlineSale);

      expect(sale.pending, isTrue);
      expect(await outbox.unsyncedCount(), 1);
    });
  }

  test('a server that answered is never queued, however it answered', () async {
    // A 500 means the request was received; the sale may already be committed,
    // so queuing it would risk a duplicate the cashier never sees.
    online.failWith = ApiException('Server error', statusCode: 500);

    await expectLater(
      () => service.createSale(payload, offlineSale: offlineSale),
      throwsA(isA<ApiException>()),
    );
    expect(await outbox.unsyncedCount(), 0);
  });

  test('a validation refusal reaches the cashier instead of being queued', () async {
    online.failWith = ApiException('Product is not available', statusCode: 422);

    await expectLater(
      () => service.createSale(payload, offlineSale: offlineSale),
      throwsA(isA<ApiException>()),
    );
    expect(await outbox.unsyncedCount(), 0);
  });

  test('a caller that did not opt into offline capture gets the error', () async {
    // Drafts and edits pass no snapshot: neither can be replayed safely.
    online.failWith = dio(DioExceptionType.connectionError);

    await expectLater(
      () => service.createSale(payload),
      throwsA(isA<DioException>()),
    );
    expect(await outbox.unsyncedCount(), 0);
  });

  test('a payload with no client uuid is not queued', () async {
    online.failWith = dio(DioExceptionType.connectionError);
    final keyless = Map<String, dynamic>.from(payload)..remove('clientUuid');

    await expectLater(
      () => service.createSale(keyless, offlineSale: offlineSale),
      throwsA(isA<DioException>()),
    );
    expect(await outbox.unsyncedCount(), 0);
  });

  test('a reachable server is passed straight through and queues nothing', () async {
    final sale = await service.createSale(payload, offlineSale: offlineSale);

    expect(sale.pending, isFalse);
    expect(sale.invoiceNo, 'INV-1');
    expect(online.calls, 1);
    expect(await outbox.unsyncedCount(), 0);
  });

  test('re-queueing the same key does not raise a second row', () async {
    online.failWith = dio(DioExceptionType.connectionError);

    final first = await service.createSale(payload, offlineSale: offlineSale);
    final second = await service.createSale(payload, offlineSale: offlineSale);

    expect(second.invoiceNo, first.invoiceNo);
    expect(await outbox.unsyncedCount(), 1);
  });

  group('not waiting out a timeout the app already knows the answer to', () {
    late ConnectivityCubit network;

    setUp(() {
      network = ConnectivityCubit();
      addTearDown(network.close);
      serviceLocator.registerSingleton<ConnectivityCubit>(network);
    });

    test('a known-offline network captures without attempting the POST', () async {
      // Something already failed to reach the server.
      network.reportOutcome(reachable: false);
      // The stub would SUCCEED if called, which is what proves the call was skipped.
      online.failWith = null;

      final sale = await service.createSale(payload, offlineSale: offlineSale);

      // `connectTimeout` is 20 seconds. Expiring it on every charge during an outage
      // is the till appearing to hang with a customer standing at it.
      expect(online.calls, 0);
      expect(sale.pending, isTrue);
      expect(sale.invoiceNo, startsWith('OFF-'));
      expect(await outbox.unsyncedCount(), 1);
    });

    test('it goes back over the network as soon as something gets through', () async {
      network.reportOutcome(reachable: false);
      await service.createSale(payload, offlineSale: offlineSale);
      expect(online.calls, 0);

      network.reportOutcome(reachable: true);
      await service.createSale(payload, offlineSale: offlineSale);

      // Self-correcting: the skip lasts only while the app believes it is offline,
      // and the 60s drain keeps probing.
      expect(online.calls, 1);
    });

    test('a draft or an edit still gets the real attempt', () async {
      network.reportOutcome(reachable: false);

      // No snapshot passed — the caller never opted into offline capture, so it must
      // get the real call and the real answer rather than a silent queue.
      await service.createSale(payload);

      expect(online.calls, 1);
      expect(await outbox.unsyncedCount(), 0);
    });
  });

  test('with no connectivity cubit registered it behaves exactly as before', () async {
    online.failWith = dio(DioExceptionType.connectionError);

    final sale = await service.createSale(payload, offlineSale: offlineSale);

    // The attempt is made, and its failure is what triggers the capture.
    expect(online.calls, 1);
    expect(sale.pending, isTrue);
  });

}

/// Online repository stand-in: records calls and fails on demand.
class _RecordingSaleRepository implements SaleRepository {
  Object? failWith;
  int calls = 0;

  @override
  Future<Sale> createSale(Map<String, dynamic> payload, {Map<String, dynamic>? offlineSale}) async {
    calls++;
    if (failWith case final error?) throw error;
    return Sale.fromJson({
      'id': '1',
      'invoice_no': 'INV-1',
      'date': '2026-08-09',
      'status': 'completed',
      'branch': 'Main',
      'customer': {'name': 'Walk-in', 'mobile': ''},
      'items': const [],
      'payments': const [],
      'summary': {'grand_total': 20.0, 'paid': 20.0, 'balance': 0},
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
    int? createdById,
    int page = 1,
    int perPage = 30,
  }) =>
      throw UnimplementedError();

  @override
  Future<Sale> updateSale(String id, Map<String, dynamic> payload) => throw UnimplementedError();
}
