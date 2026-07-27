import 'dart:ui';

import 'package:flutter/material.dart';

import 'package:invo/shared/utils/components/theme/index.dart';
import 'package:invo/shared/widgets/astra_widgets.dart';

/// The tablet layout vocabulary — the Flutter translation of the approved
/// preview in `docs/mobile-tablet-screens.html`.
///
/// The preview's premise: on a tablet the page stops being a stack of floating
/// cards on a canvas and becomes *panes* — a surfaced list column, a hairline
/// edge, a detail area — so the eye reads structure rather than a scatter of
/// shadows. These widgets are the shared pieces of that language; every screen's
/// `context.isTablet` branch is built from them so the screens stay consistent.
///
/// Nothing here is used by a phone layout. All colour comes from
/// `context.astra`, so every preset (and dark mode) re-skins the whole set.

/// Pane widths for a given amount of horizontal space — see [TabletMetrics.forWidth].
class TabletMetrics {
  const TabletMetrics._({
    required this.listColumn,
    required this.sidePanel,
    required this.settingsNav,
    required this.gutter,
    required this.detailPadding,
  });

  /// Master column in a master–detail screen (Sales, Returns).
  final double listColumn;

  /// Docked right-hand panel (Stock Check).
  final double sidePanel;

  /// Settings category rail.
  final double settingsNav;

  /// Standard gap between tablet blocks.
  final double gutter;

  /// Padding inside a detail pane.
  final EdgeInsets detailPadding;

  /// Metrics for the whole window. Fine for screens that fill it; a screen laid
  /// out beside the shell's side-rail should prefer [forWidth] with its own
  /// constraints, since the rail takes ~106pt the window width doesn't know about.
  static TabletMetrics of(BuildContext context) => forWidth(MediaQuery.sizeOf(context).width);

  /// Pane widths scale with the space actually available, so an 8" tablet in
  /// portrait doesn't get the same proportions as a 13" in landscape. Each width
  /// is a share of that space, clamped so a pane neither sprawls nor starves
  /// what sits beside it — the second clamp is what protects the detail side on
  /// a small tablet, where a fixed 300pt column would leave it unusably narrow.
  static TabletMetrics forWidth(double available) {
    final w = available.isFinite ? available : 1024.0;
    final compact = w < 900; // 11" portrait and smaller
    return TabletMetrics._(
      listColumn: (w * 0.34).clamp(280.0, 400.0).clamp(0.0, w * 0.45),
      sidePanel: (w * 0.28).clamp(260.0, 340.0).clamp(0.0, w * 0.40),
      settingsNav: (w * 0.30).clamp(248.0, 320.0).clamp(0.0, w * 0.42),
      gutter: compact ? 14 : 18,
      detailPadding: EdgeInsets.fromLTRB(compact ? 18 : 26, 18, compact ? 18 : 26, 28),
    );
  }
}

/// Which edge of a [TabletPane] carries the hairline that separates it from the
/// pane beside it.
enum PaneEdge { none, left, right }

/// A surfaced pane — the preview's `.listcol` / `.setnav` / `.sidepanel`.
///
/// Draws the card surface full-height with a single hairline edge, so two panes
/// meet on a crisp line instead of floating apart. Honours the glass skin's
/// blur the same way [AstraCard] does, so the aurora mesh still reads behind it.
class TabletPane extends StatelessWidget {
  const TabletPane({
    super.key,
    required this.child,
    this.width,
    this.edge = PaneEdge.right,
  });

  final Widget child;

  /// Fixed width; null makes the pane fill whatever the parent gives it.
  final double? width;
  final PaneEdge edge;

  @override
  Widget build(BuildContext context) {
    final p = context.astra;
    final line = BorderSide(color: p.hairline, width: 1);
    final border = Border(
      left: edge == PaneEdge.left ? line : BorderSide.none,
      right: edge == PaneEdge.right ? line : BorderSide.none,
    );

    Widget surface = DecoratedBox(
      decoration: BoxDecoration(
        color: p.surfaceBlur > 0 ? p.card : p.cardSolid,
        border: border,
      ),
      child: child,
    );
    if (p.surfaceBlur > 0) {
      surface = ClipRect(
        child: BackdropFilter(
          filter: ImageFilter.blur(sigmaX: p.surfaceBlur, sigmaY: p.surfaceBlur),
          child: surface,
        ),
      );
    }
    return width == null ? surface : SizedBox(width: width, child: surface);
  }
}

