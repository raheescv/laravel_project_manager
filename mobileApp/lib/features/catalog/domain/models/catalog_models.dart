import 'package:equatable/equatable.dart';
import 'package:flutter/material.dart';

import 'package:invo/shared/domain/constants/mobile_permissions.dart';
import 'package:invo/shared/domain/helpers/formatters.dart';

/// Products and services are separate pages over the same `products` table —
/// the type picks the API route group, the permissions, the columns and the
/// form sections. Never a switch inside one form.
enum CatalogType {
  product('product', 'Product', Icons.inventory_2_outlined),
  service('service', 'Service', Icons.content_cut);

  const CatalogType(this.key, this.label, this.icon);

  /// The API route segment (`/catalog/<key>`) and the route parameter.
  final String key;
  final String label;
  final IconData icon;

  String get plural => '${label}s';
  bool get isProduct => this == product;

  String get viewPermission => isProduct ? PermissionSlug.productView : PermissionSlug.serviceView;
  String get createPermission => isProduct ? PermissionSlug.productCreate : PermissionSlug.serviceCreate;
  String get editPermission => isProduct ? PermissionSlug.productEdit : PermissionSlug.serviceEdit;
  String get deletePermission => isProduct ? PermissionSlug.productDelete : PermissionSlug.serviceDelete;

  /// The table's columns, in the web table's order with the name pinned first.
  List<CatalogColumn> get columns => isProduct ? _productColumns : _serviceColumns;

  static CatalogType? fromKey(String? key) {
    for (final t in values) {
      if (t.key == key) return t;
    }
    return null;
  }
}

/// How a table cell renders its value.
enum CellKind { text, mono, rtl, count, money, price, minutes }

/// One column of the catalog table. [key] is both the API row field and the
/// `sort` value the server accepts. Defaults mirror the web
/// `product_visible_column` defaults (everything on except Image).
class CatalogColumn {
  const CatalogColumn(this.key, this.label,
      {this.kind = CellKind.text, this.width = 132, this.pinned = false, this.defaultOn = true});

  final String key;
  final String label;
  final CellKind kind;
  final double width;

  /// Always shown, pinned to the left edge — the item's name.
  final bool pinned;
  final bool defaultOn;

  /// The thumbnail beside the name: a toggle, not a column of its own.
  bool get isImage => key == imageKey;
  bool get sortable => !isImage;
  bool get alignEnd => kind == CellKind.count || kind == CellKind.money || kind == CellKind.price || kind == CellKind.minutes;

  String display(Map<String, dynamic> row) {
    final v = row[key];
    return switch (kind) {
      CellKind.money || CellKind.price => Money.plain(asNum(v)),
      CellKind.minutes => '${_trim(asNum(v))} min',
      CellKind.count => _trim(asNum(v)),
      _ => asStr(v),
    };
  }

  static const imageKey = 'image';
}

String _trim(num v) => v == v.roundToDouble() ? v.toInt().toString() : v.toString();

const _productColumns = [
  CatalogColumn('name', 'Product Name', pinned: true, width: 190),
  CatalogColumn('id', 'ID', kind: CellKind.count, width: 72),
  CatalogColumn(CatalogColumn.imageKey, 'Image', defaultOn: false),
  CatalogColumn('department', 'Department'),
  CatalogColumn('main_category', 'Main Category', width: 146),
  CatalogColumn('sub_category', 'Sub Category', width: 140),
  CatalogColumn('unit', 'Unit', width: 88),
  CatalogColumn('brand', 'Brand'),
  CatalogColumn('size', 'Size', width: 96),
  CatalogColumn('code', 'Code', kind: CellKind.mono),
  CatalogColumn('name_arabic', 'Arabic Name', kind: CellKind.rtl, width: 160),
  CatalogColumn('barcode', 'Barcode', kind: CellKind.mono),
  CatalogColumn('cost', 'Cost', kind: CellKind.money, width: 104),
  CatalogColumn('mrp', 'MRP', kind: CellKind.price, width: 108),
];

