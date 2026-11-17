import 'package:flutter/material.dart';
import 'package:provider/provider.dart';

import 'package:invo/shared/logic/theme_cubit/theme_cubit.dart';
import 'package:invo/shared/utils/components/theme/index.dart';

/// Click-and-go typeface picker: tapping a row re-letters the whole app
/// instantly and closes the sheet. Each row is set in the face it applies, so
/// the list is its own preview.
Future<void> showTypographySheet(BuildContext context) {
  final p = context.astra;
  return showModalBottomSheet<void>(
    context: context,
    backgroundColor: Colors.transparent,
    barrierColor: Colors.black.withValues(alpha: 0.45),
    builder: (sheetContext) {
      final controller = sheetContext.watch<ThemeCubit>();
      final current = controller.typeface;
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
                    Icon(Icons.text_fields_outlined, size: 18, color: p.primary),
                    const SizedBox(width: 9),
                    Expanded(child: Text('Typography', style: serif(size: 20, color: p.ink))),
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
                    for (final face in AstraTypefaces.all)
                      _row(sheetContext, face, face.id == current.id),
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

Widget _row(BuildContext context, AstraTypeface face, bool active) {
  final p = context.astra;
  return GestureDetector(
    onTap: () {
      context.read<ThemeCubit>().setTypeface(face);
      Navigator.of(context).pop();
    },
    behavior: HitTestBehavior.opaque,
    child: Container(
      margin: const EdgeInsets.symmetric(vertical: 4, horizontal: 4),
      padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 12),
      decoration: BoxDecoration(
        color: p.card,
        borderRadius: BorderRadius.circular(14),
        boxShadow: context.astraTheme.softShadow,
        border: Border.all(color: active ? p.primary : Colors.transparent, width: 1.5),
      ),
      child: Row(
        children: [
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                // Set in its own faces: the name in the display face, the
                // sample line and tagline in the UI face.
                Text(face.name, style: face.displayStyle(size: 19, color: p.ink)),
                const SizedBox(height: 3),
                Text('Invoice 1042 · QR 300.00',
                    style: face.uiStyle(size: 12.5, weight: FontWeight.w700, color: p.ink)),
                const SizedBox(height: 2),
                Text(face.tagline,
                    maxLines: 1,
                    overflow: TextOverflow.ellipsis,
                    style: face.uiStyle(size: 10, weight: FontWeight.w600, color: p.textMuted)),
              ],
            ),
          ),
          const SizedBox(width: 10),
          Container(
            width: 24,
            height: 24,
            decoration: BoxDecoration(
              shape: BoxShape.circle,
              color: active ? p.primary : Colors.transparent,
              border: Border.all(color: active ? p.primary : p.hairline, width: 1.5),
            ),
            child: active ? const Icon(Icons.check, size: 15, color: Colors.white) : null,
          ),
        ],
      ),
    ),
  );
}
