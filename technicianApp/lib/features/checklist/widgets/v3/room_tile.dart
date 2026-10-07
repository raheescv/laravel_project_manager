import 'package:flutter/material.dart';

import 'package:invo/shared/utils/components/theme/index.dart';

import '../../domain/models/checklist_models.dart';
import 'atelier_parts.dart';

/// A room on the Rooms screen: photo mosaic of this phase's shots, name,
/// damaged flag, status bar and "x of y checked".
class RoomTile extends StatelessWidget {
  const RoomTile({super.key, required this.room, required this.phase, this.onTap, this.mosaicHeight = 74});
  final ChecklistRoom room;
  final String phase;
  final VoidCallback? onTap;
  final double mosaicHeight;

  @override
  Widget build(BuildContext context) {
    final p = context.astra;
    final checked = room.checkedFor(phase);
    final bad = room.damagedFor(phase);
    final done = room.isDoneFor(phase);
    final photos = room.lines.map((l) => l.imageFor(phase)).whereType<String>().toList();
    return AtelierCard(
      onTap: onTap,
      padding: EdgeInsets.zero,
      clip: true,
      child: Stack(children: [
        Column(crossAxisAlignment: CrossAxisAlignment.stretch, children: [
          SizedBox(height: mosaicHeight, child: _Mosaic(photos: photos, icon: roomIconFor(room.name))),
          Padding(
            padding: const EdgeInsets.fromLTRB(10, 9, 10, 10),
            child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
              Row(children: [
                Icon(roomIconFor(room.name), size: 14, color: p.primary),
                const SizedBox(width: 6),
                Expanded(
                  child: Text(room.name,
                      maxLines: 1, overflow: TextOverflow.ellipsis, style: ui(size: 13, weight: FontWeight.w800, color: p.ink)),
                ),
                if (bad > 0)
                  Container(
                    padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 1),
                    decoration: BoxDecoration(color: ColorManager.danger, borderRadius: BorderRadius.circular(6)),
                    child: Text('$bad ✗', style: ui(size: 9.5, weight: FontWeight.w800, color: ColorManager.white)),
                  ),
              ]),
              const SizedBox(height: 8),
              StatusSegmentBar(ok: room.okFor(phase), bad: bad, total: room.lines.length),
              const SizedBox(height: 5),
              Text('$checked of ${room.lines.length} checked',
                  style: ui(size: 10.5, weight: FontWeight.w700, color: p.textMuted)),
            ]),
          ),
        ]),
        if (done)
          Positioned(
            top: 7,
            right: 7,
            child: Container(
              width: 24,
              height: 24,
              decoration: BoxDecoration(
                shape: BoxShape.circle,
                color: p.accent,
                boxShadow: [BoxShadow(color: ColorManager.black.withValues(alpha: 0.25), blurRadius: 8, offset: const Offset(0, 2))],
              ),
              child: const Icon(Icons.check, size: 13, color: ColorManager.white),
            ),
          ),
      ]),
    );
  }
}

class _Mosaic extends StatelessWidget {
  const _Mosaic({required this.photos, required this.icon});
  final List<String> photos;
  final IconData icon;

  @override
  Widget build(BuildContext context) {
    final p = context.astra;
    if (photos.isEmpty) return HatchedBox(child: Icon(icon, size: 22, color: p.textMuted));
    Widget cell(int i) => i < photos.length
        ? ChecklistPhoto(path: photos[i])
        : Container(
            color: p.goldTint,
            alignment: Alignment.center,
            child: Icon(Icons.photo_camera_outlined, size: 13, color: p.goldText),
          );
    return Row(crossAxisAlignment: CrossAxisAlignment.stretch, children: [
      Expanded(flex: 2, child: cell(0)),
      const SizedBox(width: 2),
      Expanded(
        child: Column(crossAxisAlignment: CrossAxisAlignment.stretch, children: [
          Expanded(child: cell(1)),
          const SizedBox(height: 2),
          Expanded(child: cell(2)),
        ]),
      ),
    ]);
  }
}

/// Full-width "Hand-over & signatures" tile closing the room grid.
class HandoverTile extends StatelessWidget {
  const HandoverTile({super.key, required this.detail, this.onTap});
  final ChecklistDetail detail;
  final VoidCallback? onTap;

  @override
  Widget build(BuildContext context) {
    final p = context.astra;
    final left = detail.uncheckedCount;
    final sigs = '${detail.signaturesDone} of ${detail.signatures.isEmpty ? 3 : detail.signatures.length} signed';
    final ready = left == 0;
    final String sub;
    if (detail.sealed) {
      sub = 'Sealed';
    } else if (detail.readyToSeal) {
      sub = 'All signed · ready to seal';
    } else {
      sub = ready ? 'Ready to sign · $sigs' : '$left item${left == 1 ? '' : 's'} left · $sigs';
    }
    final body = Container(
      padding: const EdgeInsets.fromLTRB(14, 13, 14, 13),
      decoration: BoxDecoration(
        color: ready ? p.goldTint : null,
        borderRadius: BorderRadius.circular(18),
        border: ready ? Border.all(color: p.accent, width: 1.5) : null,
      ),
      child: Row(children: [
        Container(
          width: 38,
          height: 38,
          decoration: BoxDecoration(color: p.goldTint, borderRadius: BorderRadius.circular(12)),
          child: Icon(ready ? Icons.draw_outlined : Icons.lock_outline, size: 17, color: p.goldText),
        ),
        const SizedBox(width: 12),
        Expanded(
          child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
            Text('Hand-over & signatures', style: ui(size: 13.5, weight: FontWeight.w800, color: p.ink)),
            const SizedBox(height: 2),
            Text(sub, style: ui(size: 11, weight: FontWeight.w700, color: p.goldText)),
          ]),
        ),
        Icon(Icons.chevron_right, size: 20, color: p.goldText),
      ]),
    );
    return GestureDetector(
      onTap: onTap,
      behavior: HitTestBehavior.opaque,
      child: ready ? body : DashedBorderBox(color: p.accent, radius: 18, child: body),
    );
  }
}
