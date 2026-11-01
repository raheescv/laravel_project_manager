part of 'catalog_view_cubit.dart';

class CatalogViewState extends Equatable {
  const CatalogViewState({required this.columns, required this.visible, this.gallery = false, this.query = const CatalogQuery()});

  /// Saved preferences over the type's defaults. A column or sort the app no
  /// longer has is dropped rather than trusted.
  factory CatalogViewState.restore(CatalogType type, Map<String, dynamic> saved) {
    final columns = type.columns;
    final known = {for (final c in columns) c.key};
    final savedColumns = saved['columns'];
    final visible = savedColumns is List
        ? {for (final k in savedColumns) if (known.contains(k)) k as String}
        : {for (final c in columns) if (!c.pinned && c.defaultOn) c.key};
    final sortKey = saved['sort'] is String && known.contains(saved['sort']) && saved['sort'] != CatalogColumn.imageKey
        ? saved['sort'] as String
        : 'id';
    return CatalogViewState(
      columns: columns,
      visible: visible,
      gallery: saved['view'] == 'gallery',
      query: CatalogQuery(sortKey: sortKey, ascending: saved['asc'] == true),
    );
  }

  final List<CatalogColumn> columns;

  /// Keys of the optional columns switched on. The pinned name is always shown.
  final Set<String> visible;
  final bool gallery;
  final CatalogQuery query;

  /// The columns the table draws, pinned name first. Image is a toggle on the
  /// name cell, not a column of its own.
  List<CatalogColumn> get shownColumns =>
      [for (final c in columns) if (!c.isImage && (c.pinned || visible.contains(c.key))) c];

  bool get showImages => visible.contains(CatalogColumn.imageKey);

  /// Optional columns switched on, for the toolbar's count badge.
  int get optionalOn => columns.where((c) => !c.pinned && visible.contains(c.key)).length;

  Map<String, dynamic> toPrefs() => {
        'view': gallery ? 'gallery' : 'table',
        'columns': visible.toList(),
        'sort': query.sortKey,
        'asc': query.ascending,
      };

  CatalogViewState copyWith({Set<String>? visible, bool? gallery, CatalogQuery? query}) => CatalogViewState(
        columns: columns,
        visible: visible ?? this.visible,
        gallery: gallery ?? this.gallery,
        query: query ?? this.query,
      );

  @override
  List<Object?> get props => [columns, visible, gallery, query];
}
