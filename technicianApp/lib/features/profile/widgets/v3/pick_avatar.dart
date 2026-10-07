import 'dart:typed_data';

import 'package:flutter/material.dart';
import 'package:flutter/services.dart' show PlatformException;
import 'package:image_picker/image_picker.dart';

import 'package:invo/shared/widgets/crop_image_screen.dart';

import 'account_form_parts.dart';

/// Picks a photo from [source] and lets the user frame it in the circular
/// cropper. Returns the cropped square bytes, or null if they backed out.
Future<Uint8List?> pickAndCropAvatar(BuildContext context, ImageSource source) async {
  XFile? file;
  try {
    // 1200px keeps even a PNG crop well under the server's 5 MB limit.
    file = await ImagePicker().pickImage(source: source, imageQuality: 90, maxWidth: 1200, maxHeight: 1200);
  } on PlatformException catch (_) {
    // Camera / library permission denied — tell the user rather than nothing.
    if (context.mounted) showAccountSnack(context, 'Camera or photos unavailable. Check permissions in Settings.');
    return null;
  }
  if (file == null || !context.mounted) return null;
  final raw = await file.readAsBytes();
  if (!context.mounted) return null;
  return cropImage(context, raw, circle: true);
}
