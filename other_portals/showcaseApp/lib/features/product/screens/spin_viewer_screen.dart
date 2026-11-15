import 'dart:math' as math;

// `ValueListenable` — the frame the three moving parts of this screen follow.
import 'package:flutter/foundation.dart';
import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import 'package:go_router/go_router.dart';

import '../../../shared/domain/constants/data_fetching_status.dart';
import '../../../shared/domain/constants/global_variables.dart';
import '../../../shared/domain/models/index.dart';
import '../../../shared/logic/funnel_cubit/funnel_cubit.dart';
import '../../../shared/utils/components/theme/pearl_theme.dart';
import '../../../shared/utils/local_storage/local_storage_service.dart';
import '../../../shared/widgets/pearl_widgets.dart';
import '../../../shared/widgets/photo.dart';
import '../logic/product_cubit/product_cubit.dart';
import '../../../l10n/app_localizations.dart';

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
      create: (_) => ProductCubit(
          productId: productId,
          inStockOnly: context.read<FunnelCubit>().state.inStockOnly,
        ),
      child: BlocBuilder<ProductCubit, ProductState>(
        builder: (context, state) {
          if (state.status.isFailed) {
            return Scaffold(
              backgroundColor: context.pearl.bg,
              body: MessageState(
                title: L.of(context).productDidNotLoad,
                detail: state.errorMessage,
                actionLabel: L.of(context).close,
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

  /// Which frame is facing the customer.
  ///
  /// A notifier rather than a field behind `setState`, because this changes on
  /// every frame of a drag and every frame of the glide that follows it. Under
  /// `setState` each of those rebuilt the whole viewer — the scaffold, the top
  /// bar, the mode bar, the ticker and the stage — to swap one photograph, which
  /// is the screen in this app most likely to be moving when somebody is
  /// watching it. Three places listen, and only those three redraw. It also
  /// stays quiet when a slow glide crosses several ticks without reaching the
  /// next frame: a `ValueNotifier` says nothing when the value has not moved.
  final ValueNotifier<int> _frame = ValueNotifier(0);

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
    _frame.dispose();
    super.dispose();
  }

  /// Every frame is fetched and decoded before the viewer becomes interactive.
  ///
  /// A spin that stalls on an uncached frame reads as a broken app rather than a
  /// slow network, so the wait is made explicit and finite instead.
  ///
  /// All frames go out at once. Awaiting them one at a time made the wait the
  /// sum of twenty-four round trips instead of the slowest one — and the
  /// product page has usually warmed them already, in which case this resolves
  /// immediately and the viewer opens straight into the spin.
  Future<void> _preload() async {
    if (_count == 0) {
      if (mounted) setState(() => _ready = true);
      return;
    }
    final width = MediaQuery.sizeOf(context).width;
    // A few at a time, not all two dozen: asking for every frame at once makes
    // them all slow rather than any of them fast, and the counter stops moving.
    await runBounded(
      _frames.map((frame) => () async {
            await precacheImage(photoProvider(context, frame.url, width), context);
            if (mounted) setState(() => _loaded++);
          }),
      concurrency: 4,
      // An image fetch has no timeout of its own, so one frame that never
      // arrives would leave this viewer counting forever. Past this the spin
      // opens with whatever landed — a stutter at one angle beats a screen
      // that never opens.
    ).timeout(const Duration(seconds: 20), onTimeout: () {});
    if (mounted) setState(() => _ready = true);
  }

  void _onGlide() {
    if (!mounted) return;
    _position = _glide.value;
    _frame.value = _wrap(_position.round());
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
    _position = _dragAnchor;
    _frame.value = _wrap(_position.round());
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
  /// Read once. The frames do not change under this screen, and the readout
  /// that asks for it is redrawn on every frame of a spin — a full scan of a
  /// seventy-two frame sequence per turn of the wheel is a scan per answer that
  /// was already known.
  late final bool _anglesAreReal = _readAngles();

  bool _readAngles() {
    if (_count < 2) return false;
    var min = _frames.first.degree;
    var max = min;
    for (final f in _frames) {
      if (f.degree < min) min = f.degree;
      if (f.degree > max) max = f.degree;
    }
    return (max - min) >= 180;
  }

  double _degreesAt(int frame) {
    if (_count == 0) return 0;
    if (_anglesAreReal) return _frames[frame].degree;
    return (360 / _count) * frame;
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
              frame: _frame,
              degreesAt: _degreesAt,
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
                                ? _frames[_frame.value].url
                                : (gallery.isEmpty ? '' : gallery[_galleryIndex]),
                          ),
                      },
                    ),
                  ),
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
    required this.frame,
    required this.degreesAt,
    required this.showDegrees,
  });

  final Product product;

  /// The readout is the only thing up here that moves with the spin, so it is
  /// the only thing that listens to it.
  final ValueListenable<int> frame;
  final double Function(int) degreesAt;
  final bool showDegrees;

  @override
  Widget build(BuildContext context) {
    final p = context.pearl;
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 12),
      decoration: BoxDecoration(border: Border(bottom: BorderSide(color: p.line))),
      child: Row(
        children: [
          IconSquare(Icons.close,
              size: 38, prominent: true, onTap: () => context.pop()),
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
            ValueListenableBuilder<int>(
              valueListenable: frame,
              builder: (context, i, _) => Text(
                '${degreesAt(i).round()}°',
                style: PearlText.price(15).copyWith(
                  color: p.ink,
                  fontFeatures: const [FontFeature.tabularFigures()],
                ),
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
  final ValueListenable<int> frame;
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
      // The listener is inside the LayoutBuilder, not around it: the stage does
      // not change size while it is being turned, and re-measuring it once a
      // frame to hand back the same width is the work this screen can least
      // afford.
      child: LayoutBuilder(
        builder: (context, constraints) => ValueListenableBuilder<int>(
          valueListenable: frame,
          builder: (context, i, _) => Photo(
            url: frames[i].url,
            width: constraints.maxWidth,
            padding: const EdgeInsets.all(30),
          ),
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
            L.of(context).loading360.toUpperCase(),
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
            L.of(context).framesLoaded(loaded, total).toUpperCase(),
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
      return MessageState(title: L.of(context).noPhotos);
    }
    return PageView.builder(
      controller: PageController(initialPage: index),
      onPageChanged: onChanged,
      itemCount: urls.length,
      itemBuilder: (context, i) => LayoutBuilder(
        builder: (context, constraints) => Photo(
          url: urls[i],
          width: constraints.maxWidth,
          padding: const EdgeInsets.all(30),
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
  final ValueListenable<int> frame;
  final bool showHint;

  @override
  Widget build(BuildContext context) {
    final p = context.pearl;
    // A 72-frame sequence would draw a solid bar; sample it instead.
    final marks = math.min(count, 36);
    return Column(
      mainAxisSize: MainAxisSize.min,
      children: [
        // Only the row of marks follows the spin. The hint underneath it is a
        // one-off that outlives a whole rotation.
        ValueListenableBuilder<int>(
          valueListenable: frame,
          builder: (context, current, _) {
            final active = ((current / count) * marks).floor();
            return Row(
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
            );
          },
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
                  L.of(context).dragToSpin(count).toUpperCase(),
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
          for (final entry in [
            (_Mode.spin, '360°'),
            (_Mode.gallery, L.of(context).gallery),
            (_Mode.zoom, L.of(context).zoom),
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
