import 'package:flutter/material.dart';

import 'package:invo/shared/domain/constants/app_config.dart';
import 'package:invo/shared/domain/constants/global_variables.dart';
import 'package:invo/shared/domain/helpers/responsive.dart';
import 'package:invo/shared/utils/components/theme/index.dart';
import 'package:invo/shared/utils/router/http_utils/http_service.dart';
import 'package:invo/shared/widgets/tablet_widgets.dart';

import '../../domain/models/checklist_models.dart';
import 'photo_viewer.dart';

/// Atelier tones derived from the live palette (ivory / emerald / gold look of
/// the approved design, mapped onto whichever preset is active).
extension AtelierTones on AstraPalette {
  /// Pale gold wash — date blocks, gold chips, the "more photos" cell.
  Color get goldTint => accent.withValues(alpha: isDark ? 0.14 : 0.16);

  /// Stripe colour for the hatched "no photo yet" placeholders.
  Color get hatch => Color.lerp(cardSolid, ink, isDark ? 0.08 : 0.05)!;

  /// Small-caps labels on the hero gradient.
  Color get heroLabel => Color.lerp(accent, ColorManager.white, 0.35)!;
}

/// Content width cap for the checklist screens: the phone layout keeps its
/// 640 cap; tablets / wide screens get room for multi-column layouts.
double checklistContentWidth(BuildContext context) => context.isWide ? 1320 : (context.isTablet ? 1100 : 640);

/// Grid columns for tiles on tablet / wide screens (phones keep 2).
int checklistGridColumns(BuildContext context, {int tablet = 3, int wide = 4}) =>
    context.isWide ? wide : (context.isTablet ? tablet : 2);

/// Live connection config so storage paths resolve against the reachable host.
AppConfig get checklistAssetConfig => serviceLocator<HttpService>().config;

/// A small icon for a room / line category.
IconData roomIconFor(String name) {
  final n = name.toLowerCase();
  if (n.contains('kitchen') || n.contains('pantry')) return Icons.kitchen_outlined;
  if (n.contains('bed')) return Icons.bed_outlined;
  if (n.contains('bath') || n.contains('toilet') || n.contains('wash') || n.contains('wc')) return Icons.bathtub_outlined;
  if (n.contains('living') || n.contains('hall') || n.contains('majlis') || n.contains('lounge')) return Icons.weekend_outlined;
  if (n.contains('dining')) return Icons.dining_outlined;
  if (n.contains('balcony') || n.contains('terrace') || n.contains('garden')) return Icons.balcony_outlined;
  if (n.contains('laundry')) return Icons.local_laundry_service_outlined;
  if (n.contains('store') || n.contains('maid')) return Icons.inventory_2_outlined;
  if (n.contains('other') || n.contains('key')) return Icons.vpn_key_outlined;
  return Icons.meeting_room_outlined;
}

/// Uppercase gold kicker above a serif title.
class AtelierKicker extends StatelessWidget {
  const AtelierKicker(this.text, {super.key, this.color});
  final String text;
  final Color? color;

  @override
  Widget build(BuildContext context) => Text(
        text.toUpperCase(),
        maxLines: 1,
        overflow: TextOverflow.ellipsis,
        style: ui(size: 9.5, weight: FontWeight.w800, letterSpacing: 1.8, color: color ?? context.astra.goldText),
      );
}

/// 36px round outline icon button (back / call / prev / next).
class AtelierRoundButton extends StatelessWidget {
  const AtelierRoundButton({super.key, required this.icon, this.onTap, this.busy = false, this.tooltip});
  final IconData icon;
  final VoidCallback? onTap;

  /// Swaps the glyph for a small spinner (a fetch in flight).
  final bool busy;
  final String? tooltip;

  @override
  Widget build(BuildContext context) {
    final p = context.astra;
    final enabled = onTap != null;
    final button = GestureDetector(
      onTap: busy ? null : onTap,
      behavior: HitTestBehavior.opaque,
      child: Container(
        width: 36,
        height: 36,
        alignment: Alignment.center,
        decoration: BoxDecoration(shape: BoxShape.circle, color: p.cardSolid, border: Border.all(color: p.hairline)),
        child: busy
            ? SizedBox(width: 14, height: 14, child: CircularProgressIndicator(strokeWidth: 2, color: p.primary))
            : Icon(icon, size: 16, color: enabled ? p.ink : p.textMuted.withValues(alpha: 0.5)),
      ),
    );
    return tooltip == null ? button : Tooltip(message: tooltip!, child: button);
  }
}

