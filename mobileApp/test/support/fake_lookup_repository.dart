import 'dart:typed_data';

import 'package:invo/shared/domain/models/index.dart';
import 'package:invo/shared/domain/repository/lookup_repository.dart';

/// A configurable in-memory [LookupRepository] for tests. By default it serves
/// a small canned catalog, the demo categories/branches/payment methods, and an
/// empty currency list (so the CurrencyCubit keeps its cached/built-in list).
///
/// [total] drives pagination: [products] honours `page`/`perPage` and reports
/// the right `lastPage`, so the catalog's load → loadMore accumulation can be
/// exercised in isolation.
class FakeLookupRepository implements LookupRepository {
  FakeLookupRepository({this.total = 4});

  final int total;
  int productCalls = 0;
  final List<int> requestedPages = [];

  Product _p(int id, {String? name, String? cat, double price = 10, String type = 'service'}) => Product(
        id: id,
        code: 'P$id',
        name: name ?? 'Item $id',
        barcode: '',
        mrp: price,
        tax: 0,
        type: type,
        categoryName: cat ?? 'Hair',
        duration: '45',
        totalStock: 5,
        thumbnail: '',
      );

  @override
  Future<Paginated<Product>> products({
    String? search,
    int? mainCategoryId,
    String? type,
    int page = 1,
    int perPage = 50,
  }) async {
    productCalls++;
    requestedPages.add(page);
    final last = (total / perPage).ceil().clamp(1, 1 << 30);
    final start = (page - 1) * perPage;
    final count = (total - start).clamp(0, perPage);
    final items = List.generate(count, (i) => _p(start + i + 1));
    return Paginated(items: items, currentPage: page, lastPage: last, total: total);
  }

  @override
  Future<Product?> productByBarcode(String barcode) async => null;

  @override
  Future<List<Category>> categories({String? type}) async => [
        Category(id: 1, name: 'Hair', productCount: total),
        Category(id: 2, name: 'Color', productCount: 1),
        Category(id: 3, name: 'Spa', productCount: 1),
      ];

  @override
  Future<List<Branch>> branches() async => [
        Branch(id: 3, name: 'Downtown', location: 'Downtown', code: 'DT-03'),
        Branch(id: 4, name: 'Uptown', location: 'Uptown', code: 'UP-04'),
      ];

  @override
  Future<List<Customer>> customers({String? mobile, String? search}) async => const [];

  @override
  Future<List<Employee>> employees({String? search, int? branchId}) async => const [];

  @override
  Future<List<PaymentMethod>> paymentMethods() async => [
        PaymentMethod(id: 1, name: 'Cash'),
        PaymentMethod(id: 2, name: 'Card'),
        PaymentMethod(id: 3, name: 'Bank Transfer'),
      ];

  @override
  Future<({String? baseCode, List<Currency> currencies})> currencies() async =>
      (baseCode: null, currencies: const <Currency>[]);

  @override
  Future<({double? defaultQuantity, bool? tipEnabled, String? defaultProductType, RemotePrintConfig? print})> saleSettings() async =>
      (defaultQuantity: null, tipEnabled: null, defaultProductType: null, print: null);

  @override
  Future<Uint8List> logo() async => Uint8List(0);
}

/// A [FakeLookupRepository] preset with the four demo catalog items the widget
/// tests rely on (so e.g. "Signature Cut" is findable on screen).
class DemoLookupRepository extends FakeLookupRepository {
  @override
  Future<Paginated<Product>> products({
    String? search,
    int? mainCategoryId,
    String? type,
    int page = 1,
    int perPage = 50,
  }) async {
    productCalls++;
    requestedPages.add(page);
    return Paginated(items: [
      _demo(1, name: 'Signature Cut', cat: 'Hair', price: 45),
      _demo(2, name: 'Balayage', cat: 'Color', price: 180),
      _demo(3, name: 'Spa Ritual', cat: 'Spa', price: 90),
      _demo(4, name: 'Shampoo Bottle', cat: 'Retail', price: 22, type: 'product'),
    ], currentPage: 1, lastPage: 1, total: 4);
  }

  Product _demo(int id, {String? name, String? cat, double price = 10, String type = 'service'}) => Product(
        id: id,
        code: 'P$id',
        name: name ?? 'Item $id',
        barcode: '',
        mrp: price,
        tax: 0,
        type: type,
        categoryName: cat ?? 'Hair',
        duration: '45',
        totalStock: 5,
        thumbnail: '',
      );
}
