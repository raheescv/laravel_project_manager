import 'dart:math' as math;

import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import 'package:go_router/go_router.dart';

import '../../../shared/domain/constants/app_config.dart';
import '../../../shared/domain/constants/data_fetching_status.dart';
import '../../../shared/domain/constants/global_variables.dart';
import '../../../shared/domain/helpers/formatters.dart';
import '../../../shared/domain/helpers/responsive.dart';
import '../../../shared/domain/models/index.dart';
import '../../../shared/utils/components/theme/pearl_theme.dart';
import '../../../shared/utils/local_storage/local_storage_service.dart';
import '../../../shared/widgets/pearl_widgets.dart';
import '../../../shared/widgets/photo.dart';
import '../logic/product_cubit/product_cubit.dart';

/// The 360° viewer.
///
/// Opened from the product page, which already has the product — so it is
/// passed in rather than re-fetched. A cold deep link has no [product] and
/// loads it.
class SpinViewerScreen extends StatelessWidget {
  const SpinViewerScreen({super.key, required this.productId, this.product});

  final int productId;
  final Product? product;

  @override
  Widget build(BuildContext context) {
    final known = product;
    if (known != null) return _SpinViewer(product: known);
    return BlocProvider<ProductCubit>(
      create: (_) => ProductCubit(productId: productId),
      child: BlocBuilder<ProductCubit, ProductState>(
        builder: (context, state) {
          if (state.status.isFailed) {
            return Scaffold(
              backgroundColor: context.pearl.bg,
              body: MessageState(
                title: 'This product did not load',
                detail: state.errorMessage,
                actionLabel: 'Close',
                onAction: () => context.pop(),
              ),
            );
          }
          final loaded = state.product;
          if (loaded == null) {
            return Scaffold(backgroundColor: context.pearl.bg, body: const SizedBox());
          }
          return _SpinViewer(product: loaded);
        },
      ),
    );
  }
}

enum _Mode { spin, gallery, zoom }

class _SpinViewer extends StatefulWidget {
  const _SpinViewer({required this.product});

  final Product product;

  @override
  State<_SpinViewer> createState() => _SpinViewerState();
}

class _SpinViewerState extends State<_SpinViewer> with SingleTickerProviderStateMixin {
  /// How far a finger travels to advance one frame. Tuned so a full turn is
  /// roughly one comfortable swipe across a tablet.
  static const double _dragPerFrame = 14;

  late final AnimationController _glide = AnimationController.unbounded(vsync: this);
  final TransformationController _zoom = TransformationController();

  _Mode _mode = _Mode.spin;
  int _frame = 0;
  double _position = 0;
  double _dragAnchor = 0;
  int _loaded = 0;
  bool _ready = false;
  bool _showHint = false;
  int _galleryIndex = 0;

  List<ProductImage> get _frames => widget.product.images360;
  int get _count => _frames.length;

  @override
  void initState() {
    super.initState();
    _glide.addListener(_onGlide);
    _showHint = !serviceLocator<LocalStorageService>().spinHintSeen;
    WidgetsBinding.instance.addPostFrameCallback((_) => _preload());
  }

  @override
  void dispose() {
    _glide
      ..removeListener(_onGlide)
      ..dispose();
    _zoom.dispose();
    super.dispose();
  }

  /// Every frame is fetched and decoded before the viewer becomes interactive.
  ///
  /// A spin that stalls on an uncached frame reads as a broken app rather than a
  /// slow network, so the wait is made explicit and finite instead.
  Future<void> _preload() async {
    if (_count == 0) {
      if (mounted) setState(() => _ready = true);
      return;
    }
    final config = serviceLocator<AppConfig>();
    final width = decodeWidthFor(context, MediaQuery.sizeOf(context).width);
    for (final frame in _frames) {
      if (!mounted) return;
      try {
        await precacheImage(
          ResizeImage(
            NetworkImage(config.assetUrl(frame.url), headers: config.assetHeaders),
            width: width,
          ),
          context,
        );
      } catch (_) {
        // A single missing frame should not strand the viewer — the spin just
        // shows the previous image for that step.
      }
      if (!mounted) return;
      setState(() => _loaded++);
    }
    if (mounted) setState(() => _ready = true);
  }

  void _onGlide() {
    if (!mounted) return;
    setState(() {
      _position = _glide.value;
      _frame = _wrap(_position.round());
    });
  }

