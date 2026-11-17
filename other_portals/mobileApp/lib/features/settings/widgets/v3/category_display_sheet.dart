import 'package:flutter/material.dart';
import 'package:provider/provider.dart';

import 'package:invo/features/settings/logic/pos_settings_cubit/pos_settings_cubit.dart';
import 'package:invo/shared/utils/components/theme/index.dart';

/// Click-and-go picker for the New Sale category rail.
///
/// Deliberately small: four rows, each carrying a short sample of the rail it
/// selects — the real shapes and names at about two-thirds size. Drawing the
/// rail life-size made every row as tall as the thing it was selecting, which
/// filled a tablet screen to describe a 36pt strip; a sample this size still
/// answers "what will it look like" without that.
Future<void> showCategoryDisplaySheet(BuildContext context) {
  final p = context.astra;
  return showModalBottomSheet<void>(
    context: context,
    backgroundColor: Colors.transparent,
    barrierColor: Colors.black.withValues(alpha: 0.45),
    builder: (sheetContext) {
      final current = sheetContext.watch<PosSettingsCubit>().categoryDisplay;
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
                padding: const EdgeInsets.fromLTRB(20, 14, 20, 2),
                child: Row(
                  children: [
                    Icon(Icons.style_outlined, size: 18, color: p.primary),
                    const SizedBox(width: 9),
                    Expanded(child: Text('Category display', style: serif(size: 20, color: p.ink))),
                    GestureDetector(
                      onTap: () => Navigator.of(sheetContext).pop(),
                      child: Icon(Icons.close, size: 20, color: p.textMuted),
                    ),
                  ],
                ),
              ),
              Padding(
                padding: const EdgeInsets.fromLTRB(20, 0, 20, 6),
                child: Text('The rail is pinned above the products — taller means less catalog.',
                    style: ui(size: 11, weight: FontWeight.w600, color: p.textMuted)),
              ),
              Flexible(
                child: ListView(
                  shrinkWrap: true,
                  padding: const EdgeInsets.fromLTRB(12, 2, 12, 12),
                  children: [
                    for (final option in CategoryDisplay.values)
                      _row(sheetContext, option, option == current),
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

Widget _row(BuildContext context, CategoryDisplay option, bool active) {
  final p = context.astra;
  return GestureDetector(
    onTap: () {
      context.read<PosSettingsCubit>().setCategoryDisplay(option);
      Navigator.of(context).pop();
    },
    behavior: HitTestBehavior.opaque,
    child: Container(
      margin: const EdgeInsets.symmetric(vertical: 3.5, horizontal: 4),
      padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 10),
      decoration: BoxDecoration(
        color: p.card,
        borderRadius: BorderRadius.circular(14),
        boxShadow: context.astraTheme.softShadow,
        border: Border.all(color: active ? p.primary : Colors.transparent, width: 1.5),
      ),
      child: Row(
        children: [
          _sample(context, option, active),
          const SizedBox(width: 12),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(option.label, style: ui(size: 13, weight: FontWeight.w800, color: p.ink)),
                const SizedBox(height: 1),
                Text('${option.blurb} · ${option.railHeight.toInt()}pt',
                    maxLines: 1,
                    overflow: TextOverflow.ellipsis,
                    style: ui(size: 10, weight: FontWeight.w600, color: p.textMuted)),
              ],
            ),
          ),
          const SizedBox(width: 10),
          Container(
            width: 20,
            height: 20,
            decoration: BoxDecoration(
              shape: BoxShape.circle,
              color: active ? p.primary : Colors.transparent,
              border: Border.all(color: active ? p.primary : p.hairline, width: 1.5),
            ),
            child: active ? const Icon(Icons.check, size: 13, color: Colors.white) : null,
          ),
        ],
      ),
    ),
  );
}

/// A short sample of the rail — the real shapes, real names, at about
/// two-thirds size, so picking is a comparison rather than a guess.
///
/// Two entries only, the first selected: enough to show the shape and its
/// active treatment without the row growing into a life-size mock of a strip
/// the till is one tap away from seeing for real.
Widget _sample(BuildContext context, CategoryDisplay option, bool active) {
  final p = context.astra;
  const names = ['All', 'Bakery'];

  // A stand-in for the category photo: the second entry has one, the first
  // ("All") never does — same as the rail itself.
  Widget puck(double size, double radius, int i) => Container(
        width: size,
        height: size,
        decoration: BoxDecoration(
          color: i == 1 ? p.primary.withValues(alpha: 0.5) : p.tint,
          borderRadius: BorderRadius.circular(radius),
        ),
        alignment: Alignment.center,
        child: Icon(i == 1 ? Icons.photo_outlined : Icons.apps_rounded,
            size: size * 0.5, color: i == 1 ? Colors.white : p.primary),
      );

  Widget entry(int i) {
    final on = i == 0;
    switch (option) {
      case CategoryDisplay.nameOnly:
      case CategoryDisplay.avatar:
        final withPhoto = option == CategoryDisplay.avatar;
        return Container(
          height: withPhoto ? 24 : 19,
          padding: EdgeInsets.fromLTRB(withPhoto ? 3 : 8, 0, 8, 0),
          decoration: BoxDecoration(
            color: on ? p.ink : p.card,
            borderRadius: BorderRadius.circular(999),
            border: Border.all(color: on ? p.ink : p.hairline),
          ),
          child: Row(
            mainAxisSize: MainAxisSize.min,
            children: [
              if (withPhoto) ...[puck(18, 9, i), const SizedBox(width: 5)],
              Text(names[i],
                  style: ui(size: 8.5, weight: FontWeight.w700, color: on ? p.canvas : p.textSecondary)),
            ],
          ),
        );
      case CategoryDisplay.card:
        return Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            Container(
              padding: const EdgeInsets.all(1.5),
              decoration: BoxDecoration(
                borderRadius: BorderRadius.circular(12),
                border: Border.all(color: on ? p.ink : Colors.transparent, width: 1.5),
              ),
              child: puck(24, 8, i),
            ),
            const SizedBox(height: 3),
            Text(names[i],
                maxLines: 1,
                overflow: TextOverflow.ellipsis,
                style: ui(
                    size: 7.5,
                    weight: on ? FontWeight.w800 : FontWeight.w700,
                    color: on ? p.ink : p.textSecondary)),
          ],
        );
      case CategoryDisplay.tile:
        return Container(
          width: 44,
          height: 30,
          clipBehavior: Clip.antiAlias,
          decoration: BoxDecoration(
            borderRadius: BorderRadius.circular(8),
            border: Border.all(color: on ? p.ink : p.hairline, width: on ? 1.5 : 1),
          ),
          child: Stack(
            fit: StackFit.expand,
            children: [
              puck(44, 0, i),
              Align(
                alignment: Alignment.bottomCenter,
                child: Container(
                  width: double.infinity,
                  padding: const EdgeInsets.fromLTRB(4, 6, 4, 2),
                  decoration: BoxDecoration(
                    gradient: LinearGradient(
                      begin: Alignment.topCenter,
                      end: Alignment.bottomCenter,
                      colors: [Colors.transparent, Colors.black.withValues(alpha: 0.6)],
                    ),
                  ),
                  child: Text(names[i],
                      maxLines: 1,
                      overflow: TextOverflow.ellipsis,
                      style: ui(size: 7.5, weight: FontWeight.w800, color: Colors.white)),
                ),
              ),
            ],
          ),
        );
    }
  }

  // Fixed box so every row lines up, clipped the way the real rail clips: a
  // long name runs off the edge rather than stretching the picker.
  return Container(
    width: 104,
    height: 42,
    alignment: AlignmentDirectional.centerStart,
    decoration: BoxDecoration(
      color: active ? p.tint.withValues(alpha: 0.55) : p.tint.withValues(alpha: 0.3),
      borderRadius: BorderRadius.circular(11),
    ),
    child: ClipRRect(
      borderRadius: BorderRadius.circular(11),
      child: OverflowBox(
        alignment: AlignmentDirectional.centerStart,
        maxWidth: 220,
        child: Padding(
          padding: const EdgeInsetsDirectional.only(start: 7),
          child: Row(
            mainAxisSize: MainAxisSize.min,
            crossAxisAlignment: CrossAxisAlignment.center,
            children: [entry(0), SizedBox(width: option == CategoryDisplay.card ? 8 : 6), entry(1)],
          ),
        ),
      ),
    ),
  );
}
