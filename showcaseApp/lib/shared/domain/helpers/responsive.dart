import 'package:flutter/widgets.dart';

/// Layout breakpoints. Device class uses the shortest side, so rotating a phone
/// never turns it into a tablet.
class Breakpoints {
  const Breakpoints._();

  static const double tablet = 600;

  /// Below this the tablet layout drops its right-hand aside column.
  static const double wide = 1000;

  /// Width cap for phone-shaped content on a large screen.
  static const double contentMaxWidth = 620;
}

extension ResponsiveX on BuildContext {
  Size get screenSize => MediaQuery.sizeOf(this);
  double get screenWidth => screenSize.width;

  /// Tablet device class in either orientation. The showcase is designed for
  /// this first; the phone layout is the fallback.
  bool get isTablet => screenSize.shortestSide >= Breakpoints.tablet;

  /// Enough horizontal room for the funnel's third column.
  bool get isWide => isTablet && screenWidth >= Breakpoints.wide;
}
