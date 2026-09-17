import 'package:flutter/material.dart';
import 'package:provider/provider.dart';

import 'package:invo/shared/logic/branch_cubit/branch_cubit.dart';
import 'package:invo/shared/utils/components/theme/index.dart';
import 'package:invo/shared/widgets/branch_tile.dart';

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
      final controller = sheetContext.watch<BranchCubit>();
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
                        BranchTile(
                          branch: b,
                          active: b.id == current?.id,
                          onTap: () {
                            sheetContext.read<BranchCubit>().setBranch(b);
                            Navigator.of(sheetContext).pop();
                          },
                        ),
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
