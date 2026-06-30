import 'package:flutter/widgets.dart';

/// Layout breakpoints. The app is phone-first; at [tablet] and up we switch to
/// split / master-detail layouts and nav rails that use the extra width.
class Breakpoints {
  static const double tablet = 820;
  static const double wide = 1200;
}

extension ResponsiveX on BuildContext {
  Size get screenSize => MediaQuery.sizeOf(this);
  double get screenWidth => screenSize.width;

  /// Tablet (or larger desktop) width — use split layouts.
  bool get isTablet => screenWidth >= Breakpoints.tablet;
  bool get isWide => screenWidth >= Breakpoints.wide;
}

/// Centers content and caps its width on large screens so phone-shaped layouts
/// don't stretch awkwardly across a tablet/desktop.
class MaxWidthBox extends StatelessWidget {
  const MaxWidthBox({super.key, required this.child, this.maxWidth = 560});
  final Widget child;
  final double maxWidth;

  @override
  Widget build(BuildContext context) {
    return LayoutBuilder(
      builder: (context, constraints) {
        final available = constraints.maxWidth;
        if (!available.isFinite || available <= maxWidth) return child;
        final pad = (available - maxWidth) / 2;
        return Padding(
          padding: EdgeInsets.symmetric(horizontal: pad),
          child: child,
        );
      },
    );
  }
}
