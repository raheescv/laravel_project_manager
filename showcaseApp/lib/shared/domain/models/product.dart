import 'package:equatable/equatable.dart';

import '../helpers/formatters.dart';
import 'lookups.dart';

/// One product photo. `method` separates the gallery from the spin frames:
/// `normal` images are the gallery, `angle` images are the 360° sequence and
/// carry a [degree].
class ProductImage extends Equatable {
  const ProductImage({
    required this.id,
    required this.url,
    required this.degree,
    required this.sortOrder,
  });

  factory ProductImage.fromJson(Map<String, dynamic> json) => ProductImage(
        id: asInt(json['id']),
        // `url` is the absolute form; `path` is the fallback for older rows.
        url: asStr(json['url']).isNotEmpty ? asStr(json['url']) : asStr(json['path']),
        degree: asNum(json['degree']).toDouble(),
        sortOrder: asInt(json['sort_order']),
      );

  final int id;
  final String url;
  final double degree;
  final int sortOrder;

  @override
  List<Object?> get props => [id, url, degree, sortOrder];
}

/// Stock for this product in one branch.
class InventoryLine extends Equatable {
  const InventoryLine({
    required this.branchId,
    required this.branchName,
    required this.quantity,
  });

  factory InventoryLine.fromJson(Map<String, dynamic> json) {
    final branch = json['branch'];
    final map = branch is Map ? Map<String, dynamic>.from(branch) : const <String, dynamic>{};
    return InventoryLine(
      branchId: asInt(map['id']),
      branchName: asStr(map['name']).trim(),
      quantity: asInt(json['quantity']),
    );
  }

  final int branchId;
  final String branchName;
  final int quantity;

  /// Stock can go negative when the shop has sold past its recorded count.
  /// Nothing customer-facing should ever show "-1 available".
  int get available => quantity < 0 ? 0 : quantity;
  bool get hasStock => quantity > 0;

  @override
  List<Object?> get props => [branchId, branchName, quantity];
}

/// One entry of `related_sizes`: the same style in another size, with the stock
/// behind it. This is what the size run on the product page is built from.
class RelatedSize extends Equatable {
  const RelatedSize({
    required this.size,
    required this.totalStock,
    required this.isOutOfStock,
    required this.branches,
  });

  factory RelatedSize.fromJson(Map<String, dynamic> json) => RelatedSize(
        // The server sends this as a number for numeric sizes and a string for
        // the rest ("10C"), so it is read as text either way.
        size: asStr(json['size']),
        totalStock: asInt(json['total_stock']),
        isOutOfStock: asBool(json['is_out_of_stock']),
        branches: asMapList(json['branches'])
            .map((b) => InventoryLine(
                  branchId: asInt(b['id']),
                  branchName: asStr(b['name']).trim(),
                  quantity: asInt(b['quantity']),
                ))
            .toList(growable: false),
      );

  final String size;
  final int totalStock;
  final bool isOutOfStock;
  final List<InventoryLine> branches;

  int stockIn(int? branchId) {
    if (branchId == null) return totalStock;
    for (final b in branches) {
      if (b.branchId == branchId) return b.available;
    }
    return 0;
  }

  @override
  List<Object?> get props => [size, totalStock, isOutOfStock, branches];
}

/// A product as the catalog API returns it.
///
/// The list endpoint deliberately omits [images], [images360], [availableSizes],
/// [relatedSizes] and [inventories] — they are per-row lookups that would be an
/// N+1 storm on a page of results. Those arrive only from `/products/{id}` and
/// `/products/single`, so a card built from the list must never assume them.
class Product extends Equatable {
  const Product({
    required this.id,
    required this.code,
    required this.name,
    required this.nameArabic,
    required this.description,
    required this.thumbnail,
    required this.barcode,
    required this.color,
    required this.size,
    required this.model,
    required this.mrp,
    required this.tax,
    required this.brand,
    required this.mainCategory,
    required this.subCategory,
    required this.unitName,
    required this.images,
    required this.images360,
    required this.inventories,
    required this.availableSizes,
    required this.relatedSizes,
    required this.totalStock,
    required this.availabilityStatus,
  });

