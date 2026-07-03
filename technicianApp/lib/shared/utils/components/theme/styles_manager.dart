import 'package:flutter/material.dart';
import 'package:google_fonts/google_fonts.dart';

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
