import 'package:flutter/material.dart';

import 'package:invo/shared/domain/constants/global_variables.dart';
import 'package:invo/shared/utils/components/theme/index.dart';
import 'package:invo/shared/utils/router/http_utils/http_service.dart';

/// Hero tag shared by a photo thumbnail and its full-screen page, so opening
/// the viewer flies the image up rather than cutting to it.
String checklistPhotoHeroTag(String path) => 'checklist-photo:$path';

/// One photo in the viewer: a root-relative storage path plus its caption
/// (e.g. "Move-in · Refrigerator").
class ChecklistViewerPhoto {
  const ChecklistViewerPhoto({required this.path, required this.caption});
  final String path;
  final String caption;
}

/// Opens [photos] full screen at [initialIndex]: black canvas, pinch and
/// double-tap zoom, swipe between photos, swipe down or ✕ to close.
Future<void> openChecklistPhotoViewer(BuildContext context, List<ChecklistViewerPhoto> photos, {int initialIndex = 0}) {
  if (photos.isEmpty) return Future.value();
  return Navigator.of(context, rootNavigator: true).push<void>(PageRouteBuilder<void>(
    opaque: false,
    barrierColor: ColorManager.transparent,
    transitionDuration: const Duration(milliseconds: 220),
    reverseTransitionDuration: const Duration(milliseconds: 180),
    pageBuilder: (_, __, ___) => ChecklistPhotoViewer(photos: photos, initialIndex: initialIndex.clamp(0, photos.length - 1)),
    transitionsBuilder: (_, animation, __, child) => FadeTransition(opacity: animation, child: child),
  ));
}

class ChecklistPhotoViewer extends StatefulWidget {
  const ChecklistPhotoViewer({super.key, required this.photos, this.initialIndex = 0});
  final List<ChecklistViewerPhoto> photos;
  final int initialIndex;

  @override
  State<ChecklistPhotoViewer> createState() => _ChecklistPhotoViewerState();
}

class _ChecklistPhotoViewerState extends State<ChecklistPhotoViewer> {
  late final _pageCtl = PageController(initialPage: widget.initialIndex);
  late int _index = widget.initialIndex;
  bool _zoomed = false;
  double _dragDy = 0;

  @override
  void dispose() {
    _pageCtl.dispose();
    super.dispose();
  }

  void _onDragEnd(DragEndDetails d) {
    final v = d.primaryVelocity ?? 0;
    if (_dragDy.abs() > 120 || v.abs() > 900) {
      Navigator.of(context).pop();
    } else {
      setState(() => _dragDy = 0);
    }
  }

  @override
  Widget build(BuildContext context) {
    final photos = widget.photos;
    final fade = (1 - _dragDy.abs() / 400).clamp(0.25, 1.0);
    final chipBg = ColorManager.white.withValues(alpha: 0.14);
    return Material(
      color: ColorManager.black.withValues(alpha: fade),
      child: Stack(children: [
        Positioned.fill(
          child: GestureDetector(
            onVerticalDragUpdate: _zoomed ? null : (d) => setState(() => _dragDy += d.delta.dy),
            onVerticalDragEnd: _zoomed ? null : _onDragEnd,
            child: Transform.translate(
              offset: Offset(0, _dragDy),
              child: PageView.builder(
                controller: _pageCtl,
                physics: _zoomed ? const NeverScrollableScrollPhysics() : const PageScrollPhysics(),
                itemCount: photos.length,
                onPageChanged: (i) => setState(() {
                  _index = i;
                  _zoomed = false;
                }),
                itemBuilder: (_, i) => _ZoomablePhoto(
                  path: photos[i].path,
                  onZoomChanged: (z) {
                    if (z != _zoomed) setState(() => _zoomed = z);
                  },
                ),
              ),
            ),
          ),
        ),
        SafeArea(
          child: Padding(
            padding: const EdgeInsets.fromLTRB(14, 8, 14, 0),
            child: Row(children: [
              if (photos.length > 1)
                Container(
                  padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 5),
                  decoration: BoxDecoration(color: chipBg, borderRadius: BorderRadius.circular(20)),
                  child: Text('${_index + 1} / ${photos.length}',
                      style: ui(size: 12, weight: FontWeight.w800, color: ColorManager.white)),
                ),
              const Spacer(),
              GestureDetector(
                onTap: () => Navigator.of(context).pop(),
                child: Container(
                  width: 40,
                  height: 40,
                  decoration: BoxDecoration(shape: BoxShape.circle, color: chipBg),
                  child: const Icon(Icons.close, size: 20, color: ColorManager.white),
                ),
              ),
            ]),
          ),
        ),
        Positioned(
          left: 0,
          right: 0,
          bottom: 0,
          child: IgnorePointer(
            child: Container(
              decoration: BoxDecoration(
                gradient: LinearGradient(
                  begin: Alignment.bottomCenter,
                  end: Alignment.topCenter,
                  colors: [ColorManager.black.withValues(alpha: 0.6), ColorManager.black.withValues(alpha: 0)],
                ),
              ),
              child: SafeArea(
                top: false,
                child: Padding(
                  padding: const EdgeInsets.fromLTRB(20, 28, 20, 16),
                  child: Text(
                    photos[_index].caption,
                    textAlign: TextAlign.center,
                    style: ui(size: 13.5, weight: FontWeight.w700, color: ColorManager.white),
                  ),
                ),
              ),
            ),
          ),
        ),
      ]),
    );
  }
}

