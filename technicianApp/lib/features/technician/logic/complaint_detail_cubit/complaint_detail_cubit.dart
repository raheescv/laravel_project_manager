import 'dart:async';

import 'package:invo/shared/domain/constants/global_variables.dart';
import 'package:invo/shared/logic/base/holder_cubit.dart';
import 'package:invo/shared/utils/router/http_utils/common_exception.dart';

import '../../domain/models/technician_models.dart';
import '../../domain/repository/technician_repository.dart';

/// Backs the complaint-detail workflow screen. Owns the loaded [detail] and
/// runs every mutation (remark, complete, supply items, notes, attachments).
/// Each write returns the server's re-fetched detail (the web loadData()
/// reconciliation), which replaces [detail] so the UI stays in lock-step.
class ComplaintDetailCubit extends HolderCubit {
  ComplaintDetailCubit(this.complaintId);

  final int complaintId;

  TechnicianRepository get _repo => serviceLocator<TechnicianRepository>();

  bool loading = false;
  String? error; // fatal load error (blocks the screen)
  ComplaintDetail? detail;

  bool busy = false; // a write is in flight
  String? actionError; // last write error (surfaced as a toast)

  // Lookups for the supply-item sheet.
  List<BranchOption> branches = [];
  List<ProductOption> products = [];
  bool productsLoading = false;

  /// Bumped per load — a pull-to-refresh racing an earlier load must not let
  /// the older response overwrite the newer one.
  int _loadId = 0;

  Future<void> load() async {
    final req = ++_loadId;
    loading = true;
    error = null;
    refresh();
    try {
      final result = await _repo.detail(complaintId);
      if (req != _loadId) return;
      detail = result;
    } on ApiException catch (e) {
      if (req != _loadId) return;
      error = e.message;
    } catch (_) {
      if (req != _loadId) return;
      error = 'Could not load this complaint.';
    }
    loading = false;
    refresh();
  }

  /// Runs a write, replacing [detail] with the re-fetched payload. Returns true
  /// on success; on failure sets [actionError] and returns false.
  Future<bool> _mutate(Future<ComplaintDetail> Function() op) async {
    busy = true;
    actionError = null;
    refresh();
    try {
      detail = await op();
      busy = false;
      refresh();
      return true;
    } on ApiException catch (e) {
      actionError = e.message;
    } catch (_) {
      actionError = 'Something went wrong. Please try again.';
    }
    busy = false;
    refresh();
    return false;
  }

  Future<bool> saveRemark(String remark) =>
      _mutate(() => _repo.saveRemark(complaintId, remark));

  Future<bool> complete(String remark) =>
      _mutate(() => _repo.complete(complaintId, remark));

  Future<bool> addSupplyItem({
    required int branchId,
    int? productId,
    String? barcode,
    String mode = 'New',
    double quantity = 1,
    double? unitPrice,
    String remarks = '',
  }) =>
      _mutate(() => _repo.addSupplyItem(
            complaintId,
            branchId: branchId,
            productId: productId,
            barcode: barcode,
            mode: mode,
            quantity: quantity,
            unitPrice: unitPrice,
            remarks: remarks,
          ));

  Future<bool> updateSupplyItem(
    int itemId, {
    int? branchId,
    String? mode,
    double? quantity,
    double? unitPrice,
    String? remarks,
  }) =>
      _mutate(() => _repo.updateSupplyItem(
            itemId,
            branchId: branchId,
            mode: mode,
            quantity: quantity,
            unitPrice: unitPrice,
            remarks: remarks,
          ));

  Future<bool> deleteSupplyItem(int itemId) =>
      _mutate(() => _repo.deleteSupplyItem(itemId));

  Future<bool> addNote(String note) =>
      _mutate(() => _repo.addNote(complaintId, note));

  Future<bool> deleteNote(int noteId) =>
      _mutate(() => _repo.deleteNote(noteId));

  Future<bool> addAttachments(List<String> paths) =>
      _mutate(() => _repo.addAttachments(complaintId, paths));

  Future<bool> deleteAttachment(int imageId) =>
      _mutate(() => _repo.deleteAttachment(imageId));

  // ---- Supply-item sheet lookups ----

  /// One in-flight fetch shared by every caller (the sheet's opener and the
  /// sheet itself), so awaiting it always means "branches are in".
  Future<void>? _branchesFetch;

  Future<void> ensureBranches() {
    if (branches.isNotEmpty) return Future.value();
    return _branchesFetch ??= _fetchBranches();
  }

  Future<void> _fetchBranches() async {
    try {
      branches = await _repo.branches();
      refresh();
    } on Exception catch (_) {
      // API or network failure — leave empty; the sheet shows a "select a store" hint and a retry
      // happens on the next open.
    } finally {
      _branchesFetch = null;
    }
  }

  Timer? _productDebounce;
  int _productReq = 0;

  /// Product search as the user types: debounced, and a slower response for
  /// an older query is dropped (including its effect on [productsLoading]).
  void searchProducts(String search) {
    _productDebounce?.cancel();
    _productDebounce = Timer(const Duration(milliseconds: 300), () => loadProducts(search));
  }

  Future<void> loadProducts(String search) async {
    final req = ++_productReq;
    productsLoading = true;
    refresh();
    List<ProductOption> result;
    try {
      result = await _repo.products(search: search);
    } on Exception catch (_) {
      // API or network failure — show "No products" rather than stale rows.
      result = [];
    }
    if (req != _productReq) return;
    products = result;
    productsLoading = false;
    refresh();
  }

  @override
  Future<void> close() {
    _productDebounce?.cancel();
    return super.close();
  }
}
