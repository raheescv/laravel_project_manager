import 'package:flutter_test/flutter_test.dart';
import 'package:invo/features/sale/domain/repository/outbox_repository.dart';
import 'package:invo/features/sale/domain/services/outbox_service.dart';
import 'package:invo/features/sale/logic/offline_sync_cubit/offline_sync_cubit.dart';
import 'package:invo/shared/domain/constants/global_variables.dart';
import 'package:invo/shared/domain/models/index.dart';
import 'package:invo/shared/domain/repository/catalog_snapshot_repository.dart';
import 'package:invo/shared/domain/services/catalog_snapshot_service.dart';

import 'support/fake_lookup_repository.dart';
import 'support/fake_repositories.dart';
import 'support/offline_harness.dart';

/// The offline snapshot has to hold **everything**, because anything missing from it
/// simply cannot be used offline — a product that is not there cannot be sold, and a
/// customer who is not there gets retyped, which creates a duplicate account for them
/// the moment the sale syncs.
///
/// The interactive lookups deliberately fetch small pages (20 customers is right for
/// a search-as-you-type sheet). Provisioning must not reuse that: these tests pin
/// that it pages to the end of each list.
void main() {
  late _PagedLookup lookup;
  late CatalogSnapshotService snapshot;
  late OfflineSyncCubit sync;
  late int branchId;

  setUp(() async {
    await setUpOfflineHarness();
    lookup = _PagedLookup();
    branchId = await registerBranchContext(lookup: lookup);
    snapshot = CatalogSnapshotService();
    serviceLocator
      ..registerSingleton<OutboxRepository>(OutboxService())
      ..registerSingleton<CatalogSnapshotRepository>(snapshot);
    sync = OfflineSyncCubit(FakeSaleRepository());
  });

  tearDown(() async {
    await sync.close();
    await tearDownOfflineHarness();
  });

  test('every page of the catalog is snapshotted, not just the first', () async {
    lookup.productPages = 4; // 4 × 100

    await sync.refreshCatalog(force: true);

    final page = await snapshot.products(branchId: branchId, perPage: 1000);
    expect(page.total, 400);
    expect(sync.state.catalogTruncated, isFalse);
  });

  test('a single-page catalog costs exactly one request', () async {
    lookup.productPages = 1;

    await sync.refreshCatalog(force: true);

    expect(lookup.productRequests, 1);
  });

  test('every page of customers is cached, not the 20 the picker asks for', () async {
    lookup.customerPages = 3; // 3 × 100

    await sync.refreshCatalog(force: true);

    // Caching 20 of a shop's customers is what makes an offline till create a
    // duplicate account for nearly every returning client it serves.
    expect(
      await snapshot.lookupCount(branchId: branchId, kind: SnapshotLookup.customer),
      300,
    );
  });

  test('every page of staff is cached', () async {
    lookup.employeePages = 2;

    await sync.refreshCatalog(force: true);

    expect(
      await snapshot.lookupCount(branchId: branchId, kind: SnapshotLookup.employee),
      200,
    );
  });

  test('provisioning uses the paged lookups, never the small interactive ones', () async {
    lookup.customerPages = 2;

    await sync.refreshCatalog(force: true);

    // `customers()` is the 20-row search endpoint. Provisioning calling it would
    // silently cap the snapshot at 20 however many pages exist.
    expect(lookup.interactiveCustomerCalls, 0);
    expect(lookup.customerRequests, 2);
  });

  test('a truncated catalog is reported rather than passed off as complete', () async {
    // Far more pages than the backstop allows.
    lookup.productPages = 100000;

    await sync.refreshCatalog(force: true);

    // Whatever was fetched is still written — a partial catalog beats none — but
    // "some products cannot be sold offline" must not look like a full snapshot.
    expect(sync.state.catalogTruncated, isTrue);
    expect((await snapshot.products(branchId: branchId)).total, greaterThan(0));
  });
}

/// A lookup repository that serves a configurable number of pages and counts the
/// requests, so a test can prove which endpoint provisioning used and how often.
class _PagedLookup extends FakeLookupRepository {
  int productPages = 1;
  int customerPages = 1;
  int employeePages = 1;

  int productRequests = 0;
  int customerRequests = 0;
  int employeeRequests = 0;

  /// Calls to the small search-as-you-type endpoint. Provisioning must never use it.
  int interactiveCustomerCalls = 0;

  static const int _perPage = 100;

  @override
  Future<({List<Map<String, dynamic>> rows, int currentPage, int lastPage})> productsRaw({
    String? type,
    int page = 1,
    int perPage = 100,
  }) async {
    productRequests++;
    return (
      rows: [
        for (var i = 0; i < _perPage; i++)
          {
            'id': (page - 1) * _perPage + i + 1,
            'name': 'Product ${(page - 1) * _perPage + i + 1}',
            'code': 'C${(page - 1) * _perPage + i + 1}',
            'barcode': 'B${(page - 1) * _perPage + i + 1}',
            'type': 'product',
            'mrp': 10.0,
            'tax': 0,
            'total_stock': 5,
          },
      ],
      currentPage: page,
      lastPage: productPages,
    );
  }

  @override
  Future<List<Customer>> customers({String? mobile, String? search}) async {
    interactiveCustomerCalls++;
    return const [];
  }

  @override
  Future<Paginated<Customer>> customersPage({int page = 1, int perPage = 100, String? search}) async {
    customerRequests++;
    return Paginated(
      items: [
        for (var i = 0; i < _perPage; i++)
          Customer(id: (page - 1) * _perPage + i + 1, name: 'Customer $i', mobile: '05$i'),
      ],
      currentPage: page,
      lastPage: customerPages,
      total: customerPages * _perPage,
    );
  }

  @override
  Future<Paginated<Employee>> employeesPage({int page = 1, int perPage = 100, int? branchId}) async {
    employeeRequests++;
    return Paginated(
      items: [
        for (var i = 0; i < _perPage; i++)
          Employee(
              id: (page - 1) * _perPage + i + 1,
              name: 'Staff $i',
              code: 'E$i',
              mobile: '05$i',
              designation: 'Stylist',
            ),
      ],
      currentPage: page,
      lastPage: employeePages,
      total: employeePages * _perPage,
    );
  }
}