/// One page: pinch-zoom via [InteractiveViewer], double-tap toggles 2.5×.
class _ZoomablePhoto extends StatefulWidget {
  const _ZoomablePhoto({required this.path, required this.onZoomChanged});
  final String path;
  final ValueChanged<bool> onZoomChanged;

  @override
  State<_ZoomablePhoto> createState() => _ZoomablePhotoState();
}

class _ZoomablePhotoState extends State<_ZoomablePhoto> {
  final _transform = TransformationController();
  Offset _tapAt = Offset.zero;

  @override
  void dispose() {
    _transform.dispose();
    super.dispose();
  }

  bool get _isZoomed => _transform.value.getMaxScaleOnAxis() > 1.01;

  void _toggleZoom() {
    if (_isZoomed) {
      _transform.value = Matrix4.identity();
    } else {
      const scale = 2.5;
      _transform.value = Matrix4.identity()
        ..translateByDouble(-_tapAt.dx * (scale - 1), -_tapAt.dy * (scale - 1), 0, 1)
        ..scaleByDouble(scale, scale, 1, 1);
    }
    widget.onZoomChanged(_isZoomed);
  }

  @override
  Widget build(BuildContext context) {
    final cfg = serviceLocator<HttpService>().config;
    final size = MediaQuery.sizeOf(context);
    final dpr = MediaQuery.devicePixelRatioOf(context);
    return GestureDetector(
      onDoubleTapDown: (d) => _tapAt = d.localPosition,
      onDoubleTap: _toggleZoom,
      child: InteractiveViewer(
        transformationController: _transform,
        minScale: 1,
        maxScale: 5,
        onInteractionEnd: (_) => widget.onZoomChanged(_isZoomed),
        child: SizedBox.expand(
          child: Hero(
            tag: checklistPhotoHeroTag(widget.path),
            child: Image.network(
            cfg.assetUrl(widget.path),
            headers: cfg.assetHeaders,
            fit: BoxFit.contain,
            cacheWidth: (size.width * dpr * 1.5).round().clamp(600, 2400),
            loadingBuilder: (_, child, progress) => progress == null
                ? child
                : Center(
                    child: SizedBox(
                      width: 40,
                      height: 40,
                      child: CircularProgressIndicator(
                        strokeWidth: 2.6,
                        color: ColorManager.white.withValues(alpha: 0.8),
                        value: progress.expectedTotalBytes != null
                            ? progress.cumulativeBytesLoaded / progress.expectedTotalBytes!
                            : null,
                      ),
                    ),
                  ),
            errorBuilder: (_, __, ___) =>
                Center(child: Icon(Icons.broken_image_outlined, size: 56, color: ColorManager.white.withValues(alpha: 0.4))),
            ),
          ),
        ),
      ),
    );
  }
}
