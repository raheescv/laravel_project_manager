import 'dart:async';
import 'dart:ui' as ui;

import 'package:flutter/foundation.dart';
import 'package:flutter/widgets.dart';

import '../utils/local_storage/image_store.dart';

/// [NetworkImage]'s replacement for anything the catalog paints.
///
/// Identical to `Image.network` in use — same headers, same `errorBuilder`, same
/// `loadingBuilder` — and different in the one way that matters: the bytes come
/// from [ImageStore], so a photo that has been seen (or pre-warmed during
/// provisioning) still paints with no network. `NetworkImage` cannot do this at
/// any price; Flutter's own image cache is memory-only and starts every cold
/// launch empty.
///
/// Use [provider] rather than the constructor at call sites that pass a
/// `cacheWidth`, so the decode stays bounded exactly as it was.
@immutable
class OfflineImage extends ImageProvider<OfflineImage> {
  const OfflineImage(this.url, {this.headers, this.scale = 1.0});

  /// Absolute URL, already resolved through `AppConfig.assetUrl`.
  final String url;

  final Map<String, String>? headers;
  final double scale;

  /// The provider a call site should actually use: [OfflineImage] wrapped in the
  /// same [ResizeImage] that `Image.network(cacheWidth:)` applies internally.
  ///
  /// Without this the decode is unbounded, and one screen of full-resolution
  /// product photographs overruns Flutter's 100MB image cache and re-decodes on
  /// every scroll — see `decodeWidthFor`.
  static ImageProvider provider(String url, {Map<String, String>? headers, int? cacheWidth}) =>
      ResizeImage.resizeIfNeeded(cacheWidth, null, OfflineImage(url, headers: headers));

  @override
  Future<OfflineImage> obtainKey(ImageConfiguration configuration) =>
      SynchronousFuture<OfflineImage>(this);

  @override
  ImageStreamCompleter loadImage(OfflineImage key, ImageDecoderCallback decode) {
    // Fed from the download's byte progress so the existing `loadingBuilder`
    // placeholders keep firing. Without any event the Image widget treats the
    // load as instant and paints an empty box until the frame arrives.
    final chunks = StreamController<ImageChunkEvent>();
    return MultiFrameImageStreamCompleter(
      codec: _load(key, decode, chunks),
      chunkEvents: chunks.stream,
      scale: key.scale,
      debugLabel: key.url,
      informationCollector: () => [DiagnosticsProperty<ImageProvider>('Image provider', this)],
    );
  }

  Future<ui.Codec> _load(
    OfflineImage key,
    ImageDecoderCallback decode,
    StreamController<ImageChunkEvent> chunks,
  ) async {
    try {
      final bytes = await ImageStore.instance.load(
        key.url,
        headers: key.headers,
        onProgress: (received, total) {
          if (chunks.isClosed) return;
          chunks.add(ImageChunkEvent(
            cumulativeBytesLoaded: received,
            expectedTotalBytes: total <= 0 ? null : total,
          ));
        },
      );
      if (bytes.isEmpty) {
        throw StateError('Image is empty: ${key.url}');
      }
      return decode(await ui.ImmutableBuffer.fromUint8List(bytes));
    } finally {
      // The completer holds this stream open until it closes; leaving it open
      // keeps the whole completer — and the bytes behind it — alive.
      unawaited(chunks.close());
    }
  }

  @override
  bool operator ==(Object other) =>
      other is OfflineImage && other.url == url && other.scale == scale;

  @override
  int get hashCode => Object.hash(url, scale);

  @override
  String toString() => '${objectRuntimeType(this, 'OfflineImage')}("$url", scale: $scale)';
}
