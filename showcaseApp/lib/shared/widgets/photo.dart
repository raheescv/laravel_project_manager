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

    final config = serviceLocator<AppConfig>();
    return Padding(
      padding: padding,
      child: Image.network(
        config.assetUrl(url),
        headers: config.assetHeaders,
        fit: fit,
        cacheWidth: decodeWidthFor(context, width),
        gaplessPlayback: true,
        loadingBuilder: (context, child, progress) =>
            progress == null ? child : _Fallback(palette: p),
        errorBuilder: (context, _, __) => _Fallback(palette: p),
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
