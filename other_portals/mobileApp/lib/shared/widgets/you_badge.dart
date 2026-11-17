import 'package:flutter/material.dart';

import 'package:invo/shared/utils/components/theme/index.dart';

/// "YOU" — marks the signed-in person's own row wherever a report lists staff
/// (Reports ▸ By Staff, the dashboard's Top performers).
class YouBadge extends StatelessWidget {
  const YouBadge({super.key});

  @override
  Widget build(BuildContext context) {
    final p = context.astra;
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 2),
      decoration: BoxDecoration(gradient: p.primaryGradient, borderRadius: BorderRadius.circular(20)),
      child: Text('YOU', style: ui(size: 8.5, weight: FontWeight.w900, color: Colors.white, letterSpacing: 0.6)),
    );
  }
}
