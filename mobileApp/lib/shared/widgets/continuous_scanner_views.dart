part of 'continuous_scanner_screen.dart';

// The scanner's presentation layer — the permission/error state card, the
// start-up veil, the viewfinder reticle and its painters. Split out of
// continuous_scanner_screen.dart purely to keep that file readable; as a
// `part` these stay library-private and nothing else changed.

class _StateAction {
  const _StateAction(this.icon, this.label, this.onTap);
  final IconData? icon;
  final String label;
  final VoidCallback onTap;
}

/// Shared layout for every full-area camera state (permission primer, settings
/// guidance, start failure, unsupported): icon in a ring, serif title, calm
/// copy, optional numbered steps, one gradient primary action and quiet links.
class _CameraStateView extends StatelessWidget {
  const _CameraStateView({
    required this.p,
    required this.icon,
    required this.title,
    required this.message,
    required this.primary,
    this.steps,
    this.technical,
    this.links = const [],
  });

  final AstraPalette p;
  final IconData icon;
  final String title;
  final String message;
  final String? technical;
  final List<(int, String)>? steps;
  final _StateAction primary;
  final List<_StateAction> links;

  @override
  Widget build(BuildContext context) {
    return Container(
      color: const Color(0xFF111111),
      alignment: Alignment.center,
      child: SingleChildScrollView(
        padding: const EdgeInsets.fromLTRB(32, 110, 32, 32),
        child: ConstrainedBox(
          constraints: const BoxConstraints(maxWidth: 340),
          child: Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              Container(
                width: 72,
                height: 72,
                decoration: BoxDecoration(
                  color: p.accent.withValues(alpha: 0.12),
                  shape: BoxShape.circle,
                  border: Border.all(color: p.accent.withValues(alpha: 0.35), width: 1.2),
                ),
                child: Icon(icon, color: p.accent, size: 32),
              ),
              const SizedBox(height: 18),
              Text(title, style: serif(size: 21, color: Colors.white), textAlign: TextAlign.center),
              const SizedBox(height: 8),
              Text(message, textAlign: TextAlign.center, style: ui(size: 13, weight: FontWeight.w500, color: Colors.white60, height: 1.5)),
              // The raw platform reason ("Camera in use", "No available
              // camera"…) — small and muted, but it turns a screenshot of
              // this card into a diagnosable report.
              if (technical != null) ...[
                const SizedBox(height: 10),
                Text(
                  technical!,
                  textAlign: TextAlign.center,
                  maxLines: 3,
                  overflow: TextOverflow.ellipsis,
                  style: ui(size: 10.5, weight: FontWeight.w600, color: Colors.white38),
                ),
              ],
              // Permission needs concrete, OS-specific guidance — a wall of
              // prose isn't actionable, a numbered checklist is.
              if (steps != null) ...[
                const SizedBox(height: 18),
                Container(
                  width: double.infinity,
                  padding: const EdgeInsets.all(14),
                  decoration: BoxDecoration(
                    color: Colors.white.withValues(alpha: 0.06),
                    borderRadius: BorderRadius.circular(14),
                    border: Border.all(color: Colors.white.withValues(alpha: 0.1)),
                  ),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      for (final step in steps!) ...[
                        _stepRow(step.$1, step.$2),
                        if (step.$1 != steps!.length) const SizedBox(height: 10),
                      ],
                    ],
                  ),
                ),
              ],
              const SizedBox(height: 22),
              Semantics(
                button: true,
                label: primary.label,
                child: GestureDetector(
                  onTap: primary.onTap,
                  child: Container(
                    width: double.infinity,
                    constraints: const BoxConstraints(minHeight: 50),
                    alignment: Alignment.center,
                    decoration: BoxDecoration(gradient: p.accentGradient, borderRadius: BorderRadius.circular(15)),
                    child: Row(
                      mainAxisSize: MainAxisSize.min,
                      children: [
                        if (primary.icon != null) ...[
                          Icon(primary.icon, size: 17, color: p.primaryDark),
                          const SizedBox(width: 8),
                        ],
                        Text(primary.label, style: ui(size: 14, weight: FontWeight.w800, color: p.primaryDark)),
                      ],
                    ),
                  ),
                ),
              ),
              const SizedBox(height: 6),
              for (final link in links)
                Semantics(
                  button: true,
                  label: link.label,
                  child: GestureDetector(
                    behavior: HitTestBehavior.opaque,
                    onTap: link.onTap,
                    child: Padding(
                      padding: const EdgeInsets.symmetric(vertical: 12),
                      child: Text(
                        link.label,
                        style: ui(
                          size: 13,
                          weight: FontWeight.w700,
                          color: link == links.last ? Colors.white70 : p.accent,
                        ),
                      ),
                    ),
                  ),
                ),
            ],
          ),
        ),
      ),
    );
  }

  Widget _stepRow(int n, String text) {
    return Row(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Container(
          width: 22,
          height: 22,
          alignment: Alignment.center,
          decoration: BoxDecoration(color: p.accent.withValues(alpha: 0.2), borderRadius: BorderRadius.circular(7)),
          child: Text('$n', style: ui(size: 11, weight: FontWeight.w800, color: p.accent)),
        ),
        const SizedBox(width: 10),
        Expanded(child: Text(text, style: ui(size: 12.5, weight: FontWeight.w600, color: Colors.white, height: 1.4))),
      ],
    );
  }
}