/// Page header: back button, gold kicker, serif title, optional trailing.
class AtelierTopBar extends StatelessWidget {
  const AtelierTopBar({super.key, required this.kicker, required this.title, this.onBack, this.trailing});
  final String kicker;
  final String title;
  final VoidCallback? onBack;
  final Widget? trailing;

  @override
  Widget build(BuildContext context) {
    final p = context.astra;
    // Tablet: no header band — the flat page head over a hairline, like every
    // other tablet screen; the rail is the chrome.
    if (context.isTablet) {
      final canBack = onBack != null || Navigator.of(context).canPop();
      return TabletPageHead(
        title: title,
        subtitle: kicker,
        leading: canBack
            ? TabletIconButton(icon: Icons.chevron_left, onTap: onBack ?? () => Navigator.of(context).maybePop())
            : null,
        actions: [if (trailing != null) trailing!],
      );
    }
    final bar = SafeArea(
      bottom: false,
      child: Padding(
        padding: const EdgeInsets.fromLTRB(14, 8, 14, 12),
        child: Row(children: [
          AtelierRoundButton(icon: Icons.arrow_back, onTap: onBack ?? () => Navigator.of(context).maybePop()),
          const SizedBox(width: 10),
          Expanded(
            child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
              AtelierKicker(kicker),
              const SizedBox(height: 3),
              Text(title, maxLines: 1, overflow: TextOverflow.ellipsis, style: serif(size: 21, color: p.ink, height: 1.15)),
            ]),
          ),
          if (trailing != null) ...[const SizedBox(width: 10), trailing!],
        ]),
      ),
    );
    return bar;
  }
}

/// Solid hairline card with the soft shadow; [highlighted] adds the gold ring.
class AtelierCard extends StatelessWidget {
  const AtelierCard({
    super.key,
    required this.child,
    this.padding = const EdgeInsets.all(12),
    this.radius = 18,
    this.onTap,
    this.highlighted = false,
    this.clip = false,
  });
  final Widget child;
  final EdgeInsets padding;
  final double radius;
  final VoidCallback? onTap;
  final bool highlighted;
  final bool clip;

  @override
  Widget build(BuildContext context) {
    final p = context.astra;
    final t = context.astraTheme;
    Widget body = Padding(padding: padding, child: child);
    if (clip) body = ClipRRect(borderRadius: BorderRadius.circular(radius - 1), child: body);
    final card = Container(
      decoration: BoxDecoration(
        color: p.cardSolid,
        borderRadius: BorderRadius.circular(radius),
        border: Border.all(color: highlighted ? p.accent : p.hairline),
        boxShadow: [
          if (highlighted) BoxShadow(color: p.goldTint, spreadRadius: 3),
          ...t.softShadow,
        ],
      ),
      child: body,
    );
    if (onTap == null) return card;
    return GestureDetector(onTap: onTap, behavior: HitTestBehavior.opaque, child: card);
  }
}

enum AtelierButtonKind { primary, gold, ghost }

/// The Atelier 50px button — emerald primary, gold CTA, or hairline ghost.
/// A null [onTap] renders the muted disabled look.
class AtelierButton extends StatelessWidget {
  const AtelierButton({
    super.key,
    required this.label,
    this.onTap,
    this.icon,
    this.leadingIcon,
    this.kind = AtelierButtonKind.primary,
    this.busy = false,
    this.height = 50,
  });
  final String label;
  final VoidCallback? onTap;
  final IconData? icon;
  final IconData? leadingIcon;
  final AtelierButtonKind kind;
  final bool busy;
  final double height;

  @override
  Widget build(BuildContext context) {
    final p = context.astra;
    final t = context.astraTheme;
    final enabled = onTap != null && !busy;
    final disabled = onTap == null && !busy;
    final Color fg = disabled
        ? p.textMuted
        : switch (kind) {
            AtelierButtonKind.primary => ColorManager.white,
            AtelierButtonKind.gold => p.primaryDark,
            AtelierButtonKind.ghost => p.ink,
          };
    final BoxDecoration deco;
    if (disabled) {
      deco = BoxDecoration(color: p.hairline, borderRadius: BorderRadius.circular(16));
    } else {
      deco = switch (kind) {
        AtelierButtonKind.primary => BoxDecoration(
            color: p.primary, borderRadius: BorderRadius.circular(16), boxShadow: t.floatShadow(p.primary)),
        AtelierButtonKind.gold => BoxDecoration(
            gradient: p.accentGradient, borderRadius: BorderRadius.circular(16), boxShadow: t.floatShadow(p.accent)),
        AtelierButtonKind.ghost => BoxDecoration(
            color: p.cardSolid, borderRadius: BorderRadius.circular(16), border: Border.all(color: p.hairline)),
      };
    }
    return GestureDetector(
      onTap: enabled ? onTap : null,
      behavior: HitTestBehavior.opaque,
      child: Container(
        height: height,
        padding: const EdgeInsets.symmetric(horizontal: 14),
        decoration: deco,
        alignment: Alignment.center,
        child: busy
            ? SizedBox(width: 18, height: 18, child: CircularProgressIndicator(strokeWidth: 2.4, color: fg))
            : Row(mainAxisSize: MainAxisSize.min, children: [
                if (leadingIcon != null) ...[Icon(leadingIcon, size: 16, color: fg), const SizedBox(width: 7)],
                Flexible(
                  child: Text(label,
                      maxLines: 1,
                      overflow: TextOverflow.ellipsis,
                      style: ui(size: 13.5, weight: FontWeight.w800, color: fg)),
                ),
                if (icon != null) ...[const SizedBox(width: 7), Icon(icon, size: 16, color: fg)],
              ]),
      ),
    );
  }
}

