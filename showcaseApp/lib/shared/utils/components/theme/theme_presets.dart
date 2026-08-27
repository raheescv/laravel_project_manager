import 'package:flutter/material.dart';

import '../../../../l10n/app_localizations.dart';
import 'pearl_theme.dart';

/// The palettes the tablet can be dressed in, and the only other place in the
/// app allowed to name a colour.
///
/// Three of the design directions from `docs/showcase-app-3-samples.html`,
/// transcribed rather than invented — each one was drawn as a whole scheme, so
/// mixing values between them is how you get a screen that looks like neither.
/// Pearl leads because it is what the app is built in; the other two are the
/// furthest from it, which is the point of offering a choice at all.
/// Sizerun is the fourth and the only one not drawn as a direction: it is the
/// shop's own logo — paper, black type, ultramarine — read straight off the
/// mark, for a floor that wants the tablet to match the sign above it.
/// Only the colour is adopted: the typeface stays Jost for every preset, since
/// the directions' type is bound up with their spacing and radii and swapping
/// it at runtime would re-lay out every screen.
///
/// Light and dark are chosen independently — a shop floor that wants paper by
/// day and obsidian after dark is the whole reason the setting exists.
enum ThemePreset {
  pearl(
    'Pearl',
    'Cool pearl and graphite. No accent hue at all — selection is an ink block.',
    PearlPalette.light,
    PearlPalette.dark,
  ),
  noir(
    'Atelier Noir',
    'Ivory paper, ink type, a brass hairline. Reads like a lookbook.',
    _noirLight,
    _noirDark,
  ),
  aurora(
    'Aurora Glass',
    'Indigo-to-cyan light behind frosted panels. The friendliest of the set.',
    _auroraLight,
    _auroraDark,
  ),
  sizerun(
    'Sizerun',
    'Paper white, black type, an electric ultramarine block. The house colours.',
    _sizerunLight,
    _sizerunDark,
  );

  const ThemePreset(this.label, this.blurbKey, this.light, this.dark);

  /// The direction's name — a proper noun, the same in every language.
  final String label;

  /// Looked up rather than stored, so the description translates while the
  /// name does not.
  final String blurbKey;

  String blurb(L t) => switch (this) {
        ThemePreset.pearl => t.presetPearlBlurb,
        ThemePreset.noir => t.presetNoirBlurb,
        ThemePreset.aurora => t.presetAuroraBlurb,
        ThemePreset.sizerun => t.presetSizerunBlurb,
      };

  /// The day palette and the night palette this direction was drawn with. Only
  /// one of the two is used at a time — which depends on the slot the preset
  /// was picked for.
  final PearlPalette light;
  final PearlPalette dark;

  /// The palette this preset contributes to [brightness].
  PearlPalette paletteFor(Brightness brightness) =>
      brightness == Brightness.dark ? dark : light;

  /// Four colours that identify the direction at a glance, in the order a
  /// swatch row reads best: ground, ink, accent, stage.
  List<Color> swatches(Brightness brightness) {
    final p = paletteFor(brightness);
    return [p.bg, p.ink, p.accent, p.shotTop];
  }

  static ThemePreset decode(String? raw, ThemePreset fallback) =>
      ThemePreset.values.firstWhere((p) => p.name == raw, orElse: () => fallback);
}

// ── Atelier Noir ────────────────────────────────────────────────────────────
const PearlPalette _noirLight = PearlPalette(
  bg: Color(0xFFF7F4EE),
  surface: Color(0xFFFFFDF8),
  shotTop: Color(0xFFF6F2E9),
  shotBottom: Color(0xFFEFEAE0),
  ink: Color(0xFF1A1714),
  muted: Color(0xFF6E655A),
  faint: Color(0xFFA79C8D),
  line: Color(0xFFE3DCCE),
  accent: Color(0xFF8A6A2F),
  accentInk: Color(0xFFFFFDF8),
  ok: Color(0xFF3F6B4A),
  okBg: Color(0xFFE8EFE7),
  brightness: Brightness.light,
);