/// The head of a list pane — the preview's `.lc-head`: the screen title (there
/// is no top header on a tablet; the rail is the chrome and each pane names
/// itself), an optional leading/trailing action, then the pane's filters,
/// closed off with a hairline.
class TabletPaneHead extends StatelessWidget {
  const TabletPaneHead({
    super.key,
    required this.title,
    this.subtitle,
    this.leading,
    this.trailing,
    this.children = const [],
    this.padding = const EdgeInsets.fromLTRB(16, 12, 16, 12),
  });

  final String title;
  final String? subtitle;

  /// Back affordance for a pane on a pushed route (no rail to navigate with).
  final Widget? leading;
  final Widget? trailing;

  /// Controls rendered under the title — the pane's filters.
  final List<Widget> children;
  final EdgeInsets padding;

  @override
  Widget build(BuildContext context) {
    final p = context.astra;
    return Container(
      padding: padding,
      decoration: BoxDecoration(
        border: Border(bottom: BorderSide(color: p.hairline, width: 1)),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              if (leading != null) ...[leading!, const SizedBox(width: 10)],
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(title, maxLines: 1, overflow: TextOverflow.ellipsis, style: serif(size: 20, color: p.ink)),
                    if (subtitle != null)
                      Padding(
                        padding: const EdgeInsets.only(top: 2),
                        child: Text(subtitle!,
                            maxLines: 1,
                            overflow: TextOverflow.ellipsis,
                            style: ui(size: 11, weight: FontWeight.w600, color: p.textMuted)),
                      ),
                  ],
                ),
              ),
              if (trailing != null) ...[const SizedBox(width: 10), trailing!],
            ],
          ),
          ...children,
        ],
      ),
    );
  }
}

/// The preview's `.pagehead` — a screen title with its actions pushed to the
/// right, sitting on a hairline. The tablet stand-in for the phone's header
/// band on screens that aren't master–detail (Reports, Stock Check).
class TabletPageHead extends StatelessWidget {
  const TabletPageHead({
    super.key,
    required this.title,
    this.subtitle,
    this.leading,
    this.actions = const [],
    this.padding = const EdgeInsets.fromLTRB(24, 14, 24, 14),
  });

  final String title;
  final String? subtitle;
  final Widget? leading;
  final List<Widget> actions;
  final EdgeInsets padding;

  @override
  Widget build(BuildContext context) {
    final p = context.astra;
    return Container(
      padding: padding,
      decoration: BoxDecoration(
        border: Border(bottom: BorderSide(color: p.hairline, width: 1)),
      ),
      child: Row(
        children: [
          if (leading != null) ...[leading!, const SizedBox(width: 12)],
          Flexible(
            flex: 4,
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(title, maxLines: 1, overflow: TextOverflow.ellipsis, style: serif(size: 21, color: p.ink)),
                if (subtitle != null)
                  Padding(
                    padding: const EdgeInsets.only(top: 2),
                    child: Text(subtitle!,
                        maxLines: 1,
                        overflow: TextOverflow.ellipsis,
                        style: ui(size: 11.5, weight: FontWeight.w600, color: p.textMuted)),
                  ),
              ],
            ),
          ),
          if (actions.isNotEmpty) ...[
            const SizedBox(width: 12),
            Flexible(
              flex: 6,
              child: Wrap(
                alignment: WrapAlignment.end,
                spacing: 8,
                runSpacing: 8,
                children: actions,
              ),
            ),
          ],
        ],
      ),
    );
  }
}

/// The head of a detail pane — the preview's `.d-head`: a quiet caption, the
/// amount as the headline, a context line, and the record's actions on the
/// right. Below ~520pt the actions drop under the amount rather than squeezing
/// it into a multi-line wrap.
class TabletDetailHead extends StatelessWidget {
  const TabletDetailHead({
    super.key,
    required this.label,
    required this.amount,
    this.amountColor,
    this.subtitle,
    this.badge,
    this.leading,
    this.actions = const [],
  });

