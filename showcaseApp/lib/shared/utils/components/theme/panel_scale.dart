import 'dart:math' as math;

import 'package:flutter/widgets.dart';

/// Draws the whole app at the size the panel it is standing on deserves.
///
/// Every number in this design system — a 38pt control, a 9.5pt eyebrow, 22pt
/// of page padding, a 1pt hairline — was drawn against a screen you hold. A
/// kiosk panel is a screen you stand in front of, and Flutter's logical pixel
/// does nothing about that: a panel that reports 1080 logical pixels across
/// paints that 38pt control at 38 of them, so the app arrived on the shop
/// floor looking like a phone screenshot someone had stretched to fill a door.
///
/// Rather than carry a scale factor through every widget in the app — a
/// hundred call sites, and the one that gets missed is the one that overflows —
/// the frame is drawn on a smaller canvas and that canvas is scaled up to the
/// glass. It is the same thing a browser's zoom does. Everything moves
/// together: type, hairlines, gaps, touch targets, badges, the lot.
///
/// The canvas is not a fixed size, so this is a scale and not a letterbox: the
/// panel keeps its own aspect and grows the canvas as well as the furniture —
/// see [softness]. A grid asked for its column count still gets a real width to
/// answer from, which is why the size run and the product grid can still open
/// up on a big panel instead of showing the same layout larger.
///
/// Text size in Settings is untouched by any of this. It multiplies on top:
/// this decides how big the app is drawn, that decides how much bigger than the
/// app its words are, and a customer who needs the second one still gets it.
class PanelScale extends StatelessWidget {
  const PanelScale({super.key, required this.child});

  final Widget child;

  /// The width this app's numbers were drawn against, and the width at which
  /// nothing happens — a tablet, a phone and every widget test still get the
  /// layout they had before this existed.
  static const double drawnAt = 720;

  /// How much of a panel's extra width becomes bigger furniture rather than
  /// more canvas.
  ///
  /// At 1 the app would be a fixed 720pt canvas blown up to any panel: the
  /// kiosk would show exactly what a tablet shows, only larger, which is half
  /// the complaint. At 0 nothing would scale at all, which is the other half.
  /// At .7 a 1080pt panel is drawn 1.33x larger on an 813pt canvas — the type
  /// and the targets grow by a third, and the extra ~90pt is real room the
  /// grids can spend on wider tiles.
  static const double softness = .7;

  /// A panel reporting a very large logical width is usually one with a density
  /// of 1, not a wall the size of a shopfront. Past here the app stops growing
  /// and starts fitting more on, which is the safer way to be wrong.
  static const double ceiling = 2.4;

  /// How much larger than drawn the app should be on a panel of [size].
  ///
  /// Driven by the shortest side, not the width: in portrait that is the width
  /// and this reads as you would expect, but it also stops a panel turned
  /// landscape from scaling off 1920 and running out of height.
  static double scaleFor(Size size) {
    final shortest = math.min(size.width, size.height);
    if (shortest <= drawnAt || !shortest.isFinite) return 1;
    return math.min(math.pow(shortest / drawnAt, softness).toDouble(), ceiling);
  }

  @override
  Widget build(BuildContext context) {
    final media = MediaQuery.of(context);
    final scale = scaleFor(media.size);
    if (scale <= 1) return child;

    final canvas = media.size / scale;
    return MediaQuery(
      // The canvas has to describe itself honestly or everything that measures
      // against it lands in the wrong units: a sheet capped at 80% of the
      // "screen", a photo decoded for the width it will be painted at, a grid
      // counting its columns. The device pixel ratio goes the other way — the
      // canvas is smaller but the glass is not, so a photo decoded for a 385pt
      // tile still gets the pixels that tile is really painted with.
      data: media.copyWith(
        size: canvas,
        devicePixelRatio: media.devicePixelRatio * scale,
        padding: media.padding / scale,
        viewPadding: media.viewPadding / scale,
        viewInsets: media.viewInsets / scale,
        systemGestureInsets: media.systemGestureInsets / scale,
      ),
      child: FittedBox(
        // The canvas is the panel divided by the scale, so filling is uniform —
        // `fill` rather than `contain` only so a rounding error cannot leave a
        // hairline of background down one edge.
        fit: BoxFit.fill,
        alignment: Alignment.topLeft,
        child: SizedBox.fromSize(size: canvas, child: child),
      ),
    );
  }
}
