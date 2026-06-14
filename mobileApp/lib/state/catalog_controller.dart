import 'package:flutter/foundation.dart' hide Category;

import '../core/api_client.dart';
import '../core/api_service.dart';
import '../models/models.dart';

/// Loads the sellable catalog (services + products), grouped by category, with
/// search and category-filter for the New Sale screen.
class CatalogController extends ChangeNotifier {
  CatalogController(this.service);
  final ApiService service;

  bool loading = false;
  String? error;
  List<Product> _all = [];
  List<Category> categories = [];
  int? selectedCategoryId; // null => All
  String search = '';
  bool _loaded = false;

  Future<void> loadIfNeeded() async {
    if (_loaded) return;
    await load();
  }

  Future<void> load() async {
    loading = true;
    error = null;
    notifyListeners();
    try {
      final results = await Future.wait([
        service.products(perPage: 100),
        service.categories(),
      ]);
      _all = (results[0] as Paginated<Product>).items;
      categories = results[1] as List<Category>;
      _loaded = true;
    } on ApiException catch (e) {
      error = e.message;
    } catch (e) {
      error = 'Could not load the catalog.';
    }
    loading = false;
    notifyListeners();
  }

  void setSearch(String v) {
    search = v;
    notifyListeners();
  }

  void selectCategory(int? id) {
    selectedCategoryId = id;
    notifyListeners();
  }

  List<Product> get _filtered {
    final term = search.trim().toLowerCase();
    return _all.where((p) {
      if (selectedCategoryId != null && _categoryIdFor(p) != selectedCategoryId) {
        return false;
      }
      if (term.isEmpty) return true;
      return p.name.toLowerCase().contains(term) ||
          p.code.toLowerCase().contains(term) ||
          p.barcode.toLowerCase().contains(term);
    }).toList();
  }

  int? _categoryIdFor(Product p) =>
      categories.where((c) => c.name == p.categoryName).firstOrNull?.id;

  /// Products grouped by category name, in category order.
  Map<String, List<Product>> get grouped {
    final map = <String, List<Product>>{};
    for (final p in _filtered) {
      map.putIfAbsent(p.categoryName.isEmpty ? 'Other' : p.categoryName, () => []).add(p);
    }
    return map;
  }

  Future<Product?> findByBarcode(String code) => service.productByBarcode(code);
}

extension _FirstOrNull<E> on Iterable<E> {
  E? get firstOrNull {
    final it = iterator;
    return it.moveNext() ? it.current : null;
  }
}
