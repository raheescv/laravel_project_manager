import 'package:flutter_test/flutter_test.dart';
import 'package:invo/core/api_client.dart';
import 'package:invo/core/api_service.dart';
import 'package:invo/core/config.dart';
import 'package:invo/core/storage.dart';
import 'package:invo/models/models.dart';
import 'package:invo/state/catalog_controller.dart';
import 'package:shared_preferences/shared_preferences.dart';

/// A catalog API that serves [total] products in pages, so we can drive the
/// controller's load → loadMore (infinite scroll) accumulation in isolation.
class PagingApiService extends ApiService {
  PagingApiService(super.client, {this.total = 55});
  final int total;
  int productCalls = 0;
  final List<int> requestedPages = [];

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
    final last = (total / perPage).ceil();
    final start = (page - 1) * perPage;
    final count = (total - start).clamp(0, perPage);
    final items = List.generate(
      count,
      (i) => Product(
        id: start + i + 1,
        code: 'P${start + i + 1}',
        name: 'Item ${start + i + 1}',
        barcode: '',
        mrp: 10,
        type: 'service',
        categoryName: 'Hair',
        duration: '',
        totalStock: 1,
        thumbnail: '',
      ),
    );
    return Paginated(items: items, currentPage: page, lastPage: last, total: total);
  }

  @override
  Future<List<Category>> categories() async => [Category(id: 1, name: 'Hair', productCount: total)];
}

void main() {
  TestWidgetsFlutterBinding.ensureInitialized();

  Future<CatalogController> makeController(PagingApiService Function(ApiClient) build) async {
    SharedPreferences.setMockInitialValues({});
    final storage = await Storage.create();
    final client = ApiClient(storage: storage, config: AppConfig(baseUrl: 'http://test.local', tenant: ''));
    return CatalogController(build(client));
  }

  test('load fetches page 1; loadMore appends until the last page, then stops', () async {
    late PagingApiService svc;
    final cat = await makeController((c) => svc = PagingApiService(c, total: 55)); // 55 → 3 pages of 20

    await cat.load();
    expect(cat.products.length, 20);
    expect(cat.hasMore, isTrue);

    await cat.loadMore();
    expect(cat.products.length, 40);
    expect(cat.hasMore, isTrue);

    await cat.loadMore();
    expect(cat.products.length, 55, reason: 'last page is partial (15 items)');
    expect(cat.hasMore, isFalse);

    // Once the last page is reached, further loadMore calls are no-ops.
    final calls = svc.productCalls;
    await cat.loadMore();
    expect(svc.productCalls, calls);
    expect(svc.requestedPages, [1, 2, 3]);

    // No duplicate ids leaked through the accumulation.
    expect(cat.products.map((p) => p.id).toSet().length, 55);
  });

  test('selecting a category reloads from page 1', () async {
    late PagingApiService svc;
    final cat = await makeController((c) => svc = PagingApiService(c, total: 55));

    await cat.load();
    await cat.loadMore();
    expect(cat.products.length, 40);

    cat.selectCategory(1); // triggers an in-place reload
    await Future<void>.delayed(const Duration(milliseconds: 10));
    expect(cat.products.length, 20, reason: 'reload resets to page 1');
    expect(svc.requestedPages.last, 1);
  });
}
