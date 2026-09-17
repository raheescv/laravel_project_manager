import 'package:flutter/material.dart';

import 'package:invo/shared/domain/models/index.dart';
import 'package:invo/shared/utils/components/theme/index.dart';

/// One branch in a click-and-go list — the Settings branch sheet and the
/// sign-in branch picker draw the same row. [busy] swaps the check for a
/// spinner while the tap is being applied.
class BranchTile extends StatelessWidget {
  const BranchTile({
    super.key,
    required this.branch,
    required this.active,
    required this.onTap,
    this.busy = false,
  });

  final Branch branch;
  final bool active;
  final bool busy;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    final p = context.astra;
    return GestureDetector(
      onTap: onTap,
      behavior: HitTestBehavior.opaque,
      child: Container(
        margin: const EdgeInsets.symmetric(vertical: 4, horizontal: 4),
        padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 11),
        decoration: BoxDecoration(
          color: p.card,
          borderRadius: BorderRadius.circular(14),
          boxShadow: context.astraTheme.softShadow,
          border: Border.all(color: active ? p.primary : Colors.transparent, width: 1.5),
        ),
        child: Row(
          children: [
            Container(
              width: 42,
              height: 42,
              alignment: Alignment.center,
              decoration: BoxDecoration(color: p.tint, borderRadius: BorderRadius.circular(12)),
              child: Icon(Icons.storefront_outlined, size: 20, color: p.primaryDark),
            ),
            const SizedBox(width: 12),
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(branch.name, style: ui(size: 13, weight: FontWeight.w700, color: p.ink)),
                  Text(
                    branch.location.isEmpty || branch.location == branch.name ? branch.code : branch.location,
                    style: ui(size: 10.5, weight: FontWeight.w600, color: p.textMuted, letterSpacing: 0.3),
                  ),
                ],
              ),
            ),
            if (busy)
              SizedBox(
                width: 24,
                height: 24,
                child: Padding(
                  padding: const EdgeInsets.all(3),
                  child: CircularProgressIndicator(strokeWidth: 2.2, color: p.primary),
                ),
              )
            else
              Container(
                width: 24,
                height: 24,
                decoration: BoxDecoration(
                  shape: BoxShape.circle,
                  gradient: active ? p.primaryGradient : null,
                  border: active ? null : Border.all(color: p.hairline, width: 1.5),
                ),
                child: active ? const Icon(Icons.check, size: 13, color: Colors.white) : null,
              ),
          ],
        ),
      ),
    );
  }
}
