import 'package:equatable/equatable.dart';

import '../helpers/formatters.dart';

class Branch extends Equatable {
  const Branch({required this.id, required this.name, required this.location, required this.code});
  final int id;
  final String name;
  final String location;
  final String code;

  factory Branch.fromJson(Map<String, dynamic> j) => Branch(
        id: asNum(j['id']).toInt(),
        name: asStr(j['name']),
        location: asStr(j['location'].toString().isEmpty ? j['name'] : j['location']),
        code: asStr(j['code']),
      );

  /// Round-trips through [Branch.fromJson] — the signed-in user's branches are
  /// cached with the user, so the sign-in branch choice works offline too.
  Map<String, dynamic> toJson() => {'id': id, 'name': name, 'code': code, 'location': location};

  @override
  List<Object?> get props => [
        id,
        name,
        location,
        code,
      ];
}

class Category extends Equatable {
  const Category({required this.id, required this.name, required this.productCount, this.imageUrl = ''});
  final int id;
  final String name;
  final int productCount;

  /// Photo set on the category in the web Settings (`categories.image_path`,
  /// served as an absolute `image_url`). Empty for a category nobody has
  /// photographed — the rail draws a tinted initial instead, so a half-shot
  /// catalog never leaves a gap. See `CategoryDisplay`.
  final String imageUrl;

  bool get hasImage => imageUrl.startsWith('http');

  factory Category.fromJson(Map<String, dynamic> j) => Category(
        id: asNum(j['id']).toInt(),
        name: asStr(j['name']),
        productCount: asNum(j['product_count']).toInt(),
        imageUrl: asStr(j['image_url']),
      );


  @override
  List<Object?> get props => [
        id,
        name,
        productCount,
        imageUrl,
      ];
}

/// Product / service (Laravel ProductResource, trimmed to what the app uses).
class Product extends Equatable {
  const Product({
    required this.id,
    required this.code,
    required this.name,
    required this.barcode,
    required this.mrp,
    required this.tax,
    required this.type,
    required this.categoryName,
    required this.duration,
    required this.totalStock,
    required this.thumbnail,
    this.priority = 0,
    this.parentId,
    this.size = '',
    this.color = '',
    this.colorName = '',
    this.colorHex = '',
    this.variants = const [],
    this.maxMrp = 0,
  });

  final int id;
  final String code;
  final String name;
  final String barcode;
  final double mrp;
  final double tax; // tax percentage applied to the line; mirrors products.tax
  final String type; // product | service
  final String categoryName;
  final String duration;
  final num totalStock;
  final String thumbnail;

  /// POS listing priority (Settings → Sale → POS Product Order); 0 = none.
  final int priority;

  /// Size / colour variants (web Variants tab): every variant is its own
  /// product with its own stock and barcode, and the family is
  /// `parentId ?? id`. [color] is as stored (a name, or a "#ff0000" code from
  /// the product form); [colorName] / [colorHex] are what to show.
  final int? parentId;
  final String size;
  final String color;
  final String colorName;
  final String colorHex;

  /// Set only on a family card (see [groupFamilies]): the variants the till
  /// asks the cashier to choose between. [mrp] is then the lowest price and
  /// [maxMrp] the highest.
  final List<Product> variants;
  final double maxMrp;

  bool get isService => type == 'service';
  bool get hasImage => thumbnail.startsWith('http');
  int get familyId => parentId ?? id;
  bool get isFamily => variants.length > 1;
  bool get hasPriceRange => isFamily && maxMrp > mrp;

  /// "42 / Blue", "M", or '' for a product that is not a variant.
  String get variantLabel =>
      [size.trim(), color.isEmpty ? '' : colorName].where((e) => e.isNotEmpty).join(' / ');

  /// The name a ticket line carries — a variant says which one it is.
  String get displayName => variantLabel.isEmpty || isFamily ? name : '$name · $variantLabel';

  /// Every product id this card stands for (a family's variants, else itself).
  List<int> get memberIds => isFamily ? [for (final v in variants) v.id] : [id];

  /// The distinct sizes / colours across a family, in catalog order.
  List<String> get familySizes =>
      {for (final v in variants) if (v.size.trim().isNotEmpty) v.size.trim()}.toList();
  List<Product> get familyColours {
    final seen = <String>{};
    return [for (final v in variants) if (v.color.isNotEmpty && seen.add(v.color)) v];
  }

  /// One card for a whole family: the head's name and photo, the stock of every
  /// variant, and the lowest price.
  factory Product.family(List<Product> members) {
    final head = members.firstWhere((m) => m.parentId == null, orElse: () => members.first);
    final prices = [for (final m in members) m.mrp];
    final photo = head.hasImage ? head.thumbnail
        : members.firstWhere((m) => m.hasImage, orElse: () => head).thumbnail;
    return Product(
      id: head.id,
      code: head.code,
      name: head.name,
      barcode: '',
      mrp: prices.reduce((a, b) => a < b ? a : b),
      maxMrp: prices.reduce((a, b) => a > b ? a : b),
      tax: head.tax,
      type: head.type,
      categoryName: head.categoryName,
      duration: head.duration,
      totalStock: members.fold<num>(0, (sum, m) => sum + m.totalStock),
      thumbnail: photo,
      priority: head.priority,
      variants: members,
    );
  }

