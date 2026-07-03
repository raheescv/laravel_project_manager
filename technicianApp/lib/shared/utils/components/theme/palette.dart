import 'package:flutter/material.dart';

/// The surface "skin" a preset wears. Beyond colour, this drives how cards and
/// backgrounds are rendered so the four presets feel genuinely distinct:
///   • [glass]      — frosted translucent cards over an aurora mesh gradient
///   • [clean]      — crisp solid cards, bright bento (no blur, soft shadow)
///   • [editorial]  — dark luxury report: near-black, gold hairlines, serif feel
///   • [signature]  — the original Emerald & Gold cream salon look
enum AstraSkin { glass, clean, editorial, signature }

/// The semantic colour set that drives the whole Astra design system.
///
/// Every preset produces one [AstraPalette]; swapping the palette re-skins the
/// app without changing any layout. The [skin] additionally re-skins surfaces.
@immutable
class AstraPalette {
  const AstraPalette({
    required this.id,
    required this.name,
    required this.tagline,
    required this.skin,
    required this.primary,
    required this.primaryDark,
    required this.accent,
    required this.canvas,
    required this.isDark,
    required this.swatch,
  });

  /// Stable key persisted to preferences.
  final String id;

  /// Human label shown in Settings.
  final String name;

  /// Short descriptor shown under the name in Settings.
  final String tagline;

  /// Surface treatment — see [AstraSkin].
  final AstraSkin skin;

  /// Main brand colour (buttons, headers, active states).
  final Color primary;

  /// Deeper brand colour (gradients, dark surfaces, emphasis text).
  final Color primaryDark;

  /// Gold / champagne / cyan accent (prices, highlights, primary CTA on dark).
  final Color accent;

  /// Page background base.
  final Color canvas;

  /// Whether this preset reads as a dark theme.
  final bool isDark;

  /// The 4 swatch colours shown as the overlapping dots in Settings.
  final List<Color> swatch;

  bool get isGlass => skin == AstraSkin.glass;
  bool get isEditorial => skin == AstraSkin.editorial;

  /// A copy with selected fields overridden — used to derive the dark variant.
  AstraPalette copyWith({bool? isDark, Color? canvas}) => AstraPalette(
        id: id,
        name: name,
        tagline: tagline,
        skin: skin,
        primary: primary,
        primaryDark: primaryDark,
        accent: accent,
        canvas: canvas ?? this.canvas,
        isDark: isDark ?? this.isDark,
        swatch: swatch,
      );

  /// The dark-brightness variant of this preset. Same hue/skin/accents; only the
  /// canvas and the [isDark]-aware surface getters flip.
  AstraPalette get dark {
    if (isDark) return this;
    const darkCanvas = {
      AstraSkin.glass: Color(0xFF0B0E1C),
      AstraSkin.clean: Color(0xFF0E1220),
      AstraSkin.editorial: Color(0xFF0C0B08),
      AstraSkin.signature: Color(0xFF07140F),
    };
    return copyWith(isDark: true, canvas: darkCanvas[skin]);
  }

  // ---- Derived, skin-aware surfaces ----------------------------------------

  /// Card fill as drawn (translucent for glass).
  Color get card {
    switch (skin) {
      case AstraSkin.glass:
        return isDark
            ? Colors.white.withValues(alpha: 0.07)
            : Colors.white.withValues(alpha: 0.62);
      case AstraSkin.editorial:
        return isDark ? const Color(0xFF15140F) : const Color(0xFFFBF8F1);
      case AstraSkin.clean:
        return isDark ? const Color(0xFF171B2B) : Colors.white;
      case AstraSkin.signature:
        return isDark ? const Color(0xFF15302A) : Colors.white;
    }
  }

  /// Opaque card colour — used for Material surfaces (dialogs, sheets).
  Color get cardSolid {
    switch (skin) {
      case AstraSkin.glass:
        return isDark ? const Color(0xFF171A2E) : Colors.white;
      case AstraSkin.editorial:
        return isDark ? const Color(0xFF15140F) : const Color(0xFFFBF8F1);
      case AstraSkin.clean:
        return isDark ? const Color(0xFF171B2B) : Colors.white;
      case AstraSkin.signature:
        return isDark ? const Color(0xFF15302A) : Colors.white;
    }
  }

