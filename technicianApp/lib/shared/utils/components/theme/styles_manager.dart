import 'package:flutter/material.dart';

import 'typeface.dart';

/// Display text style — brand, screen titles, prices, totals, KPI numbers.
/// The face comes from the Typography setting, not from this file.
TextStyle serif({
  required double size,
  Color? color,
  double height = 1.1,
  double letterSpacing = 0,
}) =>
    AstraTypefaces.current.displayStyle(
      size: size,
      color: color,
      height: height,
      letterSpacing: letterSpacing,
    );

/// UI text style — rows, labels, fields, buttons. Follows the same setting.
TextStyle ui({
  required double size,
  FontWeight weight = FontWeight.w600,
  Color? color,
  double height = 1.2,
  double letterSpacing = 0,
}) =>
    AstraTypefaces.current.uiStyle(
      size: size,
      weight: weight,
      color: color,
      height: height,
      letterSpacing: letterSpacing,
    );
