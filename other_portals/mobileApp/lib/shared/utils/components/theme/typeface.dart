import 'package:flutter/material.dart';
import 'package:google_fonts/google_fonts.dart';

/// A curated type pairing: one face for UI text (rows, labels, fields) and one
/// for the display line (screen titles, brand, prices, KPI numbers).
///
/// Templates name a pairing, never a raw family, so a stored choice can only
/// ever be one of the faces below.
@immutable
class AstraTypeface {
  const AstraTypeface({
    required this.id,
    required this.name,
    required this.tagline,
    required this.uiFamily,
    required this.displayFamily,
  });

  final String id;
  final String name;
  final String tagline;

  /// Google Fonts family names, case sensitive - `getFont` throws on a typo,
  /// so [_font] falls back to the platform face rather than blanking a screen.
  final String uiFamily;
  final String displayFamily;

  bool get isSingleFace => uiFamily == displayFamily;

  TextStyle uiStyle({
    required double size,
    FontWeight weight = FontWeight.w600,
    Color? color,
    double height = 1.2,
    double letterSpacing = 0,
  }) =>
      _font(uiFamily,
          size: size,
          weight: weight,
          color: color,
          height: height,
          letterSpacing: letterSpacing);

  TextStyle displayStyle({
    required double size,
    Color? color,
    double height = 1.1,
    double letterSpacing = 0,
  }) =>
      _font(displayFamily,
          size: size,
          color: color,
          height: height,
          letterSpacing: letterSpacing);

  /// The Material text theme every unstyled `Text` inherits.
  TextTheme textTheme(TextTheme base) {
    try {
      return GoogleFonts.getTextTheme(uiFamily, base);
    } catch (_) {
      return base;
    }
  }

  static TextStyle _font(
    String family, {
    required double size,
    FontWeight? weight,
    Color? color,
    double height = 1.2,
    double letterSpacing = 0,
  }) {
    try {
      return GoogleFonts.getFont(
        family,
        fontSize: size,
        fontWeight: weight,
        color: color,
        height: height,
        letterSpacing: letterSpacing,
      );
    } catch (_) {
      return TextStyle(
        fontSize: size,
        fontWeight: weight,
        color: color,
        height: height,
        letterSpacing: letterSpacing,
      );
    }
  }
}

/// The pairings the Typography setting offers.
///
/// Deliberately short and opinionated: a free font list only ever produces
/// screens that read worse than the default.
class AstraTypefaces {
  const AstraTypefaces._();

  /// One crisp neutral grotesque everywhere. The default - it is the most
  /// legible at the sizes this app uses and reads as plain and professional
  /// rather than styled.
  static const clean = AstraTypeface(
    id: 'clean',
    name: 'Clean',
    tagline: 'Inter throughout - crisp, neutral, professional',
    uiFamily: 'Inter',
    displayFamily: 'Inter',
  );

  static const modern = AstraTypeface(
    id: 'modern',
    name: 'Modern',
    tagline: 'Manrope throughout - soft and geometric',
    uiFamily: 'Manrope',
    displayFamily: 'Manrope',
  );

  /// Matches the receipts and barcode labels, which print in IBM Plex.
  static const corporate = AstraTypeface(
    id: 'corporate',
    name: 'Corporate',
    tagline: 'IBM Plex Sans - same face as the printed receipt',
    uiFamily: 'IBM Plex Sans',
    displayFamily: 'IBM Plex Sans',
  );

  /// What the app shipped with before Typography was a setting.
  static const signature = AstraTypeface(
    id: 'signature',
    name: 'Signature',
    tagline: 'Marcellus titles over Manrope - the original look',
    uiFamily: 'Manrope',
    displayFamily: 'Marcellus',
  );

  static const editorial = AstraTypeface(
    id: 'editorial',
    name: 'Editorial',
    tagline: 'Fraunces titles over Hanken Grotesk',
    uiFamily: 'Hanken Grotesk',
    displayFamily: 'Fraunces',
  );

  static const all = <AstraTypeface>[
    clean,
    modern,
    corporate,
    signature,
    editorial,
  ];

  static const fallback = clean;

  static AstraTypeface byId(String? id) =>
      all.firstWhere((t) => t.id == id, orElse: () => fallback);

  /// The live pairing. `ui()` and `serif()` are top level helpers with no
  /// BuildContext, so the choice has to be readable without one; ThemeCubit
  /// owns it and keeps this in step with what is persisted.
  static AstraTypeface current = fallback;
}