/// Calm placeholder while the camera is coming up — a spinner ring around a
/// camera glyph, no alarming copy.
class _StartingVeil extends StatelessWidget {
  const _StartingVeil();

  @override
  Widget build(BuildContext context) {
    return Container(
      color: Colors.black,
      alignment: Alignment.center,
      child: Column(
        mainAxisSize: MainAxisSize.min,
        children: [
          SizedBox(
            width: 64,
            height: 64,
            child: Stack(
              alignment: Alignment.center,
              children: [
                const SizedBox(
                  width: 64,
                  height: 64,
                  child: CircularProgressIndicator(strokeWidth: 1.6, color: Colors.white38),
                ),
                Container(
                  width: 46,
                  height: 46,
                  decoration: BoxDecoration(color: Colors.white.withValues(alpha: 0.08), shape: BoxShape.circle),
                  child: const Icon(Icons.photo_camera_outlined, color: Colors.white70, size: 20),
                ),
              ],
            ),
          ),
          const SizedBox(height: 16),
          Text('Starting camera…', style: ui(size: 12.5, weight: FontWeight.w600, color: Colors.white60, letterSpacing: 0.2)),
        ],
      ),
    );
  }
}

// ── VIEWFINDER ────────────────────────────────────────────────────────────────

/// Dark scrim with a transparent window, premium corner brackets, a sweeping
/// scan line, a helper caption — and a colour pulse on every scan result so
/// the user never has to look away from the shelf to know it registered.
class _ReticleOverlay extends StatelessWidget {
  const _ReticleOverlay({
    required this.accent,
    required this.scanLine,
    required this.pulse,
    required this.pulseColor,
  });

  final Color accent;
  final Animation<double> scanLine;
  final Animation<double> pulse;
  final Color pulseColor;

