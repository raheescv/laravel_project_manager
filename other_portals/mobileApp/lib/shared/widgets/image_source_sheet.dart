import 'package:flutter/material.dart';
import 'package:image_picker/image_picker.dart';

import 'package:invo/shared/utils/components/theme/index.dart';
import 'package:invo/shared/widgets/astra_widgets.dart';

/// "Take photo" or "Choose from library" — the one source picker for every
/// photo the app uploads (profile avatar, product photos).
Future<ImageSource?> showImageSourceSheet(BuildContext context) {
  final p = context.astra;
  Widget tile(BuildContext ctx, IconData icon, String label, ImageSource source) => ListTile(
        leading: IconChip(icon: icon, size: 34, radius: 9, bg: p.tint),
        title: Text(label, style: ui(size: 13, weight: FontWeight.w700, color: p.ink)),
        onTap: () => Navigator.pop(ctx, source),
      );
  return showModalBottomSheet<ImageSource>(
    context: context,
    backgroundColor: p.card,
    shape: const RoundedRectangleBorder(borderRadius: BorderRadius.vertical(top: Radius.circular(22))),
    builder: (ctx) => SafeArea(
      child: Column(
        mainAxisSize: MainAxisSize.min,
        children: [
          const SizedBox(height: 8),
          Container(width: 38, height: 4, decoration: BoxDecoration(color: p.hairline, borderRadius: BorderRadius.circular(3))),
          const SizedBox(height: 10),
          tile(ctx, Icons.camera_alt_outlined, 'Take photo', ImageSource.camera),
          tile(ctx, Icons.photo_library_outlined, 'Choose from library', ImageSource.gallery),
          const SizedBox(height: 6),
        ],
      ),
    ),
  );
}
