import 'dart:async';
import 'dart:convert';
import 'dart:io';
import 'dart:typed_data';

import 'package:flutter/foundation.dart' show visibleForTesting;
import 'package:path/path.dart' as p;
import 'package:path_provider/path_provider.dart';

import '../../domain/constants/global_variables.dart';
import '../router/http_utils/http_service.dart';

/// On-disk cache for the photos the catalog paints.
///
/// The rest of the offline layer caches *rows* — the product exists, priced and
/// searchable, with no network. Its photograph does not, because Flutter's
/// `ImageCache` is memory only and empty on every cold start. The result is a
/// snapshot that reads as broken: a grid of tinted placeholders where the
/// cashier expects to recognise the item by sight, which on a busy till is the
/// difference between tapping the right product and reading every label.
///
/// So photos are stored as files rather than rows. They are large, they are
/// written once and read many times, and they must never share the database
/// that holds the outbox — a multi-gigabyte table of blobs next to unsynced
/// takings puts real money behind a file that could be corrupted or, on a full
/// device, refuse to open at all.
///
/// Files live in Application Support, not the system cache directory: iOS purges
/// the latter under storage pressure, and it would do it precisely when a till
/// has been offline for days and has no way to fetch them again.
class ImageStore {
  ImageStore._();

  /// The one instance. Deliberately not in the service locator: [ImageProvider]s
  /// are constructed deep inside `build` methods, and threading a dependency
  /// down to them would buy nothing — there is only ever one cache directory.
  static final ImageStore instance = ImageStore._();

  static const String _dirName = 'photo_cache';

  /// Ceiling for the whole cache.
  ///
  /// A ceiling and not a target: `thumbnail` falls back to the first attached
  /// image when the server sends no thumbnail (see [Product.fromJson]), and that
  /// is a full-resolution photograph. A few hundred of those would fill a phone,
  /// so warming stops at this figure and says how far it got rather than
  /// quietly consuming the device.
  static const int maxBytes = 120 * 1024 * 1024;

  /// How many warm downloads run at once. Enough to keep the link busy, low
  /// enough that provisioning never starves the outbox drain sharing it.
  static const int warmConcurrency = 4;

  Directory? _dir;
  Future<Directory?>? _opening;

  /// One download per URL, however many tiles ask for it. The grid and the list
  /// view render the same product, and a re-layout can request a photo that is
  /// already in flight; without this each request would open its own socket and
  /// they would race to write the same file.
  final Map<String, Future<Uint8List>> _inFlight = {};

  HttpService get _http => serviceLocator<HttpService>();

  /// Test seam: point the cache at a temporary directory, or at null to run with
  /// disk caching disabled (which is also what a platform with no Application
  /// Support directory gets).
  @visibleForTesting
  static Directory? debugDirOverride;

  /// Test seam: shrink the ceiling so the budget path can be exercised without
  /// writing 120MB of fixtures.
  @visibleForTesting
  static int? debugMaxBytes;

  /// Test seam: drop the memoised directory and any in-flight downloads, so the
  /// next call re-resolves against a fresh [debugDirOverride].
  @visibleForTesting
  static Future<void> resetForTests() async {
    instance._dir = null;
    instance._opening = null;
    instance._inFlight.clear();
  }

  int get _budget => debugMaxBytes ?? maxBytes;

  Future<Directory?> _directory() async {
    final open = _dir;
    if (open != null) return open;
    return _opening ??= () async {
      try {
        final override = debugDirOverride;
        final base = override ?? Directory(p.join((await getApplicationSupportDirectory()).path, _dirName));
        if (!base.existsSync()) await base.create(recursive: true);
        return _dir = base;
      } catch (_) {
        // No writable directory (an unsupported host, a full disk). Photos then
        // behave exactly as they did before this cache existed: fetched live,
        // absent offline. Nothing else in the app should fail for it.
        return null;
      } finally {
        _opening = null;
      }
    }();
  }

  Future<File?> _fileFor(String url) async {
    final dir = await _directory();
    return dir == null ? null : File(p.join(dir.path, _name(url)));
  }

  /// The cached bytes for [url], or null when it has never been downloaded.
  ///
  /// This is the whole point of the class: offline, this is the only path that
  /// can return anything at all.
  Future<Uint8List?> cached(String url) async {
    final file = await _fileFor(url);
    if (file == null) return null;
    try {
      // `existsSync` rather than the async form throughout: this runs while the
      // grid is painting, and the async variants hop to the IO thread pool,
      // which costs more than the stat they are avoiding. The byte read below
      // stays async — that one is genuinely large.
      if (!file.existsSync()) return null;
      final bytes = await file.readAsBytes();
      // A zero-length file is a download that died mid-write on a previous run.
      // Treat it as a miss and let it be re-fetched rather than handing the
      // decoder an empty buffer.
      if (bytes.isEmpty) {
        unawaited(file.delete().catchError((_) => file));
        return null;
      }
      return bytes;
    } catch (_) {
      return null;
    }
  }