  int _wrap(int index) => _count == 0 ? 0 : ((index % _count) + _count) % _count;

  void _onDragStart(DragStartDetails _) {
    _glide.stop();
    _dragAnchor = _position;
    if (_showHint) {
      setState(() => _showHint = false);
      serviceLocator<LocalStorageService>().setSpinHintSeen();
    }
  }

  void _onDragUpdate(DragUpdateDetails details) {
    if (!_ready || _count == 0) return;
    _dragAnchor += details.delta.dx / _dragPerFrame;
    setState(() {
      _position = _dragAnchor;
      _frame = _wrap(_position.round());
    });
  }

  /// A flick keeps turning and eases out, the way a real turntable would.
  void _onDragEnd(DragEndDetails details) {
    if (!_ready || _count == 0) return;
    final velocity = details.velocity.pixelsPerSecond.dx / _dragPerFrame;
    if (velocity.abs() < 2) return;
    final extra = (velocity / 3).clamp(-_count * 1.5, _count * 1.5);
    _glide.value = _position;
    _glide.animateTo(
      _position + extra,
      duration: Duration(milliseconds: (260 + extra.abs() * 26).clamp(300, 1400).toInt()),
      curve: Curves.decelerate,
    );
  }

  /// Whether the uploaded frames carry angles worth showing.
  ///
  /// Tenants label these by hand and often do not: a sequence can arrive with
  /// every frame at 0°, or with a handful of small numbers that describe the
  /// upload order rather than a turntable. A readout that climbs 2° → 3° → 5°
  /// across a full rotation is worse than no readout, so unless the labels
  /// actually span most of a turn they are ignored in favour of an even split.
  bool get _anglesAreReal {
    if (_count < 2) return false;
    var min = _frames.first.degree;
    var max = min;
    for (final f in _frames) {
      if (f.degree < min) min = f.degree;
      if (f.degree > max) max = f.degree;
    }
    return (max - min) >= 180;
  }

  double get _degrees {
    if (_count == 0) return 0;
    if (_anglesAreReal) return _frames[_frame].degree;
    return (360 / _count) * _frame;
  }

  @override
  Widget build(BuildContext context) {
    final p = context.pearl;
    final product = widget.product;
    final gallery = product.galleryUrls;

    // No spin frames at all: the viewer degrades to the gallery rather than
    // presenting an empty stage. The entry point on the product page is already
    // gated on `hasSpin`, so this is the deep-link case.
    final hasSpin = _count > 1;
    final mode = hasSpin ? _mode : _Mode.gallery;

    return Scaffold(
      backgroundColor: p.bg,
      body: SafeArea(
        child: Column(
          children: [
            _TopBar(
              product: product,
              degrees: _degrees,
              showDegrees: hasSpin && mode == _Mode.spin,
            ),
            Expanded(
              child: Stack(
                children: [
                  Positioned.fill(
                    child: Stage(
                      border: false,
                      child: switch (mode) {
                        _Mode.spin => _SpinStage(
                            frames: _frames,
                            frame: _frame,
                            ready: _ready,
                            loaded: _loaded,
                            onDragStart: _onDragStart,
                            onDragUpdate: _onDragUpdate,
                            onDragEnd: _onDragEnd,
                          ),
                        _Mode.gallery => _GalleryStage(
                            urls: gallery,
                            index: _galleryIndex,
                            onChanged: (i) => setState(() => _galleryIndex = i),
                          ),
                        _Mode.zoom => _ZoomStage(
                            controller: _zoom,
                            url: hasSpin
                                ? _frames[_frame].url
                                : (gallery.isEmpty ? '' : gallery[_galleryIndex]),
                          ),
                      },
                    ),
                  ),
                  if (context.isTablet)
                    Positioned(top: 16, right: 16, child: _ProductCardOverlay(product: product)),
                  if (hasSpin && mode == _Mode.spin && _ready)
                    Positioned(
                      left: 0,
                      right: 0,
                      bottom: 16,
                      child: _Ticker(count: _count, frame: _frame, showHint: _showHint),
                    ),
                ],
              ),
            ),
            if (hasSpin)
              _ModeBar(
                mode: _mode,
                onChanged: (m) {
                  setState(() {
                    _mode = m;
                    if (m != _Mode.zoom) _zoom.value = Matrix4.identity();
                  });
                },
              ),
          ],
        ),
      ),
    );
  }
}