const _serviceColumns = [
  CatalogColumn('name', 'Service Name', pinned: true, width: 190),
  CatalogColumn('id', '#', kind: CellKind.count, width: 64),
  CatalogColumn(CatalogColumn.imageKey, 'Image', defaultOn: false),
  CatalogColumn('department', 'Department'),
  CatalogColumn('main_category', 'Main Category', width: 146),
  CatalogColumn('sub_category', 'Sub Category', width: 140),
  CatalogColumn('code', 'Code', kind: CellKind.mono),
  CatalogColumn('name_arabic', 'Arabic Name', kind: CellKind.rtl, width: 160),
  CatalogColumn('priority', 'Priority', kind: CellKind.count, width: 92),
  CatalogColumn('mrp', 'Price', kind: CellKind.price, width: 108),
  CatalogColumn('time', 'Duration', kind: CellKind.minutes, width: 104),
];

/// Server-side search, filters and sort for one list request.
class CatalogQuery extends Equatable {
  const CatalogQuery({
    this.search = '',
    this.departmentId,
    this.mainCategoryId,
    this.brandId,
    this.status,
    this.sortKey = 'id',
    this.ascending = false,
  });

  final String search;
  final int? departmentId;
  final int? mainCategoryId;
  final int? brandId;

  /// `active` | `disabled`, or null for both.
  final String? status;
  final String sortKey;
  final bool ascending;

  int get activeFilters => [departmentId, mainCategoryId, brandId, status].where((v) => v != null).length;

  Map<String, dynamic> toQuery(int page) => {
        'page': page,
        'sort': sortKey,
        'direction': ascending ? 'asc' : 'desc',
        if (search.isNotEmpty) 'search': search,
        if (departmentId != null) 'department_id': departmentId,
        if (mainCategoryId != null) 'main_category_id': mainCategoryId,
        if (brandId != null) 'brand_id': brandId,
        if (status != null) 'status': status,
      };

  /// Nullable fields are replaced wholesale — pass the current value to keep it.
  CatalogQuery copyWith({
    String? search,
    int? Function()? departmentId,
    int? Function()? mainCategoryId,
    int? Function()? brandId,
    String? Function()? status,
    String? sortKey,
    bool? ascending,
  }) =>
      CatalogQuery(
        search: search ?? this.search,
        departmentId: departmentId != null ? departmentId() : this.departmentId,
        mainCategoryId: mainCategoryId != null ? mainCategoryId() : this.mainCategoryId,
        brandId: brandId != null ? brandId() : this.brandId,
        status: status != null ? status() : this.status,
        sortKey: sortKey ?? this.sortKey,
        ascending: ascending ?? this.ascending,
      );

  @override
  List<Object?> get props => [search, departmentId, mainCategoryId, brandId, status, sortKey, ascending];
}

/// A department, category, unit or brand the pickers offer.
class CatalogOption extends Equatable {
  const CatalogOption({required this.id, required this.name, this.parentId});

  factory CatalogOption.fromJson(Map<String, dynamic> j) => CatalogOption(
        id: asNum(j['id']).toInt(),
        name: asStr(j['name']),
        parentId: j['parent_id'] == null ? null : asNum(j['parent_id']).toInt(),
      );

  final int id;
  final String name;
  final int? parentId;

  @override
  List<Object?> get props => [id, name, parentId];
}

/// Everything the filters and the form choose from, plus the barcode settings
/// the form adapts to.
class CatalogOptions extends Equatable {
  const CatalogOptions({
    this.departments = const [],
    this.mainCategories = const [],
    this.subCategories = const [],
    this.units = const [],
    this.brands = const [],
    this.defaultUnitId,
    this.barcodeTypedPerProduct = true,
  });

  factory CatalogOptions.fromJson(Map<String, dynamic> j) {
    List<CatalogOption> list(String key) => [
          for (final e in (j[key] as List<dynamic>? ?? const []))
            if (e is Map) CatalogOption.fromJson(Map<String, dynamic>.from(e)),
        ];
    return CatalogOptions(
      departments: list('departments'),
      mainCategories: list('main_categories'),
      subCategories: list('sub_categories'),
      units: list('units'),
      brands: list('brands'),
      defaultUnitId: j['default_unit_id'] == null ? null : asNum(j['default_unit_id']).toInt(),
      // The web form only shows the barcode field for "Product Wise".
      barcodeTypedPerProduct: asStr(j['barcode_type']) == 'product_wise',
    );
  }

  final List<CatalogOption> departments;
  final List<CatalogOption> mainCategories;
  final List<CatalogOption> subCategories;
  final List<CatalogOption> units;
  final List<CatalogOption> brands;
  final int? defaultUnitId;
  final bool barcodeTypedPerProduct;

