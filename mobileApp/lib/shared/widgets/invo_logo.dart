import 'package:flutter/material.dart';

/// The **Invo** logomark — a high-contrast gold serif "i" crowned with the
/// signature sparkle dot (design: "Invo - Logomark (i)", treatment 01 ·
/// Serif Sparkle). Tile-less; renders on light or dark.
///
/// Faithful vector port of the source SVG (viewBox 120×184).
class InvoLogomark extends StatelessWidget {
  const InvoLogomark({super.key, this.height = 40, this.color});

  /// Rendered height in logical pixels; width scales to the 120:184 viewBox.
  final double height;

  /// Optional flat colour (e.g. white for a watermark). Null = gold gradient.
  final Color? color;

  @override
  Widget build(BuildContext context) {
    return SizedBox(
      width: height * 120 / 184,
      height: height,
      child: CustomPaint(painter: _InvoPainter(color)),
    );
  }
}

class _InvoPainter extends CustomPainter {
  _InvoPainter(this.color);
  final Color? color;

  @override
  void paint(Canvas canvas, Size size) {
    canvas.save();
    canvas.scale(size.width / 120, size.height / 184);

    final paint = Paint()..isAntiAlias = true;
    if (color != null) {
      paint.color = color!;
    } else {
      paint.shader = const LinearGradient(
        begin: Alignment.topLeft,
        end: Alignment(-0.5, 1), // matches the source gradient (x2=0.25, y2=1)
        colors: [Color(0xFFF9ECC4), Color(0xFFE4C474), Color(0xFFBF943B)],
        stops: [0, 0.5, 1],
      ).createShader(const Rect.fromLTWH(0, 0, 120, 184));
    }

    // Sparkle "dot" — a 4-point star centred at (60, 36).
    const cx = 60.0, cy = 36.0;
    final sparkle = Path()
      ..moveTo(cx, cy - 13)
      ..cubicTo(cx + 2, cy - 4.6, cx + 4.6, cy - 2, cx + 13, cy)
      ..cubicTo(cx + 4.6, cy + 2, cx + 2, cy + 4.6, cx, cy + 13)
      ..cubicTo(cx - 2, cy + 4.6, cx - 4.6, cy + 2, cx - 13, cy)
      ..cubicTo(cx - 4.6, cy - 2, cx - 2, cy - 4.6, cx, cy - 13)
      ..close();
    canvas.drawPath(sparkle, paint);

    // Stem + top serif + bottom serif (rounded rects).
    RRect rr(double x, double y, double w, double h, double r) =>
        RRect.fromRectAndRadius(Rect.fromLTWH(x, y, w, h), Radius.circular(r));
    canvas.drawRRect(rr(53.5, 64, 13, 86, 2.2), paint); // stem
    canvas.drawRRect(rr(44, 62, 32, 4.8, 2.4), paint); // top serif
    canvas.drawRRect(rr(39.5, 147, 41, 5.6, 2.8), paint); // bottom serif

    canvas.restore();
  }

  @override
  bool shouldRepaint(covariant _InvoPainter old) => old.color != color;
}
