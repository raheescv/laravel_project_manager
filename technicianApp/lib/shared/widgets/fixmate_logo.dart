import 'package:flutter/material.dart';
import 'package:flutter_svg/flutter_svg.dart';

/// FixMate brand palette — the "FixPin" identity (design option 1C). These are
/// fixed brand colours (not theme-driven) so the mark reads the same across
/// every Astra preset and in light/dark.
class FixMateBrand {
  FixMateBrand._();

  /// Signature emerald.
  static const Color emerald = Color(0xFF0FA968);

  /// Ink used by the wordmark's "Fix".
  static const Color ink = Color(0xFF0E1B15);
}

// The raw pin + wrench glyph (viewBox 0 0 64 64) from "FixMate Logo" option 1C.
const String _pinPath =
    'M32 8 C42 8 50 16 50 26 C50 38 32 56 32 56 C32 56 14 38 14 26 C14 16 22 8 32 8 Z';
const String _wrenchGroup =
    '<g transform="translate(22 15) scale(0.83)"><path d="M22.7 19l-9.1-9.1c.9-2.3.4-5-1.5-6.9-2-2-5-2.4-7.4-1.3L9 6 6 9 1.6 4.7C.4 7.1.9 10.1 2.9 12.1c1.9 1.9 4.6 2.4 6.9 1.5l9.1 9.1c.4.4 1 .4 1.4 0l2.3-2.3c.5-.4.5-1.1.1-1.4z" fill="#0FA968"/></g>';

// Full-colour badge: emerald rounded tile, white pin, emerald wrench. Matches
// the 96px glyph centred in a 168 tile (radius 40) from the design.
const String _badgeSvg = '''
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 168 168">
  <rect width="168" height="168" rx="40" fill="#0FA968"/>
  <g transform="translate(36 36) scale(1.5)">
    <path d="$_pinPath" fill="#fff"/>
    $_wrenchGroup
  </g>
</svg>''';

// Monochrome silhouette: a single-colour pin with the wrench knocked out
// (transparent), for watermarks over a coloured surface.
const String _monoSvg = '''
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 64 64">
  <mask id="w" maskUnits="userSpaceOnUse" x="0" y="0" width="64" height="64">
    <rect width="64" height="64" fill="#fff"/>
    <g transform="translate(22 15) scale(0.83)"><path d="M22.7 19l-9.1-9.1c.9-2.3.4-5-1.5-6.9-2-2-5-2.4-7.4-1.3L9 6 6 9 1.6 4.7C.4 7.1.9 10.1 2.9 12.1c1.9 1.9 4.6 2.4 6.9 1.5l9.1 9.1c.4.4 1 .4 1.4 0l2.3-2.3c.5-.4.5-1.1.1-1.4z" fill="#000"/></g>
  </mask>
  <path d="$_pinPath" fill="#fff" mask="url(#w)"/>
</svg>''';

/// The **FixMate** app mark — the "FixPin" pin-and-wrench glyph.
///
/// Two looks:
///  * [FixMateLogo] (default) — the full-colour emerald badge (white pin +
///    emerald wrench on an emerald rounded tile), the app-icon look.
///  * [FixMateLogo.mono] — a single-colour silhouette (pin filled with [color],
///    wrench cut out) for watermarks over a coloured hero.
class FixMateLogo extends StatelessWidget {
  const FixMateLogo({super.key, this.size = 64})
      : color = null,
        _mono = false;

  /// A single-colour silhouette in [color] (e.g. white on the login hero).
  const FixMateLogo.mono({super.key, this.size = 64, required Color this.color})
      : _mono = true;

  /// Rendered edge length in logical pixels (the mark is square).
  final double size;

  /// Tint for the [FixMateLogo.mono] silhouette.
  final Color? color;

  final bool _mono;

  @override
  Widget build(BuildContext context) {
    if (_mono) {
      return SvgPicture.string(
        _monoSvg,
        width: size,
        height: size,
        colorFilter: ColorFilter.mode(color!, BlendMode.srcIn),
      );
    }
    return SvgPicture.string(_badgeSvg, width: size, height: size);
  }
}

/// The FixMate wordmark — "Fix" in ink + "Mate" in emerald, weight 800.
/// Optionally prefixed by the [FixMateLogo] badge.
class FixMateWordmark extends StatelessWidget {
  const FixMateWordmark({
    super.key,
    this.fontSize = 22,
    this.withBadge = false,
    this.onDark = false,
  });

  final double fontSize;
  final bool withBadge;

  /// When true, "Fix" is white (for dark surfaces) instead of ink.
  final bool onDark;

  @override
  Widget build(BuildContext context) {
    final fixColor = onDark ? Colors.white : FixMateBrand.ink;
    final text = RichText(
      text: TextSpan(
        style: TextStyle(
          fontSize: fontSize,
          fontWeight: FontWeight.w800,
          letterSpacing: -fontSize * 0.03,
          height: 1,
        ),
        children: [
          TextSpan(text: 'Fix', style: TextStyle(color: fixColor)),
          const TextSpan(text: 'Mate', style: TextStyle(color: FixMateBrand.emerald)),
        ],
      ),
    );
    if (!withBadge) return text;
    return Row(
      mainAxisSize: MainAxisSize.min,
      children: [
        FixMateLogo(size: fontSize * 1.8),
        SizedBox(width: fontSize * 0.5),
        text,
      ],
    );
  }
}
