import 'dart:typed_data';

import 'package:crop_your_image/crop_your_image.dart';
import 'package:flutter/material.dart';

import 'package:invo/shared/domain/helpers/responsive.dart';
import 'package:invo/shared/utils/components/theme/index.dart';
import 'package:invo/shared/widgets/astra_widgets.dart';

/// Premium in-app avatar cropper (Astra-styled). Presents a fixed circular
/// window over the picked photo; the user pinch-zooms and drags the image to
/// frame it. Returns the cropped square [Uint8List] via `Navigator.pop`, or
/// `null` if cancelled.
class CropPhotoScreen extends StatefulWidget {
  const CropPhotoScreen({super.key, required this.imageBytes});

  final Uint8List imageBytes;

  @override
  State<CropPhotoScreen> createState() => _CropPhotoScreenState();
}

class _CropPhotoScreenState extends State<CropPhotoScreen> {
  final _controller = CropController();
  bool _busy = false; // crop encoding in flight
  bool _ready = false; // image loaded & interactive

  void _confirm() {
    if (!_ready || _busy) return;
    setState(() => _busy = true);
    _controller.crop(); // square (bounding-box) output → onCropped
  }

  @override
  Widget build(BuildContext context) {
    final p = context.astra;
    return Scaffold(
      backgroundColor: const Color(0xFF0B0B0C),
      body: AstraBackground(
        child: Column(
          children: [
            EmeraldHeader(
              leading: HeaderIconButton(icon: Icons.close, onTap: () => Navigator.of(context).maybePop()),
              title: 'Crop Photo',
            ),
            Expanded(
              child: Stack(
                children: [
                  Crop(
                    controller: _controller,
                    image: widget.imageBytes,
                    aspectRatio: 1,
                    withCircleUi: true,
                    interactive: true, // pan + pinch-zoom the image…
                    fixCropRect: true, // …behind a fixed circular window
                    baseColor: const Color(0xFF0B0B0C),
                    maskColor: Colors.black.withValues(alpha: 0.62),
                    radius: 0,
                    cornerDotBuilder: (size, edge) => const SizedBox.shrink(),
                    progressIndicator: Center(
                      child: CircularProgressIndicator(color: p.accent, strokeWidth: 2.6),
                    ),
                    onStatusChanged: (status) {
                      final ready = status == CropStatus.ready || status == CropStatus.cropping;
                      if (ready != _ready && mounted) setState(() => _ready = ready);
                    },
                    onCropped: (result) {
                      if (!mounted) return;
                      switch (result) {
                        case CropSuccess(:final croppedImage):
                          Navigator.of(context).pop(croppedImage);
                        case CropFailure():
                          setState(() => _busy = false);
                          ScaffoldMessenger.of(context)
                            ..clearSnackBars()
                            ..showSnackBar(const SnackBar(
                              content: Text('Could not crop the image. Please try again.'),
                            ));
                      }
                    },
                  ),
                  Positioned(
                    left: 0,
                    right: 0,
                    bottom: 18,
                    child: IgnorePointer(
                      child: Center(
                        child: Container(
                          padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 8),
                          decoration: BoxDecoration(
                            color: Colors.black.withValues(alpha: 0.42),
                            borderRadius: BorderRadius.circular(30),
                          ),
                          child: Row(
                            mainAxisSize: MainAxisSize.min,
                            children: [
                              const Icon(Icons.pinch_outlined, size: 14, color: Colors.white70),
                              const SizedBox(width: 7),
                              Text('Pinch to zoom · drag to reposition',
                                  style: ui(size: 11, weight: FontWeight.w600, color: Colors.white)),
                            ],
                          ),
                        ),
                      ),
                    ),
                  ),
                ],
              ),
            ),
            SafeArea(
              top: false,
              child: MaxWidthBox(
                maxWidth: 520,
                child: Padding(
                  padding: const EdgeInsets.fromLTRB(14, 12, 14, 14),
                  child: Row(
                    children: [
                      AstraButton(
                        label: 'Cancel',
                        expand: false,
                        gold: false,
                        onTap: _busy ? null : () => Navigator.of(context).maybePop(),
                      ),
                      const SizedBox(width: 11),
                      Expanded(
                        child: AstraButton(
                          label: 'Use Photo',
                          icon: Icons.check,
                          gold: true,
                          busy: _busy,
                          onTap: _ready ? _confirm : null,
                        ),
                      ),
                    ],
                  ),
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }
}
