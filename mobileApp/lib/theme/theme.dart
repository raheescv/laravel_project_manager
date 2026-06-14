import 'package:flutter/material.dart';
import 'package:google_fonts/google_fonts.dart';

import 'palette.dart';

/// Theme extension that carries the live [AstraPalette] plus the shared design
/// tokens (radii, shadows) so any widget can read them via `context.astra`.
@immutable
class AstraTheme extends ThemeExtension<AstraTheme> {
  const AstraTheme(this.palette);

  final AstraPalette palette;

  // Radii from the design.
  double get rCard => 18;
  double get rTile => 15;
  double get rChip => 22;
  double get rButton => 15;
  double get rSheet => 30;
  double get rField => 14;

  /// Soft card shadow — tuned per skin (indigo lift for glass, deep black for
  /// editorial, the original emerald-tinted soft shadow otherwise).
  List<BoxShadow> get cardShadow {
    switch (palette.skin) {
      case AstraSkin.glass:
        return [
          BoxShadow(
            color: const Color(0xFF3A46A0).withValues(alpha: 0.22),
            blurRadius: 26,
            spreadRadius: -14,
            offset: const Offset(0, 12),
          ),
        ];
      case AstraSkin.editorial:
        return [
          BoxShadow(
            color: Colors.black.withValues(alpha: 0.55),
            blurRadius: 30,
            spreadRadius: -18,
            offset: const Offset(0, 16),
          ),
        ];
      default:
        return [
          BoxShadow(
            color: const Color(0xFF0B2821).withValues(alpha: palette.isDark ? 0.45 : 0.18),
            blurRadius: 22,
            spreadRadius: -15,
            offset: const Offset(0, 9),
          ),
        ];
    }
  }

  List<BoxShadow> get softShadow => [
        BoxShadow(
          color: const Color(0xFF0B2821).withValues(alpha: palette.isDark ? 0.40 : 0.16),
          blurRadius: 18,
          spreadRadius: -14,
          offset: const Offset(0, 7),
        ),
      ];

  List<BoxShadow> floatShadow(Color tint) => [
        BoxShadow(
          color: tint.withValues(alpha: 0.55),
          blurRadius: 24,
          spreadRadius: -10,
          offset: const Offset(0, 12),
        ),
      ];

  @override
  AstraTheme copyWith({AstraPalette? palette}) =>
      AstraTheme(palette ?? this.palette);

  @override
  AstraTheme lerp(ThemeExtension<AstraTheme>? other, double t) {
    if (other is! AstraTheme) return this;
    return t < 0.5 ? this : other;
  }
}

/// Convenience accessors on [BuildContext].
extension AstraThemeX on BuildContext {
  AstraTheme get astraTheme => Theme.of(this).extension<AstraTheme>()!;
  AstraPalette get astra => astraTheme.palette;
}

ThemeData buildAstraTheme(AstraPalette p) {
  final base = p.isDark ? ThemeData.dark() : ThemeData.light();

  final textTheme = GoogleFonts.manropeTextTheme(base.textTheme).apply(
    bodyColor: p.ink,
    displayColor: p.ink,
  );

  return base.copyWith(
    scaffoldBackgroundColor: p.canvas,
    colorScheme: (p.isDark ? const ColorScheme.dark() : const ColorScheme.light())
        .copyWith(
      primary: p.primary,
      secondary: p.accent,
      surface: p.cardSolid,
      onSurface: p.ink,
    ),
    textTheme: textTheme,
    splashFactory: InkRipple.splashFactory,
    extensions: [AstraTheme(p)],
  );
}

/// Marcellus serif text style — used for brand, prices, totals, KPI numbers.
TextStyle serif({
  required double size,
  Color? color,
  double height = 1.1,
  double letterSpacing = 0,
}) =>
    GoogleFonts.marcellus(
      fontSize: size,
      color: color,
      height: height,
      letterSpacing: letterSpacing,
    );

/// Manrope UI text style.
TextStyle ui({
  required double size,
  FontWeight weight = FontWeight.w600,
  Color? color,
  double height = 1.2,
  double letterSpacing = 0,
}) =>
    GoogleFonts.manrope(
      fontSize: size,
      fontWeight: weight,
      color: color,
      height: height,
      letterSpacing: letterSpacing,
    );
