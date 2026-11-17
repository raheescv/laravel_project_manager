import 'package:dio/dio.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:invo/features/auth/logic/auth_cubit/auth_cubit.dart';
import 'package:invo/features/sale/domain/repository/outbox_repository.dart';
import 'package:invo/features/sale/domain/services/offline_sale_service.dart';
import 'package:invo/features/sale/domain/services/outbox_service.dart';
import 'package:invo/features/sale/domain/services/staff_bill_counter.dart';
import 'package:invo/features/sale/logic/offline_sync_cubit/offline_sync_cubit.dart';
import 'package:invo/shared/domain/constants/global_variables.dart';
import 'package:invo/shared/domain/models/index.dart';
import 'package:invo/shared/domain/repository/catalog_snapshot_repository.dart';
import 'package:invo/shared/domain/services/catalog_snapshot_service.dart';
import 'package:invo/shared/utils/router/http_utils/common_exception.dart';

import 'support/fake_repositories.dart';
import 'support/offline_harness.dart';

/// Staff tally their day on the Office Bill slips — `{code}/{n}` per biller per
/// business day — so a sale rung up offline has to print the number it will
/// actually carry, not a provisional reference that leaves a hole in the pile.
/// The till counts on from the server's figure, the server keeps the claim when
/// it can, and a slip it could not keep is flagged for a reprint.
void main() {
  late _Server server;
  late OfflineFirstSaleService service;
  late OutboxService outbox;
  late OfflineSyncCubit sync;
  late AuthCubit auth;

  const day = '2026-06-14';
  var uuidSeq = 0;

  ApiUser biller({int billSequence = 2}) => ApiUser.fromJson({
        'id': '7',
        'name': 'Haneefa',
        'code': '9',
        'sale_day_session_status': 'open',
        'sale_day_session_date': day,
        'staff_bill_sequence': billSequence,
      });

  ({Map<String, dynamic> payload, Map<String, dynamic> offlineSale}) ticket({String? status}) {
    final uuid = 'dddddddd-dddd-4ddd-8ddd-${(uuidSeq++).toString().padLeft(12, '0')}';
    return (
      payload: {
        'customerName': 'Walk-in',
        'items': [
          {'productId': 1, 'quantity': 1, 'unitPrice': 20.0, 'discount': 0},
        ],
        'paymentMethod': 'Cash',
        'totalPayment': 20.0,
        'clientUuid': uuid,
        if (status != null) 'status': status,
      },
      offlineSale: {
        'id': '',
        'invoice_no': '',
        'client_uuid': uuid,
        'pending': true,
        'date': '2026-06-15',
        'status': status ?? 'completed',
        'customer': {'name': 'Walk-in', 'mobile': ''},
        'items': const [],
        'payments': const [],
        'summary': {'grand_total': 20.0, 'paid': 20.0, 'balance': 0},
        'created_by': 'Haneefa',
      },
    );
  }

  setUp(() async {
    await setUpOfflineHarness();
    await registerBranchContext();
    outbox = OutboxService();
    auth = AuthCubit()..seedSession(biller());
    serviceLocator
      ..registerSingleton<OutboxRepository>(outbox)
      ..registerSingleton<CatalogSnapshotRepository>(CatalogSnapshotService())
      ..registerSingleton<StaffBillCounter>(StaffBillCounter())
      ..registerSingleton<AuthCubit>(auth);
    server = _Server();
    service = OfflineFirstSaleService(server);
    sync = OfflineSyncCubit(server);
  });

  tearDown(() async {
    await sync.close();
    await auth.close();
    await tearDownOfflineHarness();
  });

  Future<Sale> ringUpOffline({String? status}) {
    server.failWith = DioException(requestOptions: RequestOptions(path: '/sale'), type: DioExceptionType.connectionError);
    final t = ticket(status: status);
    return service.createSale(t.payload, offlineSale: t.offlineSale);
  }

  test('an offline sale prints the next number in the biller\'s count, on the business day', () async {
    final first = await ringUpOffline();
    final second = await ringUpOffline();

    expect(first.billNo, '9/3');
    expect(first.date, day);
    expect(second.billNo, '9/4');

    final rows = await outbox.unsynced();
    expect(rows.first.payload['staffBillSequence'], 3);
    expect(rows.first.payload['staffBillDate'], day);
  });

  test('an online sale claims the next number too, and the count follows the server', () async {
    server.billSequence = 5;

    final t = ticket();
    final saved = await service.createSale(t.payload, offlineSale: t.offlineSale);

    expect(server.lastPayload?['staffBillSequence'], 3);
    expect(saved.billNo, '9/5');
    expect((await ringUpOffline()).billNo, '9/6');
  });

  test('a refused charge does not use up a number', () async {
    server.failWith = ApiException('Out of stock', statusCode: 422);
    final t = ticket();
    await expectLater(service.createSale(t.payload, offlineSale: t.offlineSale), throwsA(isA<ApiException>()));

    expect((await ringUpOffline()).billNo, '9/3');
  });

  test('a draft claims nothing', () async {
    final draft = await ringUpOffline(status: 'draft');

    expect(draft.billNo, isEmpty);
    expect((await ringUpOffline()).billNo, '9/3');
  });

  test('correcting a held sale keeps the number already printed', () async {
    final held = await ringUpOffline();
    final correction = ticket();

    await sync.editPending(
      held.clientUuid,
      payload: {...correction.payload, 'clientUuid': held.clientUuid},
      saleJson: {...correction.offlineSale, 'client_uuid': held.clientUuid},
      soldBefore: const {},
    );

    final row = (await outbox.byUuid(held.clientUuid))!;
    expect(row.payload['staffBillSequence'], 3);
    expect(row.saleJson['bill_no'], '9/3');
  });

  test('a slip the server kept needs nothing more', () async {
    await ringUpOffline();
    server
      ..failWith = null
      ..billSequence = 3;

    await sync.drain(ignoreBackoff: true);

    expect(sync.state.renumbered, isEmpty);
  });

  test('a slip the server renumbered is flagged for a reprint until dismissed', () async {
    await ringUpOffline();
    server
      ..failWith = null
      ..billSequence = 4;

    await sync.drain(ignoreBackoff: true);

    expect(sync.state.renumbered, hasLength(1));
    expect(sync.state.renumbered.single.printed, '9/3');
    expect(sync.state.renumbered.single.billNo, '9/4');

    await sync.dismissRenumbered(sync.state.renumbered.single.saleId);
    await sync.refresh();
    expect(sync.state.renumbered, isEmpty);
  });
}

/// A server that numbers every sale it accepts `9/[billSequence]`, or fails with
/// [failWith].
class _Server extends FakeSaleRepository {
  Object? failWith;
  int billSequence = 3;

  @override
  Future<Sale> createSale(Map<String, dynamic> payload, {Map<String, dynamic>? offlineSale}) async {
    lastPayload = payload;
    if (failWith case final error?) throw error;
    return Sale.fromJson({
      'id': '900$billSequence',
      'invoice_no': 'INV-$billSequence',
      'bill_no': '9/$billSequence',
      'staff_bill_sequence': billSequence,
      'date': '2026-06-14',
      'status': 'completed',
      'customer': {'name': 'Walk-in', 'mobile': ''},
      'items': const [],
      'payments': const [],
      'summary': {'grand_total': 20.0, 'paid': 20.0, 'balance': 0},
      'created_by': 'Haneefa',
    });
  }
}
