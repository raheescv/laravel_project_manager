import 'package:flutter/material.dart';

import '../domain/constants/app_config.dart';
import '../domain/constants/global_variables.dart';
import '../domain/helpers/formatters.dart';
import '../utils/components/theme/pearl_theme.dart';

/// A product photo on the Pearl stage.
///
/// Always decodes at the width it is painted at. A catalogue photo is a couple
/// of thousand pixels wide and a grid tile is under two hundred; decoding at
/// full size fills the image cache with images nothing can see and makes a long
/// scroll stutter.
class Photo extends StatelessWidget {
  const Photo({
    super.key,
    required this.url,
    required this.width,
    this.fit = BoxFit.contain,
    this.padding = const EdgeInsets.all(10),
  });

  final String url;
  final double width;
  final BoxFit fit;
  final EdgeInsets padding;

  @override
  Widget build(BuildContext context) {
    final p = context.pearl;
    if (url.isEmpty) return _Fallback(palette: p);

    return Padding(
      padding: padding,
      child: Image(
        image: photoProvider(context, url, width),
        fit: fit,
        gaplessPlayback: true,
        loadingBuilder: (context, child, progress) =>
            progress == null ? child : _Loading(palette: p, progress: progress),
        errorBuilder: (context, _, __) => _Fallback(palette: p),
      ),
    );
  }
}

/// The exact provider [Photo] paints with, for anyone who wants to warm it.
///
/// It has to be built in one place: the image cache is keyed on the provider,
/// and a `ResizeImage` at one width is a different entry from the same URL at
/// another. Pre-caching through a hand-rolled `NetworkImage` would download
/// every photo a second time and hit nothing.
ImageProvider photoProvider(BuildContext context, String url, double width) {
  final config = serviceLocator<AppConfig>();
  return ResizeImage(
    NetworkImage(config.assetUrl(url), headers: config.assetHeaders),
    width: decodeWidthFor(context, width),
  );
}

/// The width a photo is decoded at inside the zoom viewer: twice the screen, so
/// pinching reveals detail instead of magnifying a soft decode.
///
/// Shared so the product page can warm exactly the entry the viewer will ask
/// for. Measured against the screen rather than the viewer's own box, because
/// the two differ by the safe-area inset and a few pixels of difference is a
/// different cache key and a second download.
double zoomDecodeWidth(BuildContext context) =>
    MediaQuery.sizeOf(context).width * 2;

/// Warm every photo in [urls] at the width they will be painted at.
///
/// The gallery shows one shot at a time, so without this each thumbnail tap
/// started its own download and the customer watched a spinner for a photo the
/// page had already had time to fetch. They go out together and the failures
/// are swallowed — a photo that will not load is the gallery's problem to
/// render, not a reason to throw here.
Future<void> precachePhotos(
  BuildContext context,
  Iterable<String> urls,
  double width, {
  int concurrency = 3,
  bool Function()? shouldContinue,
}) async {
  final pending = urls.where((url) => url.isNotEmpty).toList(growable: false);
  await runBounded(
    pending.map((url) => () =>
        precacheImage(photoProvider(context, url, width), context)),
    concurrency: concurrency,
    shouldContinue: shouldContinue,
  );
}

/// Run [tasks] with at most [concurrency] in flight, in order.
///
/// Warming a product's photos used to start every one at once: a gallery, two
/// dozen spin frames and a zoom copy went out together, roughly thirty requests
/// the instant the page opened. On shop wifi they do not arrive faster for
/// being asked for together — they starve each other and, worse, they starve
/// the page's own API calls, so the first visit to a product sat on a spinner
/// while the second was instant off a warm cache.
///
/// Failures are swallowed: a photo that will not load is the gallery's problem
/// to render, and one bad URL must not stop the rest being fetched.
Future<void> runBounded(
  Iterable<Future<void> Function()> tasks, {
  int concurrency = 3,
  bool Function()? shouldContinue,
}) async {
  final queue = tasks.toList(growable: false);
  if (queue.isEmpty) return;
  final width = concurrency < 1 ? 1 : concurrency;
  var next = 0;

  Future<void> worker() async {
    while (true) {
      // Checked between tasks, so leaving a page stops it queuing more. The
      // request already in flight cannot be recalled, but two dozen spin
      // frames should not go on competing with the screen the customer went
      // back to — which is what made the list look stuck on return.
      if (shouldContinue != null && !shouldContinue()) return;
      final index = next++;
      if (index >= queue.length) return;
      try {
        await queue[index]();
      } catch (_) {
        // Keep going; the next one may be fine.
      }
    }
  }

  await Future.wait([
    for (var i = 0; i < width && i < queue.length; i++) worker(),
  ]);
}

/// Shown while the photo is on its way.
///
/// Distinct from [_Fallback] on purpose: a catalogue shot on a shop tablet can
/// take a moment over the shop's wifi, and drawing the broken-image mark in the
/// meantime told a customer the product had no photo when it was simply still
/// loading. Determinate when the server sent a length, so a slow image reads as
/// progress rather than a hang.
class _Loading extends StatelessWidget {
  const _Loading({required this.palette, required this.progress});

  final PearlPalette palette;
  final ImageChunkEvent progress;

  @override
  Widget build(BuildContext context) {
    final total = progress.expectedTotalBytes;
    return Center(
      child: SizedBox(
        width: 20,
        height: 20,
        child: CircularProgressIndicator(
          // A hairline, like everything else in this system.
          strokeWidth: 1.4,
          color: palette.faint,
          value: total != null && total > 0
              ? progress.cumulativeBytesLoaded / total
              : null,
        ),
      ),
    );
  }
}

class _Fallback extends StatelessWidget {
  const _Fallback({required this.palette});

  final PearlPalette palette;

  @override
  Widget build(BuildContext context) => Center(
        child: Icon(Icons.image_outlined, size: 22, color: palette.faint.withValues(alpha: .5)),
      );
}

/// The stage a photo stands on: the Pearl gradient, a hairline, and room for
/// the badges that sit over it.
class Stage extends StatelessWidget {
  const Stage({
    super.key,
    required this.child,
    this.aspectRatio,
    this.border = true,
  });

  final Widget child;
  final double? aspectRatio;
  final bool border;

  @override
  Widget build(BuildContext context) {
    final p = context.pearl;
    final box = DecoratedBox(
      decoration: BoxDecoration(
        gradient: p.stage,
        border: border ? Border.all(color: p.line, width: PearlMetrics.hairline) : null,
      ),
      child: child,
    );
    if (aspectRatio == null) return box;
    return AspectRatio(aspectRatio: aspectRatio!, child: box);
  }
}
