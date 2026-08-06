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


  @override
  List<Object?> get props => [
        id,
        name,
        location,
        code,
      ];
}

class Category extends Equatable {
  const Category({required this.id, required this.name, required this.productCount});
  final int id;
  final String name;
  final int productCount;

  factory Category.fromJson(Map<String, dynamic> j) => Category(
        id: asNum(j['id']).toInt(),
        name: asStr(j['name']),
        productCount: asNum(j['product_count']).toInt(),
      );


  @override
  List<Object?> get props => [
        id,
        name,
        productCount,
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

  bool get isService => type == 'service';
  bool get hasImage => thumbnail.startsWith('http');

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
      ];
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
  const PaymentMethod({required this.id, required this.name});
  final int id;
  final String name;

  factory PaymentMethod.fromJson(Map<String, dynamic> j) => PaymentMethod(
        id: asNum(j['id']).toInt(),
        name: asStr(j['name']),
      );


  @override
  List<Object?> get props => [
        id,
        name,
      ];
}
