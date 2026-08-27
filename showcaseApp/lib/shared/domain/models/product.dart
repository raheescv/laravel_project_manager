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
    required this.branchCode,
    required this.quantity,
  });

  factory InventoryLine.fromJson(Map<String, dynamic> json) {
    final branch = json['branch'];
    final map = branch is Map ? Map<String, dynamic>.from(branch) : const <String, dynamic>{};
    final name = asStr(map['name']).trim();
    final code = asStr(map['code']).trim();
    return InventoryLine(
      branchId: asInt(map['id']),
      branchName: name,
      // Older servers do not send the code; initials of the name beat showing
      // nothing, and beat showing "Sizerun Mall of Qatar" on a phone.
      branchCode: code.isNotEmpty ? code : _initials(name),
      quantity: asInt(json['quantity']),
    );
  }

  static String _initials(String name) {
    final words = name
        .split(RegExp(r'\s+'))
        .where((w) => w.isNotEmpty)
        .toList(growable: false);
    if (words.isEmpty) return '';
    return words.take(3).map((w) => w[0].toUpperCase()).join();
  }

  final int branchId;
  final String branchName;

  /// The short code the shops go by — "MOQ", "DM".
  final String branchCode;

  final int quantity;

  /// Stock can go negative when the shop has sold past its recorded count.
  /// Nothing customer-facing should ever show "-1 available".
  int get available => quantity < 0 ? 0 : quantity;
  bool get hasStock => quantity > 0;

  @override
  List<Object?> get props => [branchId, branchName, branchCode, quantity];
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
            .map((b) => InventoryLine.fromJson({'branch': b, 'quantity': b['quantity']}))
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
    required this.has360,
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
      // Absent, not false, when the server did not speak to it — an older
      // server says nothing here, and reading that as "no spin" would strip
      // every badge and empty the 360° filter.
      has360: json.containsKey('has_360') ? asBool(json['has_360']) : null,
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

  /// The server's own "there is a spin behind this", carried by every payload
  /// including the list — where the frames themselves are deliberately absent.
  /// Null means the server never said, which is not the same as no.
  final bool? has360;

  String get brandName => brand?.name ?? '';

  /// True only when the tenant has actually uploaded a spin sequence. Every
  /// 360° affordance in the UI is gated on this — an empty spin stage is worse
  /// than no button.
  ///
  /// The frames are a detail-view payload: a list row never carries them, so
  /// counting them there answered "no spin" for the whole catalogue — no badge
  /// on any card, and the "has a 360° view" filter emptying the grid. [has360]
  /// is the server's own answer and is the only thing a list row can go on.
  /// Frames still win where there are any, so the product page never offers a
  /// viewer it has nothing to put in.
  bool get hasSpin => images360.isNotEmpty ? images360.length > 1 : (has360 ?? false);

  /// The gallery, falling back to the card thumbnail when the product has no
  /// uploaded image rows at all.
  List<String> get galleryUrls {
    final urls = images.map((i) => i.url).where((u) => u.isNotEmpty).toList();
    if (urls.isEmpty && thumbnail.isNotEmpty) return [thumbnail];
    return urls;
  }

  bool get isOutOfStock => availabilityStatus == 'out_of_stock';
  bool get isElsewhere => availabilityStatus == 'available_in_other_branches';

  /// What is on the shelf at [branchId], from this row's per-branch rows.
  ///
  /// Not `totalStock`, which sums every shop, and not `availabilityStatus`,
  /// which the detail endpoint derives from a session the public API does not
  /// have — so it never says "in stock" no matter what is on the shelf. The
  /// inventory rows are the thing that is actually true, and they are what the
  /// availability strip is drawn from, so this keeps the two agreeing.
  int stockAt(int? branchId) => _stockIn(inventories, branchId);

  /// Branches with something on the shelf first, then the rest — a customer
  /// asking "where can I get it" should not have to read past the empty ones.
  List<InventoryLine> branchesByStock(int? activeBranchId) =>
      _byStock(inventories, activeBranchId);

  /// The shelves behind one size — or behind the whole style.
  ///
  /// A null [size] means the customer has chosen no size, and the honest answer
  /// to "where can I get this" is then every shop that has it in any size, its
  /// rows summed. A size narrows that to the shops holding that size, which is
  /// what the size run is for: tapping 42.5 should not leave the availability
  /// strip listing the shop that only has 38s.
  ///
  /// [relatedSizes] is the only source with stock per size. When the catalogue
  /// sent none — the list endpoint omits it, and `available_sizes` carries
  /// labels alone — there is nothing to filter on, so this row's own shelves
  /// are returned whatever was asked for.
  List<InventoryLine> inventoryForSize(String? size) {
    if (relatedSizes.isEmpty) return inventories;
    if (size != null) {
      for (final row in relatedSizes) {
        if (row.size == size) return row.branches;
      }
      // A size the breakdown has never heard of. If it is this product's own
      // size its inventory rows are still true; anything else has no shelves.
      return size == this.size ? inventories : const [];
    }
    // One line per shop, quantities summed across every size it carries.
    final merged = <int, InventoryLine>{};
    for (final row in relatedSizes) {
      for (final line in row.branches) {
        final seen = merged[line.branchId];
        merged[line.branchId] = seen == null
            ? line
            : InventoryLine(
                branchId: seen.branchId,
                branchName: seen.branchName,
                branchCode: seen.branchCode,
                quantity: seen.quantity + line.quantity,
              );
      }
    }
    return merged.values.toList(growable: false);
  }

  /// [stockAt], narrowed to one size. Same null-size rule as [inventoryForSize].
  int stockAtForSize(int? branchId, String? size) =>
      _stockIn(inventoryForSize(size), branchId);

  /// [branchesByStock], narrowed to one size.
  List<InventoryLine> branchesByStockForSize(int? activeBranchId, String? size) =>
      _byStock(inventoryForSize(size), activeBranchId);

  static int _stockIn(List<InventoryLine> lines, int? branchId) {
    if (branchId == null) return lines.fold(0, (sum, i) => sum + i.available);
    for (final line in lines) {
      if (line.branchId == branchId) return line.available;
    }
    return 0;
  }

  static List<InventoryLine> _byStock(List<InventoryLine> lines, int? activeBranchId) {
    final rows = [...lines];
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
