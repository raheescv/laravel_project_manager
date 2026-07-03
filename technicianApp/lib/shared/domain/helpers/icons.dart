import 'package:flutter/material.dart';

/// Maps a category / service name to a tasteful Material icon, since the live
/// catalog has no icon field. Keeps the salon feel of the mockups.
IconData iconForName(String raw) {
  final s = raw.toLowerCase();
  if (s.contains('hair') ||
      s.contains('cut') ||
      s.contains('trim') ||
      s.contains('fade')) {
    return Icons.content_cut;
  }
  if (s.contains('color') ||
      s.contains('colour') ||
      s.contains('balayage') ||
      s.contains('gloss') ||
      s.contains('dye') ||
      s.contains('paint')) {
    return Icons.brush;
  }
  if (s.contains('spa') ||
      s.contains('massage') ||
      s.contains('facial') ||
      s.contains('ritual')) {
    return Icons.spa;
  }
  if (s.contains('nail') || s.contains('mani') || s.contains('pedi')) {
    return Icons.back_hand;
  }
  if (s.contains('beard') || s.contains('shave')) {
    return Icons.face_retouching_natural;
  }
  if (s.contains('wash') ||
      s.contains('shampoo') ||
      s.contains('treatment')) {
    return Icons.water_drop;
  }
  if (s.contains('style') || s.contains('blow') || s.contains('dry')) {
    return Icons.auto_fix_high;
  }
  if (s.contains('product') || s.contains('retail')) {
    return Icons.shopping_bag_outlined;
  }
  return Icons.spa;
}