  final String label;
  final String amount;
  final Color? amountColor;
  final String? subtitle;
  final Widget? badge;

  /// Back affordance when the detail is a pushed route rather than a pane.
  final Widget? leading;
  final List<Widget> actions;

  @override
  Widget build(BuildContext context) {
    final p = context.astra;
    final body = Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Row(
          children: [
            Flexible(
              child: Text(label.toUpperCase(),
                  maxLines: 1,
                  overflow: TextOverflow.ellipsis,
                  style: ui(size: 10.5, weight: FontWeight.w800, color: p.textMuted, letterSpacing: 1.2)),
            ),
            if (badge != null) ...[const SizedBox(width: 9), badge!],
          ],
        ),
        const SizedBox(height: 5),
        FittedBox(
          fit: BoxFit.scaleDown,
          alignment: Alignment.centerLeft,
          child: Text(amount, maxLines: 1, style: serif(size: 34, color: amountColor ?? p.ink)),
        ),
        if (subtitle != null)
          Padding(
            padding: const EdgeInsets.only(top: 3),
            child: Text(subtitle!,
                style: ui(size: 12.5, weight: FontWeight.w600, color: p.textSecondary)),
          ),
      ],
    );
    final head = leading == null
        ? body
        : Row(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [leading!, const SizedBox(width: 12), Expanded(child: body)],
          );
    if (actions.isEmpty) return head;
    return LayoutBuilder(
      builder: (ctx, c) {
        if (c.maxWidth < 520) {
          return Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              head,
              const SizedBox(height: 14),
              Wrap(spacing: 9, runSpacing: 9, children: actions),
            ],
          );
        }
        return Row(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Expanded(child: head),
            const SizedBox(width: 14),
            Wrap(alignment: WrapAlignment.end, spacing: 9, runSpacing: 9, children: actions),
          ],
        );
      },
    );
  }
}

/// The preview's `.btn` / `.btn.pri` — a compact action button for a page head
/// or a detail pane's action row.
class TabletActionButton extends StatelessWidget {
  const TabletActionButton({
    super.key,
    required this.label,
    this.icon,
    this.trailingIcon,
    this.onTap,
    this.primary = false,
    this.danger = false,
  });

  final String label;
  final IconData? icon;

  /// Trailing affordance — a chevron for buttons that open a picker.
  final IconData? trailingIcon;
  final VoidCallback? onTap;
  final bool primary;
  final bool danger;

  @override
  Widget build(BuildContext context) {
    final p = context.astra;
    final accent = danger ? AstraPalette.danger : p.primary;
    final fg = primary ? Colors.white : (danger ? AstraPalette.danger : p.ink);
    return GestureDetector(
      onTap: onTap,
      behavior: HitTestBehavior.opaque,
      child: Container(
        padding: EdgeInsets.fromLTRB(icon == null ? 15 : 13, 10, trailingIcon == null ? 15 : 11, 10),
        decoration: BoxDecoration(
          gradient: primary ? p.primaryGradient : null,
          color: primary ? null : (p.surfaceBlur > 0 ? p.card : p.cardSolid),
          borderRadius: BorderRadius.circular(12),
          border: primary ? null : Border.all(color: p.hairline),
          boxShadow: primary ? null : context.astraTheme.softShadow,
        ),
        child: Row(
          mainAxisSize: MainAxisSize.min,
          children: [
            if (icon != null) ...[
              Icon(icon, size: 15, color: primary ? Colors.white : accent),
              const SizedBox(width: 7),
            ],
            Text(label, style: ui(size: 12.5, weight: FontWeight.w700, color: fg)),
            if (trailingIcon != null) ...[
              const SizedBox(width: 5),
              Icon(trailingIcon, size: 16, color: primary ? Colors.white70 : p.textMuted),
            ],
          ],
        ),
      ),
    );
  }
}

/// A square icon-only companion to [TabletActionButton] — for overflow ("•••")
/// and close, which need no label.
class TabletIconButton extends StatelessWidget {
  const TabletIconButton({super.key, required this.icon, this.onTap, this.tooltip});
  final IconData icon;
  final VoidCallback? onTap;
  final String? tooltip;

