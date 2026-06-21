import 'dart:ui' show ImageFilter;

import 'package:flutter/material.dart';

import '../theme/palette.dart';
import '../theme/theme.dart';

/// Page background — skin-aware. Glass gets a soft aurora *mesh*; editorial a
/// gold glow on near-black; clean a faint brand radial; signature the original
/// warm cream aurora.
class AstraBackground extends StatelessWidget {
  const AstraBackground({super.key, required this.child});
  final Widget child;

  Widget _blob(Color c, double size, double opacity) => Container(
        width: size,
        height: size,
        decoration: BoxDecoration(
          shape: BoxShape.circle,
          gradient: RadialGradient(
            colors: [c.withValues(alpha: opacity), c.withValues(alpha: 0)],
          ),
        ),
      );

  @override
  Widget build(BuildContext context) {
    final p = context.astra;

    switch (p.skin) {
      case AstraSkin.glass:
        final dark = p.isDark;
        return Stack(
          children: [
            Positioned.fill(
              child: DecoratedBox(
                decoration: BoxDecoration(
                  gradient: LinearGradient(
                    begin: Alignment.topLeft,
                    end: Alignment.bottomRight,
                    colors: dark
                        ? const [Color(0xFF0B0E1C), Color(0xFF11142A)]
                        : const [Color(0xFFEAF0FF), Color(0xFFF4F6FE)],
                  ),
                ),
              ),
            ),
            Positioned(left: -70, top: -50, child: _blob(const Color(0xFF6366F1), 280, dark ? 0.32 : 0.45)),
            Positioned(right: -60, top: 20, child: _blob(const Color(0xFF22D3EE), 240, dark ? 0.26 : 0.40)),
            Positioned(right: -40, bottom: 150, child: _blob(const Color(0xFFA78BFA), 260, dark ? 0.26 : 0.38)),
            Positioned.fill(child: child),
          ],
        );
      case AstraSkin.editorial:
        return DecoratedBox(
          decoration: BoxDecoration(
            gradient: RadialGradient(
              center: const Alignment(0.95, -1.05),
              radius: 1.25,
              colors: [p.accent.withValues(alpha: 0.16), p.canvas],
              stops: const [0.0, 0.6],
            ),
          ),
          child: child,
        );
      case AstraSkin.clean:
        return DecoratedBox(
          decoration: BoxDecoration(
            gradient: RadialGradient(
              center: const Alignment(0.8, -1.1),
              radius: 1.2,
              colors: [p.primary.withValues(alpha: 0.07), p.canvas],
              stops: const [0.0, 0.55],
            ),
          ),
          child: child,
        );
      case AstraSkin.signature:
        return DecoratedBox(
          decoration: BoxDecoration(
            gradient: RadialGradient(
              center: const Alignment(0.7, -1.1),
              radius: 1.2,
              colors: [
                p.isDark ? p.primary.withValues(alpha: 0.25) : const Color(0xFFCFE6DD),
                p.canvas,
              ],
              stops: const [0.0, 0.6],
            ),
          ),
          child: child,
        );
    }
  }
}

/// Emerald gradient header with rounded bottom — used on most screens.
class EmeraldHeader extends StatelessWidget {
  const EmeraldHeader({
    super.key,
    this.title,
    this.titleWidget,
    this.subtitle,
    this.leading,
    this.trailing,
    this.bottom,
    this.bigRadius = false,
  });

  final String? title;
  final Widget? titleWidget;
  final String? subtitle;
  final Widget? leading;
  final Widget? trailing;
  final Widget? bottom;
  final bool bigRadius;

  @override
  Widget build(BuildContext context) {
    final p = context.astra;
    return Container(
      decoration: BoxDecoration(
        gradient: p.heroGradient,
        borderRadius: BorderRadius.vertical(
          bottom: Radius.circular(bigRadius ? 30 : 26),
        ),
      ),
      child: SafeArea(
        bottom: false,
        child: Padding(
          padding: const EdgeInsets.fromLTRB(16, 6, 16, 16),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Row(
                children: [
                  if (leading != null) ...[leading!, const SizedBox(width: 11)],
                  Expanded(
                    child: titleWidget ??
                        Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Text(title ?? '',
                                style: serif(size: 22, color: Colors.white)),
                            if (subtitle != null)
                              Padding(
                                padding: const EdgeInsets.only(top: 3),
                                child: Text(subtitle!,
                                    style: ui(
                                        size: 11,
                                        weight: FontWeight.w600,
                                        color: Colors.white.withValues(alpha: 0.75))),
                              ),
                          ],
                        ),
                  ),
                  if (trailing != null) trailing!,
                ],
              ),
              if (bottom != null) ...[const SizedBox(height: 14), bottom!],
            ],
          ),
        ),
      ),
    );
  }
}