/// Diagonal-striped "no photo yet" placeholder.
class HatchedBox extends StatelessWidget {
  const HatchedBox({super.key, this.child});
  final Widget? child;

  @override
  Widget build(BuildContext context) {
    final p = context.astra;
    return CustomPaint(
      painter: _HatchPainter(p.hatch),
      child: Center(child: child),
    );
  }
}

class _HatchPainter extends CustomPainter {
  _HatchPainter(this.color);
  final Color color;

  @override
  void paint(Canvas canvas, Size size) {
    final paint = Paint()
      ..color = color
      ..strokeWidth = 7;
    canvas.save();
    canvas.clipRect(Offset.zero & size);
    for (double x = -size.height; x < size.width + size.height; x += 14) {
      canvas.drawLine(Offset(x, size.height), Offset(x + size.height, 0), paint);
    }
    canvas.restore();
  }

  @override
  bool shouldRepaint(_HatchPainter old) => old.color != color;
}

/// A rounded dashed outline around [child] (capture slots, hand-over tile).
class DashedBorderBox extends StatelessWidget {
  const DashedBorderBox({super.key, required this.child, required this.color, this.radius = 16, this.strokeWidth = 1.5});
  final Widget child;
  final Color color;
  final double radius;
  final double strokeWidth;

  @override
  Widget build(BuildContext context) =>
      CustomPaint(foregroundPainter: _DashPainter(color, radius, strokeWidth), child: child);
}

class _DashPainter extends CustomPainter {
  _DashPainter(this.color, this.radius, this.strokeWidth);
  final Color color;
  final double radius;
  final double strokeWidth;

  @override
  void paint(Canvas canvas, Size size) {
    final rect = (Offset.zero & size).deflate(strokeWidth / 2);
    final path = Path()..addRRect(RRect.fromRectAndRadius(rect, Radius.circular(radius)));
    final paint = Paint()
      ..color = color
      ..style = PaintingStyle.stroke
      ..strokeWidth = strokeWidth;
    for (final metric in path.computeMetrics()) {
      var d = 0.0;
      while (d < metric.length) {
        canvas.drawPath(metric.extractPath(d, d + 6), paint);
        d += 11;
      }
    }
  }

  @override
  bool shouldRepaint(_DashPainter old) => old.color != color || old.radius != radius;
}

/// A server photo (root-relative storage path) filling its box, decoded at the
/// box's pixel width. Falls back to [placeholder] (hatched) on error.
class ChecklistPhoto extends StatelessWidget {
  const ChecklistPhoto({super.key, required this.path, this.fit = BoxFit.cover, this.placeholder});
  final String path;
  final BoxFit fit;
  final Widget? placeholder;

  @override
  Widget build(BuildContext context) {
    final p = context.astra;
    final cfg = checklistAssetConfig;
    final dpr = MediaQuery.devicePixelRatioOf(context);
    return LayoutBuilder(builder: (context, c) {
      final w = c.maxWidth.isFinite ? c.maxWidth : 400.0;
      return Image.network(
        cfg.assetUrl(path),
        headers: cfg.assetHeaders,
        fit: fit,
        width: c.maxWidth.isFinite ? c.maxWidth : null,
        height: c.maxHeight.isFinite ? c.maxHeight : null,
        cacheWidth: (w * dpr).round().clamp(64, 1600),
        gaplessPlayback: true,
        loadingBuilder: (_, child, progress) => progress == null
            ? child
            : Container(
                color: p.hatch,
                alignment: Alignment.center,
                child: SizedBox(width: 16, height: 16, child: CircularProgressIndicator(strokeWidth: 2, color: p.primary)),
              ),
        errorBuilder: (_, __, ___) =>
            placeholder ?? HatchedBox(child: Icon(Icons.broken_image_outlined, size: 18, color: p.textMuted)),
      );
    });
  }
}

