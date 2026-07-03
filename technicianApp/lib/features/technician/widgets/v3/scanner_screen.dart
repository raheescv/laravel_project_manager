import 'package:flutter/material.dart';
import 'package:mobile_scanner/mobile_scanner.dart';

import 'package:invo/shared/utils/components/theme/index.dart';

/// Full-screen camera barcode/QR scanner. Pops with the scanned string, or null
/// if cancelled. Includes a manual-entry fallback for when the camera can't read.
class ScannerScreen extends StatefulWidget {
  const ScannerScreen({super.key});

  /// Opens the scanner and returns the scanned (or manually entered) code.
  static Future<String?> open(BuildContext context) =>
      Navigator.of(context).push<String>(MaterialPageRoute(builder: (_) => const ScannerScreen()));

  @override
  State<ScannerScreen> createState() => _ScannerScreenState();
}

class _ScannerScreenState extends State<ScannerScreen> {
  final MobileScannerController _controller = MobileScannerController(
    detectionSpeed: DetectionSpeed.noDuplicates,
  );
  bool _handled = false;
  bool _torch = false;

  @override
  void dispose() {
    _controller.dispose();
    super.dispose();
  }

  void _onDetect(BarcodeCapture capture) {
    if (_handled) return;
    for (final barcode in capture.barcodes) {
      final code = barcode.rawValue;
      if (code != null && code.trim().isNotEmpty) {
        _handled = true;
        Navigator.of(context).pop(code.trim());
        return;
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    final p = context.astra;
    return Scaffold(
      backgroundColor: Colors.black,
      body: Stack(
        fit: StackFit.expand,
        children: [
          MobileScanner(
            controller: _controller,
            onDetect: _onDetect,
            errorBuilder: (context, error, child) => _cameraError(p),
          ),
          // Dim everything but the central reticle window.
          _ReticleOverlay(color: p.accent),
          // Top gradient + controls.
          Positioned(
            top: 0,
            left: 0,
            right: 0,
            child: Container(
              padding: const EdgeInsets.only(bottom: 20),
              decoration: BoxDecoration(
                gradient: LinearGradient(
                  begin: Alignment.topCenter,
                  end: Alignment.bottomCenter,
                  colors: [Colors.black.withValues(alpha: 0.6), Colors.transparent],
                ),
              ),
              child: SafeArea(
                child: Padding(
                  padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
                  child: Row(
                    children: [
                      _circleBtn(Icons.close, () => Navigator.of(context).pop()),
                      const Spacer(),
                      Text('Scan barcode', style: serif(size: 18, color: Colors.white)),
                      const Spacer(),
                      _circleBtn(_torch ? Icons.flash_on : Icons.flash_off, () {
                        _controller.toggleTorch();
                        setState(() => _torch = !_torch);
                      }, gold: _torch),
                    ],
                  ),
                ),
              ),
            ),
          ),
          // Bottom hint + manual entry.
          Positioned(
            bottom: 0,
            left: 0,
            right: 0,
            child: Container(
              padding: const EdgeInsets.only(top: 28),
              decoration: BoxDecoration(
                gradient: LinearGradient(
                  begin: Alignment.bottomCenter,
                  end: Alignment.topCenter,
                  colors: [Colors.black.withValues(alpha: 0.72), Colors.transparent],
                ),
              ),
              child: SafeArea(
                top: false,
                child: Padding(
                  padding: const EdgeInsets.fromLTRB(24, 0, 24, 18),
                  child: Column(
                    mainAxisSize: MainAxisSize.min,
                    children: [
                      Text('Point the camera at the product barcode',
                          textAlign: TextAlign.center,
                          style: ui(size: 13, weight: FontWeight.w500, color: Colors.white70)),
                      const SizedBox(height: 16),
                      GestureDetector(
                        onTap: _manualEntry,
                        child: Container(
                          padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 13),
                          decoration: BoxDecoration(
                            color: Colors.white.withValues(alpha: 0.14),
                            borderRadius: BorderRadius.circular(14),
                            border: Border.all(color: Colors.white.withValues(alpha: 0.22)),
                          ),
                          child: Row(
                            mainAxisSize: MainAxisSize.min,
                            children: [
                              const Icon(Icons.keyboard_outlined, color: Colors.white, size: 18),
                              const SizedBox(width: 9),
                              Text('Enter code manually',
                                  style: ui(size: 13.5, weight: FontWeight.w700, color: Colors.white)),
                            ],
                          ),
                        ),
                      ),
                    ],
                  ),
                ),
              ),
            ),
          ),
        ],
      ),
    );
  }

  Widget _circleBtn(IconData icon, VoidCallback onTap, {bool gold = false}) {
    final p = context.astra;
    return GestureDetector(
      onTap: onTap,
      child: Container(
        width: 42,
        height: 42,
        decoration: BoxDecoration(
          color: gold ? p.accent : Colors.white.withValues(alpha: 0.16),
          shape: BoxShape.circle,
          border: Border.all(color: Colors.white.withValues(alpha: 0.22)),
        ),
        child: Icon(icon, color: gold ? p.primaryDark : Colors.white, size: 19),
      ),
    );
  }

  Widget _cameraError(p) => Container(
        color: const Color(0xFF111111),
        alignment: Alignment.center,
        child: Padding(
          padding: const EdgeInsets.all(32),
          child: Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              const Icon(Icons.no_photography_outlined, color: Colors.white54, size: 44),
              const SizedBox(height: 14),
              Text('Camera unavailable',
                  style: serif(size: 20, color: Colors.white), textAlign: TextAlign.center),
              const SizedBox(height: 8),
              Text('Grant camera access in settings, or enter the code manually.',
                  textAlign: TextAlign.center,
                  style: ui(size: 13, weight: FontWeight.w500, color: Colors.white60)),
            ],
          ),
        ),
      );

  Future<void> _manualEntry() async {
    final ctl = TextEditingController();
    final code = await showDialog<String>(
      context: context,
      builder: (ctx) => AlertDialog(
        title: const Text('Enter barcode'),
        content: TextField(controller: ctl, autofocus: true, decoration: const InputDecoration(hintText: 'Barcode / code')),
        actions: [
          TextButton(onPressed: () => Navigator.pop(ctx), child: const Text('Cancel')),
          TextButton(onPressed: () => Navigator.pop(ctx, ctl.text.trim()), child: const Text('Find')),
        ],
      ),
    );
    if (code != null && code.isNotEmpty && mounted) {
      Navigator.of(context).pop(code);
    }
  }
}

