import 'package:dio/dio.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:invo/features/sale/domain/repository/outbox_repository.dart';
import 'package:invo/features/sale/domain/services/outbox_service.dart';
import 'package:invo/features/sale/logic/offline_sync_cubit/offline_sync_cubit.dart';
import 'package:invo/features/settings/logic/print_settings_cubit/print_settings_cubit.dart';
import 'package:invo/shared/domain/constants/global_variables.dart';
import 'package:invo/shared/domain/models/index.dart';
import 'package:invo/shared/domain/repository/catalog_snapshot_repository.dart';
import 'package:invo/shared/domain/services/catalog_snapshot_service.dart';
import 'package:invo/shared/logic/branch_cubit/branch_cubit.dart';
import 'package:invo/shared/logic/connectivity_cubit/connectivity_cubit.dart';
import 'package:invo/shared/logic/currency_cubit/currency_cubit.dart';
import 'package:invo/shared/utils/router/http_utils/common_exception.dart';

import 'support/fake_lookup_repository.dart';
import 'support/fake_repositories.dart';
import 'support/offline_harness.dart';

/// Nobody taps "download the catalog". A till that only cached its products when
/// someone remembered to would be offline-capable exactly on the days somebody
/// thought of it — so provisioning is something the app does to itself while the
/// network is still there, and these tests pin *when* it does it.
///
/// [offline_provision_all_pages_test] already covers how much is fetched. This
/// file covers the decisions around that fetch: when it is skipped, when it is
/// re-taken on its own, and what it must never do to a catalog the till is
/// currently selling from.
void main() {
  late _ProvisioningLookup lookup;
  late _AgeableSnapshot snapshot;
  late ConnectivityCubit connectivity;
  late OfflineSyncCubit sync;
  late int branchId;

  setUp(() async {
    await setUpOfflineHarness();
    lookup = _ProvisioningLookup();
    branchId = await registerBranchContext(lookup: lookup);
    snapshot = _AgeableSnapshot();
    connectivity = ConnectivityCubit();
    serviceLocator
      ..registerSingleton<OutboxRepository>(OutboxService())
      ..registerSingleton<CatalogSnapshotRepository>(snapshot)
      ..registerSingleton<ConnectivityCubit>(connectivity)
      // Provisioning pulls the sale settings and the currency list through these
      // two, so without them every run would report them as missing.
      ..registerLazySingleton<CurrencyCubit>(CurrencyCubit.new)
      ..registerLazySingleton<PrintSettingsCubit>(PrintSettingsCubit.new);
    sync = OfflineSyncCubit(FakeSaleRepository());
  });

  tearDown(() async {
    await sync.close();
    await connectivity.close();
    await tearDownOfflineHarness();
  });

  /// Poll rather than sleep. Provisioning is fire-and-forget and does real
  /// database work, so a fixed delay passes on an idle machine and fails on a
  /// busy one for reasons that have nothing to do with what is being tested.
  Future<void> until(bool Function() done,
      {Duration limit = const Duration(seconds: 5)}) async {
    final deadline = DateTime.now().add(limit);
    while (DateTime.now().isBefore(deadline)) {
      if (done()) return;
      await Future<void>.delayed(const Duration(milliseconds: 10));
    }
  }

  group('what the first online session leaves on the device', () {
    test('signing in provisions the catalog without anyone asking for it', () async {
      await sync.bootstrap();
      await until(() => sync.state.hasCatalog);

      // The two lists the New Sale screen is built out of. A till that reached
      // this point can sell the moment the network goes.
      expect((await snapshot.products(branchId: branchId)).total, 3);
      expect(await snapshot.categories(branchId: branchId), isNotEmpty);
      expect(sync.state.hasCatalog, isTrue);
    });

    test('the category rail is cached for all three filters the grid offers', () async {
      await sync.refreshCatalog(force: true);

      // The grid can be on All Types, Products or Services, and each shows a
      // different list. Caching only the one that happened to be on screen
      // leaves the other two empty the moment the network goes — which reads as
      // a shop with no categories rather than as a missing download.
      expect(lookup.categoryTypes, [null, 'product', 'service']);
      expect(await snapshot.categories(branchId: branchId), isNotEmpty);
      expect(await snapshot.categories(branchId: branchId, type: 'product'), isNotEmpty);
      expect(await snapshot.categories(branchId: branchId, type: 'service'), isNotEmpty);
    });

    test('a run with everything reachable reports nothing missing', () async {
      await sync.refreshCatalog(force: true);

      expect(sync.state.provisionIncomplete, isEmpty);
      expect(sync.state.catalogTruncated, isFalse);
      expect(sync.state.catalogRefreshing, isFalse);
    });

    test('the snapshot time is published, so the grid can say how old it is', () async {
      await sync.refreshCatalog(force: true);

      expect(sync.state.catalogSyncedAt, isNotNull);
      expect(sync.state.catalogFreshness, CatalogFreshness.fresh);
    });
  });

  group('not downloading what is already there', () {
    test('a snapshot taken minutes ago is left alone', () async {
      await sync.refreshCatalog(force: true);
      final downloaded = lookup.productRequests;

      // Every screen open, resume and reconnect calls this. Re-downloading the
      // whole catalog each time would put a shop's entire product list on the
      // wire several times an hour for nothing.
      await sync.refreshCatalog();

      expect(lookup.productRequests, downloaded);
    });

    test('past six hours it is re-taken on its own', () async {
      await sync.refreshCatalog(force: true);
      final downloaded = lookup.productRequests;
      snapshot.ageOffset = const Duration(hours: 7);

      await sync.refreshCatalog();

      expect(lookup.productRequests, greaterThan(downloaded));
    });

    test('force re-takes it whatever its age', () async {
      await sync.refreshCatalog(force: true);
      final downloaded = lookup.productRequests;

      await sync.refreshCatalog(force: true);

      expect(lookup.productRequests, greaterThan(downloaded));
    });

    test('two refreshes at once cost one download', () async {
      // Boot and app-resume routinely land together. Both starting a full
      // catalog download is the largest request this app makes, twice.
      await Future.wait([
        sync.refreshCatalog(force: true),
        sync.refreshCatalog(force: true),
      ]);

      expect(lookup.productRequests, 1);
    });
  });

  group('what must never happen to a catalog the till is selling from', () {
    test('an empty response does not wipe the products already cached', () async {
      await sync.refreshCatalog(force: true);
      final written = snapshot.replaceCalls;

      // A filtered or half-failed page comes back looking exactly like a shop
      // with nothing to sell. Believing it would empty the snapshot AND stamp it
      // fresh for six hours, leaving the till unable to sell anything at all.
      lookup.productTotal = 0;
      await sync.refreshCatalog(force: true);

      expect(snapshot.replaceCalls, written, reason: 'the empty page was not written');
      expect((await snapshot.products(branchId: branchId)).total, 3);
    });

    test('a shop with nothing to sell yet still gets a snapshot', () async {
      // The mirror of the case above: with nothing to lose, an empty catalog is
      // taken at face value, or a new tenant never gets a snapshot at all and
      // the grid keeps saying the till has never been online.
      lookup.productTotal = 0;

      await sync.refreshCatalog(force: true);

      expect(await snapshot.meta(branchId), isNotNull);
      expect(sync.state.hasCatalog, isTrue);
    });

    test('a list that fails is named, and the products still land', () async {
      lookup.failPaymentMethods = true;

      await sync.refreshCatalog(force: true);

      // One missing reference list must not cost the catalog — and it must be
      // said out loud, because a half-built snapshot that looks whole is what
      // sends a cashier into an outage believing they can take a card payment.
      expect(sync.state.provisionIncomplete, contains('Payment methods'));
      expect((await snapshot.products(branchId: branchId)).total, 3);
    });

    test('a refusal from the server is surfaced rather than swallowed', () async {
      lookup.refuseWith = ApiException('Unauthenticated.', statusCode: 401);

      await sync.refreshCatalog(force: true);

      // This will not fix itself the way an outage does, and a snapshot that
      // never lands means the till silently has no offline mode at all.
      expect(sync.state.errorMessage, 'Unauthenticated.');
      expect(sync.state.catalogRefreshing, isFalse);
    });

    test('no network leaves the previous snapshot untouched and unblamed', () async {
      await sync.refreshCatalog(force: true);
      lookup.offline = true;
      snapshot.ageOffset = const Duration(hours: 7);

      await sync.refreshCatalog();

      // Failing to refresh is not an error a cashier can act on; the previous
      // catalog is still there and still sellable, and the grid's own "catalog
      // from …" chip already says how old it is.
      expect((await snapshot.products(branchId: branchId)).total, 3);
      expect(sync.state.errorMessage, isNull);
      expect(sync.state.catalogRefreshing, isFalse);
    });
  });

  group('acting on a change while there is still a network', () {
    test('switching branch provisions the branch just switched to', () async {
      await sync.refreshCatalog(force: true);

      await serviceLocator<BranchCubit>().setBranch(
        Branch(id: 4, name: 'Uptown', location: 'Uptown', code: 'UP-04'),
      );
      await until(() => snapshot.branchesWritten.contains(4));

      // Selling the previous branch's prices after a switch is the failure this
      // prevents; the catalog is branch-scoped precisely so it cannot happen.
      expect((await snapshot.products(branchId: 4)).total, 3);
    });

    test('reconnecting re-takes a snapshot that went stale during the outage', () async {
      await sync.refreshCatalog(force: true);
      final downloaded = lookup.productRequests;
      snapshot.ageOffset = const Duration(hours: 7);

      connectivity.reportOutcome(reachable: false);
      // A request got an answer — the only proof of reach the app has, and the
      // moment to re-take a snapshot rather than waiting out the six-hour check
      // on a link that may not last.
      connectivity.reportOutcome(reachable: true);
      await until(() => lookup.productRequests > downloaded);

      expect(lookup.productRequests, greaterThan(downloaded));
    });
  });
}