/// A small translucent round icon button for headers (back / close).
class HeaderIconButton extends StatelessWidget {
  const HeaderIconButton({super.key, required this.icon, this.onTap, this.gold = false});
  final IconData icon;
  final VoidCallback? onTap;
  final bool gold;

  @override
  Widget build(BuildContext context) {
    final p = context.astra;
    return GestureDetector(
      onTap: onTap,
      child: Container(
        width: 36,
        height: 36,
        decoration: BoxDecoration(
          color: Colors.white.withValues(alpha: 0.16),
          borderRadius: BorderRadius.circular(11),
          border: Border.all(color: Colors.white.withValues(alpha: 0.20)),
        ),
        child: Icon(icon, size: 16, color: gold ? p.accent : Colors.white),
      ),
    );
  }
}

/// Standard white (or dark) rounded card with the soft shadow.
class AstraCard extends StatelessWidget {
  const AstraCard({
    super.key,
    required this.child,
    this.padding = const EdgeInsets.all(14),
    this.radius,
    this.onTap,
  });
  final Widget child;
  final EdgeInsets padding;
  final double? radius;
  final VoidCallback? onTap;

  @override
  Widget build(BuildContext context) {
    final p = context.astra;
    final t = context.astraTheme;
    final r = radius ?? t.rCard;

    Widget surface;
    if (p.surfaceBlur > 0) {
      // Glass: shadow rides an outer box; the blur + translucent fill are
      // clipped inside so the frost samples whatever sits behind the card.
      surface = Container(
        decoration: BoxDecoration(
          borderRadius: BorderRadius.circular(r),
          boxShadow: t.cardShadow,
        ),
        child: ClipRRect(
          borderRadius: BorderRadius.circular(r),
          child: BackdropFilter(
            filter: ImageFilter.blur(sigmaX: p.surfaceBlur, sigmaY: p.surfaceBlur),
            child: Container(
              padding: padding,
              decoration: BoxDecoration(
                color: p.card,
                border: Border.all(color: p.cardBorder),
              ),
              child: child,
            ),
          ),
        ),
      );
    } else {
      surface = Container(
        padding: padding,
        decoration: BoxDecoration(
          color: p.card,
          borderRadius: BorderRadius.circular(r),
          border: p.isEditorial ? Border.all(color: p.cardBorder) : null,
          boxShadow: t.cardShadow,
        ),
        child: child,
      );
    }
    if (onTap == null) return surface;
    return GestureDetector(onTap: onTap, child: surface);
  }
}

/// A rounded tinted square holding an icon (the emerald-tint chips).
class IconChip extends StatelessWidget {
  const IconChip({super.key, required this.icon, this.size = 38, this.radius = 11, this.bg, this.fg});
  final IconData icon;
  final double size;
  final double radius;
  final Color? bg;
  final Color? fg;

  @override
  Widget build(BuildContext context) {
    final p = context.astra;
    return Container(
      width: size,
      height: size,
      decoration: BoxDecoration(
        color: bg ?? p.tint,
        borderRadius: BorderRadius.circular(radius),
      ),
      child: Icon(icon, size: size * 0.4, color: fg ?? p.primary),
    );
  }
}

/// Product photo with a graceful fallback to a tinted icon chip (when there's
/// no image, the URL is bad, or it's still loading).
class ProductThumb extends StatelessWidget {
  const ProductThumb({
    super.key,
    required this.url,
    required this.fallbackIcon,
    this.size = 44,
    this.radius = 12,
  });
  final String url;
  final IconData fallbackIcon;
  final double size;
  final double radius;

  @override
  Widget build(BuildContext context) {
    final p = context.astra;
    final fallback = IconChip(icon: fallbackIcon, size: size, radius: radius);
    if (!url.startsWith('http')) return fallback;
    return ClipRRect(
      borderRadius: BorderRadius.circular(radius),
      child: Image.network(
        url,
        width: size,
        height: size,
        fit: BoxFit.cover,
        gaplessPlayback: true,
        errorBuilder: (_, __, ___) => fallback,
        loadingBuilder: (context, child, progress) {
          if (progress == null) return child;
          return Container(
            width: size,
            height: size,
            decoration: BoxDecoration(color: p.tint, borderRadius: BorderRadius.circular(radius)),
            alignment: Alignment.center,
            child: SizedBox(
              width: size * 0.38,
              height: size * 0.38,
              child: CircularProgressIndicator(strokeWidth: 2, color: p.primary),
            ),
          );
        },
      ),
    );
  }
}