const PearlPalette _noirDark = PearlPalette(
  bg: Color(0xFF141210),
  surface: Color(0xFF1E1B17),
  shotTop: Color(0xFF2C2720),
  shotBottom: Color(0xFF242019),
  ink: Color(0xFFF2EDE3),
  muted: Color(0xFFA79C8D),
  faint: Color(0xFF75695A),
  line: Color(0xFF2E2921),
  accent: Color(0xFFC99B4E),
  accentInk: Color(0xFF141210),
  ok: Color(0xFF7FB08C),
  okBg: Color(0x247FB08C),
  brightness: Brightness.dark,
);

// ── Aurora Glass ────────────────────────────────────────────────────────────
// The alphas are the direction's own: its surfaces and hairlines are frosted,
// and flattening them to opaque loses the depth the whole scheme is built on.
const PearlPalette _auroraLight = PearlPalette(
  bg: Color(0xFFF4F5FC),
  surface: Color(0xB8FFFFFF),
  shotTop: Color(0xFFE8EAFB),
  shotBottom: Color(0xFFE4F3FA),
  ink: Color(0xFF191B33),
  muted: Color(0xFF5F6285),
  faint: Color(0xFF9EA1C0),
  line: Color(0x296056B4),
  accent: Color(0xFF5B4CE0),
  accentInk: Color(0xFFFFFFFF),
  ok: Color(0xFF0E9F6E),
  okBg: Color(0x1F0E9F6E),
  brightness: Brightness.light,
);

const PearlPalette _auroraDark = PearlPalette(
  bg: Color(0xFF0B0C1C),
  surface: Color(0x0FFFFFFF),
  shotTop: Color(0xFF1B1D3D),
  shotBottom: Color(0xFF122B3D),
  ink: Color(0xFFEDEEFB),
  muted: Color(0xFFA0A3C8),
  faint: Color(0xFF6D719B),
  line: Color(0x1FFFFFFF),
  accent: Color(0xFF8B7BFF),
  accentInk: Color(0xFF0B0C1C),
  ok: Color(0xFF3DD9A0),
  okBg: Color(0x243DD9A0),
  brightness: Brightness.dark,
);

// ── Sizerun ─────────────────────────────────────────────────────────────────
// Straight off the logo: #101DC1 ultramarine, black type, paper ground. The
// blue is the only hue in the scheme and it is never tinted down — it is a
// brand mark, so a wash of it reads as the wrong blue rather than a soft one.
const PearlPalette _sizerunLight = PearlPalette(
  bg: Color(0xFFFFFFFF),
  surface: Color(0xFFF6F6FA),
  shotTop: Color(0xFFFFFFFF),
  shotBottom: Color(0xFFEDEEF6),
  ink: Color(0xFF000000),
  muted: Color(0xFF5A5C6B),
  faint: Color(0xFF9A9CAB),
  line: Color(0xFFE2E3EC),
  accent: Color(0xFF101DC1),
  accentInk: Color(0xFFFFFFFF),
  ok: Color(0xFF0E7C4A),
  okBg: Color(0xFFE3F3EA),
  brightness: Brightness.light,
);

// The mark's own blue sits too close to the ground once the ground is black —
// #101DC1 on #08080C is a dim smudge — so night lifts it to #4A57FF, the same
// hue with the value it needs to still read as a block.
const PearlPalette _sizerunDark = PearlPalette(
  bg: Color(0xFF08080C),
  surface: Color(0xFF121218),
  shotTop: Color(0xFF1D1E2C),
  shotBottom: Color(0xFF101017),
  ink: Color(0xFFF4F4F8),
  muted: Color(0xFF9C9EB0),
  faint: Color(0xFF63657A),
  line: Color(0xFF23242E),
  accent: Color(0xFF4A57FF),
  accentInk: Color(0xFFFFFFFF),
  ok: Color(0xFF3FBF83),
  okBg: Color(0x243FBF83),
  brightness: Brightness.dark,
);