/// A server-hosted signature PNG on a white card (ink is always dark). Tap
/// opens it full screen.
class SignatureImage extends StatelessWidget {
  const SignatureImage({super.key, required this.path, this.height = 54, this.caption = 'Signature'});
  final String path;
  final double height;
  final String caption;

  @override
  Widget build(BuildContext context) {
    final p = context.astra;
    return GestureDetector(
      onTap: () => openChecklistPhotoViewer(context, [ChecklistViewerPhoto(path: path, caption: caption)]),
      child: Hero(
        tag: checklistPhotoHeroTag(path),
        child: Container(
      height: height,
      width: double.infinity,
      padding: const EdgeInsets.all(4),
      decoration: BoxDecoration(
        color: ColorManager.white,
        borderRadius: BorderRadius.circular(10),
        border: Border.all(color: p.hairline),
      ),
      // A plain Image, not ChecklistPhoto: the signing timeline sits in an
      // IntrinsicHeight, and ChecklistPhoto's LayoutBuilder cannot report
      // intrinsic sizes (it threw, then cascaded into null-check errors).
      // The box height is known, so decode at that height instead.
      child: Image.network(
        checklistAssetConfig.assetUrl(path),
        headers: checklistAssetConfig.assetHeaders,
        fit: BoxFit.contain,
        cacheHeight: (height * MediaQuery.devicePixelRatioOf(context)).round(),
        gaplessPlayback: true,
        errorBuilder: (_, __, ___) => const SizedBox.shrink(),
      ),
        ),
      ),
    );
  }
}

/// Horizontal stacked status bar: ok · damaged · remaining track.
class StatusSegmentBar extends StatelessWidget {
  const StatusSegmentBar({
    super.key,
    required this.ok,
    required this.bad,
    required this.total,
    this.height = 4,
    this.okColor,
    this.badColor,
    this.trackColor,
  });
  final int ok;
  final int bad;
  final int total;
  final double height;
  final Color? okColor;
  final Color? badColor;
  final Color? trackColor;

  @override
  Widget build(BuildContext context) {
    final p = context.astra;
    final rest = (total - ok - bad).clamp(0, total);
    return ClipRRect(
      borderRadius: BorderRadius.circular(height),
      child: SizedBox(
        height: height,
        child: total == 0
            ? ColoredBox(color: trackColor ?? p.hairline)
            : Row(children: [
                if (ok > 0) Expanded(flex: ok, child: ColoredBox(color: okColor ?? ColorManager.success)),
                if (bad > 0) Expanded(flex: bad, child: ColoredBox(color: badColor ?? ColorManager.danger)),
                if (rest > 0) Expanded(flex: rest, child: ColoredBox(color: trackColor ?? p.hairline)),
              ]),
      ),
    );
  }
}

/// Small uppercase pill: Move-in (green) / Move-out (amber) / gold.
class AtelierPill extends StatelessWidget {
  const AtelierPill({super.key, required this.label, required this.bg, required this.fg, this.icon});
  final String label;
  final Color bg;
  final Color fg;
  final IconData? icon;

  factory AtelierPill.phase(BuildContext context, ChecklistJob job) {
    final p = context.astra;
    return job.isMoveOut
        ? AtelierPill(label: job.phaseName, bg: p.warnTint, fg: p.warnText)
        : AtelierPill(label: job.phaseName, bg: p.successTint, fg: ColorManager.success);
  }

  @override
  Widget build(BuildContext context) => Container(
        padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 2),
        decoration: BoxDecoration(color: bg, borderRadius: BorderRadius.circular(6)),
        child: Row(mainAxisSize: MainAxisSize.min, children: [
          if (icon != null) ...[Icon(icon, size: 10, color: fg), const SizedBox(width: 3)],
          Text(label.toUpperCase(), style: ui(size: 9, weight: FontWeight.w800, letterSpacing: 0.5, color: fg)),
        ]),
      );
}

/// 42px progress ring with the percent (gold + pen when [complete], gold +
/// seal when [sealed]).
class ProgressRing extends StatelessWidget {
  const ProgressRing({super.key, required this.value, this.complete = false, this.sealed = false, this.size = 42});
  final double value;
  final bool complete;
  final bool sealed;
  final double size;