/// Teal uppercase section label.
class SectionLabel extends StatelessWidget {
  const SectionLabel(this.text, {super.key, this.trailing});
  final String text;
  final Widget? trailing;
  @override
  Widget build(BuildContext context) {
    final p = context.astra;
    final label = Text(
      text.toUpperCase(),
      style: ui(
        size: 10.5,
        weight: FontWeight.w800,
        color: p.isEditorial ? p.goldText : (p.isDark ? p.accent : const Color(0xFF0F7A66)),
        letterSpacing: 1.2,
      ),
    );
    if (trailing == null) return label;
    return Row(mainAxisAlignment: MainAxisAlignment.spaceBetween, children: [label, trailing!]);
  }
}

/// Pill filter chip (category filter row).
class AstraChip extends StatelessWidget {
  const AstraChip({super.key, required this.label, required this.active, this.onTap, this.icon});
  final String label;
  final bool active;
  final VoidCallback? onTap;
  final IconData? icon;

  @override
  Widget build(BuildContext context) {
    final p = context.astra;
    final t = context.astraTheme;
    return GestureDetector(
      onTap: onTap,
      child: Container(
        padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 9),
        decoration: BoxDecoration(
          gradient: active ? p.primaryGradient : null,
          color: active ? null : p.card,
          borderRadius: BorderRadius.circular(t.rChip),
          boxShadow: active ? t.floatShadow(p.primary) : t.softShadow,
        ),
        child: Row(
          mainAxisSize: MainAxisSize.min,
          children: [
            if (icon != null) ...[
              Icon(icon, size: 12, color: active ? Colors.white : p.textSecondary),
              const SizedBox(width: 6),
            ],
            Text(label,
                style: ui(
                    size: 12.5,
                    weight: FontWeight.w700,
                    color: active ? Colors.white : p.textSecondary)),
          ],
        ),
      ),
    );
  }
}

/// Full-width gradient primary button (emerald) or gold CTA.
class AstraButton extends StatelessWidget {
  const AstraButton({
    super.key,
    required this.label,
    this.onTap,
    this.icon,
    this.gold = false,
    this.expand = true,
    this.busy = false,
  });
  final String label;
  final VoidCallback? onTap;
  final IconData? icon;
  final bool gold;
  final bool expand;
  final bool busy;

  @override
  Widget build(BuildContext context) {
    final p = context.astra;
    final t = context.astraTheme;
    final fg = gold ? p.primaryDark : Colors.white;
    final child = Row(
      mainAxisSize: expand ? MainAxisSize.max : MainAxisSize.min,
      mainAxisAlignment: MainAxisAlignment.center,
      children: [
        if (busy)
          SizedBox(
            width: 18,
            height: 18,
            child: CircularProgressIndicator(strokeWidth: 2.4, color: fg),
          )
        else ...[
          Text(label, style: ui(size: 14.5, weight: FontWeight.w800, color: fg)),
          if (icon != null) ...[const SizedBox(width: 8), Icon(icon, size: 16, color: fg)],
        ],
      ],
    );
    return GestureDetector(
      onTap: busy ? null : onTap,
      child: Container(
        padding: const EdgeInsets.symmetric(horizontal: 22, vertical: 15),
        decoration: BoxDecoration(
          gradient: gold ? p.accentGradient : p.primaryGradient,
          borderRadius: BorderRadius.circular(t.rButton),
          boxShadow: t.floatShadow(gold ? p.accent : p.primary),
        ),
        child: child,
      ),
    );
  }
}

/// Gold-ringed circular monogram (avatar / brand emblem).
class Monogram extends StatelessWidget {
  const Monogram({super.key, required this.letter, this.size = 42, this.fontSize});
  final String letter;
  final double size;
  final double? fontSize;
  @override
  Widget build(BuildContext context) {
    final p = context.astra;
    return Container(
      width: size,
      height: size,
      padding: const EdgeInsets.all(2),
      decoration: BoxDecoration(shape: BoxShape.circle, gradient: p.accentGradient),
      child: Container(
        decoration: const BoxDecoration(shape: BoxShape.circle, color: Color(0xFF0E5347)),
        alignment: Alignment.center,
        child: Text(letter, style: serif(size: fontSize ?? size * 0.42, color: Colors.white)),
      ),
    );
  }
}

/// Status pill (Paid / Refund / Held etc.).
class StatusPill extends StatelessWidget {
  const StatusPill({super.key, required this.label, required this.bg, required this.fg, this.icon});
  final String label;
  final Color bg;
  final Color fg;
  final IconData? icon;
  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 9, vertical: 4),
      decoration: BoxDecoration(color: bg, borderRadius: BorderRadius.circular(20)),
      child: Row(
        mainAxisSize: MainAxisSize.min,
        children: [
          if (icon != null) ...[Icon(icon, size: 11, color: fg), const SizedBox(width: 4)],
          Text(label, style: ui(size: 10, weight: FontWeight.w800, color: fg)),
        ],
      ),
    );
  }
}

