import 'package:flutter/material.dart';

import 'package:invo/features/student_card/domain/models/student_card.dart';
import 'package:invo/shared/utils/components/theme/index.dart';

/// Colour, icon and copy for a student's card state — shared by the list row
/// and grid tile on the Link Card screen so both read the same at a glance.
class CardStatus {
  const CardStatus({
    required this.bg,
    required this.fg,
    required this.icon,
    required this.label,
    required this.shortLabel,
  });

  final Color bg;
  final Color fg;
  final IconData icon;

  /// Full copy for the list row, e.g. "Card 8979878 · tap to replace".
  final String label;

  /// One or two words for the grid tile's pill, e.g. "Linked".
  final String shortLabel;
}

CardStatus cardStatusOf(StudentCard card, AstraPalette p) {
  if (card.isBlocked) {
    return CardStatus(
      bg: p.dangerTint,
      fg: AstraPalette.danger,
      icon: Icons.block_rounded,
      label: 'Card ${card.cardUid} · blocked',
      shortLabel: 'Blocked',
    );
  }
  if (card.hasCard) {
    return CardStatus(
      bg: p.successTint,
      fg: AstraPalette.success,
      icon: Icons.nfc_rounded,
      label: 'Card ${card.cardUid} · tap to replace',
      shortLabel: 'Linked',
    );
  }
  return CardStatus(
    bg: p.goldTint,
    fg: p.goldText,
    icon: Icons.add_link,
    label: 'No card linked · tap to link',
    shortLabel: 'Not linked',
  );
}
