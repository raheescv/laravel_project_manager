import 'dart:math' as math;
import 'package:flutter/material.dart';

import 'package:invo/shared/domain/helpers/formatters.dart';
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
  BarDatum(this.label, this.value, {this.peak = false, this.detail});

  /// Short axis label under the bar (`Mon`, `3/8`).
  final String label;
  final double value;
  final bool peak;

  /// Full label for the readout strip (`Mon, 3 Aug 2026`); falls back to [label].
  final String? detail;
}

/// Vertical bar chart (gross sales by day).
///
/// Every bar carries its own value as a label pinned above it, so the chart is
/// readable without tapping anything; the dashed line is the period average, and
/// tapping a bar promotes it to the readout strip with the exact amount and its
/// full date. With more days than fit legibly the plot scrolls sideways instead
/// of squeezing the bars into unreadable slivers.
class BarChart extends StatefulWidget {
  const BarChart({
    super.key,
    required this.data,
    this.height = 132,
    this.showValues = true,
    this.showAverage = true,
    this.showReadout = false,
    this.minSlot = 44,
    this.valueLabel,
  });

  final List<BarDatum> data;

  /// Height of the plot block (bars + value labels + axis labels); the readout
  /// strip, when shown, sits above it.
  final double height;
  final bool showValues;
  final bool showAverage;

  /// Show the "peak / selected day" strip above the plot.
  final bool showReadout;

  /// Narrowest a bar slot may get before the plot starts scrolling sideways.
  final double minSlot;

  /// Formats the label above each bar; defaults to a compact, symbol-free number.
  final String Function(double value)? valueLabel;

  @override
  State<BarChart> createState() => _BarChartState();
}

class _BarChartState extends State<BarChart> {
  // Fixed rows so every bar starts from the same baseline no matter how wide
  // its value label is. _labelH clears the selected day's filled pill.
  static const _valueH = 14.0, _valueGap = 4.0, _labelH = 18.0, _labelGap = 6.0;

  int? _selected;

  @override
  void didUpdateWidget(covariant BarChart old) {
    super.didUpdateWidget(old);
    // A new range means new days — the held index would point at a different
    // day, so drop the selection rather than silently re-point it.
    final changed = old.data.length != widget.data.length ||
        (old.data.isNotEmpty && old.data.first.label != widget.data.first.label);
    if (changed) _selected = null;
  }

  String _fmt(double v) {
    final f = widget.valueLabel;
    return f != null ? f(v) : Money.compactPlain(v);
  }