/// Quantity stepper (− value +).
class QtyStepper extends StatelessWidget {
  const QtyStepper({super.key, required this.qty, this.onMinus, this.onPlus});
  final String qty;
  final VoidCallback? onMinus;
  final VoidCallback? onPlus;
  @override
  Widget build(BuildContext context) {
    final p = context.astra;
    Widget btn(IconData i, Color bg, Color fg, VoidCallback? on) => GestureDetector(
          onTap: on,
          child: Container(
            width: 28,
            height: 28,
            decoration: BoxDecoration(color: bg, borderRadius: BorderRadius.circular(8)),
            child: Icon(i, size: 13, color: fg),
          ),
        );
    return Row(
      mainAxisSize: MainAxisSize.min,
      children: [
        btn(Icons.remove, p.isDark ? Colors.white12 : const Color(0xFFF3EFE6), p.ink, onMinus),
        Padding(
          padding: const EdgeInsets.symmetric(horizontal: 12),
          child: Text(qty, style: ui(size: 14, weight: FontWeight.w800, color: p.ink)),
        ),
        btn(Icons.add, p.primary, Colors.white, onPlus),
      ],
    );
  }
}

/// A friendly empty / error state.
class EmptyState extends StatelessWidget {
  const EmptyState({super.key, required this.icon, required this.title, this.message, this.action});
  final IconData icon;
  final String title;
  final String? message;
  final Widget? action;
  @override
  Widget build(BuildContext context) {
    final p = context.astra;
    final content = Padding(
      padding: const EdgeInsets.all(32),
      child: Column(
        mainAxisSize: MainAxisSize.min,
        children: [
          IconChip(icon: icon, size: 64, radius: 22),
          const SizedBox(height: 16),
          Text(title, style: serif(size: 20, color: p.ink), textAlign: TextAlign.center),
          if (message != null) ...[
            const SizedBox(height: 8),
            Text(message!,
                textAlign: TextAlign.center,
                style: ui(size: 13, weight: FontWeight.w500, color: p.textSecondary)),
          ],
          if (action != null) ...[const SizedBox(height: 18), action!],
        ],
      ),
    );
    // Center vertically when the height is bounded (e.g. inside Expanded / a
    // Scaffold body). When height is unbounded (e.g. a direct ListView child),
    // skip the filling Center — it can't lay out under unbounded constraints and
    // throws the shifted_box `hasSize` assertion, which also orphans siblings.
    return LayoutBuilder(
      builder: (context, constraints) =>
          constraints.maxHeight.isFinite ? Center(child: content) : content,
    );
  }
}

/// Wraps a numeric field so a slim "Done" bar appears just above the on-screen
/// keyboard while [focusNode] is focused. iOS number pads have no return key, so
/// without this there is no way to dismiss them — tap Done (or anywhere outside
/// the field) to close. Pass the same [focusNode] to the wrapped [TextField].
class KeyboardDoneField extends StatefulWidget {
  const KeyboardDoneField({super.key, required this.focusNode, required this.child});
  final FocusNode focusNode;
  final Widget child;

  @override
  State<KeyboardDoneField> createState() => _KeyboardDoneFieldState();
}

class _KeyboardDoneFieldState extends State<KeyboardDoneField> {
  OverlayEntry? _entry;

  @override
  void initState() {
    super.initState();
    widget.focusNode.addListener(_onFocusChange);
  }

  @override
  void didUpdateWidget(KeyboardDoneField old) {
    super.didUpdateWidget(old);
    if (old.focusNode != widget.focusNode) {
      old.focusNode.removeListener(_onFocusChange);
      widget.focusNode.addListener(_onFocusChange);
    }
  }

  @override
  void dispose() {
    widget.focusNode.removeListener(_onFocusChange);
    _remove();
    super.dispose();
  }

  void _onFocusChange() => widget.focusNode.hasFocus ? _insert() : _remove();

  void _insert() {
    if (_entry != null || !mounted) return;
    _entry = OverlayEntry(builder: _buildBar);
    Overlay.of(context, rootOverlay: true).insert(_entry!);
  }

  void _remove() {
    _entry?.remove();
    _entry = null;
  }

  Widget _buildBar(BuildContext context) {
    final p = context.astra;
    final t = context.astraTheme;
    return Positioned(
      left: 0,
      right: 0,
      bottom: MediaQuery.of(context).viewInsets.bottom,
      child: Material(
        color: Colors.transparent,
        child: Container(
          height: 44,
          alignment: Alignment.centerRight,
          decoration: BoxDecoration(
            color: p.card,
            border: Border(top: BorderSide(color: p.hairline)),
            boxShadow: t.softShadow,
          ),
          child: TextButton(
            onPressed: () => widget.focusNode.unfocus(),
            child: Text('Done', style: ui(size: 15, weight: FontWeight.w800, color: p.primary)),
          ),
        ),
      ),
    );
  }

  @override
  Widget build(BuildContext context) => widget.child;
}