/// The real snapshot storage, with the two things a test needs to steer: how old
/// the stored snapshot claims to be, and what was written to it.
///
/// Subclassed rather than faked so every assertion still goes through the SQLite
/// the app actually ships — the staleness rule is only worth testing against the
/// timestamps that rule really reads.
class _AgeableSnapshot extends CatalogSnapshotService {
  /// Backdates whatever [meta] reports, so the six-hour rule can be crossed
  /// without a test sleeping through it.
  Duration ageOffset = Duration.zero;

  int replaceCalls = 0;
  final Set<int> branchesWritten = {};

  @override
  Future<CatalogSnapshotMeta?> meta(int branchId) async {
    final real = await super.meta(branchId);
    if (real == null || ageOffset == Duration.zero) return real;
    return CatalogSnapshotMeta(
      syncedAt: real.syncedAt.subtract(ageOffset),
      productCount: real.productCount,
    );
  }

  @override
  Future<void> replace({
    required int branchId,
    required List<Map<String, dynamic>> products,
    required Map<String, List<Category>> categoriesByType,
  }) async {
    replaceCalls++;
    branchesWritten.add(branchId);
    return super.replace(
        branchId: branchId, products: products, categoriesByType: categoriesByType);
  }
}

/// A server that can be turned empty, unreachable or hostile between calls, and
/// that records which type filter each category request asked for.
class _ProvisioningLookup extends FakeLookupRepository {
  /// How many products the shop has. Mutable so a stocked catalog can come back
  /// empty on the next refresh, which is what a half-failed page looks like.
  int productTotal = 3;