  /// Border drawn around cards (glass + editorial only; transparent otherwise).
  Color get cardBorder {
    switch (skin) {
      case AstraSkin.glass:
        return isDark
            ? Colors.white.withValues(alpha: 0.12)
            : Colors.white.withValues(alpha: 0.70);
      case AstraSkin.editorial:
        return accent.withValues(alpha: isDark ? 0.16 : 0.30);
      default:
        return Colors.transparent;
    }
  }

  /// Gaussian blur applied behind glass cards (0 = no blur).
  double get surfaceBlur => skin == AstraSkin.glass ? 14 : 0;

  Color get sheet => canvas;

  Color get ink {
    switch (skin) {
      case AstraSkin.editorial:
        return isDark ? const Color(0xFFF1ECE0) : const Color(0xFF1C1A14);
      case AstraSkin.glass:
        return isDark ? const Color(0xFFEAEDF9) : const Color(0xFF13183A);
      case AstraSkin.clean:
        return isDark ? const Color(0xFFEDEFF7) : const Color(0xFF161A2B);
      case AstraSkin.signature:
        return isDark ? const Color(0xFFF3F1EA) : const Color(0xFF16312A);
    }
  }

  Color get textSecondary {
    switch (skin) {
      case AstraSkin.editorial:
        return isDark ? const Color(0xFFB7B09C) : const Color(0xFF54503F);
      case AstraSkin.glass:
        return isDark ? const Color(0xFFAAB2D8) : const Color(0xFF46507E);
      case AstraSkin.clean:
        return isDark ? const Color(0xFFADB3C9) : const Color(0xFF4A4F63);
      case AstraSkin.signature:
        return isDark ? const Color(0xFFA9C2BA) : const Color(0xFF5D7269);
    }
  }

  Color get textMuted {
    switch (skin) {
      case AstraSkin.editorial:
        return isDark ? const Color(0xFF857E6C) : const Color(0xFF928C79);
      case AstraSkin.glass:
        return isDark ? const Color(0xFF7079A6) : const Color(0xFF7F88B3);
      case AstraSkin.clean:
        return isDark ? const Color(0xFF767C92) : const Color(0xFF8A8FA3);
      case AstraSkin.signature:
        return isDark ? const Color(0xFF7C9189) : const Color(0xFFA8B3AB);
    }
  }

  Color get hairline {
    switch (skin) {
      case AstraSkin.editorial:
        return isDark ? const Color(0xFF2A271D) : const Color(0xFFE6DFCE);
      case AstraSkin.glass:
        return isDark
            ? Colors.white.withValues(alpha: 0.10)
            : Colors.white.withValues(alpha: 0.55);
      case AstraSkin.clean:
        return isDark ? const Color(0xFF272C3E) : const Color(0xFFE7E9F2);
      case AstraSkin.signature:
        return isDark ? const Color(0x1AFFFFFF) : const Color(0xFFF0ECE1);
    }
  }

  /// Tinted chip / icon background.
  Color get tint {
    if (skin == AstraSkin.signature) {
      return isDark
          ? primary.withValues(alpha: 0.18)
          : const Color(0xFFE6F0EC);
    }
    return primary.withValues(alpha: isDark ? 0.22 : 0.12);
  }

  Color get goldText {
    switch (skin) {
      case AstraSkin.signature:
        return isDark ? accent : const Color(0xFFBF953F);
      default:
        return accent;
    }
  }

  // Status colours (consistent across presets).
  static const Color success = Color(0xFF1F9D63);
  static const Color danger = Color(0xFFD4546A);

  // Status chip tints.
  Color get successTint =>
      isDark ? success.withValues(alpha: 0.16) : const Color(0xFFE4F6EC);
  Color get dangerTint =>
      isDark ? danger.withValues(alpha: 0.18) : const Color(0xFFFBE9EC);
  Color get warnTint => isDark
      ? const Color(0xFFC9A24B).withValues(alpha: 0.18)
      : const Color(0xFFFBF3DF);
  Color get warnText =>
      isDark ? const Color(0xFFE6C16B) : const Color(0xFF9A6A00);