  Future<bool> has(String url) async {
    final file = await _fileFor(url);
    if (file == null) return false;
    try {
      return file.existsSync() && file.lengthSync() > 0;
    } catch (_) {
      return false;
    }
  }

  /// The bytes for [url] — from disk when they are there, otherwise downloaded
  /// and stored on the way through.
  ///
  /// Throws when there is nothing cached and the download fails, which is what
  /// lets the call sites keep their existing `errorBuilder` fallback.
  Future<Uint8List> load(
    String url, {
    Map<String, String>? headers,
    void Function(int received, int total)? onProgress,
  }) async {
    final hit = await cached(url);
    if (hit != null) return hit;

    final existing = _inFlight[url];
    if (existing != null) return existing;

    // A block body, not an arrow. `Map.remove` returns the value it removed —
    // which here is this very future — and `whenComplete` waits on a returned
    // future, so the arrow form makes the download wait for itself and the tile
    // never paints. Discarding the result is the entire point.
    final download = _download(url, headers: headers, onProgress: onProgress)
        .whenComplete(() {
      _inFlight.remove(url);
    });
    _inFlight[url] = download;
    return download;
  }

  Future<Uint8List> _download(
    String url, {
    Map<String, String>? headers,
    void Function(int received, int total)? onProgress,
    bool awaitStore = false,
  }) async {
    final bytes = await _http.getAssetBytes(url, headers: headers, onProgress: onProgress);
    if (bytes.isEmpty) throw const FileSystemException('Empty image response');
    // Interactive loads do not wait on the write: the bytes are already in hand
    // and the frame should paint now, not one disk round-trip from now. Warming
    // does wait, because getting the file onto disk IS the whole job — returning
    // before the write lands would let the pass report photos it had not stored.
    if (awaitStore) {
      await _store(url, bytes);
    } else {
      unawaited(_store(url, bytes));
    }
    return bytes;
  }

  /// Write to a temporary name and rename into place, so a download killed
  /// halfway leaves no truncated file that a later read would trust.
  Future<void> _store(String url, Uint8List bytes) async {
    final file = await _fileFor(url);
    if (file == null) return;
    try {
      final tmp = File('${file.path}.part');
      await tmp.writeAsBytes(bytes, flush: true);
      await tmp.rename(file.path);
    } catch (_) {
      // Out of space, or the directory vanished. The image is already decoded
      // for this frame; only the next cold start loses it.
    }
  }

  /// Pre-download [urls] so they are on disk before the network goes away.
  ///
  /// Fetching a photo the first time it is *shown* is useless for offline: by
  /// then the till is already off the network. This is called during
  /// provisioning, in the order the grid paints, so if the budget runs out it is
  /// the products nobody scrolls to that go without.
  ///
  /// Failures are counted, never thrown — one unreachable photo must not cost
  /// the rest of the catalog its images.
  Future<ImageWarmResult> warm(
    List<String> urls, {
    Map<String, String>? headers,
    void Function(int done, int total)? onProgress,
    bool Function()? shouldContinue,
  }) async {
    if (await _directory() == null) {
      return const ImageWarmResult(cached: 0, failed: 0, skipped: 0, stoppedOnBudget: false);
    }

    var bytesHeld = await _totalBytes();
    var done = 0, cached = 0, failed = 0, skipped = 0;
    var stoppedOnBudget = false;

    final queue = List<String>.from(urls);
    var next = 0;

    Future<void> worker() async {
      while (true) {
        if (stoppedOnBudget || shouldContinue?.call() == false) return;
        if (next >= queue.length) return;
        final url = queue[next++];
        if (await has(url)) {
          cached++;
          onProgress?.call(++done, queue.length);
          continue;
        }
        // Checked per item rather than once up front: each download moves the
        // figure, and stopping at the ceiling is what keeps a catalog of
        // full-resolution photos from filling the device.
        //
        // Approximate by up to [warmConcurrency] items — the workers in flight
        // when the ceiling is crossed still finish. That is why [_evict] runs
        // afterwards: the check bounds the download, the eviction bounds the disk.
        if (bytesHeld >= _budget) {
          stoppedOnBudget = true;
          skipped = queue.length - done;
          return;
        }
        try {
          final bytes = await _download(url, headers: headers, awaitStore: true);
          bytesHeld += bytes.length;
          cached++;
        } catch (_) {
          failed++;
        }
        onProgress?.call(++done, queue.length);
      }
    }

    await Future.wait([
      for (var i = 0; i < warmConcurrency && i < queue.length; i++) worker(),
    ]);

    await _evict();
    return ImageWarmResult(
      cached: cached,
      failed: failed,
      skipped: skipped,
      stoppedOnBudget: stoppedOnBudget,
    );
  }

