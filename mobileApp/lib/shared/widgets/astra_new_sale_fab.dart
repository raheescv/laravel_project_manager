import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';

import 'package:invo/shared/domain/constants/global_variables.dart';
import 'package:invo/shared/utils/components/theme/index.dart';
import 'package:invo/shared/utils/local_storage/local_storage_service.dart';
import 'package:invo/shared/utils/router/routes.dart';

/// The tablet's New Sale button — it used to be the gold "+" at the foot of the
/// rail, and now floats over the content so the cashier can park it wherever
/// their hand already is (preview: `docs/mobile-tablet-rail-fab-preview.html`,
/// direction C · Ink Notch).
///
/// Tap starts a sale. Drag moves it; on release it springs to the nearer
/// left/right edge. The rest is saved as an edge plus a 0–1 fraction of the
/// free height rather than raw pixels, so a rotation or the keyboard shrinking
/// the content puts it back on screen at the same relative spot.
///
/// Fills its parent [Stack] but only the button itself takes hits — the empty
/// area passes taps through to the page underneath.
class AstraNewSaleFab extends StatefulWidget {
  const AstraNewSaleFab({super.key});

  /// Outer box, halo ring included.
  static const double size = 70;
  static const double gutter = 18;

  @override
  State<AstraNewSaleFab> createState() => _AstraNewSaleFabState();
}

class _AstraNewSaleFabState extends State<AstraNewSaleFab> {
  final _storage = serviceLocator<LocalStorageService>();
  bool _left = false;
  double _y = 1;

  /// Live top-left while a drag is in progress; null at rest.
  Offset? _drag;
  Size _area = Size.zero;

  @override
  void initState() {
    super.initState();
    final saved = _storage.railFabPosition?.split(':');
    if (saved != null && saved.length == 2) {
      _left = saved[0] == 'left';
      _y = (double.tryParse(saved[1]) ?? 1).clamp(0.0, 1.0);
    }
  }

  double get _freeHeight =>
      (_area.height - AstraNewSaleFab.size - AstraNewSaleFab.gutter * 2).clamp(0.0, double.infinity);

  Offset get _rest => Offset(
        _left ? AstraNewSaleFab.gutter : _area.width - AstraNewSaleFab.size - AstraNewSaleFab.gutter,
        AstraNewSaleFab.gutter + _y * _freeHeight,
      );

  void _onPanUpdate(DragUpdateDetails d) {
    final next = (_drag ?? _rest) + d.delta;
    setState(() => _drag = Offset(
          next.dx.clamp(6.0, (_area.width - AstraNewSaleFab.size - 6).clamp(6.0, double.infinity)),
          next.dy.clamp(6.0, (_area.height - AstraNewSaleFab.size - 6).clamp(6.0, double.infinity)),
        ));
  }

  void _onPanEnd(DragEndDetails _) {
    final at = _drag;
    if (at == null) return;
    setState(() {
      _left = at.dx + AstraNewSaleFab.size / 2 < _area.width / 2;
      _y = _freeHeight == 0 ? 1 : ((at.dy - AstraNewSaleFab.gutter) / _freeHeight).clamp(0.0, 1.0);
      _drag = null;
    });
    _storage.setRailFabPosition('${_left ? 'left' : 'right'}:${_y.toStringAsFixed(3)}');
  }

  @override
  Widget build(BuildContext context) {
    final p = context.astra;
    return Positioned.fill(
      child: LayoutBuilder(builder: (context, c) {
        _area = c.biggest;
        final at = _drag ?? _rest;
        final dragging = _drag != null;
        return Stack(
          children: [
            AnimatedPositioned(
              // Follows the finger 1:1 while dragging; springs only on release.
              duration: dragging ? Duration.zero : const Duration(milliseconds: 320),
              curve: Curves.easeOutBack,
              left: at.dx,
              top: at.dy,
              child: Semantics(
                button: true,
                label: 'New Sale',
                child: GestureDetector(
                  onTap: () => context.push(Routes.sale),
                  onPanStart: (_) => setState(() => _drag = _rest),
                  onPanUpdate: _onPanUpdate,
                  onPanEnd: _onPanEnd,
                  onPanCancel: () => setState(() => _drag = null),
                  child: AnimatedScale(
                    scale: dragging ? 1.08 : 1,
                    duration: const Duration(milliseconds: 150),
                    child: _button(p, dragging),
                  ),
                ),
              ),
            ),
          ],
        );
      }),
    );
  }

  Widget _button(AstraPalette p, bool dragging) => Container(
        width: AstraNewSaleFab.size,
        height: AstraNewSaleFab.size,
        padding: const EdgeInsets.all(5),
        decoration: BoxDecoration(
          borderRadius: BorderRadius.circular(24),
          border: Border.all(color: p.accent.withValues(alpha: 0.45), width: 1.5),
        ),
        child: Container(
          decoration: BoxDecoration(
            gradient: p.accentGradient,
            borderRadius: BorderRadius.circular(19),
            boxShadow: [
              BoxShadow(
                color: Color.lerp(p.accent, Colors.black, 0.3)!.withValues(alpha: dragging ? 0.55 : 0.45),
                blurRadius: dragging ? 30 : 22,
                offset: Offset(0, dragging ? 16 : 10),
              ),
            ],
          ),
          child: Icon(Icons.add, size: 28, color: p.primaryDark),
        ),
      );
}
