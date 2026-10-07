import 'package:flutter/material.dart';

import 'package:invo/shared/utils/components/theme/index.dart';

/// Loading placeholders. A list that is fetching shows the *shape* of what is
/// coming — pulsing blocks where the rows will be — instead of a bare spinner,
/// so the layout doesn't jump when the data lands.
///
/// One [SkeletonPulse] drives every block beneath it, so a whole list breathes
/// in step (and costs one ticker, not one per block).
class SkeletonPulse extends StatefulWidget {
  const SkeletonPulse({super.key, required this.child});
  final Widget child;

  @override
  State<SkeletonPulse> createState() => _SkeletonPulseState();
}

class _SkeletonPulseState extends State<SkeletonPulse> with SingleTickerProviderStateMixin {
  late final _ctl = AnimationController(vsync: this, duration: const Duration(milliseconds: 1100))
    ..repeat(reverse: true);

  @override
  void dispose() {
    _ctl.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) => FadeTransition(
        opacity: Tween(begin: 0.45, end: 1.0).animate(CurvedAnimation(parent: _ctl, curve: Curves.easeInOut)),
        child: widget.child,
      );
}

/// A single grey block. Lives inside a [SkeletonPulse].
class SkeletonBox extends StatelessWidget {
  const SkeletonBox({super.key, this.width, required this.height, this.radius = 8});
  final double? width;
  final double height;
  final double radius;

  @override
  Widget build(BuildContext context) {
    final p = context.astra;
    return Container(
      width: width,
      height: height,
      decoration: BoxDecoration(
        color: Color.lerp(p.cardSolid, p.ink, p.isDark ? 0.10 : 0.07),
        borderRadius: BorderRadius.circular(radius),
      ),
    );
  }
}

/// A card-shaped skeleton row: leading tile, two text lines, trailing pill.
class SkeletonCard extends StatelessWidget {
  const SkeletonCard({super.key, this.height = 84, this.leading = 42, this.radius = 16});
  final double height;
  final double leading;
  final double radius;

  @override
  Widget build(BuildContext context) {
    final p = context.astra;
    return Container(
      height: height,
      padding: const EdgeInsets.all(14),
      decoration: BoxDecoration(
        color: p.cardSolid,
        borderRadius: BorderRadius.circular(radius),
        border: Border.all(color: p.hairline),
      ),
      child: Row(children: [
        SkeletonBox(width: leading, height: leading, radius: 12),
        const SizedBox(width: 12),
        const Expanded(
          child: Column(
            mainAxisAlignment: MainAxisAlignment.center,
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              FractionallySizedBox(widthFactor: 0.7, child: SkeletonBox(height: 12)),
              SizedBox(height: 8),
              FractionallySizedBox(widthFactor: 0.45, child: SkeletonBox(height: 10)),
            ],
          ),
        ),
        const SizedBox(width: 12),
        const SkeletonBox(width: 54, height: 20, radius: 10),
      ]),
    );
  }
}

/// [count] skeleton cards in a non-scrolling column — drop it where the list
/// will render (inside a sliver adapter or a scroll view).
class SkeletonList extends StatelessWidget {
  const SkeletonList({
    super.key,
    this.count = 6,
    this.padding = const EdgeInsets.fromLTRB(16, 8, 16, 16),
    this.itemHeight = 84,
    this.gap = 10,
  });
  final int count;
  final EdgeInsets padding;
  final double itemHeight;
  final double gap;

  @override
  Widget build(BuildContext context) => SkeletonPulse(
        child: Padding(
          padding: padding,
          child: Column(children: [
            for (var i = 0; i < count; i++) ...[
              if (i > 0) SizedBox(height: gap),
              SkeletonCard(height: itemHeight),
            ],
          ]),
        ),
      );
}
