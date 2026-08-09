import 'dart:typed_data';

import 'package:dio/dio.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:invo/shared/domain/constants/global_variables.dart';
import 'package:invo/shared/domain/models/index.dart';
import 'package:invo/shared/domain/repository/catalog_snapshot_repository.dart';
import 'package:invo/shared/domain/repository/lookup_repository.dart';
import 'package:invo/shared/domain/services/catalog_snapshot_service.dart';
import 'package:invo/shared/domain/services/offline_first_lookup_service.dart';
import 'package:invo/shared/utils/router/http_utils/common_exception.dart';

import 'support/offline_harness.dart';

/// The reference lists a cashier cannot ring a sale without: the configured
/// payment methods, the staff who can be assigned to a ticket, and the customers
/// who must be found rather than retyped (retyping creates a duplicate account
/// when the sale syncs).
void main() {
  late CatalogSnapshotService snapshot;
  const branch = 1;

  setUp(() async {
    await setUpOfflineHarness();
    snapshot = CatalogSnapshotService();
  });

  tearDown(tearDownOfflineHarness);

  group('storage', () {
    test('each kind round-trips through its own model', () async {
      await snapshot.replaceLookups(branchId: branch, kind: SnapshotLookup.paymentMethod, rows: [
        {'id': 3, 'name': 'Cash'},
        {'id': 4, 'name': 'Card'},
      ]);
      await snapshot.replaceLookups(branchId: branch, kind: SnapshotLookup.employee, rows: [
        {'id': 9, 'name': 'Maya', 'code': 'E9', 'mobile': '5551234', 'designation': 'Stylist'},
      ]);
      await snapshot.replaceLookups(branchId: branch, kind: SnapshotLookup.customer, rows: [
        {'id': 11, 'name': 'Aisha', 'mobile': '7778888'},
      ]);

      expect((await snapshot.paymentMethods(branchId: branch)).map((m) => m.name), ['Cash', 'Card']);
      expect((await snapshot.employees(branchId: branch)).single.name, 'Maya');
      expect((await snapshot.customers(branchId: branch)).single.mobile, '7778888');
    });

    test('server order is preserved', () async {
      await snapshot.replaceLookups(branchId: branch, kind: SnapshotLookup.paymentMethod, rows: [
        {'id': 3, 'name': 'Zebra'},
        {'id': 1, 'name': 'Apple'},
      ]);

      // Not re-sorted locally — the server decides the order the sheet shows.
      expect((await snapshot.paymentMethods(branchId: branch)).map((m) => m.name), ['Zebra', 'Apple']);
    });

    test('an empty fetch never wipes a list that already works', () async {
      await snapshot.replaceLookups(branchId: branch, kind: SnapshotLookup.employee, rows: [
        {'id': 9, 'name': 'Maya'},
      ]);

      await snapshot.replaceLookups(branchId: branch, kind: SnapshotLookup.employee, rows: []);

      // An empty page is far likelier to be a failed fetch than a salon with no
      // staff, and losing the list means no stylist can be assigned.
      expect(await snapshot.lookupCount(branchId: branch, kind: SnapshotLookup.employee), 1);
    });

    test('an empty fetch is honoured when there was nothing to lose', () async {
      await snapshot.replaceLookups(branchId: branch, kind: SnapshotLookup.employee, rows: []);

      expect(await snapshot.lookupCount(branchId: branch, kind: SnapshotLookup.employee), 0);
    });

    test('customers are searchable by name or by phone', () async {
      await snapshot.replaceLookups(branchId: branch, kind: SnapshotLookup.customer, rows: [
        {'id': 1, 'name': 'Aisha Khan', 'mobile': '5551111'},
        {'id': 2, 'name': 'Bilal Ahmed', 'mobile': '5552222'},
      ]);

      expect((await snapshot.customers(branchId: branch, search: 'bil')).single.id, 2);
      expect((await snapshot.customers(branchId: branch, mobile: '5551111')).single.id, 1);
      // Case-insensitive, because nobody types the capital.
      expect((await snapshot.customers(branchId: branch, search: 'AISHA')).single.id, 1);
    });

    test('one branch list never leaks into another', () async {
      await snapshot.replaceLookups(branchId: branch, kind: SnapshotLookup.employee, rows: [
        {'id': 9, 'name': 'Maya'},
      ]);
      await snapshot.replaceLookups(branchId: 2, kind: SnapshotLookup.employee, rows: [
        {'id': 10, 'name': 'Sara'},
      ]);

      expect((await snapshot.employees(branchId: branch)).single.name, 'Maya');
      expect((await snapshot.employees(branchId: 2)).single.name, 'Sara');
    });
  });

  group('offline-first decorator', () {
    late _StubLookup online;
    late OfflineFirstLookupService service;

    setUp(() async {
      // Seed against whichever branch actually became active — the decorator
      // reads the snapshot for the branch the till is on.
      final active = await registerBranchContext();
      serviceLocator.registerSingleton<CatalogSnapshotRepository>(snapshot);
      online = _StubLookup();
      service = OfflineFirstLookupService(online);
      await snapshot.replaceLookups(branchId: active, kind: SnapshotLookup.paymentMethod, rows: [
        {'id': 3, 'name': 'Cached Cash'},
      ]);
      await snapshot.replaceLookups(branchId: active, kind: SnapshotLookup.employee, rows: [
        {'id': 9, 'name': 'Cached Maya'},
      ]);
      await snapshot.replaceLookups(branchId: active, kind: SnapshotLookup.customer, rows: [
        {'id': 11, 'name': 'Cached Aisha', 'mobile': '7778888'},
      ]);
    });

    test('serves the live list when the server answers', () async {
      expect((await service.paymentMethods()).single.name, 'Live Cash');
    });

    test('falls back to the snapshot when the server is unreachable', () async {
      online.failWith = DioException(
        requestOptions: RequestOptions(path: '/x'),
        type: DioExceptionType.connectionError,
      );

      expect((await service.paymentMethods()).single.name, 'Cached Cash');
      expect((await service.employees()).single.name, 'Cached Maya');
      expect((await service.customers(mobile: '7778888')).single.name, 'Cached Aisha');
    });

    test('a refusal from the server is passed on, not papered over', () async {
      // A 403 is a real answer. Swallowing it would hide a permissions problem
      // behind a list that quietly stops updating.
      online.failWith = ApiException('Forbidden', statusCode: 403);

      await expectLater(service.paymentMethods(), throwsA(isA<ApiException>()));
      await expectLater(service.employees(), throwsA(isA<ApiException>()));
    });
  });
}

