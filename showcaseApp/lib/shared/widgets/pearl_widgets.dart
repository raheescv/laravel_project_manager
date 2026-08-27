import 'package:flutter/material.dart';

import '../utils/components/theme/pearl_theme.dart';

/// A hairline-bordered surface. Pearl has no fills and no shadows, so this is
/// the only container the app uses.
class Hairline extends StatelessWidget {
  const Hairline({
    super.key,
    required this.child,
    this.padding = EdgeInsets.zero,
    this.filled = false,
    this.onTap,
  });

  final Widget child;
  final EdgeInsets padding;

  /// Barely-there fill for the few surfaces that must separate from the ground.
  final bool filled;
  final VoidCallback? onTap;

  @override
  Widget build(BuildContext context) {
    final p = context.pearl;
    final body = Container(
      padding: padding,
      decoration: BoxDecoration(
        color: filled ? p.surface : null,
        border: Border.all(color: p.line, width: PearlMetrics.hairline),
      ),
      child: child,
    );
    if (onTap == null) return body;
    return InkWell(onTap: onTap, child: body);
  }
}

/// Section heading: uppercase and wide-tracked, with optional meta on the right.
class SectionHeading extends StatelessWidget {
  const SectionHeading(this.title, {super.key, this.meta, this.padding});

  final String title;
  final String? meta;
  final EdgeInsets? padding;

  @override
  Widget build(BuildContext context) {
    final p = context.pearl;
    return Padding(
      padding: padding ?? const EdgeInsets.only(bottom: 12),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.baseline,
        textBaseline: TextBaseline.alphabetic,
        children: [
          Expanded(
            child: Text(title.toUpperCase(), style: PearlText.section.copyWith(color: p.ink)),
          ),
          if (meta != null)
            Text(meta!.toUpperCase(), style: PearlText.micro.copyWith(color: p.faint)),
        ],
      ),
    );
  }
}

/// Column heading inside the funnel / filter rails.
class ColumnHeading extends StatelessWidget {
  const ColumnHeading(this.text, {super.key});

  final String text;

  @override
  Widget build(BuildContext context) => Padding(
        padding: const EdgeInsets.only(top: 18, bottom: 10),
        child: Text(
          text.toUpperCase(),
          style: PearlText.micro.copyWith(color: context.pearl.faint),
        ),
      );
}

/// The primary action: an ink block with wide-tracked uppercase type. There is
/// at most one of these on a screen.
class PearlButton extends StatelessWidget {
  const PearlButton({
    super.key,
    required this.label,
    this.icon,
    this.onTap,
    this.ghost = false,
    this.height = 46,
  });

  final String label;
  final IconData? icon;
  final VoidCallback? onTap;

  /// Hairline outline instead of the ink block — for the secondary of a pair.
  final bool ghost;
  final double height;

