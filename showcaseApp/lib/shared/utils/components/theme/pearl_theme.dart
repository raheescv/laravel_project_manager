import 'package:flutter/material.dart';
import 'package:google_fonts/google_fonts.dart';

/// The "Pearl" design system — direction 06 of the showcase samples.
///
/// Hard luxury: cool pearl and graphite, hairlines instead of fills, and **no
/// accent hue at all**. Selection is signalled by an ink block and nothing else,
/// which is why [accent] is the ink colour rather than a brand colour. Type is
/// Jost, set small and wide-tracked, uppercase almost everywhere, with more air
/// than any other direction.
///
/// Two rules keep it from drifting:
/// 1. Nothing outside this folder names a colour. Screens read `context.pearl`;
///    the alternative palettes live in `theme_presets.dart` next door.
/// 2. Nothing introduces a hue. If something needs emphasis it gets an ink
///    block, more space, or a heavier hairline — never a colour.
@immutable
class PearlPalette extends ThemeExtension<PearlPalette> {
  const PearlPalette({
    required this.bg,
    required this.surface,
    required this.shotTop,
    required this.shotBottom,
    required this.ink,
    required this.muted,
    required this.faint,
    required this.line,
    required this.accent,
    required this.accentInk,
    required this.ok,
    required this.okBg,
    required this.brightness,
  });

  /// Page ground.
  final Color bg;

  /// Cards, chips, rails — barely separated from [bg] on purpose.
  final Color surface;

  /// The two stops of the product "stage" gradient every photo sits on.
  final Color shotTop;
  final Color shotBottom;

  final Color ink;
  final Color muted;
  final Color faint;
  final Color line;

  /// Selection. Deliberately the ink colour — see the class doc.
  final Color accent;
  final Color accentInk;

  /// In-stock. The one semantic colour, and it is desaturated to stay in family.
  final Color ok;
  final Color okBg;

  final Brightness brightness;

  static const PearlPalette light = PearlPalette(
    bg: Color(0xFFF0F0F2),
    surface: Color(0xFFFAFAFB),
    shotTop: Color(0xFFFFFFFF),
    shotBottom: Color(0xFFE6E6EA),
    ink: Color(0xFF191A1E),
    muted: Color(0xFF6D6F78),
    faint: Color(0xFFA7A9B2),
    line: Color(0xFFE0E0E5),
    accent: Color(0xFF191A1E),
    accentInk: Color(0xFFFAFAFB),
    ok: Color(0xFF3E6152),
    okBg: Color(0xFFE8EDEA),
    brightness: Brightness.light,
  );

  static const PearlPalette dark = PearlPalette(
    bg: Color(0xFF121316),
    surface: Color(0xFF1A1B1F),
    shotTop: Color(0xFF2A2C32),
    shotBottom: Color(0xFF141519),
    ink: Color(0xFFEFEFF2),
    muted: Color(0xFF9A9CA5),
    faint: Color(0xFF63656E),
    line: Color(0xFF26272D),
    accent: Color(0xFFEFEFF2),
    accentInk: Color(0xFF121316),
    ok: Color(0xFF8FB3A2),
    okBg: Color(0x248FB3A2),
    brightness: Brightness.dark,
  );

  /// The stage a product photo is presented on.
  LinearGradient get stage => LinearGradient(
        begin: Alignment.topLeft,
        end: Alignment.bottomRight,
        colors: [shotTop, shotBottom],
      );

  bool get isDark => brightness == Brightness.dark;

  @override
  PearlPalette copyWith({
    Color? bg,
    Color? surface,
    Color? shotTop,
    Color? shotBottom,
    Color? ink,
    Color? muted,
    Color? faint,
    Color? line,
    Color? accent,
    Color? accentInk,
    Color? ok,
    Color? okBg,
    Brightness? brightness,
  }) =>
      PearlPalette(
        bg: bg ?? this.bg,
        surface: surface ?? this.surface,
        shotTop: shotTop ?? this.shotTop,
        shotBottom: shotBottom ?? this.shotBottom,
        ink: ink ?? this.ink,
        muted: muted ?? this.muted,
        faint: faint ?? this.faint,
        line: line ?? this.line,
        accent: accent ?? this.accent,
        accentInk: accentInk ?? this.accentInk,
        ok: ok ?? this.ok,
        okBg: okBg ?? this.okBg,
        brightness: brightness ?? this.brightness,
      );