  /// No route to the host — the outage case.
  bool offline = false;

  /// The server answered and refused: an expired token, a revoked permission.
  ApiException? refuseWith;

  bool failPaymentMethods = false;

  int productRequests = 0;

  /// The type filter of each `categories` call, in order.
  final List<String?> categoryTypes = [];

  DioException get _unreachable =>
      DioException.connectionError(requestOptions: RequestOptions(), reason: 'no route');

  @override
  Future<({List<Map<String, dynamic>> rows, int currentPage, int lastPage})> productsRaw({
    String? type,
    int page = 1,
    int perPage = 100,
  }) async {
    productRequests++;
    if (refuseWith != null) throw refuseWith!;
    if (offline) throw _unreachable;
    return (
      rows: [
        for (var id = 1; id <= productTotal; id++)
          <String, dynamic>{
            'id': id,
            'name': 'Service $id',
            'code': 'S$id',
            'barcode': 'B$id',
            'type': 'service',
            'mrp': 45.0,
            'tax': 0,
            'total_stock': 5,
          },
      ],
      currentPage: 1,
      lastPage: 1,
    );
  }

  @override
  Future<List<Category>> categories({String? type}) async {
    categoryTypes.add(type);
    if (offline) throw _unreachable;
    return [
      Category(id: 1, name: 'Hair', productCount: productTotal),
      Category(id: 2, name: 'Color', productCount: 1),
    ];
  }

  @override
  Future<List<PaymentMethod>> paymentMethods() async {
    if (failPaymentMethods) throw _unreachable;
    return super.paymentMethods();
  }
}