  /// How much is held, for the offline-data readout.
  Future<ImageCacheStats> stats() async {
    final dir = await _directory();
    if (dir == null) return const ImageCacheStats(files: 0, bytes: 0);
    var files = 0, bytes = 0;
    try {
      await for (final entity in dir.list()) {
        if (entity is! File) continue;
        files++;
        // Sync inside an async walk: `list()` already yields between entries, so
        // the isolate stays responsive, and a per-file hop to the IO pool over
        // thousands of photos would cost far more than the stat itself.
        bytes += entity.lengthSync();
      }
    } catch (_) {
      // Mid-listing deletion (an eviction running alongside). Report what was
      // counted; this figure is a readout, not a decision input.
    }
    return ImageCacheStats(files: files, bytes: bytes);
  }

  /// Drop every cached photo. Called from the offline-data screen, and on the
  /// same sign-out that wipes the catalog — a shared till must not keep the
  /// previous tenant's product photography.
  Future<void> clear() async {
    final dir = await _directory();
    if (dir == null) return;
    _inFlight.clear();
    try {
      if (dir.existsSync()) await dir.delete(recursive: true);
      await dir.create(recursive: true);
    } catch (_) {
      // Nothing to do about a directory that will not delete; the cap below
      // still bounds it.
    }
  }

  Future<int> _totalBytes() async => (await stats()).bytes;

  /// Enforce [maxBytes], oldest file first.
  ///
  /// Age is write time, not read time: keeping a true LRU would mean an extra
  /// disk write on every single tile that paints, and the catalog is re-warmed
  /// wholesale on each refresh anyway, so write order already tracks the order
  /// the grid asked for them.
  Future<void> _evict() async {
    final dir = await _directory();
    if (dir == null) return;
    try {
      final files = <(File, DateTime, int)>[];
      var total = 0;
      await for (final entity in dir.list()) {
        if (entity is! File) continue;
        final stat = entity.statSync();
        files.add((entity, stat.modified, stat.size));
        total += stat.size;
      }
      if (total <= _budget) return;
      files.sort((a, b) => a.$2.compareTo(b.$2));
      for (final (file, _, size) in files) {
        if (total <= _budget) break;
        await file.delete();
        total -= size;
      }
    } catch (_) {
      // Best effort. A cache slightly over its ceiling is not worth surfacing.
    }
  }

  /// A filesystem-safe, collision-resistant name for [url].
  ///
  /// Two independent 32-bit FNV-1a passes rather than one: at one hash a
  /// few thousand product URLs carry a real chance of a collision, and a
  /// collision here does not fail — it silently paints one product's photo on
  /// another product's tile, which a cashier would act on.
  String _name(String url) {
    final bytes = utf8.encode(url);
    var a = 0x811C9DC5, b = 0x01000193;
    for (final byte in bytes) {
      a = ((a ^ byte) * 0x01000193) & 0xFFFFFFFF;
    }
    for (var i = bytes.length - 1; i >= 0; i--) {
      b = ((b ^ bytes[i]) * 0x01000193) & 0xFFFFFFFF;
    }
    final hex = '${a.toRadixString(16).padLeft(8, '0')}${b.toRadixString(16).padLeft(8, '0')}';
    return '$hex-${bytes.length}';
  }
}

/// What a warm pass managed to do — reported so "photos are ready" and "photos
/// ran out of room" never look the same on the offline-data screen.
class ImageWarmResult {
  const ImageWarmResult({
    required this.cached,
    required this.failed,
    required this.skipped,
    required this.stoppedOnBudget,
  });

  final int cached;
  final int failed;

  /// Photos never attempted because the cache hit its ceiling first.
  final int skipped;

  final bool stoppedOnBudget;
}

class ImageCacheStats {
  const ImageCacheStats({required this.files, required this.bytes});

  final int files;
  final int bytes;

  String get sizeLabel {
    if (bytes < 1024) return '$bytes B';
    if (bytes < 1024 * 1024) return '${(bytes / 1024).toStringAsFixed(0)} KB';
    return '${(bytes / (1024 * 1024)).toStringAsFixed(bytes < 100 * 1024 * 1024 ? 1 : 0)} MB';
  }
}
