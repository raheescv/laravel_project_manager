import 'package:flutter/foundation.dart';

import '../core/api_client.dart';
import '../core/api_service.dart';
import '../core/storage.dart';
import '../models/models.dart';

/// Holds the [Branch] the user is operating as and persists the choice. The
/// selected branch id is pushed onto [ApiClient.activeBranchId] so every request
/// carries `branch_id` app-wide (mirrors how the tenant is injected). Changing it
/// notifies, so any screen watching the active branch reloads.
class BranchController extends ChangeNotifier {
  BranchController({
    required this.service,
    required this.client,
    required this.storage,
    this.userBranchId,
  }) {
    // Apply the persisted (or the user's home) branch immediately so requests
    // made before the branch list returns already carry branch_id.
    client.activeBranchId = storage.branchId ?? userBranchId;
    _load();
  }

  final ApiService service;
  final ApiClient client;
  final Storage storage;
  final int? userBranchId;

  List<Branch> branches = const [];
  Branch? _selected;
  bool loading = false;
  String? error;

  Branch? get selected => _selected;
  int? get selectedId => _selected?.id ?? client.activeBranchId;

  Future<void> _load() async {
    loading = true;
    error = null;
    notifyListeners();
    try {
      branches = await service.branches();
      if (branches.isNotEmpty) {
        // Prefer the stored choice, then the user's home branch, else the first.
        final targetId = storage.branchId ?? userBranchId;
        _selected = branches.firstWhere(
          (b) => b.id == targetId,
          orElse: () => branches.first,
        );
        client.activeBranchId = _selected!.id;
      }
    } on ApiException catch (e) {
      error = e.message;
    } catch (_) {
      error = 'Could not load branches.';
    }
    loading = false;
    notifyListeners();
  }

  /// Re-fetch the branch list (e.g. after a fresh sign-in).
  Future<void> refresh() => _load();

  /// After a fresh sign-in, default the active branch to the user's home branch
  /// — but only when they haven't already made an explicit choice on this device.
  Future<void> applyUserDefault(int? homeBranchId) async {
    if (storage.branchId != null) return; // respect an explicit pick
    if (branches.isEmpty) await _load();
    if (homeBranchId == null) return;
    final match = branches.where((b) => b.id == homeBranchId);
    if (match.isEmpty) return;
    _selected = match.first;
    client.activeBranchId = _selected!.id;
    notifyListeners();
  }

  Future<void> setBranch(Branch b) async {
    if (_selected?.id == b.id) return;
    _selected = b;
    client.activeBranchId = b.id;
    notifyListeners();
    await storage.setBranchId(b.id);
  }
}
