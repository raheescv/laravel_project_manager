import 'package:flutter/material.dart';

import 'package:invo/shared/utils/components/theme/index.dart';

import '../../domain/models/stock_check_models.dart';

/// Pill colours + icon for a count's own status, so the list card, the counting
/// screen and the picker sheet all read the same.
({Color bg, Color fg, IconData icon}) stockCheckStatusStyle(BuildContext context, String status) {
  final p = context.astra;
  return switch (status) {
    StockCheckStatus.completed => (bg: p.successTint, fg: AstraPalette.success, icon: Icons.check_circle),
    StockCheckStatus.cancelled => (bg: p.dangerTint, fg: AstraPalette.danger, icon: Icons.block),
    _ => (bg: p.warnTint, fg: p.warnText, icon: Icons.schedule),
  };
}

/// The status pill. Tappable when [onTap] is supplied — it carries a small caret
/// then, so it reads as a control and not just a label.
class StockCheckStatusPill extends StatelessWidget {
  const StockCheckStatusPill({super.key, required this.status, this.onTap, this.onDark = false});

  final String status;
  final VoidCallback? onTap;

  /// On the gradient hero the tinted pill backgrounds disappear — a translucent
  /// white chip reads instead.
  final bool onDark;

  @override
  Widget build(BuildContext context) {
    final s = stockCheckStatusStyle(context, status);
    final bg = onDark ? Colors.white.withValues(alpha: 0.16) : s.bg;
    final fg = onDark ? Colors.white : s.fg;
    final pill = Container(
      padding: const EdgeInsets.symmetric(horizontal: 9, vertical: 5),
      decoration: BoxDecoration(
        color: bg,
        borderRadius: BorderRadius.circular(20),
        border: onDark ? Border.all(color: Colors.white.withValues(alpha: 0.22)) : null,
      ),
      child: Row(
        mainAxisSize: MainAxisSize.min,
        children: [
          Icon(s.icon, size: 11, color: fg),
          const SizedBox(width: 4),
          Text(StockCheckStatus.label(status).toUpperCase(), style: ui(size: 10, weight: FontWeight.w800, color: fg)),
          if (onTap != null) ...[
            const SizedBox(width: 3),
            Icon(Icons.expand_more, size: 12, color: fg),
          ],
        ],
      ),
    );
    if (onTap == null) return pill;
    return GestureDetector(behavior: HitTestBehavior.opaque, onTap: onTap, child: pill);
  }
}

/// Click-and-go status picker: tapping an option applies it, there is no Save.
/// Returns null when the sheet was dismissed or the current status re-picked.
Future<String?> pickStockCheckStatus(BuildContext context, {required String current}) async {
  final p = context.astra;
  final picked = await showModalBottomSheet<String>(
    context: context,
    backgroundColor: Colors.transparent,
    builder: (ctx) => Container(
      decoration: BoxDecoration(color: p.cardSolid, borderRadius: const BorderRadius.vertical(top: Radius.circular(24))),
      padding: const EdgeInsets.fromLTRB(18, 12, 18, 26),
      child: Column(
        mainAxisSize: MainAxisSize.min,
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Center(child: Container(width: 40, height: 4, margin: const EdgeInsets.only(bottom: 16), decoration: BoxDecoration(color: p.hairline, borderRadius: BorderRadius.circular(4)))),
          Text('Count status', style: serif(size: 19, color: p.ink)),
          const SizedBox(height: 6),
          Text('Where this count stands. Counted quantities and real inventory are not changed — only marking an item done reconciles stock.',
              style: ui(size: 12, weight: FontWeight.w600, color: p.textSecondary, height: 1.5)),
          const SizedBox(height: 14),
          for (final status in StockCheckStatus.all)
            _StatusOption(status: status, active: status == current, onTap: () => Navigator.pop(ctx, status)),
        ],
      ),
    ),
  );
  return picked == current ? null : picked;
}

class _StatusOption extends StatelessWidget {
  const _StatusOption({required this.status, required this.active, required this.onTap});
  final String status;
  final bool active;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    final p = context.astra;
    final s = stockCheckStatusStyle(context, status);
    return GestureDetector(
      behavior: HitTestBehavior.opaque,
      onTap: onTap,
      child: Container(
        margin: const EdgeInsets.only(bottom: 8),
        padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 12),
        decoration: BoxDecoration(
          color: active ? p.tint : Colors.transparent,
          borderRadius: BorderRadius.circular(14),
          border: Border.all(color: active ? p.primary.withValues(alpha: 0.35) : p.cardBorder),
        ),
        child: Row(
          children: [
            Container(
              width: 30,
              height: 30,
              alignment: Alignment.center,
              decoration: BoxDecoration(color: s.bg, borderRadius: BorderRadius.circular(9)),
              child: Icon(s.icon, size: 15, color: s.fg),
            ),
            const SizedBox(width: 11),
            Expanded(child: Text(StockCheckStatus.label(status), style: ui(size: 13.5, weight: FontWeight.w800, color: p.ink))),
            if (active) Icon(Icons.check, size: 16, color: p.primary),
          ],
        ),
      ),
    );
  }
}
