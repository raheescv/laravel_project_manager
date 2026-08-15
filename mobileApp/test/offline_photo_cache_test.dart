import 'dart:io';
import 'dart:typed_data';

import 'package:flutter_test/flutter_test.dart';
import 'package:invo/shared/domain/constants/app_config.dart';
import 'package:invo/shared/domain/constants/global_variables.dart';
import 'package:invo/shared/domain/models/index.dart';
import 'package:invo/shared/domain/services/catalog_snapshot_service.dart';
import 'package:invo/shared/utils/local_storage/image_store.dart';
import 'package:invo/shared/utils/local_storage/local_storage_service.dart';
import 'package:invo/shared/utils/router/http_utils/http_service.dart';

import 'support/offline_harness.dart';

/// Rows alone do not make a catalog usable offline.
///
/// The snapshot has always stored what a product *is* — name, price, stock — but
/// its photograph lived only in Flutter's in-memory image cache, which is empty
/// on every cold start. An offline grid was therefore a wall of blank tiles: all
/// the data present, none of it recognisable at a glance.
void main() {
  late Directory dir;
  late _FakeAssetHttp http;

  setUp(() async {
    await setUpOfflineHarness();
    dir = await Directory.systemTemp.createTemp('astra_photo_cache_test');
    await ImageStore.resetForTests();
    ImageStore.debugDirOverride = dir;
    ImageStore.debugMaxBytes = null;

    http = _FakeAssetHttp(
      storage: serviceLocator<LocalStorageService>(),
      config: AppConfig(baseUrl: 'https://shop.test', tenant: 'shop'),
    );
    serviceLocator.registerSingleton<HttpService>(http);
  });

  tearDown(() async {
    ImageStore.debugDirOverride = null;
    ImageStore.debugMaxBytes = null;
    await ImageStore.resetForTests();
    if (dir.existsSync()) await dir.delete(recursive: true);
    await tearDownOfflineHarness();
  });

  group('ImageStore', () {
    test('serves a photo it has already seen with the server unreachable', () async {
      const url = 'https://shop.test/storage/products/1.jpg';
      http.bytes['/storage/products/1.jpg'] = Uint8List.fromList(List.filled(64, 7));

      final live = await ImageStore.instance.load(url);
      expect(live, hasLength(64));
      // The interactive path returns the bytes without waiting on the write —
      // the frame should paint now, not a disk round-trip from now — so settle
      // before pulling the network away.
      await _settleUntil(() => ImageStore.instance.has(url));

      http.offline = true;
      final offline = await ImageStore.instance.load(url);

      expect(offline, hasLength(64), reason: 'the tile must still paint with no network');
      expect(http.requests, 1, reason: 'the second load came off disk, not the wire');
    });

    test('a photo never fetched still fails offline, so the tile can fall back', () async {
      http.offline = true;
      await expectLater(
        ImageStore.instance.load('https://shop.test/storage/products/9.jpg'),
        throwsA(anything),
      );
    });

    test('two tiles asking at once share one download', () async {
      const url = 'https://shop.test/storage/products/2.jpg';
      http.bytes['/storage/products/2.jpg'] = Uint8List.fromList(List.filled(32, 1));

      final results = await Future.wait([
        ImageStore.instance.load(url),
        ImageStore.instance.load(url),
        ImageStore.instance.load(url),
      ]);

      expect(results.every((r) => r.length == 32), isTrue);
      expect(http.requests, 1);
    });

    test('warm pre-downloads so the photos are on disk before the network goes', () async {
      final urls = [
        for (var i = 1; i <= 4; i++) 'https://shop.test/storage/products/w$i.jpg',
      ];
      for (var i = 1; i <= 4; i++) {
        http.bytes['/storage/products/w$i.jpg'] = Uint8List.fromList(List.filled(100, i));
      }

      final result = await ImageStore.instance.warm(urls);

      expect(result.cached, 4);
      expect(result.failed, 0);
      expect(result.stoppedOnBudget, isFalse);
      for (final url in urls) {
        expect(await ImageStore.instance.has(url), isTrue);
      }
    });

    test('warm counts a failed photo without losing the rest', () async {
      http.bytes['/storage/products/ok.jpg'] = Uint8List.fromList(List.filled(10, 3));
      final result = await ImageStore.instance.warm([
        'https://shop.test/storage/products/ok.jpg',
        'https://shop.test/storage/products/missing.jpg',
      ]);

      expect(result.cached, 1);
      expect(result.failed, 1);
    });

    test('warm stops at the storage ceiling and says so rather than filling the device', () async {
      // The budget is checked per item, so the workers already in flight when it
      // is crossed still finish — the ceiling has to be small against the queue
      // for the stop to be the reason the queue ends, rather than the queue
      // simply running out.
      ImageStore.debugMaxBytes = 150;
      for (var i = 1; i <= 20; i++) {
        http.bytes['/storage/products/b$i.jpg'] = Uint8List.fromList(List.filled(100, i % 250));
      }

      final result = await ImageStore.instance.warm([
        for (var i = 1; i <= 20; i++) 'https://shop.test/storage/products/b$i.jpg',
      ]);

      expect(result.stoppedOnBudget, isTrue);
      expect(result.cached, lessThan(20));
      expect(result.skipped, greaterThan(0));
      // The per-item check bounds the download; the eviction that follows is
      // what actually bounds the disk, and that one is exact.
      expect((await ImageStore.instance.stats()).bytes, lessThanOrEqualTo(150));
    });

    test('similar URLs do not collide onto one file', () async {
      // A collision here would not fail — it would paint one product's photo on
      // another product's tile, which a cashier would act on.
      const a = 'https://shop.test/storage/products/1001.jpg';
      const b = 'https://shop.test/storage/products/1010.jpg';
      http.bytes['/storage/products/1001.jpg'] = Uint8List.fromList(List.filled(16, 1));
      http.bytes['/storage/products/1010.jpg'] = Uint8List.fromList(List.filled(16, 2));

      await ImageStore.instance.warm([a, b]);

      expect((await ImageStore.instance.cached(a))!.first, 1);
      expect((await ImageStore.instance.cached(b))!.first, 2);
      expect((await ImageStore.instance.stats()).files, 2);
    });

    test('clear leaves nothing of the previous tenant behind', () async {
      http.bytes['/storage/products/3.jpg'] = Uint8List.fromList(List.filled(8, 5));
      await ImageStore.instance.warm(['https://shop.test/storage/products/3.jpg']);
      expect((await ImageStore.instance.stats()).files, 1);

      await ImageStore.instance.clear();

      expect((await ImageStore.instance.stats()).files, 0);
      expect(await ImageStore.instance.has('https://shop.test/storage/products/3.jpg'), isFalse);
    });
  });

  group('snapshot photo plumbing', () {
    late CatalogSnapshotService snapshot;
    const branch = 1;

    setUp(() => snapshot = CatalogSnapshotService());

    Map<String, dynamic> product({
      required int id,
      required String name,
      int priority = 0,
      String? thumbnail,
      List<Map<String, dynamic>>? images,
    }) =>
        {
          'id': id,
          'name': name,
          'code': 'C$id',
          'barcode': '',
          'type': 'product',
          'mrp': 10.0,
          'tax': 0,
          'total_stock': 1,
          'priority': priority,
          'thumbnail': thumbnail,
          if (images != null) 'images': images,
          'main_category': {'id': 5, 'name': 'Hair'},
        };

    test('thumbnails come back in grid order and skip products with no photo', () async {
      await snapshot.replace(
        branchId: branch,
        products: [
          product(id: 1, name: 'Beta', priority: 1, thumbnail: 'https://s/b.jpg'),
          product(id: 2, name: 'Alpha', priority: 9, thumbnail: 'https://s/a.jpg'),
          product(id: 3, name: 'NoPhoto'),
        ],
        categoriesByType: const {'': [], 'product': [], 'service': []},
      );

      // priority DESC then name — the same order `products()` pages in, so a
      // budget that runs out costs the tail of the catalog, not the middle.
      expect(await snapshot.thumbnails(branch), ['https://s/a.jpg', 'https://s/b.jpg']);
    });

    test('a product with no thumbnail falls back to its first image, as the tile does', () async {
      await snapshot.replace(
        branchId: branch,
        products: [
          product(id: 1, name: 'A', images: [
            {'url': 'https://s/first.jpg'},
            {'url': 'https://s/second.jpg'},
          ]),
        ],
        categoriesByType: const {'': [], 'product': [], 'service': []},
      );

      // Deriving this any other way would pre-download one URL while the tile
      // requests another, which looks exactly like the cache not working.
      final stored = await snapshot.thumbnails(branch);
      final painted = (await snapshot.products(branchId: branch)).items.single.thumbnail;
      expect(stored, [painted]);
    });

    test('categoryCount reports the All Types list, not every slice of it', () async {
      await snapshot.replace(
        branchId: branch,
        products: [product(id: 1, name: 'A')],
        categoriesByType: const {
          '': [Category(id: 5, name: 'Hair', productCount: 1), Category(id: 6, name: 'Nails', productCount: 0)],
          'product': [Category(id: 5, name: 'Hair', productCount: 1)],
          'service': [Category(id: 6, name: 'Nails', productCount: 0)],
        },
      );

      // The per-type lists are the same categories sliced again; summing the
      // table would report four where the shop has two.
      expect(await snapshot.categoryCount(branch), 2);
    });
  });
}

/// Wait for a background write to land, bounded so a genuine failure still ends
/// as a failed expectation rather than a timeout.
Future<void> _settleUntil(Future<bool> Function() done) async {
  for (var i = 0; i < 50; i++) {
    if (await done()) return;
    await Future<void>.delayed(const Duration(milliseconds: 10));
  }
}

/// Stands in for the real Dio-backed service. Only [getAssetBytes] is reachable
/// from [ImageStore], so nothing else needs implementing.
class _FakeAssetHttp extends HttpService {
  _FakeAssetHttp({required super.storage, required super.config});

  final Map<String, Uint8List> bytes = {};
  bool offline = false;
  int requests = 0;

  @override
  Future<Uint8List> getAssetBytes(
    String url, {
    Map<String, String>? headers,
    void Function(int received, int total)? onProgress,
  }) async {
    requests++;
    if (offline) throw const SocketException('offline');
    final data = bytes[Uri.parse(url).path];
    if (data == null) throw const HttpException('404');
    onProgress?.call(data.length, data.length);
    return data;
  }
}
