import 'dart:ui' as ui;

import 'package:flutter/material.dart';

import 'package:invo/shared/utils/components/theme/index.dart';

/// The **QLOUD POS** logomark — a cloud outline cradling a gradient "Q", its
/// tail cutting out through the ring at 45°.
///
/// Faithful vector port of the brand artwork (`assets/brand/qloud_mark.svg`);
/// the geometry below is in the artwork's own coordinate space (the mark
/// occupies 161,161 → 695,570) and is scaled to fit whatever box is asked for.
/// Vector rather than a bitmap so it stays crisp from a 24px app-bar mark up to
/// the 320px login watermark, and so it can be re-inked per theme.
///
/// The gradients are derived from the active preset rather than hard-coded, so
/// the mark wears whatever palette Settings → Theme is on. Under **Qloud Blue**
/// — the default, itself sampled off the artwork — that reproduces the brand
/// colours almost exactly; under the other presets it re-inks to match instead
/// of clashing. The launcher icons and `assets/brand/qloud_mark.svg` stay true
/// brand blue: those live outside the app's theme.
class QloudLogomark extends StatelessWidget {
  const QloudLogomark({
    super.key,
    this.height = 40,
    this.color,
    this.palette,
    this.onDark = false,
  });

  /// Rendered height in logical pixels; width scales to the 534:409 artwork.
  final double height;

  /// Optional flat colour (e.g. white for a watermark). Wins over [palette].
  final Color? color;

  /// Palette to ink the mark with. Defaults to the active preset; pass
  /// [AstraPresets.qloudBlue] to force true brand colours on any theme.
  final AstraPalette? palette;

  /// Set on a dark brand surface — the side rail, a hero panel, anything
  /// painted `p.darkSurface`. On a light preset that surface *is* `primaryDark`,
  /// which is also the mark's deepest ink, so the Q sinks into the panel. This
  /// shifts the whole ramp one step brighter: the mark keeps the preset's hue
  /// but reads as a highlight on top of it rather than a hole in it.
  final bool onDark;

  @override
  Widget build(BuildContext context) {
    return SizedBox(
      width: height * _vbW / _vbH,
      height: height,
      child: CustomPaint(
        painter: _QloudPainter(
            color, color == null ? (palette ?? context.astra) : null, onDark),
      ),
    );
  }
}

// Artwork bounds, in the source coordinate space.
const double _vbX = 161, _vbY = 161, _vbW = 534, _vbH = 409;

class _QloudPainter extends CustomPainter {
  _QloudPainter(this.color, this.palette, this.onDark);
  final Color? color;
  final AstraPalette? palette;
  final bool onDark;

  static Path _circle(double cx, double cy, double r) =>
      Path()..addOval(Rect.fromCircle(center: Offset(cx, cy), radius: r));

  static Path _poly(List<Offset> pts) => Path()..addPolygon(pts, true);

  static Path _union(Path a, Path b) => Path.combine(PathOperation.union, a, b);
  static Path _minus(Path a, Path b) => Path.combine(PathOperation.difference, a, b);

  static Color _mix(Color a, Color b, double t) => Color.lerp(a, b, t)!;

