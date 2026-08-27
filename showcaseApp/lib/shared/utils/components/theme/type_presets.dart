import 'package:flutter/material.dart';
import 'package:google_fonts/google_fonts.dart';

/// The typefaces the tablet can be set in.
///
/// A pairing, not a font: a display face for the one headline on each screen
/// and a text face for everything else, plus how hard the micro labels are
/// tracked. Pearl's voice is wide tracking, and a face that is already
/// distinctive does not want as much of it — so the tracking travels with the
/// pairing rather than being fixed in the type scale.
///
/// Arabic ignores all of this and uses IBM Plex Sans Arabic: no Latin display
/// face carries Arabic glyphs, so the second script is always a substitution.
enum TypePreset {
  jost(
    'Jost',
    'Geometric · one family',
    display: TypeFace.jost,
    text: TypeFace.jost,
    tracking: 1,
  ),
  editorial(
    'Fraunces',
    'Soft serif · storefront pairing',
    display: TypeFace.fraunces,
    text: TypeFace.hanken,
    tracking: .65,
  ),
  magazine(
    'Instrument',
    'High-contrast serif · editorial',
    display: TypeFace.instrument,
    text: TypeFace.manrope,
    tracking: .47,
  ),
  neutral(
    'Inter Tight',
    'Workhorse · nothing styled',
    display: TypeFace.inter,
    text: TypeFace.inter,
    tracking: .35,
  );

  const TypePreset(
    this.label,
    this.blurb, {
    required this.display,
    required this.text,
    required this.tracking,
  });

  final String label;
  final String blurb;
  /// The headline face and the everything-else face.
  final TypeFace display;
  final TypeFace text;

  /// Multiplier on the type scale's tracking. Jost was drawn at 1.
  final double tracking;

  static TypePreset decode(String? raw, TypePreset fallback) =>
      TypePreset.values.firstWhere((p) => p.name == raw, orElse: () => fallback);

  /// A sample in this pairing's own display face, for the picker.
  TextStyle sample(double size) => display.style(
        size: size,
        weight: FontWeight.w300,
        letterSpacing: 0,
        height: null,
      );
}

/// The individual faces, so a pairing can name one without repeating the
/// GoogleFonts call at every role. Public only because the pairings above
/// expose which one they use.
enum TypeFace {
  jost,
  fraunces,
  hanken,
  instrument,
  manrope,
  inter,
  plexArabic;

  TextStyle style({
    required double size,
    required FontWeight weight,
    required double letterSpacing,
    required double? height,
  }) {
    final args = (
      fontSize: size,
      fontWeight: weight,
      letterSpacing: letterSpacing,
      height: height,
    );
    return switch (this) {
      TypeFace.jost => GoogleFonts.jost(
          fontSize: args.fontSize,
          fontWeight: args.fontWeight,
          letterSpacing: args.letterSpacing,
          height: args.height),
      TypeFace.fraunces => GoogleFonts.fraunces(
          fontSize: args.fontSize,
          fontWeight: args.fontWeight,
          letterSpacing: args.letterSpacing,
          height: args.height),
      TypeFace.hanken => GoogleFonts.hankenGrotesk(
          fontSize: args.fontSize,
          fontWeight: args.fontWeight,
          letterSpacing: args.letterSpacing,
          height: args.height),
      // Instrument Serif ships one weight; asking for another silently
      // synthesises a bolder face that does not match the drawing.
      TypeFace.instrument => GoogleFonts.instrumentSerif(
          fontSize: args.fontSize,
          letterSpacing: args.letterSpacing,
          height: args.height),
      TypeFace.manrope => GoogleFonts.manrope(
          fontSize: args.fontSize,
          fontWeight: args.fontWeight,
          letterSpacing: args.letterSpacing,
          height: args.height),
      TypeFace.inter => GoogleFonts.interTight(
          fontSize: args.fontSize,
          fontWeight: args.fontWeight,
          letterSpacing: args.letterSpacing,
          height: args.height),
      TypeFace.plexArabic => GoogleFonts.ibmPlexSansArabic(
          fontSize: args.fontSize,
          fontWeight: args.fontWeight,
          letterSpacing: args.letterSpacing,
          height: args.height),
    };
  }
}

/// Reached by [PearlText] once it knows the script.
extension TypePresetFaces on TypePreset {
  TextStyle displayStyle({
    required double size,
    required FontWeight weight,
    required double letterSpacing,
    required double? height,
    required bool arabic,
  }) =>
      (arabic ? TypeFace.plexArabic : display).style(
        size: size,
        weight: weight,
        letterSpacing: letterSpacing,
        height: height,
      );

  TextStyle textStyle({
    required double size,
    required FontWeight weight,
    required double letterSpacing,
    required double? height,
    required bool arabic,
  }) =>
      (arabic ? TypeFace.plexArabic : text).style(
        size: size,
        weight: weight,
        letterSpacing: letterSpacing,
        height: height,
      );
}
