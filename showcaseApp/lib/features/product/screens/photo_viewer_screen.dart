import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import 'package:go_router/go_router.dart';

import '../../../shared/domain/constants/data_fetching_status.dart';
import '../../../shared/domain/models/index.dart';
import '../../../shared/utils/components/theme/pearl_theme.dart';
import '../../../shared/widgets/pearl_widgets.dart';
import '../../../shared/widgets/photo.dart';
import '../../catalog/logic/funnel_cubit/funnel_cubit.dart';
import '../logic/product_cubit/product_cubit.dart';

/// The photo, on its own and close up.
///
/// A catalogue shot is the whole decision for a shoe — the stitching, the sole
/// unit, whether the beige is warm or grey — and on the product page it shares
/// the screen with a price and a size run. Tapping it gives it the whole
/// display, with pinch, drag and explicit zoom controls: a customer standing at
/// a tablet on a shop floor is not always going to pinch, and staff showing a
/// customer something want a button they can press once.
///
/// Opened from the product page, which already has the product, so it is passed
/// in rather than re-fetched. A cold deep link has no [product] and loads it.
class PhotoViewerScreen extends StatelessWidget {
  const PhotoViewerScreen({
    super.key,
    required this.productId,
    this.initialIndex = 0,
    this.product,
  });

  final int productId;
  final int initialIndex;
  final Product? product;

  @override
  Widget build(BuildContext context) {
    final known = product;
    if (known != null) {
      return _PhotoViewer(product: known, initialIndex: initialIndex);
    }
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
                title: 'This photo did not load',
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
          return _PhotoViewer(product: loaded, initialIndex: initialIndex);
        },
      ),
    );
  }
}

class _PhotoViewer extends StatefulWidget {
  const _PhotoViewer({required this.product, required this.initialIndex});

  final Product product;
  final int initialIndex;

  @override
  State<_PhotoViewer> createState() => _PhotoViewerState();
}

class _PhotoViewerState extends State<_PhotoViewer> {
  /// Past this the decode starts to show; below it the buttons feel like they
  /// did nothing.
  static const double _minScale = 1;
  static const double _maxScale = 5;
  static const double _step = 1.6;

  late final PageController _pages = PageController(initialPage: _index);
  final TransformationController _zoom = TransformationController();

  late int _index = widget.initialIndex.clamp(0, _urls.length - 1);

  /// Where a double tap zoomed to, so the next one can go back there.
  Offset? _lastTap;

  /// The viewer's own box, not the screen's. The button zoom focuses on the
  /// middle of the photo, and the two differ by the safe-area inset.
  Size? _viewport;

  List<String> get _urls =>
      widget.product.galleryUrls.isEmpty ? const [''] : widget.product.galleryUrls;

  double get _scale => _zoom.value.getMaxScaleOnAxis();

  @override
  void initState() {
    super.initState();
    // Rebuilds the controls: the zoom-out button and the reset are only live
    // once the photo is actually zoomed.
    _zoom.addListener(_onZoomChanged);
  }

  @override
  void dispose() {
    _zoom
      ..removeListener(_onZoomChanged)
      ..dispose();
    _pages.dispose();
    super.dispose();
  }

  void _onZoomChanged() => setState(() {});

  void _reset() => _zoom.value = Matrix4.identity();

  /// Zoom about the middle of the screen, which is where the eye already is
  /// when the button was pressed.
  void _zoomBy(double factor) {
    final next = (_scale * factor).clamp(_minScale, _maxScale);
    if (next == _scale) return;
    final size = _viewport;
    if (size == null) return;
    _zoomTo(next, Offset(size.width / 2, size.height / 2));
  }

  /// Scale to [target] keeping [focal] under the same pixel.
  void _zoomTo(double target, Offset focal) {
    final current = _scale;
    final scene = _zoom.toScene(focal);
    final matrix = Matrix4.identity()
      ..translateByDouble(focal.dx, focal.dy, 0, 1)
      ..scaleByDouble(target, target, target, 1)
      ..translateByDouble(-scene.dx, -scene.dy, 0, 1);
    _zoom.value = current == target ? _zoom.value : matrix;
  }

  void _onDoubleTap() {
    if (_scale > _minScale + 0.01) {
      _reset();
      return;
    }
    _zoomTo(2.5, _lastTap ?? Offset.zero);
  }

  void _showPage(int i) {
    // Landing on the next photo already magnified is disorienting — every page
    // starts fitted.
    _reset();
    setState(() => _index = i);
  }