  @override
  Widget build(BuildContext context) {
    final p = context.astra;
    final full = complete || sealed || value >= 1;
    return SizedBox(
      width: size,
      height: size,
      child: Stack(alignment: Alignment.center, children: [
        SizedBox(
          width: size,
          height: size,
          child: CircularProgressIndicator(
            value: full ? 1 : value.clamp(0, 1).toDouble(),
            strokeWidth: 4,
            backgroundColor: p.hairline,
            color: full ? p.accent : p.primary,
          ),
        ),
        sealed
            ? Icon(Icons.verified_outlined, size: 16, color: p.goldText)
            : complete
            ? Icon(Icons.edit_outlined, size: 15, color: p.goldText)
            : Text('${(value * 100).round()}%', style: ui(size: 10, weight: FontWeight.w800, color: p.ink)),
      ]),
    );
  }
}

/// Round corner badge showing a line's status for the phase.
class LineStatusBadge extends StatelessWidget {
  const LineStatusBadge({super.key, required this.status, this.size = 24});
  final String? status;
  final double size;

  @override
  Widget build(BuildContext context) {
    final p = context.astra;
    final (Color bg, Color fg, IconData icon) = switch (status) {
      ChecklistLineStatus.ok => (ColorManager.success, ColorManager.white, Icons.check),
      ChecklistLineStatus.notOk => (ColorManager.danger, ColorManager.white, Icons.close),
      _ => (p.cardSolid.withValues(alpha: 0.92), p.textMuted, Icons.radio_button_unchecked),
    };
    return Container(
      width: size,
      height: size,
      decoration: BoxDecoration(
        shape: BoxShape.circle,
        color: bg,
        boxShadow: [BoxShadow(color: ColorManager.black.withValues(alpha: 0.25), blurRadius: 8, offset: const Offset(0, 2))],
      ),
      child: Icon(icon, size: size * 0.5, color: fg),
    );
  }
}

/// Serif section heading with a muted trailing note.
class AtelierSectionTitle extends StatelessWidget {
  const AtelierSectionTitle({super.key, required this.title, this.trailing, this.action});
  final String title;
  final String? trailing;
  final Widget? action;

  @override
  Widget build(BuildContext context) {
    final p = context.astra;
    return Padding(
      padding: const EdgeInsets.fromLTRB(18, 4, 18, 10),
      child: Row(crossAxisAlignment: CrossAxisAlignment.center, children: [
        Expanded(child: Text(title, style: serif(size: 19, color: p.ink))),
        if (trailing != null) Text(trailing!, style: ui(size: 11, weight: FontWeight.w700, color: p.textMuted)),
        if (action != null) action!,
      ]),
    );
  }
}

/// Hairline card with an uppercase field label (comment, cost, remarks…).
class AtelierField extends StatelessWidget {
  const AtelierField({super.key, required this.label, required this.child, this.borderColor});
  final String label;
  final Widget child;
  final Color? borderColor;

  @override
  Widget build(BuildContext context) {
    final p = context.astra;
    return Container(
      width: double.infinity,
      padding: const EdgeInsets.fromLTRB(12, 9, 12, 8),
      decoration: BoxDecoration(
        color: p.cardSolid,
        borderRadius: BorderRadius.circular(14),
        border: Border.all(color: borderColor ?? p.hairline),
      ),
      child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
        Text(label.toUpperCase(),
            style: ui(size: 9.5, weight: FontWeight.w800, letterSpacing: 0.8, color: p.textMuted)),
        child,
      ]),
    );
  }
}

/// The grab handle at the top of the Atelier bottom sheets.
class SheetGrabber extends StatelessWidget {
  const SheetGrabber({super.key});

  @override
  Widget build(BuildContext context) => Center(
        child: Container(
          width: 38,
          height: 5,
          margin: const EdgeInsets.only(bottom: 10),
          decoration: BoxDecoration(color: context.astra.hairline, borderRadius: BorderRadius.circular(5)),
        ),
      );
}

/// Shows a floating snack message (root messenger survives navigation).
///
/// [clearBottomBar]: the screen docks its own action bar at the bottom of its
/// body (Room, Hand-over). A floating snackbar is only lifted above a
/// Scaffold's *bottomNavigationBar*, so without the margin the toast would sit
/// on that bar for four seconds and swallow the next tap on it.
void showChecklistToast(BuildContext context, String message, {bool clearBottomBar = false}) {
  ScaffoldMessenger.of(context)
    ..hideCurrentSnackBar()
    ..showSnackBar(SnackBar(
      content: Text(message),
      behavior: SnackBarBehavior.floating,
      margin: clearBottomBar ? const EdgeInsets.fromLTRB(14, 0, 14, 84) : null,
    ));
}