/// Dark scrim with a transparent rounded window and gold corner brackets.
class _ReticleOverlay extends StatelessWidget {
  const _ReticleOverlay({required this.color});
  final Color color;

  @override
  Widget build(BuildContext context) {
    return LayoutBuilder(
      builder: (context, c) {
        final w = c.maxWidth * 0.74;
        final h = w * 0.62;
        return Stack(
          children: [
            // 4 dim panels around the window.
            Positioned.fill(
              child: CustomPaint(painter: _ScrimPainter(windowWidth: w, windowHeight: h)),
            ),
            Center(
              child: Container(
                width: w,
                height: h,
                decoration: BoxDecoration(
                  borderRadius: BorderRadius.circular(20),
                  border: Border.all(color: color, width: 2.5),
                ),
              ),
            ),
          ],
        );
      },
    );
  }
}

class _ScrimPainter extends CustomPainter {
  _ScrimPainter({required this.windowWidth, required this.windowHeight});
  final double windowWidth;
  final double windowHeight;

  @override
  void paint(Canvas canvas, Size size) {
    final rect = Rect.fromCenter(
      center: size.center(Offset.zero),
      width: windowWidth,
      height: windowHeight,
    );
    final window = RRect.fromRectAndRadius(rect, const Radius.circular(20));
    final scrim = Path()..addRect(Offset.zero & size);
    final hole = Path()..addRRect(window);
    final cut = Path.combine(PathOperation.difference, scrim, hole);
    canvas.drawPath(cut, Paint()..color = Colors.black.withValues(alpha: 0.55));
  }

  @override
  bool shouldRepaint(covariant _ScrimPainter old) =>
      old.windowWidth != windowWidth || old.windowHeight != windowHeight;
}
