import 'package:flutter/material.dart';

import 'package:invo/shared/domain/helpers/responsive.dart';

import 'palette.dart';
import 'typeface.dart';

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

  /// Soft card shadow — tuned per skin.
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
            color: const Color(0xFF0B2821)
                .withValues(alpha: palette.isDark ? 0.45 : 0.18),
            blurRadius: 22,
            spreadRadius: -15,
            offset: const Offset(0, 9),
          ),
        ];
    }
  }

  List<BoxShadow> get softShadow => [
        BoxShadow(
          color: const Color(0xFF0B2821)
              .withValues(alpha: palette.isDark ? 0.40 : 0.16),
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

/// Build the single [ThemeData] for the given [AstraPalette] and type pairing.
/// [typeface] defaults to the live choice so a caller that only cares about
/// colour can keep passing a palette alone.
ThemeData buildAstraTheme(AstraPalette p, [AstraTypeface? typeface]) {
  final base = p.isDark ? ThemeData.dark() : ThemeData.light();

  final textTheme =
      (typeface ?? AstraTypefaces.current).textTheme(base.textTheme).apply(
            bodyColor: p.ink,
            displayColor: p.ink,
          );

  return base.copyWith(
    scaffoldBackgroundColor: p.canvas,
    colorScheme:
        (p.isDark ? const ColorScheme.dark() : const ColorScheme.light())
            .copyWith(
      primary: p.primary,
      secondary: p.accent,
      surface: p.cardSolid,
      onSurface: p.ink,
    ),
    textTheme: textTheme,
    splashFactory: InkRipple.splashFactory,
    // Every modal sheet in the app is phone-shaped (keypads, pickers, confirm
    // prompts). Material 3 already centres them and caps them at 640 on a wide
    // viewport; this tightens that to the same column width every other capped
    // surface uses (MaxWidthBox), so a sheet lines up with the form behind it
    // rather than sitting 80px wider. On a phone the viewport is narrower than
    // the cap, so this changes nothing.
    bottomSheetTheme: base.bottomSheetTheme.copyWith(
      constraints: const BoxConstraints(maxWidth: Breakpoints.contentMaxWidth),
    ),
    extensions: [AstraTheme(p)],
  );
}
