import 'package:flutter/widgets.dart';

/// Layout breakpoints. Device class uses the shortest side so rotating a phone
/// never turns it into a tablet, while 7–8" portrait tablets get tablet layouts
/// (mirrors the POS app's tablet system).
class Breakpoints {
  static const double tablet = 600;
  static const double wide = 1200;

  /// Width cap for phone-shaped content (forms, modal sheets) so it stays a
  /// readable column instead of stretching across a tablet. Used by
  /// [MaxWidthBox] and, via `bottomSheetTheme` in `buildAstraTheme`, by every
  /// modal sheet. On a phone the viewport is narrower, so the cap is a no-op.
  static const double contentMaxWidth = 560;
}

extension ResponsiveX on BuildContext {
  Size get screenSize => MediaQuery.sizeOf(this);
  double get screenWidth => screenSize.width;

  /// Tablet device class in either orientation.
  bool get isTablet => screenSize.shortestSide >= Breakpoints.tablet;

  /// A wide tablet/desktop viewport with room for dense multi-column content.
  bool get isWide => isTablet && screenWidth >= Breakpoints.wide;
}

/// Centers content and caps its width on large screens so phone-shaped layouts
/// don't stretch awkwardly across a tablet/desktop.
class MaxWidthBox extends StatelessWidget {
  const MaxWidthBox({super.key, required this.child, this.maxWidth = Breakpoints.contentMaxWidth});
  final Widget child;
  final double maxWidth;

  @override
  Widget build(BuildContext context) {
    return LayoutBuilder(
      builder: (context, constraints) {
        final available = constraints.maxWidth;
        // Always the same Padding, zero when the content fits: returning the bare
        // child below the cap would change the tree shape whenever the width
        // crosses it (rotation, split view) and rebuild everything inside from
        // scratch — state, controllers and any cubit provided in there.
        final pad = available.isFinite && available > maxWidth ? (available - maxWidth) / 2 : 0.0;
        return Padding(
          padding: EdgeInsets.symmetric(horizontal: pad),
          child: child,
        );
      },
    );
  }
}