  @override
  Widget build(BuildContext context) {
    final p = context.astra;
    final button = GestureDetector(
      onTap: onTap,
      behavior: HitTestBehavior.opaque,
      child: Container(
        width: 40,
        height: 40,
        alignment: Alignment.center,
        decoration: BoxDecoration(
          color: p.surfaceBlur > 0 ? p.card : p.cardSolid,
          borderRadius: BorderRadius.circular(12),
          border: Border.all(color: p.hairline),
          boxShadow: context.astraTheme.softShadow,
        ),
        child: Icon(icon, size: 17, color: p.ink),
      ),
    );
    return tooltip == null ? button : Tooltip(message: tooltip!, child: button);
  }
}

/// A titled block inside a detail pane — the preview's `.panel`: a quiet
/// uppercase caption over a card.
class TabletPanel extends StatelessWidget {
  const TabletPanel({
    super.key,
    required this.title,
    required this.child,
    this.padding = const EdgeInsets.fromLTRB(16, 6, 16, 6),
  });

  final String title;
  final Widget child;
  final EdgeInsets padding;

  @override
  Widget build(BuildContext context) {
    final p = context.astra;
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Padding(
          padding: const EdgeInsets.only(left: 2, bottom: 8),
          child: Text(title.toUpperCase(),
              style: ui(size: 10, weight: FontWeight.w800, color: p.textMuted, letterSpacing: 1.2)),
        ),
        AstraCard(radius: 16, padding: padding, child: child),
      ],
    );
  }
}



/// The preview's `.fchip` — a pill filter inside a pane head.
class TabletFilterChip extends StatelessWidget {
  const TabletFilterChip({
    super.key,
    required this.label,
    required this.active,
    this.icon,
    this.trailingIcon,
    this.onTap,
  });

  final String label;
  final bool active;
  final IconData? icon;
  final IconData? trailingIcon;
  final VoidCallback? onTap;

  @override
  Widget build(BuildContext context) {
    final p = context.astra;
    final fg = active ? Colors.white : p.textSecondary;
    return GestureDetector(
      onTap: onTap,
      behavior: HitTestBehavior.opaque,
      child: Container(
        padding: EdgeInsets.fromLTRB(icon == null ? 12 : 9, 7, trailingIcon == null ? 12 : 8, 7),
        decoration: BoxDecoration(
          gradient: active ? p.primaryGradient : null,
          color: active ? null : p.tint,
          borderRadius: BorderRadius.circular(20),
        ),
        child: Row(
          mainAxisSize: MainAxisSize.min,
          children: [
            if (icon != null) ...[
              Icon(icon, size: 13, color: active ? Colors.white : p.primary),
              const SizedBox(width: 6),
            ],
            Text(label, style: ui(size: 11.5, weight: FontWeight.w700, color: fg)),
            if (trailingIcon != null) ...[
              const SizedBox(width: 3),
              Icon(trailingIcon, size: 15, color: active ? Colors.white70 : p.textMuted),
            ],
          ],
        ),
      ),
    );
  }
}

/// The preview's `.lrow` — a flat, hairline-separated list row. Replaces the
/// phone's floating card inside a pane, where stacked shadows read as noise.
/// The selected row is tinted and carries a primary bar on its leading edge.
class TabletListRow extends StatelessWidget {
  const TabletListRow({
    super.key,
    required this.child,
    required this.selected,
    this.onTap,
    this.padding = const EdgeInsets.fromLTRB(16, 13, 16, 13),
  });

  final Widget child;
  final bool selected;
  final VoidCallback? onTap;
  final EdgeInsets padding;

  @override
  Widget build(BuildContext context) {
    final p = context.astra;
    return GestureDetector(
      onTap: onTap,
      behavior: HitTestBehavior.opaque,
      child: AnimatedContainer(
        duration: const Duration(milliseconds: 160),
        curve: Curves.easeOut,
        decoration: BoxDecoration(
          color: selected ? p.tint : Colors.transparent,
          border: Border(
            bottom: BorderSide(color: p.hairline, width: 1),
            left: BorderSide(color: selected ? p.primary : Colors.transparent, width: 3),
          ),
        ),
        // Keep the text baseline identical whether or not the 3px selection bar
        // is drawn, so picking a row doesn't nudge the whole column.
        padding: padding.copyWith(left: padding.left - 3),
        child: child,
      ),
    );
  }
}