  @override
  Widget build(BuildContext context) {
    final p = context.pearl;
    final zoomed = _scale > _minScale + 0.01;

    return Scaffold(
      backgroundColor: p.bg,
      body: SafeArea(
        child: Stack(
          children: [
            Positioned.fill(child: _stage(zoomed: zoomed)),
            Positioned(
              top: 12,
              left: 14,
              child: IconSquare(
                Icons.close,
                size: 40,
                filled: true,
                onTap: () => context.canPop() ? context.pop() : null,
              ),
            ),
            if (_urls.length > 1)
              Positioned(
                top: 24,
                left: 0,
                right: 0,
                child: Center(
                  child: Text(
                    '${_index + 1} / ${_urls.length}',
                    style: PearlText.micro.copyWith(color: p.muted),
                  ),
                ),
              ),
            Positioned(
              right: 14,
              bottom: 18,
              child: _ZoomControls(
                canZoomIn: _scale < _maxScale - 0.01,
                canZoomOut: zoomed,
                onIn: () => _zoomBy(_step),
                onOut: () => _zoomBy(1 / _step),
                onReset: zoomed ? _reset : null,
              ),
            ),
            if (!zoomed)
              Positioned(
                bottom: 26,
                left: 0,
                right: 0,
                child: Center(
                  child: Text(
                    (_urls.length > 1
                            ? 'Pinch or double tap to zoom · swipe for the next photo'
                            : 'Pinch or double tap to zoom')
                        .toUpperCase(),
                    style: PearlText.micro.copyWith(fontSize: 8.5, color: p.faint),
                  ),
                ),
              ),
          ],
        ),
      ),
    );
  }

  Widget _stage({required bool zoomed}) {
    return LayoutBuilder(
      builder: (context, constraints) {
        // Cached so the button zoom can focus on the middle of the photo. The
        // screen's centre is not the same point — the safe area moves it.
        _viewport = constraints.biggest;
        return GestureDetector(
          onDoubleTapDown: (d) => _lastTap = d.localPosition,
          onDoubleTap: _onDoubleTap,
          child: PageView.builder(
            controller: _pages,
            // A drag while zoomed in belongs to the photo, not the pager.
            physics:
                zoomed ? const NeverScrollableScrollPhysics() : const PageScrollPhysics(),
            itemCount: _urls.length,
            onPageChanged: _showPage,
            itemBuilder: (context, i) => InteractiveViewer(
              // Only the visible page drives the shared controller; the others
              // are always fitted, so they need no state.
              transformationController: i == _index ? _zoom : null,
              minScale: _minScale,
              maxScale: _maxScale,
              // Decoded for a deep zoom, not the resting size, or zooming in
              // just magnifies a blurry decode. The product page warms this
              // exact size for the shot that was on screen, so the tap that
              // opened this usually lands on a photo that is already decoded.
              child: Photo(
                url: _urls[i],
                width: zoomDecodeWidth(context),
                padding: const EdgeInsets.all(20),
              ),
            ),
          ),
        );
      },
    );
  }
}

/// The buttons, for the hands that will not pinch.
class _ZoomControls extends StatelessWidget {
  const _ZoomControls({
    required this.canZoomIn,
    required this.canZoomOut,
    required this.onIn,
    required this.onOut,
    this.onReset,
  });

  final bool canZoomIn;
  final bool canZoomOut;
  final VoidCallback onIn;
  final VoidCallback onOut;
  final VoidCallback? onReset;

  @override
  Widget build(BuildContext context) {
    return Column(
      mainAxisSize: MainAxisSize.min,
      children: [
        if (onReset != null) ...[
          IconSquare(Icons.fit_screen_outlined, size: 40, filled: true, onTap: onReset),
          const SizedBox(height: 8),
        ],
        _Step(icon: Icons.add, enabled: canZoomIn, onTap: onIn),
        const SizedBox(height: 8),
        _Step(icon: Icons.remove, enabled: canZoomOut, onTap: onOut),
      ],
    );
  }
}

class _Step extends StatelessWidget {
  const _Step({required this.icon, required this.enabled, required this.onTap});

  final IconData icon;
  final bool enabled;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) => Opacity(
        // Kept in place rather than removed: controls that come and go under
        // the thumb are worse than ones that grey out.
        opacity: enabled ? 1 : .35,
        child: IconSquare(icon, size: 40, filled: true, onTap: enabled ? onTap : null),
      );
}