  @override
  void paint(Canvas canvas, Size size) {
    canvas.save();
    canvas.scale(size.width / _vbW, size.height / _vbH);
    canvas.translate(-_vbX, -_vbY);

    // ---- Cloud: an outline, i.e. the union of three lobes minus the same
    // union inset, then knocked out around the Q so the two never touch.
    final cloudOuter = _union(
        _union(_union(_circle(455, 324, 163), _circle(266, 388, 105)),
            _circle(592, 390, 103)),
        Path()..addRect(const Rect.fromLTRB(266, 324, 592, 493)));
    final cloudInner = _union(
        _union(_union(_circle(448, 339, 137), _circle(305, 383, 94)),
            _circle(592, 400, 74)),
        Path()..addRect(const Rect.fromLTRB(305, 339, 592, 474)));
    final cloud = _minus(_minus(cloudOuter, cloudInner), _circle(433, 425, 164));

    // ---- Q ring: an annulus, cut by the tail's knockout so the tail reads as
    // passing through it rather than sitting on top.
    final ring = _minus(
        _minus(_circle(433, 425, 147), _circle(433, 425, 89)),
        _poly(const [Offset(388, 400), Offset(460, 400), Offset(650, 590), Offset(578, 590)]));

    // ---- Tail: a 45° parallelogram. Drawn inset and re-stroked with a round
    // join, which is how the artwork's corner radius is reproduced.
    final tail = _poly(const [
      Offset(415, 449),
      Offset(475, 449),
      Offset(602, 564),
      Offset(542, 564),
    ]);

    void strokedTail(Paint fill) {
      canvas.drawPath(tail, fill);
      canvas.drawPath(tail, Paint()
        ..isAntiAlias = true
        ..color = fill.color
        ..shader = fill.shader
        ..style = PaintingStyle.stroke
        ..strokeWidth = 12
        ..strokeJoin = StrokeJoin.round);
    }

    final flat = color;
    if (flat != null) {
      final p = Paint()..isAntiAlias = true..color = flat;
      canvas.drawPath(cloud, p);
      canvas.drawPath(ring, p);
      strokedTail(p);
      canvas.restore();
      return;
    }

    // Three inks pulled off the palette: the bright accent, the mid brand
    // colour, and the deep one. On a dark preset the deep ink is lifted toward
    // white — unlifted, the Q's shadowed side disappears into a dark card.
    final pal = palette!;
    final Color bright, mid, deep;
    if (onDark) {
      // Same hues, every stop lifted toward white, so the whole mark clears
      // the panel instead of only its accent edge showing. Tuned against every
      // preset: unlifted, `deep` IS the panel colour (contrast 1.00 — the Q's
      // shadowed side simply vanished); lifted, the worst case across the five
      // presets is ~2.9:1, and most sit between 4:1 and 6:1.
      bright = _mix(pal.accent, Colors.white, 0.62);
      mid = _mix(pal.accent, Colors.white, 0.35);
      deep = _mix(pal.primary, Colors.white, 0.50);
    } else {
      bright = pal.accent;
      mid = pal.primary;
      deep = pal.isDark ? _mix(pal.primaryDark, Colors.white, 0.30) : pal.primaryDark;
    }

    canvas.drawPath(cloud, Paint()
      ..isAntiAlias = true
      ..shader = ui.Gradient.linear(
          const Offset(190, 250), const Offset(690, 430),
          [bright, _mix(bright, mid, 0.55), _mix(mid, deep, 0.45)], const [0, 0.52, 1]));

    canvas.drawPath(ring, Paint()
      ..isAntiAlias = true
      ..shader = ui.Gradient.linear(
          const Offset(315, 525), const Offset(565, 330),
          [bright, mid, deep], const [0, 0.48, 1]));

    // The tail casts a soft shadow onto the ring it crosses — clipped to the
    // ring so it never spills onto the canvas.
    final shade = _mix(deep, Colors.black, 0.22);
    canvas.save();
    canvas.clipPath(ring);
    canvas.drawPath(
        _poly(const [Offset(409, 443), Offset(536, 570), Offset(492, 614), Offset(365, 487)]),
        Paint()
          ..isAntiAlias = true
          ..shader = ui.Gradient.linear(
              const Offset(409, 443), const Offset(365, 487),
              [shade.withValues(alpha: 0.85), shade.withValues(alpha: 0)]));
    canvas.restore();

    strokedTail(Paint()
      ..isAntiAlias = true
      ..shader = ui.Gradient.linear(
          const Offset(409, 443), const Offset(608, 570),
          [_mix(mid, deep, 0.10), _mix(mid, bright, 0.55), _mix(mid, deep, 0.12)],
          const [0, 0.5, 1]));

    canvas.restore();
  }

  @override
  bool shouldRepaint(covariant _QloudPainter old) =>
      old.color != color ||
      old.onDark != onDark ||
      old.palette?.primary != palette?.primary ||
      old.palette?.primaryDark != palette?.primaryDark ||
      old.palette?.accent != palette?.accent ||
      old.palette?.isDark != palette?.isDark;
}
