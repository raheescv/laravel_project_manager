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

/// A face's resolved identity at one weight: the two strings a [TextStyle] has
/// to name to be painted in it.
typedef _FontId = ({String? family, List<String>? fallback});

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

  /// What each face resolves to, per weight, once.
  ///
  /// Reaching a `GoogleFonts` method is not the map lookup it reads as. Every
  /// call rebuilds the family's whole variant table — eighteen entries for Jost,
  /// each one an allocation — scores all of them against the weight asked for,
  /// builds two TextStyles, and schedules a load future with a closure and a
  /// set entry behind it. And when the font has not landed yet it starts a file
  /// read and an HTTP fetch, so an offline panel was doing both of those per
  /// call, forever.
  ///
  /// Per call matters because [PearlText] is reached statically from eighty-odd
  /// sites, which is once per `Text` per build: a results tile pays three times
  /// over and the grid pays three more measuring the caption above it, on every
  /// layout, for every tile on screen.
  ///
  /// What comes back that we cannot compute ourselves is two strings — the
  /// variant's family name and its fallback — and for a given face and weight
  /// they never change. So they are asked for once and the style is built from
  /// them after that.
  static final Map<TypeFace, Map<int, _FontId>> _ids = {};

  /// Forget the resolved names, so the next style asks for them again.
  ///
  /// Which re-arms the download. The first call for a face is what starts the
  /// fetch, and a panel that was offline when it started has no font behind the
  /// name it cached — google_fonts drops a failed family from its own loaded
  /// set, so asking again is all it takes. [ConnectivityCubit] calls this when
  /// the server comes back.
  static void forgetResolved() => _ids.clear();

  /// Whether asking this face for a weight means anything.
  ///
  /// Instrument Serif ships one; asking for another silently synthesises a
  /// bolder face that does not match the drawing, which is why the call below
  /// leaves it out — and so, therefore, does the style built from it.
  bool get _weighted => this != TypeFace.instrument;

  TextStyle style({
    required double size,
    required FontWeight weight,
    required double letterSpacing,
    required double? height,
  }) {
    final id = _identity(_weighted ? weight : FontWeight.w400);
    return TextStyle(
      fontSize: size,
      fontWeight: _weighted ? weight : null,
      letterSpacing: letterSpacing,
      height: height,
      fontFamily: id.family,
      fontFamilyFallback: id.fallback,
    );
  }

  /// Keyed on the weight's number rather than the object: `FontWeight` is a
  /// const set, but a synthesised one would hash apart from its own value.
  _FontId _identity(FontWeight weight) =>
      (_ids[this] ??= <int, _FontId>{}).putIfAbsent(weight.value, () {
        final resolved = _resolve(weight);
        return (
          family: resolved.fontFamily,
          // Copied, and fixed-length: the list google_fonts hands back is
          // growable and is about to be shared by every style in this face.
          fallback: resolved.fontFamilyFallback?.toList(growable: false),
        );
      });

  /// The one call per face and weight that this whole file exists to avoid
  /// making twice. It also starts the font loading, which is the side effect
  /// the identity is worth nothing without.
  TextStyle _resolve(FontWeight weight) => switch (this) {
        TypeFace.jost => GoogleFonts.jost(fontWeight: weight),
        TypeFace.fraunces => GoogleFonts.fraunces(fontWeight: weight),
        TypeFace.hanken => GoogleFonts.hankenGrotesk(fontWeight: weight),
        TypeFace.instrument => GoogleFonts.instrumentSerif(),
        TypeFace.manrope => GoogleFonts.manrope(fontWeight: weight),
        TypeFace.inter => GoogleFonts.interTight(fontWeight: weight),
        TypeFace.plexArabic => GoogleFonts.ibmPlexSansArabic(fontWeight: weight),
      };
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
