import 'package:flutter/foundation.dart';
import 'package:flutter/material.dart';
import 'package:permission_handler/permission_handler.dart';

import 'package:invo/shared/utils/components/theme/index.dart';
import 'package:invo/shared/widgets/astra_widgets.dart';

/// Ensures the camera permission before opening a scanner.
///
/// Flow:
///  • already granted → returns true immediately.
///  • deniable (never asked, or a soft Android denial) → shows the OS prompt
///    again. This is what re-asks after an accidental "Deny".
///  • permanently denied / restricted (iOS after one denial, Android
///    "don't ask again") → the OS won't prompt again, so we show a themed sheet
///    with OS-specific steps and a one-tap shortcut to the app's Settings page.
///
/// Returns true only when the permission is granted.
Future<bool> ensureCameraPermission(BuildContext context) async {
  var status = await Permission.camera.status;
  if (status.isGranted || status.isLimited) return true;

  // Deniable → re-request (re-shows the OS dialog after an accidental deny).
  if (status.isDenied) {
    status = await Permission.camera.request();
    if (status.isGranted || status.isLimited) return true;
  }

  // The OS won't prompt again (permanentlyDenied / restricted, or the user just
  // denied the re-request) → guide them to Settings.
  if (context.mounted) {
    await _showSettingsSheet(context);
  }
  return false;
}

String _osSteps() {
  final os = kIsWeb ? null : defaultTargetPlatform;
  return switch (os) {
    TargetPlatform.iOS =>
      'Settings → Privacy & Security → Camera → turn this app on.\nOr: Settings → this app → Camera.',
    TargetPlatform.android =>
      'Settings → Apps → this app → Permissions → Camera → Allow.\n(On some phones: Settings → Privacy → Permission manager → Camera.)',
    _ => 'Open your device Settings and allow camera access for this app.',
  };
}

Future<void> _showSettingsSheet(BuildContext context) {
  final p = context.astra;
  return showModalBottomSheet<void>(
    context: context,
    backgroundColor: Colors.transparent,
    isScrollControlled: true,
    builder: (ctx) => Container(
      decoration: BoxDecoration(
        color: p.cardSolid,
        borderRadius: const BorderRadius.vertical(top: Radius.circular(24)),
      ),
      padding: const EdgeInsets.fromLTRB(18, 12, 18, 26),
      child: Column(
        mainAxisSize: MainAxisSize.min,
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Center(
            child: Container(
              width: 40,
              height: 4,
              margin: const EdgeInsets.only(bottom: 16),
              decoration: BoxDecoration(color: p.hairline, borderRadius: BorderRadius.circular(4)),
            ),
          ),
          Row(
            children: [
              IconChip(icon: Icons.photo_camera_outlined, size: 40, radius: 12),
              const SizedBox(width: 12),
              Expanded(child: Text('Camera access needed', style: serif(size: 18, color: p.ink))),
            ],
          ),
          const SizedBox(height: 12),
          Text(
            'Barcode scanning needs the camera, but access is turned off. Enable it, then tap Scan again:',
            style: ui(size: 12.5, weight: FontWeight.w600, color: p.textSecondary, height: 1.5),
          ),
          const SizedBox(height: 12),
          Container(
            width: double.infinity,
            padding: const EdgeInsets.all(12),
            decoration: BoxDecoration(color: p.tint, borderRadius: BorderRadius.circular(13)),
            child: Text(_osSteps(), style: ui(size: 11.5, weight: FontWeight.w700, color: p.ink, height: 1.6)),
          ),
          const SizedBox(height: 18),
          Row(
            children: [
              Expanded(
                child: GestureDetector(
                  onTap: () => Navigator.pop(ctx),
                  child: Container(
                    padding: const EdgeInsets.symmetric(vertical: 14),
                    alignment: Alignment.center,
                    decoration: BoxDecoration(color: p.tint, borderRadius: BorderRadius.circular(14)),
                    child: Text('Not now', style: ui(size: 13.5, weight: FontWeight.w800, color: p.ink)),
                  ),
                ),
              ),
              const SizedBox(width: 10),
              Expanded(
                flex: 2,
                child: AstraButton(
                  label: 'Open Settings',
                  icon: Icons.settings_outlined,
                  onTap: () async {
                    Navigator.pop(ctx);
                    await openAppSettings();
                  },
                ),
              ),
            ],
          ),
        ],
      ),
    ),
  );
}
