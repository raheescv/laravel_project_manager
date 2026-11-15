import 'package:flutter/material.dart';
import 'package:google_fonts/google_fonts.dart';

import 'type_presets.dart';

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

  /// Cards, chips, rails. In the light palettes this *is* the ground — a
  /// panel is told apart from the page by its [line] and nothing else. The
  /// dark palettes still lift it, because a hairline on black is not enough.
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
    bg: Color(0xFFFFFFFF),
    surface: Color(0xFFFFFFFF),
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
}

/// Every text style in the app. Screens never call `GoogleFonts` directly, so
/// the typeface is one edit away from being swapped for a bundled font.
class PearlText {
  const PearlText._();

  /// Whether the app is currently set to Arabic.
  ///
  /// A mutable static rather than something read from context, because every
  /// style below is reached as `PearlText.label` from a hundred call sites that
  /// have no BuildContext to offer. [LocaleCubit] owns it and sets it before
  /// the frame that changes language.
  static bool _arabic = false;

  static void useArabic(bool value) => _arabic = value;

  static bool get isArabic => _arabic;

  /// The pairing chosen in Settings → Appearance. Same reasoning as [_arabic]:
  /// these styles are reached statically from everywhere.
  static TypePreset _type = TypePreset.jost;

  static void useType(TypePreset preset) => _type = preset;

  static TypePreset get type => _type;

  /// One face for each script.
  ///
  /// Jost has no Arabic glyphs at all, so Arabic would fall back to whatever
  /// the platform picked and stop looking like this app. IBM Plex Sans Arabic
  /// is the closest thing to Jost's geometry that shapes Arabic properly.
  /// Positive tracking pulls apart the joins in Arabic script — the cursive
  /// stops being cursive and the words stop being readable. Pearl's whole voice
  /// is wide tracking, so this is the one place the direction bends.
  ///
  /// Pulled out of [_face] so it can be checked without resolving a font: the
  /// Google Fonts call underneath cannot run in a test.
  ///
  /// [arabic] names the script explicitly; null means the app's own language,
  /// which is what every ordinary call wants.
  @visibleForTesting
  static double trackingFor(double tracking, {bool? arabic}) =>
      (arabic ?? _arabic) ? 0 : tracking * _type.tracking;

  /// Arabic sits taller in its line box; the tight leading Jost is set with
  /// clips the descenders.
  @visibleForTesting
  static double? leadingFor(double? height, {bool? arabic}) =>
      (arabic ?? _arabic) && height != null ? height + .18 : height;

  static TextStyle _face({
    required double size,
    required FontWeight weight,
    required double tracking,
    double? height,
    bool display = false,
    bool? arabic,
  }) {
    final rtl = arabic ?? _arabic;
    final letterSpacing = trackingFor(tracking, arabic: rtl);
    final leading = leadingFor(height, arabic: rtl);
    return display
        ? _type.displayStyle(
            size: size,
            weight: weight,
            letterSpacing: letterSpacing,
            height: leading,
            arabic: rtl,
          )
        : _type.textStyle(
            size: size,
            weight: weight,
            letterSpacing: letterSpacing,
            height: leading,
            arabic: rtl,
          );
  }

  /// Page headline. Light weight, large, tight — the one place Pearl is loud.
  static TextStyle display(double size) => _face(
        size: size,
        weight: FontWeight.w300,
        tracking: -0.3,
        height: 1.08,
        display: true,
      );

  /// [display], set in the script named here rather than the app's own.
  ///
  /// For the one heading that carries both languages at once: whichever way
  /// the tablet is set, half of it is in the script the app is *not* in, and
  /// the Latin faces have no Arabic glyphs — left to [display] that half would
  /// fall back to whatever the platform picked and stop looking like this app.
  ///
  /// [weight] and [tracking] are open because the one heading set this way is
  /// set in capitals: capitals want the bold weight and air between the
  /// letters, where the sentence-case headline wants neither. Tracking still
  /// goes through [_face], so the Arabic half is left at zero whatever is
  /// asked for here — positive tracking pulls apart the joins.
  static TextStyle displayIn(
    double size, {
    required bool arabic,
    FontWeight weight = FontWeight.w300,
    double tracking = -0.3,
  }) =>
      _face(
        size: size,
        weight: weight,
        tracking: tracking,
        height: 1.08,
        display: true,
        arabic: arabic,
      );

  /// The funnel's question — "WHAT IS YOUR SIZE?", "WHICH BRAND?" — set the
  /// way this app asks a question: the display face in capitals, bold, with a
  /// little air. Capitals are all one height with no descenders to hold them
  /// apart, so the tracking goes positive where the sentence-case [display]
  /// keeps it negative, and the weight goes up because a light capital at this
  /// size reads as thin rather than quiet.
  ///
  /// Arabic has no capitals to raise and no tracking to give — the script
  /// joins, and [displayIn] already leaves that half at zero — so in Arabic
  /// this is the same heading a shade heavier, which is the whole of the
  /// difference the script allows.
  ///
  /// [arabic] names the script for the one heading that carries both at once;
  /// every ordinary call leaves it to the app's own language.
  static TextStyle displayCaps(double size, {bool? arabic}) => displayIn(
        size,
        arabic: arabic ?? _arabic,
        weight: FontWeight.w700,
        tracking: 0.6,
      );

  /// Section headings: uppercase, small, wide.
  static TextStyle get section => _face(size: 10.5, weight: FontWeight.w500, tracking: 2.6);

  /// Eyebrows, column headings, breadcrumbs — the widest tracking in the system.
  static TextStyle get micro => _face(size: 9.5, weight: FontWeight.w500, tracking: 3.4);

  /// Brand line on a product card.
  static TextStyle get brand => _face(size: 8.5, weight: FontWeight.w500, tracking: 2.2);

  /// Product name on a card. Uppercase at the call site.
  static TextStyle productName(double size) =>
      _face(size: size, weight: FontWeight.w500, tracking: 1.7, height: 1.3);

  /// Prices are set in the regular weight — bolding them would be the loudest
  /// thing on the screen, and in this direction the product is.
  static TextStyle price(double size) =>
      _face(size: size, weight: FontWeight.w400, tracking: 0.4);

  static TextStyle body(double size) =>
      _face(size: size, weight: FontWeight.w400, tracking: 0.1, height: 1.5);

  static TextStyle get label => _face(size: 12, weight: FontWeight.w500, tracking: 0.6);

  /// Buttons: the widest tracking of all, because there are very few of them.
  static TextStyle get button => _face(size: 9.5, weight: FontWeight.w500, tracking: 3);
}

/// The ambient text theme, built once per brightness.
///
/// `jostTextTheme` is fourteen `GoogleFonts.jost` calls, and this is reached
/// twice — light and dark — every time the theme is rebuilt, which the theme
/// cubit and the locale cubit both do. It does not vary with anything but the
/// brightness: the colours are applied on top by the caller, and the face is
/// deliberately Jost whatever Appearance is set to, because this is only the
/// default a widget inherits when it names no style of its own.
final Map<Brightness, TextTheme> _ambientTextThemes = {};

TextTheme _ambientText(Brightness brightness) =>
    _ambientTextThemes[brightness] ??= GoogleFonts.jostTextTheme(
      brightness == Brightness.dark
          ? ThemeData.dark().textTheme
          : ThemeData.light().textTheme,
    );

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
    textTheme: _ambientText(brightness).apply(bodyColor: p.ink, displayColor: p.ink),
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
