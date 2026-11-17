import 'package:dio/dio.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:invo/features/auth/domain/repository/auth_repository.dart';
import 'package:invo/features/auth/logic/auth_cubit/auth_cubit.dart';
import 'package:invo/features/sale/domain/models/pending_sale.dart';
import 'package:invo/features/sale/domain/repository/outbox_repository.dart';
import 'package:invo/features/sale/domain/services/outbox_service.dart';
import 'package:invo/features/sales/logic/sales_cubit/sales_cubit.dart';
import 'package:invo/shared/domain/constants/app_config.dart';
import 'package:invo/shared/domain/constants/global_variables.dart';
import 'package:invo/shared/domain/models/index.dart';
import 'package:invo/shared/domain/repository/catalog_snapshot_repository.dart';
import 'package:invo/shared/domain/services/catalog_snapshot_service.dart';
import 'package:invo/shared/utils/local_storage/local_storage_service.dart';
import 'package:invo/shared/utils/router/http_utils/http_service.dart';

import 'support/fake_repositories.dart';
import 'support/offline_harness.dart';

/// One till, worked by several people in a day: a cashier rings sales up, signs
/// out, and the next person signs in. This walks that handover end to end, because
/// the failures it caused were only ever visible from the far side of it — the
/// second person finding an empty till, or a manager unable to see what the shift
/// actually took.
void main() {
  late _SwitchableAuth authRepo;
  late AuthCubit auth;
  late OutboxService outbox;
  late CatalogSnapshotService snapshot;
  late SalesCubit sales;

  const ticket = 'aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa';

  setUp(() async {
    await setUpOfflineHarness();
    authRepo = _SwitchableAuth();
    outbox = OutboxService();
    snapshot = CatalogSnapshotService();
    serviceLocator
      ..registerSingleton<HttpService>(HttpService(
        storage: serviceLocator<LocalStorageService>(),
        config: AppConfig(baseUrl: 'http://localhost', tenant: 't'),
      ))
      ..registerSingleton<AuthRepository>(authRepo)
      ..registerSingleton<OutboxRepository>(outbox)
      ..registerSingleton<CatalogSnapshotRepository>(snapshot);
    auth = AuthCubit();
    serviceLocator.registerSingleton<AuthCubit>(auth);
    // The list itself is offline throughout: the server is never reachable for it.
    sales = SalesCubit(sales: _UnreachableSales(), lookup: null);
  });

  tearDown(() async {
    await auth.close();
    await tearDownOfflineHarness();
  });

  /// A sale rung up on this till by [userId], still waiting to sync.
  Future<void> ringUp(String userId) => outbox.enqueue(
        clientUuid: ticket,
        payload: const {'customerName': 'Walk-in', 'totalPayment': 10.0},
        saleJson: {
          'id': '',
          'invoice_no': '',
          'client_uuid': ticket,
          'date': _today,
          'status': 'completed',
          'created_by': 'Cashier $userId',
          'customer': const {'name': 'Walk-in', 'mobile': ''},
          'items': const [],
          'payments': const [],
          'summary': const {'grand_total': 10.0, 'paid': 10.0, 'balance': 0},
        },
        userId: userId,
        branchId: 1,
      );

  /// What the till can sell from: the branch's staff list, cached for offline.
  Future<void> provisionStaff() => snapshot.replaceLookups(
        branchId: 1,
        kind: SnapshotLookup.employee,
        rows: [
          {'id': 7, 'name': 'Cashier 7'},
          {'id': 9, 'name': 'Rana'},
        ],
      );

  test('a manager sees what the previous cashier rang up', () async {
    await provisionStaff();
    await auth.login('1111'); // the cashier
    await ringUp('7');
    await auth.logout();

    authRepo.beAdmin();
    await auth.login('2222');
    final page = await sales.fetchPage(page: 1);

    expect(page.rows, hasLength(1));
    expect(page.rows.single['client_uuid'], ticket);
    // And it says whose it was, which is the whole point of showing it to them.
    expect(page.rows.single['created_by'], 'Cashier 7');
  });

  test('the next cashier does not', () async {
    await auth.login('1111');
    await ringUp('7');
    await auth.logout();

    authRepo.beOtherCashier();
    await auth.login('3333');

    expect((await sales.fetchPage(page: 1)).rows, isEmpty);
  });

  test('once the queue drains, the sale is on the server and not on the till', () async {
    await auth.login('1111');
    await ringUp('7');
    await auth.logout();
    authRepo.beAdmin();
    await auth.login('2222');
    expect((await sales.fetchPage(page: 1)).rows, hasLength(1));

    // The network came back for a moment — long enough for the outbox to post
    // this sale and mark it done. That is the whole point of the queue.
    final row = (await outbox.byUuid(ticket))!;
    await outbox.save(row.copyWith(status: PendingSaleStatus.synced, serverSaleId: '5001'));

    // And now the offline list has nothing to show: the sale lives on the server,
    // and the device keeps no copy of a committed sale. Not a scoping problem —
    // there is simply nothing left on this till to show anybody.
    expect((await sales.fetchPage(page: 1)).rows, isEmpty);
  });

  test('the handover does not empty the till', () async {
    await provisionStaff();
    await auth.login('1111');

    await auth.logout();
    authRepo.beOtherCashier();
    await auth.login('3333');

    // Signing out used to wipe the catalog and every lookup with it, so the next
    // person on a till with no network had nothing to sell.
    expect(await snapshot.lookupCount(branchId: 1, kind: SnapshotLookup.employee), 2);
  });
}

final String _today = DateTime.now().toIso8601String().split('T').first;

/// An auth repository that can be told who signs in next.
class _SwitchableAuth implements AuthRepository {
  String _id = '7';
  String _name = 'Cashier 7';
  bool _admin = false;
  String _type = 'employee';

  void beAdmin() {
    _id = '9';
    _name = 'Rana';
    _admin = true;
  }

  void beOtherCashier() {
    _id = '8';
    _name = 'Cashier 8';
    _admin = false;
    _type = 'employee';
  }

  @override
  Future<({String token, ApiUser user})> login(String pin) async => (
        token: 'token-$_id',
        user: ApiUser.fromJson({
          'id': _id,
          'name': _name,
          'is_admin': _admin,
          'type': _type,
          'branch_id': '1',
          'permissions': const <String>[],
        }),
      );

  @override
  Future<({String token, ApiUser user})> loginCredential(String u, String p) => login('');

  @override
  Future<void> logout() async {}

  @override
  Future<void> changePin(String currentPin, String newPin) async {}

  @override
  Future<void> changePassword(String current, String next) async {}
}

/// The sales endpoint, with no network to reach it on.
class _UnreachableSales extends FakeSaleRepository {
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
  }) async =>
      throw DioException.connectionError(
          requestOptions: RequestOptions(), reason: 'no route to host');
}
