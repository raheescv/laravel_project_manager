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

  /// Fills the card with the surface tone. In the light palettes that tone
  /// *is* the ground, so this only lifts a card in dark mode — light relies
  /// on the hairline, which every card here draws anyway.
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
          // Room for a button that is sized by its label rather than stretched
          // by an Expanded — without it the words touch the border.
          padding: const EdgeInsets.symmetric(horizontal: 16),
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

/// A square icon control — back, home, share, close. Hairline box, nothing else.
class IconSquare extends StatelessWidget {
  const IconSquare(
    this.icon, {
    super.key,
    this.onTap,
    this.size = 40,
    this.filled = false,
    this.prominent = false,
  });

  final IconData icon;
  final VoidCallback? onTap;
  final double size;
  final bool filled;

  /// Drawn in the accent instead of a hairline.
  ///
  /// For the two controls that move a customer rather than change what they are
  /// looking at: Back and Home. A grey hairline square is the right weight on a
  /// screen you hold and furniture nobody sees on a panel the size of a door —
  /// and on that panel it is the *only* way back, because the system bars are
  /// hidden and there is no gesture to fall back on. Somebody three screens
  /// into somebody else's visit has to be able to find the way out from across
  /// the shop.
  ///
  /// The same accent, at the same weight, that the chosen size plate wears: it
  /// reads as a thing to press rather than as decoration. Deliberately not
  /// given to the tools beside it — the settings square and the sort direction
  /// change what you are looking at, they do not take you anywhere, and if
  /// everything in the bar is emphasised then nothing is.
  final bool prominent;

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
          color: filled || prominent ? p.surface : null,
          border: Border.all(
            color: prominent ? p.accent : p.line,
            width: prominent ? 1.5 : 1,
          ),
        ),
        child: Icon(
          icon,
          size: size * (prominent ? .46 : .42),
          color: prominent ? p.accent : p.ink,
        ),
      ),
    );
  }
}

/// A size / filter chip. Selection is a heavy accent outline; unavailable is
/// struck through rather than hidden, because customers ask for sizes that are
/// out.
///
/// The idle chip is drawn in the palette's accent, not in [PearlPalette.line].
/// A hairline is the right weight on a screen you hold; on a kiosk panel the
/// size of a door it disappears entirely and the run reads as loose numbers
/// floating on a wall with no target to aim at. Held at a fraction of the
/// accent so the chosen plate still wins the screen.
///
/// Two places this bends Pearl, both on purpose and both only here:
///
/// * **It has corners.** Pearl's radius is zero everywhere else, and that is
///   still the direction. But the size plate stopped being a chip when it grew
///   to fill its column — at a quarter of the panel a square corner reads as a
///   panel seam rather than a target, and the run reads as a grid of tiles
///   rather than a row of things to press. Proportional, so a plate and the
///   product page's small chips are recognisably the same shape.
/// * **The selection is an outline, not a fill.** A filled plate at this size
///   is a slab of accent big enough to be the loudest thing in the shop, and
///   the number inside it — the one piece of information on the screen — has to
///   be reversed out of it to survive. Outlined, the answer keeps the same ink
///   as every other plate and the accent does nothing but point at it.
class PearlChip extends StatelessWidget {
  const PearlChip({
    super.key,
    required this.label,
    this.selected = false,
    this.available = true,
    this.onTap,
    this.height = 54,
    this.labelSize = 15,
    this.labelWeight = FontWeight.w500,
  });

  final String label;
  final bool selected;
  final bool available;
  final VoidCallback? onTap;
  final double height;

  /// The label carries the chip, so it is set larger than the body scale.
  final double labelSize;

  /// Heavier on the size run, where the number is the whole screen.
  final FontWeight labelWeight;

  /// Everything about the plate's shape is a fraction of its height, so one
  /// widget covers a 46pt chip on the product page and a 250pt plate on the
  /// panel without either looking like a scaled photograph of the other.
  double get _radius => (height * .095).clamp(6.0, 24.0);

  double get _borderWidth => selected
      ? (height * .045).clamp(2.0, 4.0)
      : (available ? 1.5 : 1);

  @override
  Widget build(BuildContext context) {
    final p = context.pearl;
    final fg = available || selected ? p.ink : p.faint;
    // Three states, three weights: the chosen plate is ringed in the accent at
    // full strength, an idle plate is a lighter accent outline you can still
    // pick out across a shop floor, and a size that cannot be sold keeps the
    // quiet hairline it always had — it is struck through as well, and
    // outlining it in the brand colour would advertise the one plate nobody
    // can have.
    final border = selected
        ? p.accent
        : (available ? p.accent.withValues(alpha: .42) : p.line);
    final shape = BorderRadius.circular(_radius);
    return InkWell(
      onTap: available ? onTap : null,
      borderRadius: shape,
      child: Container(
        height: height,
        // Clipped, so the strike-through stops at the rounded corner instead of
        // running out past it into the page.
        clipBehavior: Clip.antiAlias,
        decoration: BoxDecoration(
          color: available ? p.surface : null,
          borderRadius: shape,
          border: Border.all(color: border, width: _borderWidth),
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
              // Scale down, do not ellipse. A size plate now takes whatever its
              // column share works out to and sets its label as a fraction of
              // that, so the one case left is a long label on a narrow plate —
              // a six-across run, or a size written "XXL-44". "XXL-4…" is not a
              // size anybody can read; the same number a little smaller is.
              child: Padding(
                padding: EdgeInsets.symmetric(horizontal: height * .08),
                child: FittedBox(
                  fit: BoxFit.scaleDown,
                  child: Text(
                    label,
                    maxLines: 1,
                    style: PearlText.label.copyWith(
                      color: fg,
                      fontSize: labelSize,
                      fontWeight: labelWeight,
                      letterSpacing: .9,
                    ),
                  ),
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
  const SkeletonBlock({super.key, this.height = 14, this.width, this.radius = 0});

  final double height;
  final double? width;

  /// Matched to whatever it is standing in for. The size run rehearses in
  /// rounded plates, because a grid of squares that turns into a grid of
  /// rounded plates the moment the sizes land is a visible flinch.
  final double radius;

  @override
  Widget build(BuildContext context) => Container(
        height: height,
        width: width,
        decoration: BoxDecoration(
          color: context.pearl.line.withValues(alpha: .55),
          borderRadius: radius == 0 ? null : BorderRadius.circular(radius),
        ),
      );
}
