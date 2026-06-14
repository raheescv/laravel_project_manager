import 'package:flutter/foundation.dart';

import '../core/api_client.dart';
import '../core/api_service.dart';
import '../models/models.dart';

/// Loads the assignable stylists (active employees) for the New Sale / Edit
/// Line stylist pickers. The list is small and stable, so it is fetched once
/// and filtered client-side by the picker's search box.
class StylistController extends ChangeNotifier {
  StylistController(this.service);
  final ApiService service;

  bool loading = false;
  String? error;
  List<Employee> _all = [];
  bool _loaded = false;

  List<Employee> get all => List.unmodifiable(_all);

  Future<void> loadIfNeeded() async {
    if (_loaded) return;
    await load();
  }

  Future<void> load() async {
    loading = true;
    error = null;
    notifyListeners();
    try {
      _all = await service.employees();
      _loaded = true;
    } on ApiException catch (e) {
      error = e.message;
    } catch (e) {
      error = 'Could not load stylists.';
    }
    loading = false;
    notifyListeners();
  }

  /// Stylists whose name / code / mobile match [term] (case-insensitive).
  List<Employee> search(String term) {
    final q = term.trim().toLowerCase();
    if (q.isEmpty) return all;
    return _all
        .where((e) =>
            e.name.toLowerCase().contains(q) ||
            e.code.toLowerCase().contains(q) ||
            e.mobile.toLowerCase().contains(q))
        .toList();
  }
}