  List<CatalogOption> subCategoriesOf(int? mainCategoryId) =>
      [for (final s in subCategories) if (s.parentId == mainCategoryId) s];

  @override
  List<Object?> get props =>
      [departments, mainCategories, subCategories, units, brands, defaultUnitId, barcodeTypedPerProduct];
}

/// A picker's value: an existing option, or a name typed into the picker that
/// the server creates on save (the web form's `add <name>` convention).
class CatalogRef extends Equatable {
  const CatalogRef.existing(int this.id, this.name) : isNew = false;
  const CatalogRef.create(this.name)
      : id = null,
        isNew = true;

  static CatalogRef? of(dynamic id, dynamic name) =>
      id == null ? null : CatalogRef.existing(asNum(id).toInt(), asStr(name));

  final int? id;
  final String name;
  final bool isNew;

  Object get payload => isNew ? 'add $name' : id!;

  @override
  List<Object?> get props => [id, name, isNew];
}

class CatalogImage extends Equatable {
  const CatalogImage({required this.id, required this.path});

  final int id;
  final String path;

  @override
  List<Object?> get props => [id, path];
}

/// The Add / Edit form's item. [CatalogItem.blank] seeds a new one.
class CatalogItem extends Equatable {
  const CatalogItem({
    this.id,
    this.name = '',
    this.nameArabic = '',
    this.code = '',
    this.active = true,
    this.department,
    this.mainCategory,
    this.subCategory,
    this.unit,
    this.brand,
    this.priority = 0,
    this.size = '',
    this.sizeCategory = '',
    this.hsnCode = '',
    this.tax = 0,
    this.barcodeNumber = '',
    this.description = '',
    this.isSelling = true,
    this.isFavorite = false,
    this.cost = 0,
    this.mrp = 0,
    this.time = 0,
    this.thumbnail = '',
    this.images = const [],
  });

  factory CatalogItem.fromJson(Map<String, dynamic> j) => CatalogItem(
        id: asNum(j['id']).toInt(),
        name: asStr(j['name']),
        nameArabic: asStr(j['name_arabic']),
        code: asStr(j['code']),
        active: asStr(j['status']) != 'disabled',
        department: CatalogRef.of(j['department_id'], j['department']),
        mainCategory: CatalogRef.of(j['main_category_id'], j['main_category']),
        subCategory: CatalogRef.of(j['sub_category_id'], j['sub_category']),
        unit: CatalogRef.of(j['unit_id'], j['unit']),
        brand: CatalogRef.of(j['brand_id'], j['brand']),
        priority: asNum(j['priority']).toInt(),
        size: asStr(j['size']),
        sizeCategory: asStr(j['size_category']),
        hsnCode: asStr(j['hsn_code']),
        tax: asNum(j['tax']).toDouble(),
        barcodeNumber: asStr(j['barcode_number']),
        description: asStr(j['description']),
        isSelling: j['is_selling'] != false,
        isFavorite: j['is_favorite'] == true,
        cost: asNum(j['cost']).toDouble(),
        mrp: asNum(j['mrp']).toDouble(),
        time: asNum(j['time']).toDouble(),
        thumbnail: asStr(j['thumbnail']),
        images: [
          for (final e in (j['images'] as List<dynamic>? ?? const []))
            if (e is Map) CatalogImage(id: asNum(e['id']).toInt(), path: asStr(e['path'])),
        ],
      );

  /// A new item starts like the web form: first department, default unit.
  factory CatalogItem.blank(CatalogOptions options) => CatalogItem(
        department: options.departments.isEmpty
            ? null
            : CatalogRef.existing(options.departments.first.id, options.departments.first.name),
        unit: _unit(options),
      );

  static CatalogRef? _unit(CatalogOptions options) {
    for (final u in options.units) {
      if (u.id == options.defaultUnitId) return CatalogRef.existing(u.id, u.name);
    }
    return null;
  }

  final int? id;
  final String name;
  final String nameArabic;
  final String code;
  final bool active;
  final CatalogRef? department;
  final CatalogRef? mainCategory;
  final CatalogRef? subCategory;
  final CatalogRef? unit;
  final CatalogRef? brand;
  final int priority;
  final String size;
  final String sizeCategory;
  final String hsnCode;
  final double tax;
  final String barcodeNumber;
  final String description;
  final bool isSelling;
  final bool isFavorite;
  final double cost;
  final double mrp;

