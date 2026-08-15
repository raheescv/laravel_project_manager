import 'package:dio/dio.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:invo/features/auth/logic/auth_cubit/auth_cubit.dart';
import 'package:invo/features/sale/domain/models/pending_sale.dart';
import 'package:invo/features/sale/domain/repository/outbox_repository.dart';
import 'package:invo/features/sale/domain/services/outbox_service.dart';
import 'package:invo/features/sales/logic/sales_cubit/sales_cubit.dart';
import 'package:invo/shared/domain/constants/global_variables.dart';
import 'package:invo/shared/domain/helpers/formatters.dart';
import 'package:invo/shared/domain/models/index.dart';
import 'package:invo/shared/utils/router/http_utils/common_exception.dart';

import 'support/fake_repositories.dart';
import 'support/offline_harness.dart';

/// A cashier who has just rung something up offline looks for it in Sales. A list
/// that omits it reads as a lost sale — so held rows appear there, at the top of the
/// first page, and are the only thing shown when the server cannot be reached.
///
/// From that row the invoice screen opens it and Edit corrects the queued row, which
/// is the only way to change an offline sale.
void main() {
  late OutboxService outbox;
  late _StubSales online;
  late SalesCubit sales;

  const a = 'aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa';
  const b = 'bbbbbbbb-bbbb-4bbb-8bbb-bbbbbbbbbbbb';

  setUp(() async {
    await setUpOfflineHarness();
    outbox = OutboxService();
    online = _StubSales();
    serviceLocator.registerSingleton<OutboxRepository>(outbox);
    sales = SalesCubit(sales: online, lookup: null);
  });

  tearDown(tearDownOfflineHarness);

  /// Signs [id] in, so the held rows have somebody to be scoped against.
  /// [admin] is the difference between "sees the whole till" and "sees their own".
  void signIn(String id, {required bool admin, String type = 'employee'}) {
    if (serviceLocator.isRegistered<AuthCubit>()) serviceLocator.unregister<AuthCubit>();
    final auth = AuthCubit()
      ..seedSession(ApiUser.fromJson({
        'id': id,
        'name': 'Cashier $id',
        'is_admin': admin,
        'type': type,
        'permissions': const <String>[],
      }));
    serviceLocator.registerSingleton<AuthCubit>(auth);
    addTearDown(auth.close);
  }

  Future<PendingSale> queue(String uuid,
          {String? status, String date = '2026-08-09', String userId = '7'}) =>
      outbox.enqueue(
        clientUuid: uuid,
        payload: {
          'customerName': 'Walk-in',
          'items': [
            {'productId': 1, 'quantity': 1, 'unitPrice': 10.0, 'discount': 0},
          ],
          'paymentMethod': 'Cash',
          'totalPayment': 10.0,
          'clientUuid': uuid,
          if (status != null) 'status': status,
        },
        saleJson: {
          'id': '',
          'invoice_no': '',
          'client_uuid': uuid,
          'pending': true,
          'date': date,
          'status': status ?? 'completed',
          'customer': {'name': 'Walk-in', 'mobile': ''},
          'items': const [],
          'payments': const [],
          'summary': {'grand_total': 10.0, 'paid': 10.0, 'balance': 0},
        },
        userId: userId,
        branchId: 1,
      );

  test('a held sale appears at the top of the first page, above the server rows', () async {
    final row = await queue(a);

    final page = await sales.fetchPage(page: 1);

    expect(page.rows.first['invoice_no'], row.provisionalRef);
    expect(page.rows.first['pending'], isTrue);
    // The server's own rows follow, untouched.
    expect(page.rows.last['invoice_no'], 'INV-1');
    expect(page.total, 2);
  });

  test('held sales are not repeated on later pages', () async {
    await queue(a);

    final page = await sales.fetchPage(page: 2);

    // Showing them again would list the same sale twice.
    expect(page.rows.every((r) => r['pending'] != true), isTrue);
  });

  test('an already-synced row is not shown, because the server list has it', () async {
    final row = await queue(a);
    await outbox.save(row.copyWith(status: PendingSaleStatus.synced));

    final page = await sales.fetchPage(page: 1);

    expect(page.rows.where((r) => r['pending'] == true), isEmpty);
  });

  test('with no network the list is the held sales, and says nothing more', () async {
    await queue(a);
    await queue(b);
    online.failWith =
        DioException.connectionError(requestOptions: RequestOptions(), reason: 'no route');

    final page = await sales.fetchPage(page: 1);

    expect(page.rows, hasLength(2));
    expect(page.lastPage, 1);
  });

  test('a server that answered is surfaced, not replaced by a short list', () async {
    await queue(a);
    online.failWith = ApiException('Something went wrong', statusCode: 500);

    // Showing two rows here would read as "these are all your sales", which is a
    // lie, and would bury a real server problem.
    await expectLater(sales.fetchPage(page: 1), throwsA(isA<ApiException>()));
  });

  test('the status filter is honoured for held rows too', () async {
    await queue(a, status: 'draft');
    await queue(b);

    final drafts = await sales.fetchPage(page: 1, status: 'draft');
    final completed = await sales.fetchPage(page: 1, status: 'completed');

    expect(drafts.rows.where((r) => r['pending'] == true), hasLength(1));
    expect(completed.rows.where((r) => r['pending'] == true), hasLength(1));
  });

  test('a payment-method filter excludes held rows rather than ignoring the filter', () async {
    await queue(a);

    final page = await sales.fetchPage(page: 1, paymentMethodId: 3);

    // A held sale has no payment-method id yet — the server assigns those — so it
    // cannot honestly be claimed to match one.
    expect(page.rows.where((r) => r['pending'] == true), isEmpty);
  });

  test('a date filter is applied against the till clock that captured the sale', () async {
    await queue(a, date: '2026-08-09');

    final inRange = await sales.fetchPage(page: 1, fromDate: '2026-08-01', toDate: '2026-08-31');
    final outOfRange = await sales.fetchPage(page: 1, fromDate: '2026-07-01', toDate: '2026-07-31');

    expect(inRange.rows.where((r) => r['pending'] == true), hasLength(1));
    expect(outOfRange.rows.where((r) => r['pending'] == true), isEmpty);
  });

  test('the row parses through the same Sale model as a committed one', () async {
    final row = await queue(a);

    final page = await sales.fetchPage(page: 1);
    final sale = Sale.fromJson(page.rows.first);

    // Which is what lets the invoice screen open it with no special case, see that
    // it is pending, and point Edit at the queue instead of at the server.
    expect(sale.pending, isTrue);
    expect(sale.invoiceNo, row.provisionalRef);
    expect(sale.clientUuid, a);
  });

  group('the search filter', () {
    test('the term reaches the server', () async {
      await sales.fetchPage(page: 1, search: 'INV-1');

      expect(online.lastSearch, 'INV-1');
    });

    test('a held row matches on its provisional reference', () async {
      final row = await queue(a);

      final page = await sales.fetchPage(page: 1, search: row.provisionalRef.toLowerCase());

      // Which is what the cashier will type: the provisional reference is what is
      // printed on the receipt in their hand.
      expect(page.rows.where((r) => r['pending'] == true), hasLength(1));
    });

    test('a held row matches on part of the reference, not just the whole thing', () async {
      final row = await queue(a);

      final page = await sales.fetchPage(page: 1, search: row.provisionalRef.substring(4, 8));

      expect(page.rows.where((r) => r['pending'] == true), hasLength(1));
    });

    test('a held row matches on the customer name', () async {
      await queue(a);

      final page = await sales.fetchPage(page: 1, search: 'walk');

      expect(page.rows.where((r) => r['pending'] == true), hasLength(1));
    });

    test('a held row that matches nothing is filtered out', () async {
      await queue(a);

      final page = await sales.fetchPage(page: 1, search: 'nobody');

      expect(page.rows.where((r) => r['pending'] == true), isEmpty);
    });

    test('an empty term is not a filter', () async {
      await queue(a);

      final page = await sales.fetchPage(page: 1, search: '   ');

      expect(page.rows.where((r) => r['pending'] == true), hasLength(1));
    });

    test('search still works with no network at all', () async {
      final row = await queue(a);
      await queue(b);
      online.failWith =
          DioException.connectionError(requestOptions: RequestOptions(), reason: 'no route');

      final page = await sales.fetchPage(page: 1, search: row.provisionalRef);

      // Matched on the device, because there is nothing to ask — and this is exactly
      // when someone is hunting for a sale they just took.
      expect(page.rows, hasLength(1));
    });
  });

  group('whose sales a held row belongs to', () {
    /// One sale each from two cashiers on the same till.
    Future<void> twoCashiersHaveSold() async {
      await queue(a, userId: '7');
      await queue(b, userId: '8');
    }

    test('a non-admin employee sees only their own', () async {
      await twoCashiersHaveSold();
      signIn('7', admin: false);
      online.failWith = DioException.connectionError(
          requestOptions: RequestOptions(), reason: 'no route');

      final page = await sales.fetchPage(page: 1);

      // Offline the server's own scoping cannot run, so going offline must not
      // hand a cashier their colleague's takings.
      expect(page.rows, hasLength(1));
      expect(page.rows.single['client_uuid'], a);
    });

    test('an admin sees the whole till', () async {
      await twoCashiersHaveSold();
      signIn('9', admin: true);
      online.failWith = DioException.connectionError(
          requestOptions: RequestOptions(), reason: 'no route');

      final page = await sales.fetchPage(page: 1);

      expect(page.rows, hasLength(2));
    });

    test('a back-office account is not treated as an employee', () async {
      await twoCashiersHaveSold();
      signIn('9', admin: false, type: 'user');
      online.failWith = DioException.connectionError(
          requestOptions: RequestOptions(), reason: 'no route');

      expect((await sales.fetchPage(page: 1)).rows, hasLength(2));
    });

    test('the staff filter narrows held rows and is sent to the server', () async {
      await twoCashiersHaveSold();
      signIn('9', admin: true);

      final page = await sales.fetchPage(page: 1, staffId: 8);

      expect(online.lastCreatedById, 8, reason: 'the committed rows are filtered server-side');
      final held = page.rows.where((r) => r['pending'] == true).toList();
      expect(held, hasLength(1));
      expect(held.single['client_uuid'], b);
    });

    test('an unattributed row stays visible — it is still money taken', () async {
      await queue(a, userId: '');
      signIn('7', admin: false);
      online.failWith = DioException.connectionError(
          requestOptions: RequestOptions(), reason: 'no route');

      expect((await sales.fetchPage(page: 1)).rows, hasLength(1));
    });
  });

  group('editing a held row from the list', () {
    test('the row carries the key the Edit action needs', () async {
      await queue(a);

      final held = (await sales.fetchPage(page: 1)).rows.first;

      // The list has no server id to work with, so the client uuid is the only
      // handle on the outbox row — without it the Edit button has nothing to open.
      expect(held['client_uuid'], a);
      expect(asStr(held['id']), isEmpty);
    });

    test('that key resolves the outbox row the correction rewrites', () async {
      final queued = await queue(a);

      final held = (await sales.fetchPage(page: 1)).rows.first;
      final resolved = await outbox.byUuid(asStr(held['client_uuid']));

      expect(resolved, isNotNull);
      expect(resolved!.provisionalRef, queued.provisionalRef);
    });

    test('a row that synced between the tap and the lookup resolves to nothing',
        () async {
      await queue(a);
      final held = (await sales.fetchPage(page: 1)).rows.first;

      // The drain got there first, which is the good outcome — the sale is an
      // ordinary one now, and the screen says so rather than editing a ghost.
      await outbox.discard(a);

      expect(await outbox.byUuid(asStr(held['client_uuid'])), isNull);
    });

    test('a committed row has no key, so it is never routed to the queue', () async {
      final committed = (await sales.fetchPage(page: 1)).rows.last;

      expect(committed['pending'], isNot(true));
      expect(asStr(committed['client_uuid']), isEmpty);
    });
  });
}

/// A sale repository whose list always answers with one committed row.
class _StubSales extends FakeSaleRepository {
  Object? failWith;

  /// The term the list asked the server for, so a test can prove it was passed on
  /// rather than only applied locally.
  String? lastSearch;

  /// Same, for the staff filter.
  int? lastCreatedById;

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
  }) async {
    lastSearch = search;
    lastCreatedById = createdById;
    if (failWith case final failure?) throw failure;
    return SalesPage(
      rows: [
        {
          'id': '1',
          'invoice_no': 'INV-1',
          'date': '2026-08-09',
          'status': 'completed',
          'customer': const {'name': 'Walk-in', 'mobile': ''},
          'items': const [],
          'payments': const [],
          'summary': const {'grand_total': 10.0, 'paid': 10.0, 'balance': 0},
        },
      ],
      currentPage: page,
      lastPage: 1,
      total: 1,
      totalPaid: 10,
    );
  }
}
