import 'package:equatable/equatable.dart';

/// How New Sale orders the catalog — Settings → Sale Configuration → POS
/// Product Order on the web (`product_sort` on GET /settings/sale), editable
/// from the app too. Shared by every till, cached for offline. Lowest priority
/// number first unless the business changes it.
class ProductSort extends Equatable {
  const ProductSort({this.by = byPriority, this.direction = asc});

  static const String byName = 'name';
  static const String byPriority = 'priority';
  static const String asc = 'asc';
  static const String desc = 'desc';

  /// Anything unrecognised (an older server, a hand-edited pref) falls back to
  /// the server default, so the catalog always has an order it can send.
  factory ProductSort.from(String? by, String? direction) => ProductSort(
        by: by == byName ? byName : byPriority,
        direction: direction == desc ? desc : asc,
      );

  factory ProductSort.fromJson(Map<String, dynamic> json) =>
      ProductSort.from(json['by']?.toString(), json['direction']?.toString());

  /// 'name' | 'priority'.
  final String by;

  /// 'asc' | 'desc'.
  final String direction;

  bool get isByName => by == byName;
  bool get isDescending => direction == desc;

  /// The `sort_by` / `sort_direction` pair GET /products takes.
  Map<String, String> get query => {'sort_by': by, 'sort_direction': direction};

  /// The same order for the offline snapshot. Priority ties break by name, as
  /// the server does.
  String get sqlOrderBy => isByName
      ? 'name COLLATE NOCASE ${direction.toUpperCase()}'
      : 'priority ${direction.toUpperCase()}, name COLLATE NOCASE ASC';

  Map<String, dynamic> toJson() => {'by': by, 'direction': direction};

  ProductSort copyWith({String? by, String? direction}) =>
      ProductSort.from(by ?? this.by, direction ?? this.direction);

  @override
  List<Object?> get props => [by, direction];
}
