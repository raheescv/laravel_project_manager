import 'dart:async';

import 'package:equatable/equatable.dart';
import 'package:flutter_bloc/flutter_bloc.dart';

import 'package:invo/shared/utils/local_storage/local_storage_service.dart';

import '../../domain/models/catalog_models.dart';

part 'catalog_view_state.dart';

/// How one catalog page is looked at: table or gallery, which columns, the
/// sort, the search and the filters. The list reloads whenever [CatalogViewState.query]
/// changes. View, columns and sort are remembered per user on this device;
/// search and filters start clean every visit.
class CatalogViewCubit extends Cubit<CatalogViewState> {
  CatalogViewCubit({required this.type, required LocalStorageService storage, required String? userId})
      : _storage = storage,
        _userId = userId,
        super(CatalogViewState.restore(type, storage.catalogListFor(userId, type.key)));

  final CatalogType type;
  final LocalStorageService _storage;
  final String? _userId;
  Timer? _searchDebounce;

  void setGallery(bool gallery) => _emitSaved(state.copyWith(gallery: gallery));

  void toggleColumn(String key) {
    final visible = {...state.visible};
    visible.contains(key) ? visible.remove(key) : visible.add(key);
    _emitSaved(state.copyWith(visible: visible));
  }

  /// Back to the web defaults: default columns, newest first.
  void resetColumns() => _emitSaved(CatalogViewState.restore(type, const {}).copyWith(
        gallery: state.gallery,
        query: state.query.copyWith(sortKey: 'id', ascending: false),
      ));

  /// A header tap: the same column flips direction, a new one starts
  /// descending — exactly as the web table's `sortBy`.
  void sortBy(String key) {
    final q = state.query;
    _emitSaved(state.copyWith(
        query: q.copyWith(sortKey: key, ascending: q.sortKey == key ? !q.ascending : false)));
  }

  /// Debounced, so typing doesn't fire a request per keystroke.
  void search(String text) {
    _searchDebounce?.cancel();
    _searchDebounce = Timer(const Duration(milliseconds: 350), () {
      if (!isClosed) emit(state.copyWith(query: state.query.copyWith(search: text.trim())));
    });
  }

  void setFilters(CatalogQuery Function(CatalogQuery q) change) => emit(state.copyWith(query: change(state.query)));

  void clearFilters() => emit(state.copyWith(
        query: state.query.copyWith(departmentId: () => null, mainCategoryId: () => null, brandId: () => null, status: () => null),
      ));

  void _emitSaved(CatalogViewState next) {
    emit(next);
    _storage.setCatalogListFor(_userId, type.key, next.toPrefs());
  }

  @override
  Future<void> close() {
    _searchDebounce?.cancel();
    return super.close();
  }
}