  @override
  PearlPalette lerp(ThemeExtension<PearlPalette>? other, double t) {
    if (other is! PearlPalette) return this;
    return PearlPalette(
      bg: Color.lerp(bg, other.bg, t)!,
      surface: Color.lerp(surface, other.surface, t)!,
      shotTop: Color.lerp(shotTop, other.shotTop, t)!,
      shotBottom: Color.lerp(shotBottom, other.shotBottom, t)!,
      ink: Color.lerp(ink, other.ink, t)!,
      muted: Color.lerp(muted, other.muted, t)!,
      faint: Color.lerp(faint, other.faint, t)!,
      line: Color.lerp(line, other.line, t)!,
      accent: Color.lerp(accent, other.accent, t)!,
      accentInk: Color.lerp(accentInk, other.accentInk, t)!,
      ok: Color.lerp(ok, other.ok, t)!,
      okBg: Color.lerp(okBg, other.okBg, t)!,
      brightness: t < 0.5 ? brightness : other.brightness,
    );
  }
}

/// Spacing and geometry. Pearl has no corner radius anywhere — that is the
/// direction, not an oversight.
class PearlMetrics {
  const PearlMetrics._();

  static const double pad = 22;
  static const double gap = 14;
  static const double radius = 0;
  static const double hairline = 1;

  /// Left icon rail on tablet.
  static const double rail = 68;

  /// The funnel's "choices so far" column.
  static const double funnelColumn = 252;

  /// The right-hand aside (live preview, top brands).
  static const double aside = 296;

  /// Standing info panel beside the product gallery.
  static const double infoPanel = 348;
}

/// Every text style in the app. Screens never call `GoogleFonts` directly, so
/// the typeface is one edit away from being swapped for a bundled font.
class PearlText {
  const PearlText._();

  static TextStyle _jost({
    required double size,
    required FontWeight weight,
    required double tracking,
    double? height,
  }) =>
      GoogleFonts.jost(
        fontSize: size,
        fontWeight: weight,
        letterSpacing: tracking,
        height: height,
      );

  /// Page headline. Light weight, large, tight — the one place Pearl is loud.
  static TextStyle display(double size) =>
      _jost(size: size, weight: FontWeight.w300, tracking: -0.3, height: 1.08);

  /// Section headings: uppercase, small, wide.
  static TextStyle section = _jost(size: 10.5, weight: FontWeight.w500, tracking: 2.6);

  /// Eyebrows, column headings, breadcrumbs — the widest tracking in the system.
  static TextStyle micro = _jost(size: 9.5, weight: FontWeight.w500, tracking: 3.4);

  /// Brand line on a product card.
  static TextStyle brand = _jost(size: 8.5, weight: FontWeight.w500, tracking: 2.2);

  /// Product name on a card. Uppercase at the call site.
  static TextStyle productName(double size) =>
      _jost(size: size, weight: FontWeight.w500, tracking: 1.7, height: 1.3);

  /// Prices are set in the regular weight — bolding them would be the loudest
  /// thing on the screen, and in this direction the product is.
  static TextStyle price(double size) =>
      _jost(size: size, weight: FontWeight.w400, tracking: 0.4);

  static TextStyle body(double size) =>
      _jost(size: size, weight: FontWeight.w400, tracking: 0.1, height: 1.5);

  static TextStyle label = _jost(size: 12, weight: FontWeight.w500, tracking: 0.6);

  /// Buttons: the widest tracking of all, because there are very few of them.
  static TextStyle button = _jost(size: 9.5, weight: FontWeight.w500, tracking: 3);
}

/// Build the app theme from [p]. The palette carries its own brightness, so a
/// preset is the only thing a caller has to choose.
ThemeData buildPearlTheme(PearlPalette p) {
  final brightness = p.brightness;
  return ThemeData(
    useMaterial3: true,
    brightness: brightness,
    scaffoldBackgroundColor: p.bg,
    canvasColor: p.bg,
    dividerColor: p.line,
    splashFactory: InkRipple.splashFactory,
    colorScheme: ColorScheme.fromSeed(
      seedColor: p.ink,
      brightness: brightness,
    ).copyWith(
      surface: p.bg,
      onSurface: p.ink,
      primary: p.accent,
      onPrimary: p.accentInk,
      outline: p.line,
    ),
    textTheme: GoogleFonts.jostTextTheme(
      brightness == Brightness.dark
          ? ThemeData.dark().textTheme
          : ThemeData.light().textTheme,
    ).apply(bodyColor: p.ink, displayColor: p.ink),
    // Pearl has no rounded corners; the sheet inherits that.
    bottomSheetTheme: BottomSheetThemeData(
      backgroundColor: p.bg,
      surfaceTintColor: Colors.transparent,
      shape: const RoundedRectangleBorder(),
    ),
    dialogTheme: DialogThemeData(
      backgroundColor: p.bg,
      surfaceTintColor: Colors.transparent,
      shape: const RoundedRectangleBorder(),
    ),
    extensions: [p],
  );
}

extension PearlX on BuildContext {
  /// The active palette. Falls back to the light set so a widget rendered
  /// outside the themed subtree (a route transition, a test) still paints.
  PearlPalette get pearl =>
      Theme.of(this).extension<PearlPalette>() ?? PearlPalette.light;
}