  /// Service duration, in minutes.
  final double time;
  final String thumbnail;
  final List<CatalogImage> images;

  bool get isNew => id == null;

  /// The SaveRequest body. Product-only fields are left out of a service, so
  /// the web action never sees a stray unit or barcode for one.
  Map<String, dynamic> toPayload(CatalogType type) => {
        'name': name.trim(),
        'name_arabic': nameArabic.trim(),
        'code': code.trim(),
        'status': active ? 'active' : 'disabled',
        'department_id': department?.payload,
        'main_category_id': mainCategory?.payload,
        'sub_category_id': subCategory?.payload,
        'priority': priority,
        'description': description.trim(),
        'is_favorite': isFavorite,
        'mrp': mrp,
        if (type.isProduct) ...{
          'unit_id': unit?.id,
          'brand_id': brand?.payload,
          'size': size.trim(),
          'size_category': sizeCategory.trim(),
          'hsn_code': hsnCode.trim(),
          'tax': tax,
          'barcode_number': barcodeNumber.trim(),
          'is_selling': isSelling,
          'cost': cost,
        } else
          'time': time,
      };

  @override
  List<Object?> get props => [
        id, name, nameArabic, code, active, department, mainCategory, subCategory, unit, brand, priority, size,
        sizeCategory, hsnCode, tax, barcodeNumber, description, isSelling, isFavorite, cost, mrp, time, thumbnail,
        images,
      ];
}

/// The price breakdown the form shows live. Prices are tax-inclusive, as on
/// the web form's Purchase / Sales Information cards.
class PriceBreakdown {
  PriceBreakdown({required double cost, required double mrp, required double taxPercent})
      : costWithoutTax = cost / (1 + taxPercent / 100),
        mrpWithoutTax = mrp / (1 + taxPercent / 100),
        _cost = cost,
        _mrp = mrp;

  final double _cost;
  final double _mrp;
  final double costWithoutTax;
  final double mrpWithoutTax;

  double get costTax => _cost - costWithoutTax;
  double get mrpTax => _mrp - mrpWithoutTax;
  double get profit => mrpWithoutTax - costWithoutTax;
  double get marginPercent => mrpWithoutTax == 0 ? 0 : profit / mrpWithoutTax * 100;
}

/// Settings → Product Configuration — the shared web keys.
class ProductConfig extends Equatable {
  const ProductConfig({
    this.barcodeTypedPerProduct = false,
    this.barcodePrefix = '',
    this.barcodeIsSku = false,
    this.hideOutOfStock = false,
  });

  factory ProductConfig.fromJson(Map<String, dynamic> j) => ProductConfig(
        // Unset behaves like System Generation on the web: no barcode field,
        // one generated on save.
        barcodeTypedPerProduct: asStr(j['barcode_type']) == 'product_wise',
        barcodePrefix: asStr(j['barcode_prefix']),
        barcodeIsSku: asStr(j['sync_barcode_to_code']) == 'yes',
        hideOutOfStock: asStr(j['hide_out_of_stock_sale_items']) == 'yes',
      );

  final bool barcodeTypedPerProduct;
  final String barcodePrefix;
  final bool barcodeIsSku;
  final bool hideOutOfStock;

  Map<String, dynamic> toJson() => {
        'barcode_type': barcodeTypedPerProduct ? 'product_wise' : 'system_generation',
        'barcode_prefix': barcodePrefix,
        'sync_barcode_to_code': barcodeIsSku ? 'yes' : 'no',
        'hide_out_of_stock_sale_items': hideOutOfStock ? 'yes' : 'no',
      };

  ProductConfig copyWith({bool? barcodeTypedPerProduct, String? barcodePrefix, bool? barcodeIsSku, bool? hideOutOfStock}) =>
      ProductConfig(
        barcodeTypedPerProduct: barcodeTypedPerProduct ?? this.barcodeTypedPerProduct,
        barcodePrefix: barcodePrefix ?? this.barcodePrefix,
        barcodeIsSku: barcodeIsSku ?? this.barcodeIsSku,
        hideOutOfStock: hideOutOfStock ?? this.hideOutOfStock,
      );

  @override
  List<Object?> get props => [barcodeTypedPerProduct, barcodePrefix, barcodeIsSku, hideOutOfStock];
}