  @override
  Widget build(BuildContext context) {
    final p = context.pearl;
    final enabled = onTap != null;
    final fg = ghost ? p.ink : p.accentInk;
    return Opacity(
      opacity: enabled ? 1 : .4,
      child: InkWell(
        onTap: onTap,
        child: Container(
          height: height,
          alignment: Alignment.center,
          decoration: BoxDecoration(
            color: ghost ? null : p.accent,
            border: Border.all(color: ghost ? p.line : p.accent),
          ),
          child: Row(
            mainAxisAlignment: MainAxisAlignment.center,
            mainAxisSize: MainAxisSize.min,
            children: [
              if (icon != null) ...[
                Icon(icon, size: 15, color: fg),
                const SizedBox(width: 10),
              ],
              Flexible(
                child: Text(
                  label.toUpperCase(),
                  overflow: TextOverflow.ellipsis,
                  style: PearlText.button.copyWith(color: fg),
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }
}

/// A square icon control — back, share, search. Hairline box, nothing else.
class IconSquare extends StatelessWidget {
  const IconSquare(this.icon, {super.key, this.onTap, this.size = 40, this.filled = false});

  final IconData icon;
  final VoidCallback? onTap;
  final double size;
  final bool filled;

  @override
  Widget build(BuildContext context) {
    final p = context.pearl;
    return InkWell(
      onTap: onTap,
      child: Container(
        width: size,
        height: size,
        alignment: Alignment.center,
        decoration: BoxDecoration(
          color: filled ? p.surface : null,
          border: Border.all(color: p.line),
        ),
        child: Icon(icon, size: size * .42, color: p.ink),
      ),
    );
  }
}

/// A size / filter chip. Selected is an ink block; unavailable is struck
/// through rather than hidden, because customers ask for sizes that are out.
class PearlChip extends StatelessWidget {
  const PearlChip({
    super.key,
    required this.label,
    this.sub,
    this.selected = false,
    this.available = true,
    this.onTap,
    this.height = 54,
    this.labelSize = 15,
  });

  final String label;

  /// A secondary figure, set quietly in the top-right corner rather than under
  /// the label.
  ///
  /// Stacking the two put a number directly beneath a number doing a different
  /// job, so the eye had to stop on every chip to tell the size from its count
  /// — sixty stops on a full size run. In the corner the label is
  /// unmistakably the subject and the count is available without being read.
  final String? sub;

  final bool selected;
  final bool available;
  final VoidCallback? onTap;
  final double height;

  /// The label carries the chip, so it is set larger than the body scale.
  final double labelSize;

  @override
  Widget build(BuildContext context) {
    final p = context.pearl;
    final fg = selected ? p.accentInk : (available ? p.ink : p.faint);
    return InkWell(
      onTap: available ? onTap : null,
      child: Container(
        height: height,
        decoration: BoxDecoration(
          color: selected ? p.accent : null,
          border: Border.all(color: selected ? p.accent : p.line),
        ),
        // `StackFit.expand`, and no `alignment` on the Container: an alignment
        // wraps the child in an Align, which hands the Stack loose constraints
        // — the Stack then shrink-wraps to the label and every Positioned child
        // measures from the corner of the *text* instead of the corner of the
        // chip. That put the count beside the label and left the strike-through
        // crossing only the digits.
        child: Stack(
          fit: StackFit.expand,
          children: [
            Center(
              child: Text(
                label,
                maxLines: 1,
                style: PearlText.label
                    .copyWith(color: fg, fontSize: labelSize, letterSpacing: .9),
              ),
            ),
            if (sub != null)
              Positioned(
                top: 6,
                right: 8,
                child: Text(
                  sub!.toUpperCase(),
                  style: PearlText.micro.copyWith(
                    fontSize: 9,
                    letterSpacing: .4,
                    color: selected ? p.accentInk.withValues(alpha: .7) : p.faint,
                  ),
                ),
              ),
            // Never strike through the current selection: the chip the customer
            // is standing on reading as unavailable is just confusing.
            if (!available && !selected)
              Positioned.fill(
                child: CustomPaint(painter: _StrikePainter(p.line)),
              ),
          ],
        ),
      ),
    );
  }
}

class _StrikePainter extends CustomPainter {
  const _StrikePainter(this.color);

  final Color color;

  @override
  void paint(Canvas canvas, Size size) {
    final paint = Paint()
      ..color = color
      ..strokeWidth = 1;
    canvas.drawLine(Offset(0, size.height), Offset(size.width, 0), paint);
  }

  @override
  bool shouldRepaint(_StrikePainter old) => old.color != color;
}

/// The one semantic colour in the system, used for the stock pill.
class StockPill extends StatelessWidget {
  const StockPill({super.key, required this.label, this.positive = true});

  final String label;
  final bool positive;

  @override
  Widget build(BuildContext context) {
    final p = context.pearl;
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 9, vertical: 5),
      decoration: BoxDecoration(color: positive ? p.okBg : null, border: positive ? null : Border.all(color: p.line)),
      child: Text(
        label.toUpperCase(),
        style: PearlText.micro.copyWith(
          fontSize: 8.5,
          letterSpacing: 1.8,
          color: positive ? p.ok : p.faint,
        ),
      ),
    );
  }
}

/// Empty and failed states. Both are quiet and both offer a way forward —
/// a dead end on a shop-floor tablet means a customer walks away.
class MessageState extends StatelessWidget {
  const MessageState({
    super.key,
    required this.title,
    this.detail,
    this.actionLabel,
    this.onAction,
  });

  final String title;
  final String? detail;
  final String? actionLabel;
  final VoidCallback? onAction;

  @override
  Widget build(BuildContext context) {
    final p = context.pearl;
    return Center(
      child: Padding(
        padding: const EdgeInsets.all(32),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            Text(
              title.toUpperCase(),
              textAlign: TextAlign.center,
              style: PearlText.section.copyWith(color: p.ink),
            ),
            if (detail != null) ...[
              const SizedBox(height: 12),
              Text(
                detail!,
                textAlign: TextAlign.center,
                style: PearlText.body(12).copyWith(color: p.muted),
              ),
            ],
            if (actionLabel != null && onAction != null) ...[
              const SizedBox(height: 22),
              SizedBox(
                width: 220,
                child: PearlButton(label: actionLabel!, onTap: onAction, ghost: true),
              ),
            ],
          ],
        ),
      ),
    );
  }
}

/// A hairline placeholder block, used while a list loads. Pearl never shows a
/// full-screen spinner — the layout arrives first and fills in.
class SkeletonBlock extends StatelessWidget {
  const SkeletonBlock({super.key, this.height = 14, this.width});

  final double height;
  final double? width;

  @override
  Widget build(BuildContext context) => Container(
        height: height,
        width: width,
        color: context.pearl.line.withValues(alpha: .55),
      );
}