  factory Product.fromJson(Map<String, dynamic> j) {
    final main = j['main_category'];
    // Prefer the thumbnail; fall back to the first attached image's url.
    var thumb = asStr(j['thumbnail']);
    if (!thumb.startsWith('http')) {
      final images = j['images'];
      if (images is List && images.isNotEmpty && images.first is Map) {
        thumb = asStr((images.first as Map)['url']);
      }
    }
    return Product(
      id: asNum(j['id']).toInt(),
      code: asStr(j['code']),
      name: asStr(j['name']),
      barcode: asStr(j['barcode']),
      mrp: asNum(j['mrp']).toDouble(),
      tax: asNum(j['tax']).toDouble(),
      type: asStr(j['type']).isEmpty ? 'service' : asStr(j['type']),
      categoryName: main is Map ? asStr(main['name']) : 'Other',
      duration: asStr(j['time']),
      totalStock: asNum(j['total_stock']),
      thumbnail: thumb,
      priority: asNum(j['priority']).toInt(),
      parentId: j['parent_id'] == null ? null : asNum(j['parent_id']).toInt(),
      size: asStr(j['size']).trim(),
      color: asStr(j['color']).trim(),
      colorName: asStr(j['color_name']).isEmpty ? asStr(j['color']).trim() : asStr(j['color_name']),
      colorHex: asStr(j['color_hex']),
    );
  }


  @override
  List<Object?> get props => [
        id,
        code,
        name,
        barcode,
        mrp,
        tax,
        type,
        categoryName,
        duration,
        totalStock,
        thumbnail,
        priority,
        parentId,
        size,
        color,
        colorName,
        colorHex,
        variants,
        maxMrp,
      ];
}

/// Fold size / colour variants into one card each, keeping the catalog order
/// (a family sits where its first member came in). A family with a single
/// member in [rows] — one on the shelf, or a search that matched just one —
/// stays a plain card.
List<Product> groupFamilies(List<Product> rows) {
  final families = <int, List<Product>>{};
  for (final row in rows) {
    final members = families.putIfAbsent(row.familyId, () => []);
    if (!members.any((m) => m.id == row.id)) members.add(row);
  }
  return [for (final members in families.values) members.length == 1 ? members.first : Product.family(members)];
}

class Customer extends Equatable {
  const Customer({required this.id, required this.name, required this.mobile});
  final int id;
  final String name;
  final String mobile;

  factory Customer.fromJson(Map<String, dynamic> j) => Customer(
        id: asNum(j['id']).toInt(),
        name: asStr(j['name']),
        mobile: asStr(j['mobile']),
      );


  @override
  List<Object?> get props => [
        id,
        name,
        mobile,
      ];
}

/// A staff member who can be assigned to a sale / line as the stylist.
/// Mirrors `GET /employees` (active users with type = employee).
class Employee extends Equatable {
  const Employee({
    required this.id,
    required this.name,
    required this.code,
    required this.mobile,
    required this.designation,
    this.photoUrl = '',
  });
  final int id;
  final String name;
  final String code;
  final String mobile;
  final String designation;
  // Root-relative avatar path (e.g. /storage/users/…), '' when none. Resolve to
  // an absolute URL with AppConfig.assetUrl before display.
  final String photoUrl;

  factory Employee.fromJson(Map<String, dynamic> j) => Employee(
        id: asNum(j['id']).toInt(),
        name: asStr(j['name']),
        code: asStr(j['code']),
        mobile: asStr(j['mobile']),
        designation: asStr(j['designation']),
        photoUrl: asStr(j['photo']),
      );

  bool get hasPhoto => photoUrl.isNotEmpty;

  String get initial => name.isNotEmpty ? name[0].toUpperCase() : '?';


  @override
  List<Object?> get props => [
        id,
        name,
        code,
        mobile,
        designation,
        photoUrl,
      ];
}

/// A configured payment-method account (Cash, Card, Bank, …) used by the
/// custom-payment selector. Mirrors `GET /payment-methods`.
class PaymentMethod extends Equatable {
  const PaymentMethod({required this.id, required this.name, this.slug, this.icon});
  final int id;
  final String name;

  /// The account's system slug — 'cash' / 'card' for the locked accounts. Only
  /// the POS payment buttons carry it (`/settings/sale` → `payment_buttons`).
  final String? slug;

  /// The icon picked for its POS button — a Font Awesome key from the web
  /// (`posPaymentButtonIcons()`), or null to pick one from the name.
  final String? icon;

  factory PaymentMethod.fromJson(Map<String, dynamic> j) => PaymentMethod(
        id: asNum(j['id']).toInt(),
        name: asStr(j['name']),
        slug: j['slug'] == null ? null : asStr(j['slug']),
        icon: j['icon'] == null ? null : asStr(j['icon']),
      );

  Map<String, dynamic> toJson() => {'id': id, 'name': name, 'slug': slug, 'icon': icon};

  @override
  List<Object?> get props => [
        id,
        name,
        slug,
        icon,
      ];
}
