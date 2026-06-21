import 'package:flutter/material.dart';
import 'package:provider/provider.dart';

import '../../models/models.dart';
import '../../state/branch_controller.dart';
import '../../theme/theme.dart';

/// Click-and-go branch picker: tapping a row sets the active branch instantly
/// (so every API call now carries its branch_id) and closes the sheet. Premium,
/// theme-var styled to match the app.
Future<void> showBranchSheet(BuildContext context) {
  final p = context.astra;
  return showModalBottomSheet<void>(
    context: context,
    backgroundColor: Colors.transparent,
    barrierColor: Colors.black.withValues(alpha: 0.45),
    builder: (sheetContext) {
      final controller = sheetContext.watch<BranchController>();
      final current = controller.selected;
      return Container(
        decoration: BoxDecoration(
          color: p.canvas,
          borderRadius: const BorderRadius.vertical(top: Radius.circular(30)),
        ),
        child: SafeArea(
          top: false,
          child: Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              const SizedBox(height: 10),
              Container(
                width: 40,
                height: 4,
                decoration: BoxDecoration(color: p.hairline, borderRadius: BorderRadius.circular(3)),
              ),
              Padding(
                padding: const EdgeInsets.fromLTRB(20, 14, 20, 6),
                child: Row(
                  children: [
                    Icon(Icons.business, size: 18, color: p.primary),
                    const SizedBox(width: 9),
                    Expanded(child: Text('Branch', style: serif(size: 20, color: p.ink))),
                    GestureDetector(
                      onTap: () => Navigator.of(sheetContext).pop(),
                      child: Icon(Icons.close, size: 20, color: p.textMuted),
                    ),
                  ],
                ),
              ),
              if (controller.loading && controller.branches.isEmpty)
                Padding(
                  padding: const EdgeInsets.symmetric(vertical: 28),
                  child: CircularProgressIndicator(strokeWidth: 2.4, color: p.primary),
                )
              else if (controller.branches.isEmpty)
                Padding(
                  padding: const EdgeInsets.fromLTRB(20, 8, 20, 28),
                  child: Text(
                    controller.error ?? 'No branches available.',
                    style: ui(size: 12.5, weight: FontWeight.w600, color: p.textMuted),
                  ),
                )
              else
                Flexible(
                  child: ListView(
                    shrinkWrap: true,
                    padding: const EdgeInsets.fromLTRB(12, 4, 12, 12),
                    children: [
                      for (final b in controller.branches)
                        _row(sheetContext, b, b.id == current?.id),
                    ],
                  ),
                ),
            ],
          ),
        ),
      );
    },
  );
}

Widget _row(BuildContext context, Branch b, bool active) {
  final p = context.astra;
  return GestureDetector(
    onTap: () {
      context.read<BranchController>().setBranch(b);
      Navigator.of(context).pop();
    },
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
                Text(b.name, style: ui(size: 13, weight: FontWeight.w700, color: p.ink)),
                Text(
                  b.location.isEmpty ? b.code : b.location,
                  style: ui(size: 10.5, weight: FontWeight.w600, color: p.textMuted, letterSpacing: 0.3),
                ),
              ],
            ),
          ),
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