class _TopBar extends StatelessWidget {
  const _TopBar({
    required this.product,
    required this.degrees,
    required this.showDegrees,
  });

  final Product product;
  final double degrees;
  final bool showDegrees;

  @override
  Widget build(BuildContext context) {
    final p = context.pearl;
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 12),
      decoration: BoxDecoration(border: Border(bottom: BorderSide(color: p.line))),
      child: Row(
        children: [
          IconSquare(Icons.close, size: 38, onTap: () => context.pop()),
          const SizedBox(width: 14),
          Expanded(
            child: Text(
              product.name.toUpperCase(),
              maxLines: 1,
              overflow: TextOverflow.ellipsis,
              style: PearlText.section.copyWith(color: p.ink),
            ),
          ),
          if (showDegrees)
            Text(
              '${degrees.round()}°',
              style: PearlText.price(15).copyWith(
                color: p.ink,
                fontFeatures: const [FontFeature.tabularFigures()],
              ),
            ),
        ],
      ),
    );
  }
}

class _SpinStage extends StatelessWidget {
  const _SpinStage({
    required this.frames,
    required this.frame,
    required this.ready,
    required this.loaded,
    required this.onDragStart,
    required this.onDragUpdate,
    required this.onDragEnd,
  });

  final List<ProductImage> frames;
  final int frame;
  final bool ready;
  final int loaded;
  final GestureDragStartCallback onDragStart;
  final GestureDragUpdateCallback onDragUpdate;
  final GestureDragEndCallback onDragEnd;

  @override
  Widget build(BuildContext context) {
    if (!ready) return _Preloading(loaded: loaded, total: frames.length);
    return GestureDetector(
      behavior: HitTestBehavior.opaque,
      onHorizontalDragStart: onDragStart,
      onHorizontalDragUpdate: onDragUpdate,
      onHorizontalDragEnd: onDragEnd,
      child: LayoutBuilder(
        builder: (context, constraints) => Photo(
          url: frames[frame].url,
          width: constraints.maxWidth,
          padding: EdgeInsets.all(context.isTablet ? 60 : 30),
        ),
      ),
    );
  }
}

class _Preloading extends StatelessWidget {
  const _Preloading({required this.loaded, required this.total});

  final int loaded;
  final int total;

  @override
  Widget build(BuildContext context) {
    final p = context.pearl;
    final progress = total == 0 ? 0.0 : loaded / total;
    return Center(
      child: Column(
        mainAxisSize: MainAxisSize.min,
        children: [
          Text(
            'Loading 360°'.toUpperCase(),
            style: PearlText.section.copyWith(color: p.ink),
          ),
          const SizedBox(height: 14),
          SizedBox(
            width: 200,
            child: Stack(
              children: [
                Container(height: 2, color: p.line),
                FractionallySizedBox(
                  widthFactor: progress.clamp(0, 1),
                  child: Container(height: 2, color: p.accent),
                ),
              ],
            ),
          ),
          const SizedBox(height: 12),
          Text(
            '$loaded / $total frames'.toUpperCase(),
            style: PearlText.micro.copyWith(color: p.faint),
          ),
        ],
      ),
    );
  }
}

class _GalleryStage extends StatelessWidget {
  const _GalleryStage({required this.urls, required this.index, required this.onChanged});

  final List<String> urls;
  final int index;
  final ValueChanged<int> onChanged;

  @override
  Widget build(BuildContext context) {
    if (urls.isEmpty) {
      return const MessageState(title: 'No photos for this product');
    }
    return PageView.builder(
      controller: PageController(initialPage: index),
      onPageChanged: onChanged,
      itemCount: urls.length,
      itemBuilder: (context, i) => LayoutBuilder(
        builder: (context, constraints) => Photo(
          url: urls[i],
          width: constraints.maxWidth,
          padding: EdgeInsets.all(context.isTablet ? 60 : 30),
        ),
      ),
    );
  }
}

class _ZoomStage extends StatelessWidget {
  const _ZoomStage({required this.controller, required this.url});

  final TransformationController controller;
  final String url;