  @override
  Widget build(BuildContext context) {
    final p = context.astra;
    final data = widget.data;
    if (data.isEmpty) return SizedBox(height: widget.height);

    final maxV = data.map((d) => d.value).reduce(math.max);
    final avg = data.fold<double>(0, (a, d) => a + d.value) / data.length;
    final plotH = widget.height - _labelH - _labelGap;
    final barMax = math.max(10.0, plotH - (widget.showValues ? _valueH + _valueGap : 0));
    // Only meaningful once the average sits clear of both the floor and the peak.
    final showAvg = widget.showAverage && maxV > 0 && data.length > 2 && avg > maxV * 0.08;

    final plot = LayoutBuilder(
      builder: (context, c) {
        final slot = math.max(widget.minSlot, c.maxWidth / data.length);
        final contentW = math.max(slot * data.length, c.maxWidth);
        final scrolls = contentW > c.maxWidth + 0.5;

        final board = SizedBox(
          width: contentW,
          height: widget.height,
          child: Stack(
            children: [
              // Baseline the bars stand on.
              Positioned(
                left: 0,
                right: 0,
                top: plotH - 1,
                child: Container(height: 1, color: p.hairline),
              ),
              if (showAvg)
                Positioned(
                  left: 0,
                  right: 0,
                  top: plotH - (avg / maxV) * barMax,
                  child: CustomPaint(
                    size: Size(contentW, 1),
                    painter: _DashPainter(p.textMuted.withValues(alpha: 0.45)),
                  ),
                ),
              Row(children: [for (var i = 0; i < data.length; i++) _slot(i, slot, maxV, plotH, barMax)]),
            ],
          ),
        );

        if (!scrolls) return board;
        return Stack(
          children: [
            SingleChildScrollView(
              scrollDirection: Axis.horizontal,
              physics: const BouncingScrollPhysics(),
              child: board,
            ),
            // Fade the trailing edge so it reads as "there is more this way".
            Positioned(
              top: 0,
              bottom: _labelH,
              right: 0,
              width: 22,
              child: IgnorePointer(
                child: DecoratedBox(
                  decoration: BoxDecoration(
                    gradient: LinearGradient(
                      colors: [p.card.withValues(alpha: 0), p.card],
                    ),
                  ),
                ),
              ),
            ),
          ],
        );
      },
    );

    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        if (widget.showReadout) ...[
          _readout(data, maxV, avg, showAvg),
          const SizedBox(height: 12),
        ],
        SizedBox(height: widget.height, child: plot),
      ],
    );
  }

  /// The strip above the plot: the tapped day, or the peak day until one is tapped.
  Widget _readout(List<BarDatum> data, double maxV, double avg, bool showAvg) {
    final p = context.astra;
    final i = _selected ?? data.indexWhere((d) => d.peak);
    final d = i >= 0 ? data[i] : data.last;
    final picked = _selected != null;
    final tone = picked ? p.primary : p.goldText;
    return Container(
      padding: const EdgeInsets.fromLTRB(11, 9, 11, 9),
      decoration: BoxDecoration(
        color: tone.withValues(alpha: p.isDark ? 0.14 : 0.09),
        borderRadius: BorderRadius.circular(12),
      ),
      child: Row(
        children: [
          Icon(picked ? Icons.touch_app_rounded : Icons.workspace_premium_rounded, size: 15, color: tone),
          const SizedBox(width: 8),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(picked ? 'Selected day' : 'Best day',
                    style: ui(size: 8.5, weight: FontWeight.w800, color: p.textMuted, letterSpacing: 0.6)),
                const SizedBox(height: 1),
                Text(d.detail ?? d.label,
                    maxLines: 1,
                    overflow: TextOverflow.ellipsis,
                    style: ui(size: 12, weight: FontWeight.w800, color: p.ink)),
              ],
            ),
          ),
          const SizedBox(width: 8),
          Column(
            crossAxisAlignment: CrossAxisAlignment.end,
            children: [
              Text(Money.of(d.value), style: ui(size: 13, weight: FontWeight.w900, color: tone)),
              if (showAvg)
                Text('avg ${Money.compact(avg)}',
                    style: ui(size: 9, weight: FontWeight.w700, color: p.textMuted)),
            ],
          ),
        ],
      ),
    );
  }

  /// One day: value label, bar (on its own faint rail), axis label — the whole
  /// column is the tap target so short bars are still easy to hit.
  Widget _slot(int i, double slot, double maxV, double plotH, double barMax) {
    final p = context.astra;
    final d = widget.data[i];
    final selected = _selected == i;
    final dimmed = _selected != null && !selected;
    final frac = maxV <= 0 ? 0.0 : (d.value / maxV);
    final barW = math.min(30.0, math.max(9.0, slot - 12));
    final hot = selected || (d.peak && _selected == null);
    final tone = selected ? p.primary : (d.peak ? p.accent : p.primary);

    final fill = hot
        ? [Color.lerp(tone, Colors.white, 0.18)!, tone]
        : [
            p.primary.withValues(alpha: dimmed ? 0.32 : 0.70),
            p.primary.withValues(alpha: dimmed ? 0.16 : 0.38),
          ];

    return GestureDetector(
      behavior: HitTestBehavior.opaque,
      onTap: () => setState(() => _selected = selected ? null : i),
      child: SizedBox(
        width: slot,
        child: Column(
          mainAxisAlignment: MainAxisAlignment.end,
          children: [
            SizedBox(
              height: plotH,
              child: Stack(
                alignment: Alignment.bottomCenter,
                children: [
                  // Rail: the full-scale column each bar is measured against.
                  Container(
                    width: barW,
                    height: plotH - 1,
                    decoration: BoxDecoration(
                      color: p.hairline.withValues(alpha: selected ? 0.9 : 0.35),
                      borderRadius: const BorderRadius.vertical(top: Radius.circular(7), bottom: Radius.circular(3)),
                    ),
                  ),
                  Column(
                    mainAxisAlignment: MainAxisAlignment.end,
                    children: [
                      if (widget.showValues) ...[
                        SizedBox(
                          height: _valueH,
                          child: FittedBox(
                            fit: BoxFit.scaleDown,
                            child: Container(
                              padding: hot ? const EdgeInsets.symmetric(horizontal: 5, vertical: 1) : EdgeInsets.zero,
                              decoration: hot
                                  ? BoxDecoration(
                                      color: tone.withValues(alpha: p.isDark ? 0.24 : 0.14),
                                      borderRadius: BorderRadius.circular(6),
                                    )
                                  : null,
                              child: Text(
                                _fmt(d.value),
                                style: ui(
                                  size: 9.5,
                                  weight: hot ? FontWeight.w900 : FontWeight.w700,
                                  color: hot ? (selected ? p.primary : p.goldText) : p.textSecondary,
                                ),
                              ),
                            ),
                          ),
                        ),
                        const SizedBox(height: _valueGap),
                      ],
                      AnimatedContainer(
                        duration: const Duration(milliseconds: 220),
                        curve: Curves.easeOutCubic,
                        width: barW,
                        height: math.max(4, frac * barMax),
                        decoration: BoxDecoration(
                          gradient: LinearGradient(
                            begin: Alignment.topCenter,
                            end: Alignment.bottomCenter,
                            colors: fill,
                          ),
                          borderRadius: const BorderRadius.vertical(top: Radius.circular(7), bottom: Radius.circular(3)),
                          boxShadow: hot
                              ? [BoxShadow(color: tone.withValues(alpha: 0.32), blurRadius: 10, offset: const Offset(0, 4))]
                              : null,
                        ),
                      ),
                    ],
                  ),
                ],
              ),
            ),
            const SizedBox(height: _labelGap),
            SizedBox(
              height: _labelH,
              child: Center(
                child: Container(
                  padding: selected ? const EdgeInsets.symmetric(horizontal: 6, vertical: 2) : EdgeInsets.zero,
                  decoration: selected
                      ? BoxDecoration(color: p.primary, borderRadius: BorderRadius.circular(7))
                      : null,
                  child: Text(
                    d.label,
                    maxLines: 1,
                    overflow: TextOverflow.ellipsis,
                    textAlign: TextAlign.center,
                    style: ui(
                      size: 9.5,
                      weight: hot ? FontWeight.w900 : FontWeight.w700,
                      color: selected
                          ? Colors.white
                          : (d.peak && _selected == null ? p.goldText : p.textMuted),
                    ),
                  ),
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }
}

class _DashPainter extends CustomPainter {
  _DashPainter(this.color);
  final Color color;

  @override
  void paint(Canvas canvas, Size size) {
    final paint = Paint()
      ..color = color
      ..strokeWidth = 1
      ..strokeCap = StrokeCap.round;
    const dash = 4.0, gap = 4.0;
    for (var x = 0.0; x < size.width; x += dash + gap) {
      canvas.drawLine(Offset(x, 0), Offset(math.min(x + dash, size.width), 0), paint);
    }
  }

  @override
  bool shouldRepaint(covariant _DashPainter old) => old.color != color;
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
