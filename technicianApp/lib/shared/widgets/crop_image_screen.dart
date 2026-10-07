import 'dart:typed_data';

import 'package:crop_your_image/crop_your_image.dart';
import 'package:flutter/material.dart';

import 'package:invo/shared/domain/helpers/responsive.dart';
import 'package:invo/shared/utils/components/theme/index.dart';
import 'package:invo/shared/widgets/astra_widgets.dart';

/// Opens [CropImageScreen] over [bytes]. Returns the cropped bytes, null when
/// cancelled — or [bytes] itself (same instance) when a free crop was left
/// untouched, so the caller can keep the original file.
Future<Uint8List?> cropImage(BuildContext context, Uint8List bytes, {bool circle = false}) =>
    Navigator.of(context).push<Uint8List>(
      MaterialPageRoute(fullscreenDialog: true, builder: (_) => CropImageScreen(imageBytes: bytes, circle: circle)),
    );

/// The cropper turns a photo upright from its EXIF orientation, then writes
/// that same EXIF back into the crop — so a viewer would rotate it a second
/// time (Android camera shots arrive with the flag set). Drops the JPEG's APP1
/// (EXIF/XMP) segments; pixels are untouched. Anything else is returned as is.
Uint8List stripJpegMetadata(Uint8List b) {
  if (b.length < 4 || b[0] != 0xFF || b[1] != 0xD8) return b;
  final out = BytesBuilder(copy: false)..add(const [0xFF, 0xD8]);
  var i = 2;
  while (i + 4 <= b.length && b[i] == 0xFF) {
    final marker = b[i + 1];
    if (marker == 0xFF) {
      i++; // fill byte before a marker
      continue;
    }
    if (marker == 0xDA) break; // start of scan: the rest is image data
    final end = i + 2 + ((b[i + 2] << 8) | b[i + 3]);
    if (end > b.length) return b; // malformed — leave it alone
    if (marker != 0xE1) out.add(Uint8List.sublistView(b, i, end));
    i = end;
  }
  out.add(Uint8List.sublistView(b, i));
  return out.takeBytes();
}

/// In-app image cropper, two ways:
/// - [circle] (avatars): a fixed circular window; pinch-zoom and drag the
///   image to frame it. Always pops the square crop.
/// - free (evidence photos): the whole photo is selected; drag the corners or
///   the box to crop. Pops the crop, or the original bytes when untouched.
class CropImageScreen extends StatefulWidget {
  const CropImageScreen({super.key, required this.imageBytes, this.circle = false});

  final Uint8List imageBytes;
  final bool circle;

  @override
  State<CropImageScreen> createState() => _CropImageScreenState();
}

class _CropImageScreenState extends State<CropImageScreen> {
  final _controller = CropController();
  bool _busy = false; // crop encoding in flight
  bool _ready = false; // image decoded and interactive
  bool _edited = false; // the free crop box was moved by the user

  void _confirm() {
    if (!_ready || _busy) return;
    // An untouched free crop is the whole photo — skip the re-encode. A real
    // crop is cut from the full-resolution photo and re-encoded at JPEG 100
    // (4:4:4) or lossless PNG, so no detail is lost.
    if (!widget.circle && !_edited) {
      Navigator.of(context).pop(widget.imageBytes);
      return;
    }
    setState(() => _busy = true);
    _controller.crop();
  }

  @override
  Widget build(BuildContext context) {
    final p = context.astra;
    final circle = widget.circle;
    return Scaffold(
      backgroundColor: const Color(0xFF0B0B0C),
      body: AstraBackground(
        child: Column(children: [
          EmeraldHeader(
            leading: HeaderIconButton(icon: Icons.close, onTap: () => Navigator.of(context).maybePop()),
            title: 'Crop Photo',
          ),
          Expanded(
            child: Stack(children: [
              Padding(
                // Room for the corner handles at the screen edge.
                padding: EdgeInsets.all(circle ? 0 : 14),
                child: Crop(
                  controller: _controller,
                  image: widget.imageBytes,
                  aspectRatio: circle ? 1 : null,
                  withCircleUi: circle,
                  interactive: circle, // avatar: pan + pinch-zoom the image…
                  fixCropRect: circle, // …behind a fixed circular window
                  baseColor: const Color(0xFF0B0B0C),
                  maskColor: Colors.black.withValues(alpha: 0.62),
                  radius: 0,
                  cornerDotBuilder: (size, edge) => circle ? const SizedBox.shrink() : DotControl(color: p.accent),
                  progressIndicator: Center(child: CircularProgressIndicator(color: p.accent, strokeWidth: 2.6)),
                  onMoved: (_, __) {
                    // The first placement happens before "ready"; only later moves are the user's.
                    if (_ready && !_edited) setState(() => _edited = true);
                  },
                  onStatusChanged: (status) {
                    final ready = status == CropStatus.ready || status == CropStatus.cropping;
                    if (ready != _ready && mounted) setState(() => _ready = ready);
                  },
                  onCropped: (result) {
                    if (!mounted) return;
                    switch (result) {
                      case CropSuccess(:final croppedImage):
                        Navigator.of(context).pop(stripJpegMetadata(croppedImage));
                      case CropFailure():
                        setState(() => _busy = false);
                        ScaffoldMessenger.of(context)
                          ..clearSnackBars()
                          ..showSnackBar(const SnackBar(content: Text('Could not crop the image. Please try again.')));
                    }
                  },
                ),
              ),
              Positioned(
                left: 0,
                right: 0,
                bottom: 18,
                child: IgnorePointer(
                  child: Center(
                    child: _Hint(
                      icon: circle ? Icons.pinch_outlined : Icons.crop,
                      text: circle ? 'Pinch to zoom · drag to reposition' : 'Drag the corners to crop',
                    ),
                  ),
                ),
              ),
            ]),
          ),
          SafeArea(
            top: false,
            child: MaxWidthBox(
              maxWidth: 520,
              child: Padding(
                padding: const EdgeInsets.fromLTRB(14, 12, 14, 14),
                child: Row(children: [
                  AstraButton(
                    label: 'Cancel',
                    expand: false,
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
                ]),
              ),
            ),
          ),
        ]),
      ),
    );
  }
}

class _Hint extends StatelessWidget {
  const _Hint({required this.icon, required this.text});
  final IconData icon;
  final String text;

  @override
  Widget build(BuildContext context) => Container(
        padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 8),
        decoration: BoxDecoration(color: Colors.black.withValues(alpha: 0.42), borderRadius: BorderRadius.circular(30)),
        child: Row(mainAxisSize: MainAxisSize.min, children: [
          Icon(icon, size: 14, color: Colors.white70),
          const SizedBox(width: 7),
          Text(text, style: ui(size: 11, weight: FontWeight.w600, color: Colors.white)),
        ]),
      );
}
