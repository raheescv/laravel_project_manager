import 'dart:math' as math;
import 'package:flutter/material.dart';

import 'package:invo/shared/utils/components/theme/index.dart';

/// Smooth area + line trend chart (revenue trend / today vs last week).
class AreaTrendChart extends StatelessWidget {
  const AreaTrendChart({super.key, required this.values, this.height = 80, this.fill, this.stroke, this.dot});
  final List<double> values;
  final double height;
  final Color? fill;
  final Color? stroke;
  final Color? dot;

  @override
  Widget build(BuildContext context) {
    final p = context.astra;
    return SizedBox(
      height: height,
      width: double.infinity,
      child: CustomPaint(
        painter: _AreaPainter(values, fill ?? p.primary, stroke ?? p.primaryDark, dot ?? p.accent),
      ),
    );
  }
}

class _AreaPainter extends CustomPainter {
  _AreaPainter(this.values, this.fill, this.stroke, this.dot);
  final List<double> values;
  final Color fill, stroke, dot;

  @override
  void paint(Canvas canvas, Size size) {
    if (values.length < 2) return;
    final maxV = values.reduce(math.max);
    final minV = values.reduce(math.min);
    final range = (maxV - minV).abs() < 1e-6 ? 1 : (maxV - minV);
    final dx = size.width / (values.length - 1);

    Offset pt(int i) => Offset(
          i * dx,
          size.height - ((values[i] - minV) / range) * (size.height * 0.82) - size.height * 0.08,
        );

    final path = Path()..moveTo(pt(0).dx, pt(0).dy);
    for (var i = 0; i < values.length - 1; i++) {
      final c = pt(i), n = pt(i + 1);
      final midX = (c.dx + n.dx) / 2;
      path.cubicTo(midX, c.dy, midX, n.dy, n.dx, n.dy);
    }

    final area = Path.from(path)
      ..lineTo(size.width, size.height)
      ..lineTo(0, size.height)
      ..close();
    canvas.drawPath(
      area,
      Paint()
        ..shader = LinearGradient(
          begin: Alignment.topCenter,
          end: Alignment.bottomCenter,
          colors: [fill.withValues(alpha: 0.40), fill.withValues(alpha: 0)],
        ).createShader(Offset.zero & size),
    );
    canvas.drawPath(
      path,
      Paint()
        ..color = stroke
        ..style = PaintingStyle.stroke
        ..strokeWidth = 2.5
        ..strokeCap = StrokeCap.round,
    );
    final last = pt(values.length - 1);
    canvas.drawCircle(last, 4, Paint()..color = dot);
    canvas.drawCircle(last, 4, Paint()..color = Colors.white..style = PaintingStyle.stroke..strokeWidth = 2);
  }

  @override
  bool shouldRepaint(covariant _AreaPainter old) => old.values != values;
}

class DonutSlice {
  DonutSlice(this.value, this.color, this.label);
  final double value;
  final Color color;
  final String label;
}

/// Donut chart with a centred total label (by category / payment).
class DonutChart extends StatelessWidget {
  const DonutChart({super.key, required this.slices, required this.centerTop, required this.centerBottom, this.size = 120});
  final List<DonutSlice> slices;
  final String centerTop;
  final String centerBottom;
  final double size;

  @override
  Widget build(BuildContext context) {
    final p = context.astra;
    return SizedBox(
      width: size,
      height: size,
      child: Stack(
        alignment: Alignment.center,
        children: [
          CustomPaint(size: Size.square(size), painter: _DonutPainter(slices, p.hairline)),
          Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              Text(centerTop, style: serif(size: size * 0.15, color: p.ink)),
              Text(centerBottom,
                  style: ui(size: size * 0.07, weight: FontWeight.w800, color: p.textMuted, letterSpacing: 0.5)),
            ],
          ),
        ],
      ),
    );
  }
}

class _DonutPainter extends CustomPainter {
  _DonutPainter(this.slices, this.track);
  final List<DonutSlice> slices;
  final Color track;

  @override
  void paint(Canvas canvas, Size size) {
    final stroke = size.width * 0.12;
    final rect = Rect.fromCircle(
      center: size.center(Offset.zero),
      radius: size.width / 2 - stroke / 2,
    );
    canvas.drawArc(rect, 0, math.pi * 2,
        false, Paint()..color = track..style = PaintingStyle.stroke..strokeWidth = stroke);

    final total = slices.fold<double>(0, (a, s) => a + s.value);
    if (total <= 0) return;
    var start = -math.pi / 2;
    const gap = 0.04;
    for (final s in slices) {
      final sweep = (s.value / total) * (math.pi * 2) - gap;
      if (sweep <= 0) continue;
      canvas.drawArc(
        rect,
        start,
        sweep,
        false,
        Paint()
          ..color = s.color
          ..style = PaintingStyle.stroke
          ..strokeWidth = stroke
          ..strokeCap = StrokeCap.round,
      );
      start += (s.value / total) * (math.pi * 2);
    }
  }

  @override
  bool shouldRepaint(covariant _DonutPainter old) => old.slices != slices;
}

class BarDatum {
  BarDatum(this.label, this.value, {this.peak = false});
  final String label;
  final double value;
  final bool peak;
}

/// Vertical bar chart (gross sales by day).
class BarChart extends StatelessWidget {
  const BarChart({super.key, required this.data, this.height = 104});
  final List<BarDatum> data;
  final double height;

  @override
  Widget build(BuildContext context) {
    final p = context.astra;
    final maxV = data.isEmpty ? 1.0 : data.map((d) => d.value).reduce(math.max);
    return SizedBox(
      height: height,
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.end,
        children: [
          for (final d in data)
            Expanded(
              child: Padding(
                padding: const EdgeInsets.symmetric(horizontal: 3),
                child: Column(
                  mainAxisAlignment: MainAxisAlignment.end,
                  children: [
                    Container(
                      height: math.max(6, (d.value / (maxV == 0 ? 1 : maxV)) * (height - 22)),
                      decoration: BoxDecoration(
                        gradient: LinearGradient(
                          begin: Alignment.topCenter,
                          end: Alignment.bottomCenter,
                          colors: d.peak
                              ? [p.primary, p.accent]
                              : [p.primary.withValues(alpha: 0.75), p.primary.withValues(alpha: 0.4)],
                        ),
                        borderRadius: const BorderRadius.vertical(top: Radius.circular(6), bottom: Radius.circular(3)),
                      ),
                    ),
                    const SizedBox(height: 6),
                    Text(d.label, style: ui(size: 9, weight: FontWeight.w700, color: p.textMuted)),
                  ],
                ),
              ),
            ),
        ],
      ),
    );
  }
}

/// Thin progress bar (emerald→gold) for ranked lists.
class ProgressBar extends StatelessWidget {
  const ProgressBar({super.key, required this.fraction, this.height = 5, this.color});
  final double fraction;
  final double height;

  /// Fill colour; defaults to the theme primary when omitted.
  final Color? color;
  @override
  Widget build(BuildContext context) {
    final p = context.astra;
    return ClipRRect(
      borderRadius: BorderRadius.circular(height),
      child: LinearProgressIndicator(
        value: fraction.clamp(0, 1),
        minHeight: height,
        backgroundColor: p.isDark ? Colors.white12 : const Color(0xFFEEF0EA),
        valueColor: AlwaysStoppedAnimation(color ?? p.primary),
      ),
    );
  }
}