class _StubLookup implements LookupRepository {
  Object? failWith;

  @override
  Future<List<PaymentMethod>> paymentMethods() async {
    if (failWith case final e?) throw e;
    return const [PaymentMethod(id: 1, name: 'Live Cash')];
  }

  @override
  Future<List<Employee>> employees({String? search, int? branchId}) async {
    if (failWith case final e?) throw e;
    return const [];
  }

  @override
  Future<List<Customer>> customers({String? mobile, String? search}) async {
    if (failWith case final e?) throw e;
    return const [];
  }

  @override
  Future<List<Branch>> branches() async => const [];

  @override
  Future<List<Category>> categories({String? type}) async => const [];

  @override
  Future<Product?> productByBarcode(String barcode) async => null;

  @override
  Future<Paginated<Product>> products({
    String? search,
    int? mainCategoryId,
    String? type,
    int page = 1,
    int perPage = 50,
  }) async =>
      const Paginated(items: [], currentPage: 1, lastPage: 1, total: 0);

  @override
  Future<({List<Map<String, dynamic>> rows, int currentPage, int lastPage})> productsRaw({
    String? type,
    int page = 1,
    int perPage = 100,
  }) async =>
      (rows: <Map<String, dynamic>>[], currentPage: 1, lastPage: 1);

  @override
  Future<({String? baseCode, List<Currency> currencies})> currencies() async =>
      (baseCode: null, currencies: const <Currency>[]);

  @override
  Future<({double? defaultQuantity, bool? tipEnabled, String? defaultProductType, RemotePrintConfig? print})>
      saleSettings() async =>
          (defaultQuantity: null, tipEnabled: null, defaultProductType: null, print: null);

  @override
  Future<Uint8List> logo() => throw UnimplementedError();

  @override
  Future<RemotePrintConfig?> savePrintSettings(Map<String, dynamic> body) async => null;

  @override
  Future<Paginated<Customer>> customersPage({int page = 1, int perPage = 100, String? search}) async =>
      Paginated(items: await customers(search: search), currentPage: 1, lastPage: 1, total: 0);

  @override
  Future<Paginated<Employee>> employeesPage({int page = 1, int perPage = 100, int? branchId}) async =>
      Paginated(items: await employees(branchId: branchId), currentPage: 1, lastPage: 1, total: 0);
}
