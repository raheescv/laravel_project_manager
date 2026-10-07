import 'package:flutter/material.dart';

import 'package:invo/shared/domain/helpers/formatters.dart';
import 'package:invo/shared/utils/components/theme/index.dart';

import '../../domain/models/checklist_models.dart';
import 'atelier_parts.dart';

/// A checklist line in the room grid: this phase's photo (or a hatched
/// "Tap to inspect"), status badge, move-in tag on a move-out, qty, comment
/// and damage cost.
class ChecklistItemCard extends StatelessWidget {
  const ChecklistItemCard({super.key, required this.line, required this.phase, this.onTap, this.photoHeight = 98});
  final ChecklistLine line;
  final String phase;
  final VoidCallback? onTap;
  final double photoHeight;

  @override
  Widget build(BuildContext context) {
    final p = context.astra;
    final photo = line.imageFor(phase);
    final moveOut = phase == ChecklistPhase.moveOut;
    final hasComment = (line.commentFor(phase) ?? '').isNotEmpty;
    final showCost = moveOut && line.isDamagedFor(phase) && line.damageCost > 0;
    final metaStyle = ui(size: 10.5, weight: FontWeight.w700, color: p.textMuted);
    return AtelierCard(
      onTap: onTap,
      padding: EdgeInsets.zero,
      clip: true,
      child: Stack(children: [
        Column(crossAxisAlignment: CrossAxisAlignment.stretch, children: [
          SizedBox(
            height: photoHeight,
            child: photo != null
                ? ChecklistPhoto(path: photo)
                : HatchedBox(
                    child: Column(mainAxisSize: MainAxisSize.min, children: [
                      Icon(Icons.photo_camera_outlined, size: 18, color: p.textMuted),
                      const SizedBox(height: 3),
                      Text('Tap to inspect', style: ui(size: 11, weight: FontWeight.w800, color: p.textMuted)),
                    ]),
                  ),
          ),
          Padding(
            padding: const EdgeInsets.fromLTRB(10, 8, 10, 10),
            child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
              Text(line.name,
                  maxLines: 1, overflow: TextOverflow.ellipsis, style: ui(size: 12.5, weight: FontWeight.w800, color: p.ink)),
              const SizedBox(height: 3),
              Row(children: [
                Text('Qty ${qtyLabel(line.qty)}', style: metaStyle),
                if (hasComment) ...[
                  Text(' · ', style: metaStyle),
                  Icon(Icons.chat_bubble_outline, size: 11, color: p.textMuted),
                ],
                if (showCost) ...[
                  Text(' · ', style: metaStyle),
                  Flexible(
                    child: Text(Money.of(line.damageCost),
                        maxLines: 1,
                        overflow: TextOverflow.ellipsis,
                        style: ui(size: 10.5, weight: FontWeight.w800, color: ColorManager.danger)),
                  ),
                ],
              ]),
            ]),
          ),
        ]),
        Positioned(top: 8, right: 8, child: LineStatusBadge(status: line.statusFor(phase))),
        if (moveOut)
          Positioned(
            left: 8,
            top: photoHeight - 26,
            child: Container(
              padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 2),
              decoration: BoxDecoration(color: ColorManager.black.withValues(alpha: 0.55), borderRadius: BorderRadius.circular(6)),
              child: Text(line.moveInStatus == ChecklistLineStatus.ok ? 'IN ✓' : 'IN —',
                  style: ui(size: 9, weight: FontWeight.w800, letterSpacing: 0.3, color: ColorManager.white)),
            ),
          ),
      ]),
    );
  }
}