  @override
  Widget build(BuildContext context) {
    return LayoutBuilder(
      builder: (context, c) {
        // Wide and short — the right window for 1D retail barcodes.
        final w = c.maxWidth * 0.8;
        final h = w * 0.52;
        final centerY = c.maxHeight * 0.42;
        final top = centerY - h / 2;
        final left = (c.maxWidth - w) / 2;
        return IgnorePointer(
          child: AnimatedBuilder(
            animation: Listenable.merge([scanLine, pulse]),
            builder: (_, __) {
              // pulse rests at 1; forward(from: 0) plays a decaying flash.
              final flash = Curves.easeOutCubic.transform(1 - pulse.value);
              final frameColor = Color.lerp(accent, pulseColor, flash)!;
              return Stack(
                children: [
                  Positioned.fill(
                    child: CustomPaint(
                      painter: _ScrimPainter(windowWidth: w, windowHeight: h, centerYFraction: 0.42),
                    ),
                  ),
                  Positioned(
                    left: left,
                    top: top,
                    width: w,
                    height: h,
                    child: Stack(
                      clipBehavior: Clip.none,
                      children: [
                        // Result-flash glow around the window.
                        if (flash > 0.01)
                          Positioned.fill(
                            child: DecoratedBox(
                              decoration: BoxDecoration(
                                borderRadius: BorderRadius.circular(22),
                                boxShadow: [
                                  BoxShadow(color: frameColor.withValues(alpha: 0.55 * flash), blurRadius: 34, spreadRadius: 3),
                                ],
                              ),
                            ),
                          ),
                        // Hairline window edge + corner brackets.
                        Positioned.fill(
                          child: DecoratedBox(
                            decoration: BoxDecoration(
                              borderRadius: BorderRadius.circular(22),
                              border: Border.all(color: Colors.white.withValues(alpha: 0.18), width: 1),
                            ),
                          ),
                        ),
                        Positioned.fill(
                          child: CustomPaint(painter: _BracketsPainter(color: frameColor)),
                        ),
                        // Sweeping scan line.
                        ClipRRect(
                          borderRadius: BorderRadius.circular(22),
                          child: SizedBox(
                            width: w,
                            height: h,
                            child: Stack(
                              children: [
                                Positioned(
                                  left: 12,
                                  right: 12,
                                  top: 10 + scanLine.value * (h - 22),
                                  child: Container(
                                    height: 2.5,
                                    decoration: BoxDecoration(
                                      gradient: LinearGradient(colors: [Colors.transparent, frameColor, Colors.transparent]),
                                      boxShadow: [BoxShadow(color: frameColor.withValues(alpha: 0.7), blurRadius: 14)],
                                    ),
                                  ),
                                ),
                              ],
                            ),
                          ),
                        ),
                      ],
                    ),
                  ),
                  // Helper caption under the window.
                  Positioned(
                    left: 0,
                    right: 0,
                    top: top + h + 16,
                    child: Center(
                      child: Container(
                        padding: const EdgeInsets.symmetric(horizontal: 13, vertical: 7),
                        decoration: BoxDecoration(
                          color: Colors.black.withValues(alpha: 0.45),
                          borderRadius: BorderRadius.circular(99),
                          border: Border.all(color: Colors.white.withValues(alpha: 0.12), width: 0.5),
                        ),
                        child: Text(
                          'Align the barcode inside the frame',
                          style: ui(size: 11.5, weight: FontWeight.w600, color: Colors.white.withValues(alpha: 0.85)),
                        ),
                      ),
                    ),
                  ),
                ],
              );
            },
          ),
        );
      },
    );
  }
}

/// Four rounded corner brackets — reads "viewfinder" without boxing the whole
/// window in a heavy border.
class _BracketsPainter extends CustomPainter {
  _BracketsPainter({required this.color});
  final Color color;

  static const _len = 26.0;
  static const _radius = 22.0;
  static const _stroke = 3.4;

  @override
  void paint(Canvas canvas, Size size) {
    final paint = Paint()
      ..color = color
      ..style = PaintingStyle.stroke
      ..strokeWidth = _stroke
      ..strokeCap = StrokeCap.round;

    Path corner() => Path()
      ..moveTo(0, _radius + _len)
      ..lineTo(0, _radius)
      ..quadraticBezierTo(0, 0, _radius, 0)
      ..lineTo(_radius + _len, 0);

    void draw(double dx, double dy, double rotation) {
      canvas.save();
      canvas.translate(dx, dy);
      canvas.rotate(rotation);
      canvas.drawPath(corner(), paint);
      canvas.restore();
    }

    const quarter = 1.5707963267948966; // pi / 2
    draw(0, 0, 0); // top-left
    draw(size.width, 0, quarter); // top-right
    draw(size.width, size.height, 2 * quarter); // bottom-right
    draw(0, size.height, 3 * quarter); // bottom-left
  }

  @override
  bool shouldRepaint(covariant _BracketsPainter old) => old.color != color;
}

class _ScrimPainter extends CustomPainter {
  _ScrimPainter({required this.windowWidth, required this.windowHeight, required this.centerYFraction});
  final double windowWidth;
  final double windowHeight;
  final double centerYFraction;

  @override
  void paint(Canvas canvas, Size size) {
    final rect = Rect.fromCenter(center: Offset(size.width / 2, size.height * centerYFraction), width: windowWidth, height: windowHeight);
    final window = RRect.fromRectAndRadius(rect, const Radius.circular(22));
    final scrim = Path()..addRect(Offset.zero & size);
    final hole = Path()..addRRect(window);
    final cut = Path.combine(PathOperation.difference, scrim, hole);
    canvas.drawPath(cut, Paint()..color = Colors.black.withValues(alpha: 0.55));
  }

  @override
  bool shouldRepaint(covariant _ScrimPainter old) =>
      old.windowWidth != windowWidth || old.windowHeight != windowHeight || old.centerYFraction != centerYFraction;
}
