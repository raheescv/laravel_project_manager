import 'package:flutter/material.dart';
import 'package:provider/provider.dart';

import 'package:invo/shared/logic/theme_cubit/theme_cubit.dart';
import 'package:invo/shared/utils/components/theme/index.dart';

/// Click-and-go appearance picker: tapping Light / Dark / System applies the
/// brightness instantly app-wide and closes the sheet. System follows the OS.
Future<void> showAppearanceSheet(BuildContext context) {
  final p = context.astra;
  return showModalBottomSheet<void>(
    context: context,
    backgroundColor: Colors.transparent,
    barrierColor: Colors.black.withValues(alpha: 0.45),
    builder: (sheetContext) {
      final controller = sheetContext.watch<ThemeCubit>();
      final current = controller.mode;
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
                    Icon(Icons.brightness_6_outlined, size: 18, color: p.primary),
                    const SizedBox(width: 9),
                    Expanded(child: Text('Appearance', style: serif(size: 20, color: p.ink))),
                    GestureDetector(
                      onTap: () => Navigator.of(sheetContext).pop(),
                      child: Icon(Icons.close, size: 20, color: p.textMuted),
                    ),
                  ],
                ),
              ),
              Flexible(
                child: ListView(
                  shrinkWrap: true,
                  padding: const EdgeInsets.fromLTRB(12, 4, 12, 12),
                  children: [
                    for (final mode in AstraMode.values)
                      _row(sheetContext, mode, mode == current),
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

IconData _iconFor(AstraMode mode) => switch (mode) {
      AstraMode.light => Icons.light_mode_outlined,
      AstraMode.dark => Icons.dark_mode_outlined,
      AstraMode.system => Icons.brightness_auto_outlined,
    };

String _subtitleFor(AstraMode mode) => switch (mode) {
      AstraMode.light => 'Always the light theme',
      AstraMode.dark => 'Always the dark theme',
      AstraMode.system => 'Follow the device setting',
    };

Widget _row(BuildContext context, AstraMode mode, bool active) {
  final p = context.astra;
  return GestureDetector(
    onTap: () {
      context.read<ThemeCubit>().setMode(mode);
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
            child: Icon(_iconFor(mode), size: 20, color: p.primaryDark),
          ),
          const SizedBox(width: 12),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(mode.label, style: ui(size: 13, weight: FontWeight.w700, color: p.ink)),
                Text(_subtitleFor(mode),
                    style: ui(size: 10.5, weight: FontWeight.w600, color: p.textMuted)),
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