  @override
  Widget build(BuildContext context) {
    return GestureDetector(
      // Double tap resets, so a customer who has zoomed into the sole can get
      // back out without pinching.
      onDoubleTap: () => controller.value = Matrix4.identity(),
      child: InteractiveViewer(
        transformationController: controller,
        minScale: 1,
        maxScale: 4,
        child: LayoutBuilder(
          builder: (context, constraints) => Photo(
            url: url,
            // Decoded for the deepest zoom, not the resting size, or zooming in
            // just magnifies a blurry decode.
            width: constraints.maxWidth * 2,
            padding: const EdgeInsets.all(20),
          ),
        ),
      ),
    );
  }
}

/// The frame ticker: one mark per uploaded frame, the current one full height.
class _Ticker extends StatelessWidget {
  const _Ticker({required this.count, required this.frame, required this.showHint});

  final int count;
  final int frame;
  final bool showHint;

  @override
  Widget build(BuildContext context) {
    final p = context.pearl;
    // A 72-frame sequence would draw a solid bar; sample it instead.
    final marks = math.min(count, 36);
    final active = ((frame / count) * marks).floor();
    return Column(
      mainAxisSize: MainAxisSize.min,
      children: [
        Row(
          mainAxisAlignment: MainAxisAlignment.center,
          crossAxisAlignment: CrossAxisAlignment.end,
          children: [
            for (var i = 0; i < marks; i++)
              Container(
                width: 2,
                height: i == active ? 18 : 7,
                margin: const EdgeInsets.symmetric(horizontal: 2),
                color: i == active ? p.accent : p.faint,
              ),
          ],
        ),
        const SizedBox(height: 12),
        if (showHint)
          Container(
            padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 9),
            decoration: BoxDecoration(color: p.bg, border: Border.all(color: p.line)),
            child: Row(
              mainAxisSize: MainAxisSize.min,
              children: [
                Icon(Icons.swipe, size: 14, color: p.ink),
                const SizedBox(width: 9),
                Text(
                  'Drag to spin · $count frames'.toUpperCase(),
                  style: PearlText.micro.copyWith(fontSize: 8.5, color: p.ink),
                ),
              ],
            ),
          ),
      ],
    );
  }
}

class _ModeBar extends StatelessWidget {
  const _ModeBar({required this.mode, required this.onChanged});

  final _Mode mode;
  final ValueChanged<_Mode> onChanged;

  @override
  Widget build(BuildContext context) {
    final p = context.pearl;
    return Container(
      padding: const EdgeInsets.fromLTRB(PearlMetrics.pad, 12, PearlMetrics.pad, 14),
      decoration: BoxDecoration(border: Border(top: BorderSide(color: p.line))),
      child: Row(
        children: [
          for (final entry in const [
            (_Mode.spin, '360° spin'),
            (_Mode.gallery, 'Gallery'),
            (_Mode.zoom, 'Zoom'),
          ])
            Expanded(
              child: Padding(
                padding: const EdgeInsets.symmetric(horizontal: 3),
                child: InkWell(
                  onTap: () => onChanged(entry.$1),
                  child: Container(
                    height: 42,
                    alignment: Alignment.center,
                    decoration: BoxDecoration(
                      color: mode == entry.$1 ? p.accent : null,
                      border: Border.all(color: mode == entry.$1 ? p.accent : p.line),
                    ),
                    child: Text(
                      entry.$2.toUpperCase(),
                      style: PearlText.button.copyWith(
                        color: mode == entry.$1 ? p.accentInk : p.ink,
                      ),
                    ),
                  ),
                ),
              ),
            ),
        ],
      ),
    );
  }
}

class _ProductCardOverlay extends StatelessWidget {
  const _ProductCardOverlay({required this.product});

  final Product product;

  @override
  Widget build(BuildContext context) {
    final p = context.pearl;
    return Container(
      width: 230,
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(color: p.bg, border: Border.all(color: p.line)),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(
            product.brandName.toUpperCase(),
            style: PearlText.micro.copyWith(fontSize: 8.5, color: p.faint),
          ),
          const SizedBox(height: 8),
          Text(
            product.name,
            maxLines: 2,
            overflow: TextOverflow.ellipsis,
            style: PearlText.display(16).copyWith(color: p.ink),
          ),
          const SizedBox(height: 10),
          Text(money(product.mrp), style: PearlText.price(15).copyWith(color: p.ink)),
        ],
      ),
    );
  }
}
