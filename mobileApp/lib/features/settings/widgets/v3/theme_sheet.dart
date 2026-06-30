import 'package:flutter/material.dart';
import 'package:provider/provider.dart';

import 'package:invo/shared/logic/theme_cubit/theme_cubit.dart';
import 'package:invo/shared/utils/components/theme/index.dart';

/// Click-and-go colour-preset picker: tapping a row re-skins the whole app
/// instantly and closes the sheet. Premium, theme-var styled to match the app.
Future<void> showThemeSheet(BuildContext context) {
  final p = context.astra;
  return showModalBottomSheet<void>(
    context: context,
    backgroundColor: Colors.transparent,
    barrierColor: Colors.black.withValues(alpha: 0.45),
    builder: (sheetContext) {
      final controller = sheetContext.watch<ThemeCubit>();
      final current = controller.preset;
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
                    Icon(Icons.palette_outlined, size: 18, color: p.primary),
                    const SizedBox(width: 9),
                    Expanded(child: Text('Colour preset', style: serif(size: 20, color: p.ink))),
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
                    for (final preset in AstraPresets.all)
                      _row(sheetContext, preset, preset.id == current.id),
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

Widget _row(BuildContext context, AstraPalette preset, bool active) {
  final p = context.astra;
  return GestureDetector(
    onTap: () {
      context.read<ThemeCubit>().setPreset(preset);
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
          SizedBox(
            width: 24.0 + 15 * 3,
            height: 24,
            child: Stack(
              children: [
                for (var i = 0; i < preset.swatch.length; i++)
                  Positioned(
                    left: i * 15.0,
                    child: Container(
                      width: 24,
                      height: 24,
                      decoration: BoxDecoration(
                        color: preset.swatch[i],
                        shape: BoxShape.circle,
                        border: Border.all(color: p.cardSolid, width: 2),
                      ),
                    ),
                  ),
              ],
            ),
          ),
          const SizedBox(width: 12),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(preset.name, style: ui(size: 13, weight: FontWeight.w700, color: p.ink)),
                const SizedBox(height: 1),
                Text(preset.tagline, style: ui(size: 10, weight: FontWeight.w600, color: p.textMuted)),
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
