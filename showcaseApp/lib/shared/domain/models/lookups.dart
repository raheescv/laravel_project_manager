import 'package:equatable/equatable.dart';

import '../helpers/formatters.dart';

/// A `{id, name}` pair as the API emits for brand / category / sub-category.
class Ref extends Equatable {
  const Ref({required this.id, required this.name});

  factory Ref.fromJson(Map<String, dynamic> json) =>
      Ref(id: asInt(json['id']), name: asStr(json['name']));

  final int id;
  final String name;

  @override
  List<Object?> get props => [id, name];
}

/// `GET /categories` — a main category with how many products sit under it.
class CategoryOption extends Equatable {
  const CategoryOption({required this.id, required this.name, required this.productCount});

  factory CategoryOption.fromJson(Map<String, dynamic> json) => CategoryOption(
        id: asInt(json['id']),
        name: asStr(json['name']),
        productCount: asInt(json['product_count']),
      );

  final int id;
  final String name;
  final int productCount;

  @override
  List<Object?> get props => [id, name, productCount];
}

/// `GET /brands` — scoped to whatever category/size is already chosen, so
/// [productCount] means "in this size", not "in the whole catalogue".
class BrandOption extends Equatable {
  const BrandOption({
    required this.id,
    required this.name,
    required this.productCount,
    this.imagePath = '',
  });

  factory BrandOption.fromJson(Map<String, dynamic> json) => BrandOption(
        id: asInt(json['id']),
        name: asStr(json['name']),
        productCount: asInt(json['product_count']),
        imagePath: asStr(json['image_path']),
      );

  final int id;
  final String name;
  final int productCount;

  /// The brand logo, absolute, as `/brands` and the product payload both send
  /// it. Empty for the brands nobody has uploaded one for — the tile falls back
  /// to a monogram rather than a broken frame.
  final String imagePath;

  bool get hasLogo => imagePath.isNotEmpty;

  /// The letter shown when there is no logo.
  String get monogram {
    final trimmed = name.trim();
    return trimmed.isEmpty ? '·' : trimmed.substring(0, 1).toUpperCase();
  }

  @override
  List<Object?> get props => [id, name, productCount, imagePath];
}

/// Which run a size belongs to. `GET /sizes` returns the two groups separately.
enum SizeGroup { young, adult }

/// One size chip: the label, how many products carry it, and whether any of
/// them are actually on the shelf in the active branch.
class SizeOption extends Equatable {
  const SizeOption({
    required this.size,
    required this.group,
    required this.productCount,
    required this.inStockProductCount,
    required this.stockTotal,
    required this.inStock,
  });

  factory SizeOption.fromJson(Map<String, dynamic> json, SizeGroup group) {
    // `in_stock` / `stock_total` were added for this app. Older servers return
    // `{size}` alone — treat those as available rather than greying out the
    // entire size run.
    final hasStock = json.containsKey('in_stock');
    final productCount = asInt(json['product_count']);
    return SizeOption(
      size: asStr(json['size']),
      group: group,
      productCount: productCount,
      // Also newer than the first cut: fall back to the plain count so an older
      // server shows a number rather than a zero.
      inStockProductCount: json.containsKey('in_stock_product_count')
          ? asInt(json['in_stock_product_count'])
          : productCount,
      stockTotal: asInt(json['stock_total']),
      inStock: hasStock ? asBool(json['in_stock']) : true,
    );
  }

  final String size;
  final SizeGroup group;

  /// Every product carrying this size.
  final int productCount;

  /// How many of them are on the shelf at the active branch — the same rule the
  /// results grid filters on, so this is what the grid behind the chip holds.
  final int inStockProductCount;

  /// Units, not products: the sum of the quantities behind [productCount].
  final int stockTotal;

  final bool inStock;

  /// What the chip should say, given whether the customer asked for stock only.
  int countFor({required bool inStockOnly}) =>
      inStockOnly ? inStockProductCount : productCount;

  @override
  List<Object?> get props =>
      [size, group, productCount, inStockProductCount, stockTotal, inStock];
}

/// `GET /colors`.
class ColorOption extends Equatable {
  const ColorOption({required this.color, required this.productCount});

  factory ColorOption.fromJson(Map<String, dynamic> json) => ColorOption(
        color: asStr(json['color']),
        productCount: asInt(json['product_count']),
      );

  final String color;
  final int productCount;

  @override
  List<Object?> get props => [color, productCount];
}

/// `GET /branches` — the shops a customer can be sent to.
class Branch extends Equatable {
  const Branch({
    required this.id,
    required this.name,
    required this.code,
    required this.location,
    required this.mobile,
  });

  factory Branch.fromJson(Map<String, dynamic> json) => Branch(
        id: asInt(json['id']),
        name: asStr(json['name']).trim(),
        code: asStr(json['code']),
        location: asStr(json['location']).trim(),
        mobile: asStr(json['mobile']),
      );

  final int id;
  final String name;
  final String code;
  final String location;
  final String mobile;

  /// Branch rows carry the shop name in `location` about as often as in `name`.
  String get label => location.isNotEmpty ? location : name;

  @override
  List<Object?> get props => [id, name, code, location, mobile];
}

/// `GET /settings/branding` — the tenant's accent colour and logo.
class Branding extends Equatable {
  const Branding({required this.primaryColor, required this.logo});

  factory Branding.fromJson(Map<String, dynamic> json) => Branding(
        primaryColor: asStr(json['primary_color']),
        logo: asStr(json['logo']),
      );

  final String primaryColor;
  final String logo;

  @override
  List<Object?> get props => [primaryColor, logo];
}