  /// Header / hero gradient. Editorial overrides to a near-black field.
  LinearGradient get heroGradient {
    if (skin == AstraSkin.editorial) {
      return const LinearGradient(
        begin: Alignment.topLeft,
        end: Alignment.bottomRight,
        colors: [Color(0xFF1F1C13), Color(0xFF0C0B08)],
      );
    }
    return LinearGradient(
      begin: Alignment.topLeft,
      end: Alignment.bottomRight,
      colors: [primary, primaryDark],
    );
  }

  /// Primary button gradient.
  LinearGradient get primaryGradient => LinearGradient(
        begin: Alignment.topLeft,
        end: Alignment.bottomRight,
        colors: [
          Color.lerp(primary, Colors.white, 0.05)!,
          primaryDark,
        ],
      );

  /// Gold CTA gradient.
  LinearGradient get accentGradient => LinearGradient(
        begin: Alignment.topLeft,
        end: Alignment.bottomRight,
        colors: [
          Color.lerp(accent, Colors.white, 0.18)!,
          accent,
        ],
      );

  /// Bottom-nav / floating dark surface.
  Color get darkSurface {
    if (!isDark) return primaryDark;
    switch (skin) {
      case AstraSkin.glass:
        return const Color(0xFF14172B);
      case AstraSkin.clean:
        return const Color(0xFF161A28);
      case AstraSkin.editorial:
        return const Color(0xFF15140F);
      case AstraSkin.signature:
        return const Color(0xFF0A1B17);
    }
  }
}

/// The four premium presets shown in Settings. Aurora Glass is the default.
class AstraPresets {
  static const auroraGlass = AstraPalette(
    id: 'aurora_indigo',
    name: 'Aurora Glass',
    tagline: 'Frosted glass · light',
    skin: AstraSkin.glass,
    primary: Color(0xFF4F46E5),
    primaryDark: Color(0xFF3730A3),
    accent: Color(0xFF38BDF8),
    canvas: Color(0xFFEEF1F7),
    isDark: false,
    swatch: [
      Color(0xFF3730A3),
      Color(0xFF4F46E5),
      Color(0xFF38BDF8),
      Color(0xFFEAF0FF),
    ],
  );

  static const luminousIndigo = AstraPalette(
    id: 'luminous_indigo',
    name: 'Luminous Indigo',
    tagline: 'Clean bento · light',
    skin: AstraSkin.clean,
    primary: Color(0xFF4F46E5),
    primaryDark: Color(0xFF3B35B0),
    accent: Color(0xFF7C3AED),
    canvas: Color(0xFFF4F5FB),
    isDark: false,
    swatch: [
      Color(0xFF3B35B0),
      Color(0xFF4F46E5),
      Color(0xFF7C3AED),
      Color(0xFFF4F5FB),
    ],
  );

  static const emeraldGold = AstraPalette(
    id: 'emerald_gold',
    name: 'Emerald & Gold',
    tagline: 'Cream signature · light',
    skin: AstraSkin.signature,
    primary: Color(0xFF15806C),
    primaryDark: Color(0xFF0E5648),
    accent: Color(0xFFC9A24B),
    canvas: Color(0xFFF6F1E8),
    isDark: false,
    swatch: [
      Color(0xFF0E5648),
      Color(0xFF15806C),
      Color(0xFFC9A24B),
      Color(0xFFF1E9DA),
    ],
  );

  static const editorialNoir = AstraPalette(
    id: 'editorial_noir',
    name: 'Editorial Noir',
    tagline: 'Warm paper · gold · dark hero',
    skin: AstraSkin.editorial,
    primary: Color(0xFFB5862F),
    primaryDark: Color(0xFF8A6A22),
    accent: Color(0xFFC8A24A),
    canvas: Color(0xFFF3EFE6),
    isDark: false,
    swatch: [
      Color(0xFF15140F),
      Color(0xFF8A6A22),
      Color(0xFFC8A24A),
      Color(0xFFF3EFE6),
    ],
  );

  static const List<AstraPalette> all = [
    auroraGlass,
    luminousIndigo,
    emeraldGold,
    editorialNoir,
  ];

  /// The preset applied on a fresh install / when nothing is persisted yet.
  static const AstraPalette fallback = emeraldGold;

  /// Resolve a persisted id to a preset. Unknown ids fall back to the default.
  static AstraPalette byId(String? id) =>
      all.firstWhere((p) => p.id == id, orElse: () => fallback);
}