  factory Product.fromJson(Map<String, dynamic> json) {
    Ref? ref(dynamic v) =>
        v is Map ? Ref.fromJson(Map<String, dynamic>.from(v)) : null;

    final unit = json['unit'];

    return Product(
      id: asInt(json['id']),
      code: asStr(json['code']),
      name: asStr(json['name']).trim(),
      nameArabic: asStr(json['name_arabic']).trim(),
      description: asStr(json['description']),
      thumbnail: asStr(json['thumbnail']),
      barcode: asStr(json['barcode']),
      color: asStr(json['color']),
      size: asStr(json['size']),
      model: asStr(json['model']),
      mrp: asNum(json['mrp']).toDouble(),
      tax: asNum(json['tax']).toDouble(),
      brand: ref(json['brand']),
      mainCategory: ref(json['main_category']),
      subCategory: ref(json['sub_category']),
      unitName: unit is Map ? asStr(unit['name']) : '',
      images: asMapList(json['images']).map(ProductImage.fromJson).toList(growable: false),
      images360: _spinFrames(json['images360']),
      inventories:
          asMapList(json['inventories']).map(InventoryLine.fromJson).toList(growable: false),
      // The server can repeat a size here (one row per matching product), and a
      // duplicated chip in the size run reads as a bug.
      availableSizes: _uniqueSizes(json['available_sizes']),
      relatedSizes: asMapList(json['related_sizes'])
          .map(RelatedSize.fromJson)
          .toList(growable: false),
      totalStock: asInt(json['total_stock']),
      availabilityStatus: asStr(json['stock_quantity_availability_status']),
    );
  }

  /// Spin frames arrive ordered by degree, but a re-upload can leave the order
  /// to chance — sorting here means the viewer never has to.
  static List<ProductImage> _spinFrames(dynamic raw) {
    final frames = asMapList(raw).map(ProductImage.fromJson).toList();
    frames.sort((a, b) {
      final byDegree = a.degree.compareTo(b.degree);
      return byDegree != 0 ? byDegree : a.sortOrder.compareTo(b.sortOrder);
    });
    return List.unmodifiable(frames);
  }

  static List<String> _uniqueSizes(dynamic raw) {
    if (raw is! List) return const [];
    final seen = <String>{};
    for (final v in raw) {
      final s = asStr(v).trim();
      if (s.isNotEmpty) seen.add(s);
    }
    return List.unmodifiable(seen);
  }

  final int id;
  final String code;
  final String name;
  final String nameArabic;
  final String description;
  final String thumbnail;
  final String barcode;
  final String color;
  final String size;
  final String model;
  final double mrp;
  final double tax;
  final Ref? brand;
  final Ref? mainCategory;
  final Ref? subCategory;
  final String unitName;
  final List<ProductImage> images;
  final List<ProductImage> images360;
  final List<InventoryLine> inventories;
  final List<String> availableSizes;
  final List<RelatedSize> relatedSizes;
  final int totalStock;
  final String availabilityStatus;

  String get brandName => brand?.name ?? '';

  /// True only when the tenant has actually uploaded a spin sequence. Every
  /// 360° affordance in the UI is gated on this — an empty spin stage is worse
  /// than no button.
  bool get hasSpin => images360.length > 1;

  /// The gallery, falling back to the card thumbnail when the product has no
  /// uploaded image rows at all.
  List<String> get galleryUrls {
    final urls = images.map((i) => i.url).where((u) => u.isNotEmpty).toList();
    if (urls.isEmpty && thumbnail.isNotEmpty) return [thumbnail];
    return urls;
  }

  bool get isOutOfStock => availabilityStatus == 'out_of_stock';
  bool get isElsewhere => availabilityStatus == 'available_in_other_branches';

  /// Branches with something on the shelf first, then the rest — a customer
  /// asking "where can I get it" should not have to read past the empty ones.
  List<InventoryLine> branchesByStock(int? activeBranchId) {
    final rows = [...inventories];
    rows.sort((a, b) {
      if (a.branchId == activeBranchId) return -1;
      if (b.branchId == activeBranchId) return 1;
      final byStock = b.available.compareTo(a.available);
      return byStock != 0 ? byStock : a.branchName.compareTo(b.branchName);
    });
    return rows;
  }

  @override
  List<Object?> get props => [id, code, name, mrp, size, totalStock, availabilityStatus];
}
