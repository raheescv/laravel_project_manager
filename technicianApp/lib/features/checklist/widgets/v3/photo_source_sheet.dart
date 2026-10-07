import 'dart:io';
import 'dart:typed_data';

import 'package:flutter/material.dart';
import 'package:flutter/services.dart' show PlatformException;
import 'package:image_picker/image_picker.dart';

import 'package:invo/shared/utils/components/theme/index.dart';
import 'package:invo/shared/widgets/astra_widgets.dart';
import 'package:invo/shared/widgets/crop_image_screen.dart';

/// Asks Camera / Gallery, captures one photo (95% JPEG, ≤2048px), then
/// lets the user crop it. Returns null when the user cancels at any step or the
/// picker is unavailable; the original file when the crop was left as is.
Future<XFile?> pickChecklistPhoto(BuildContext context, {String title = 'Add a photo'}) async {
  final source = await showModalBottomSheet<ImageSource>(
    context: context,
    backgroundColor: Colors.transparent,
    builder: (ctx) => _PhotoSourceSheet(title: title),
  );
  if (source == null) return null;
  XFile? file;
  try {
    // Inspection evidence: near-lossless 95% and 2048px keeps fine detail
    // (scratches, stains) while a cropped re-encode stays under the 8 MB limit.
    file = await ImagePicker().pickImage(source: source, imageQuality: 95, maxWidth: 2048, maxHeight: 2048);
  } on PlatformException catch (_) {
    // Camera / photo-library permission denied or no camera — treated as a cancel.
    if (context.mounted) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Camera or photos unavailable. Check permissions in Settings.')),
      );
    }
    return null;
  }
  if (file == null || !context.mounted) return null;
  final raw = await file.readAsBytes();
  if (!context.mounted) return null;
  final cropped = await cropImage(context, raw);
  if (cropped == null) return null;
  if (identical(cropped, raw)) return file;
  return XFile((await writeCroppedPhoto(cropped)).path);
}

/// Saves cropped bytes to a temp file so the upload path (a file) is unchanged.
/// The cropper keeps the picked format: JPEG from the camera, else PNG.
Future<File> writeCroppedPhoto(Uint8List bytes) {
  final jpeg = bytes.length > 2 && bytes[0] == 0xFF && bytes[1] == 0xD8;
  final name = 'checklist_${DateTime.now().microsecondsSinceEpoch}.${jpeg ? 'jpg' : 'png'}';
  return File('${Directory.systemTemp.path}/$name').writeAsBytes(bytes, flush: true);
}

class _PhotoSourceSheet extends StatelessWidget {
  const _PhotoSourceSheet({required this.title});
  final String title;

  @override
  Widget build(BuildContext context) {
    final p = context.astra;
    return Container(
      decoration: BoxDecoration(color: p.canvas, borderRadius: const BorderRadius.vertical(top: Radius.circular(26))),
      child: SafeArea(
        top: false,
        child: Padding(
          padding: const EdgeInsets.fromLTRB(16, 16, 16, 16),
          child: Column(mainAxisSize: MainAxisSize.min, crossAxisAlignment: CrossAxisAlignment.start, children: [
            Text(title, style: serif(size: 19, color: p.ink)),
            const SizedBox(height: 12),
            _SourceTile(icon: Icons.camera_alt_outlined, label: 'Take a photo', source: ImageSource.camera),
            const SizedBox(height: 10),
            _SourceTile(icon: Icons.photo_library_outlined, label: 'Choose from gallery', source: ImageSource.gallery),
          ]),
        ),
      ),
    );
  }
}

class _SourceTile extends StatelessWidget {
  const _SourceTile({required this.icon, required this.label, required this.source});
  final IconData icon;
  final String label;
  final ImageSource source;

  @override
  Widget build(BuildContext context) {
    final p = context.astra;
    return AstraCard(
      radius: 12,
      onTap: () => Navigator.of(context).pop(source),
      child: Row(children: [
        IconChip(icon: icon, size: 34, radius: 10, bg: p.tint),
        const SizedBox(width: 12),
        Text(label, style: ui(size: 13.5, weight: FontWeight.w700, color: p.ink)),
      ]),
    );
  }
}
