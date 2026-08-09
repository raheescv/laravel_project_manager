import 'dart:typed_data';

import 'package:invo/shared/logic/branch_cubit/branch_cubit.dart';

import '../../utils/router/http_utils/reachability.dart';
import '../constants/global_variables.dart';
import '../models/index.dart';
import '../repository/catalog_snapshot_repository.dart';
import '../repository/lookup_repository.dart';

/// Wraps [LookupService] so the three reference lists the New Sale flow depends
/// on keep answering with no network.
///
/// Only those three are decorated. Products and categories are deliberately left
/// alone: [CatalogCubit] does its own fallback because it has to *show* that the
/// grid is serving a stored copy, and a silent fallback here would take that
/// away from it. Settings, currencies and the logo already cache into prefs
/// through their own cubits.
///
/// As with sales, only an unreachable server triggers the fallback. A server
/// that answered and refused is a real answer and is passed on.
class OfflineFirstLookupService implements LookupRepository {
  OfflineFirstLookupService(this._online);

  final LookupRepository _online;

  CatalogSnapshotRepository get _snapshot => serviceLocator<CatalogSnapshotRepository>();

  int? get _branchId =>
      serviceLocator.isRegistered<BranchCubit>() ? serviceLocator<BranchCubit>().selectedId : null;

  @override
  Future<List<PaymentMethod>> paymentMethods() async {
    try {
      final live = await _online.paymentMethods();
      return live;
    } catch (e) {
      final branchId = _branchId;
      if (branchId == null || !isServerUnreachable(e)) rethrow;
      // Without these the Custom split sheet is empty and a cashier can only
      // take Cash, Card or Credit — a real loss of function, not cosmetic.
      return _snapshot.paymentMethods(branchId: branchId);
    }
  }

  @override
  Future<List<Employee>> employees({String? search, int? branchId}) async {
    try {
      return await _online.employees(search: search, branchId: branchId);
    } catch (e) {
      final id = branchId ?? _branchId;
      if (id == null || !isServerUnreachable(e)) rethrow;
      return _snapshot.employees(branchId: id, search: search);
    }
  }

  @override
  Future<List<Customer>> customers({String? mobile, String? search}) async {
    try {
      return await _online.customers(mobile: mobile, search: search);
    } catch (e) {
      final branchId = _branchId;
      if (branchId == null || !isServerUnreachable(e)) rethrow;
      // Finding the returning client matters offline: retyping them creates a
      // second account for the same person when the sale syncs.
      return _snapshot.customers(branchId: branchId, mobile: mobile, search: search);
    }
  }

  // ---- pass-through ----
  //
  // The paged variants are only used to BUILD the offline snapshot, so there is
  // nothing to fall back to: failing is the honest answer, and provisioning already
  // records which lists it could not fetch.

  @override
  Future<Paginated<Customer>> customersPage({int page = 1, int perPage = 100, String? search}) =>
      _online.customersPage(page: page, perPage: perPage, search: search);

  @override
  Future<Paginated<Employee>> employeesPage({int page = 1, int perPage = 100, int? branchId}) =>
      _online.employeesPage(page: page, perPage: perPage, branchId: branchId);

  @override
  Future<Paginated<Product>> products({
    String? search,
    int? mainCategoryId,
    String? type,
    int page = 1,
    int perPage = 50,
  }) =>
      _online.products(
        search: search,
        mainCategoryId: mainCategoryId,
        type: type,
        page: page,
        perPage: perPage,
      );

  @override
  Future<({List<Map<String, dynamic>> rows, int currentPage, int lastPage})> productsRaw({
    String? type,
    int page = 1,
    int perPage = 100,
  }) =>
      _online.productsRaw(type: type, page: page, perPage: perPage);

  @override
  Future<Product?> productByBarcode(String barcode) => _online.productByBarcode(barcode);

  @override
  Future<List<Category>> categories({String? type}) => _online.categories(type: type);

  @override
  Future<List<Branch>> branches() => _online.branches();

  @override
  Future<({String? baseCode, List<Currency> currencies})> currencies() => _online.currencies();

  @override
  Future<({double? defaultQuantity, bool? tipEnabled, String? defaultProductType, RemotePrintConfig? print})>
      saleSettings() => _online.saleSettings();

  @override
  Future<Uint8List> logo() => _online.logo();

  @override
  Future<RemotePrintConfig?> savePrintSettings(Map<String, dynamic> body) =>
      _online.savePrintSettings(body);
}
