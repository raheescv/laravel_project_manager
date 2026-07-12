import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:mobile_scanner/mobile_scanner.dart';

import 'package:invo/shared/domain/constants/global_variables.dart';
import 'package:invo/shared/domain/helpers/formatters.dart';
import 'package:invo/shared/utils/components/theme/index.dart';
import 'package:invo/shared/utils/router/http_utils/common_exception.dart';

import '../../domain/models/stock_check_models.dart';
import '../../domain/repository/stock_check_repository.dart';

/// One line in the live scan feed.
class _Hit {
  _Hit(this.name, this.detail, this.ok);
  final String name;
  final String detail;
  final bool ok;
}

/// Continuous barcode counting: each scan increments the matched item's physical
/// quantity by 1 (mirrors the web scan-gun flow). Stays on the camera; a running
/// tally and a feed confirm every hit. Re-present a product (after a short gap)
/// to count another unit of it.
class StockCheckScanScreen extends StatefulWidget {
  const StockCheckScanScreen({super.key, required this.detail});
  final StockCheckDetail detail;
  @override
  State<StockCheckScanScreen> createState() => _StockCheckScanScreenState();
}

class _StockCheckScanScreenState extends State<StockCheckScanScreen> {
  final MobileScannerController _controller = MobileScannerController(detectionSpeed: DetectionSpeed.normal);
  StockCheckRepository get _repo => serviceLocator<StockCheckRepository>();

  final List<_Hit> _feed = [];
  int _count = 0;
  bool _busy = false;
  bool _torch = false;
  String? _lastCode;
  DateTime _lastAt = DateTime.fromMillisecondsSinceEpoch(0);

  @override
  void dispose() {
    _controller.dispose();
    super.dispose();
  }

  Future<void> _onDetect(BarcodeCapture capture) async {
    if (_busy) return;
    final code = capture.barcodes.isEmpty ? null : capture.barcodes.first.rawValue?.trim();
    if (code == null || code.isEmpty) return;
    final now = DateTime.now();
    // One presentation = one +1. Re-present after ~1.5s to count another unit.
    if (code == _lastCode && now.difference(_lastAt).inMilliseconds < 1500) return;
    _lastCode = code;
    _lastAt = now;

    setState(() => _busy = true);
    try {
      final res = await _repo.scan(widget.detail.id, code);
      HapticFeedback.mediumImpact();
      setState(() {
        _count++;
        _feed.insert(0, _Hit(res.productName.isEmpty ? code : res.productName, 'now ${qtyLabel(res.physical)} · system ${qtyLabel(res.recorded)}', true));
        if (_feed.length > 30) _feed.removeLast();
      });
    } on ApiException catch (e) {
      HapticFeedback.vibrate();
      setState(() => _feed.insert(0, _Hit(code, e.message, false)));
    } catch (_) {
      setState(() => _feed.insert(0, _Hit(code, 'Scan failed', false)));
    }
    if (mounted) setState(() => _busy = false);
  }

  @override
  Widget build(BuildContext context) {
    final p = context.astra;
    return Scaffold(
      backgroundColor: Colors.black,
      body: Column(
        children: [
          Expanded(
            child: Stack(
              fit: StackFit.expand,
              children: [
                MobileScanner(controller: _controller, onDetect: _onDetect, errorBuilder: (_, __, ___) => _cameraError()),
                _ReticleOverlay(color: p.accent),
                // Top bar.
                Positioned(
                  top: 0, left: 0, right: 0,
                  child: Container(
                    decoration: BoxDecoration(gradient: LinearGradient(begin: Alignment.topCenter, end: Alignment.bottomCenter, colors: [Colors.black.withValues(alpha: 0.6), Colors.transparent])),
                    child: SafeArea(
                      bottom: false,
                      child: Padding(
                        padding: const EdgeInsets.fromLTRB(14, 8, 14, 20),
                        child: Row(
                          children: [
                            _circleBtn(Icons.close, () => Navigator.of(context).pop()),
                            const Spacer(),
                            Text('SCANNING', style: ui(size: 10, weight: FontWeight.w800, color: p.accent, letterSpacing: 2)),
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
                // Running tally.
                Positioned(
                  top: 70, left: 0, right: 0,
                  child: SafeArea(
                    bottom: false,
                    child: Column(
                      children: [
                        Text('$_count', style: serif(size: 40, color: Colors.white)),
                        Text('SCANNED THIS SESSION', style: ui(size: 9.5, weight: FontWeight.w800, color: p.accent, letterSpacing: 2)),
                      ],
                    ),
                  ),
                ),
                if (_busy)
                  const Positioned(bottom: 16, left: 0, right: 0, child: Center(child: SizedBox(width: 22, height: 22, child: CircularProgressIndicator(strokeWidth: 2.4, color: Colors.white)))),
              ],
            ),
          ),
          _feedPanel(p),
        ],
      ),
    );
  }

  Widget _feedPanel(AstraPalette p) {
    return Container(
      color: const Color(0xFF0F0F0F),
      padding: const EdgeInsets.fromLTRB(14, 12, 14, 0),
      child: SafeArea(
        top: false,
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            SizedBox(
              height: 140,
              child: _feed.isEmpty
                  ? Center(child: Text('Point the camera at a product barcode to add +1', textAlign: TextAlign.center, style: ui(size: 12, weight: FontWeight.w600, color: Colors.white54)))
                  : ListView.builder(
                      itemCount: _feed.length,
                      itemBuilder: (_, i) {
                        final h = _feed[i];
                        final top = i == 0;
                        return Container(
                          margin: EdgeInsets.only(bottom: top ? 10 : 0),
                          padding: EdgeInsets.symmetric(vertical: top ? 11 : 8, horizontal: top ? 12 : 2),
                          decoration: top
                              ? BoxDecoration(
                                  gradient: LinearGradient(colors: [p.primaryDark, Color.lerp(Colors.black, p.primaryDark, 0.6)!]),
                                  borderRadius: BorderRadius.circular(14),
                                  border: Border.all(color: (h.ok ? p.accent : AstraPalette.danger).withValues(alpha: 0.34)),
                                )
                              : BoxDecoration(border: Border(top: BorderSide(color: Colors.white.withValues(alpha: 0.06)))),
                          child: Row(
                            children: [
                              Container(
                                width: top ? 36 : 22, height: top ? 36 : 22,
                                decoration: BoxDecoration(color: (h.ok ? p.accent : AstraPalette.danger).withValues(alpha: 0.18), borderRadius: BorderRadius.circular(top ? 11 : 7)),
                                child: Icon(h.ok ? Icons.add : Icons.error_outline, size: top ? 18 : 12, color: h.ok ? p.accent : const Color(0xFFF3A6B2)),
                              ),
                              const SizedBox(width: 10),
                              Expanded(
                                child: Column(
                                  crossAxisAlignment: CrossAxisAlignment.start,
                                  children: [
                                    Text(h.name, maxLines: 1, overflow: TextOverflow.ellipsis, style: ui(size: top ? 12.5 : 11.5, weight: FontWeight.w800, color: Colors.white)),
                                    if (top) ...[const SizedBox(height: 2), Text(h.detail, maxLines: 1, overflow: TextOverflow.ellipsis, style: ui(size: 10, weight: FontWeight.w600, color: Colors.white70))],
                                  ],
                                ),
                              ),
                              if (h.ok && !top) Text('+1', style: ui(size: 11, weight: FontWeight.w800, color: p.accent)),
                              if (top && h.ok) Text('+1', style: serif(size: 20, color: p.accent)),
                            ],
                          ),
                        );
                      },
                    ),
            ),
            Padding(
              padding: const EdgeInsets.symmetric(vertical: 10),
              child: GestureDetector(
                onTap: () => Navigator.of(context).pop(),
                child: Container(
                  padding: const EdgeInsets.symmetric(vertical: 14),
                  alignment: Alignment.center,
                  decoration: BoxDecoration(gradient: p.primaryGradient, borderRadius: BorderRadius.circular(15)),
                  child: Row(
                    mainAxisSize: MainAxisSize.min,
                    children: [
                      const Icon(Icons.check_rounded, size: 17, color: Colors.white),
                      const SizedBox(width: 8),
                      Text('Done scanning', style: ui(size: 14, weight: FontWeight.w800, color: Colors.white)),
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

  Widget _circleBtn(IconData icon, VoidCallback onTap, {bool gold = false}) {
    final p = context.astra;
    return GestureDetector(
      onTap: onTap,
      child: Container(
        width: 42, height: 42,
        decoration: BoxDecoration(color: gold ? p.accent : Colors.white.withValues(alpha: 0.16), shape: BoxShape.circle, border: Border.all(color: Colors.white.withValues(alpha: 0.22))),
        child: Icon(icon, color: gold ? p.primaryDark : Colors.white, size: 19),
      ),
    );
  }

  Widget _cameraError() => Container(
        color: const Color(0xFF111111),
        alignment: Alignment.center,
        child: Padding(
          padding: const EdgeInsets.all(32),
          child: Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              const Icon(Icons.no_photography_outlined, color: Colors.white54, size: 44),
              const SizedBox(height: 14),
              Text('Camera unavailable', style: serif(size: 20, color: Colors.white), textAlign: TextAlign.center),
              const SizedBox(height: 8),
              Text('Grant camera access in settings to scan, or count from the list instead.', textAlign: TextAlign.center, style: ui(size: 13, weight: FontWeight.w500, color: Colors.white60)),
            ],
          ),
        ),
      );
}

/// Dark scrim with a transparent rounded window and accent corner brackets.
class _ReticleOverlay extends StatelessWidget {
  const _ReticleOverlay({required this.color});
  final Color color;

  @override
  Widget build(BuildContext context) {
    return LayoutBuilder(
      builder: (context, c) {
        final w = c.maxWidth * 0.72;
        final h = w * 0.6;
        return Stack(
          children: [
            Positioned.fill(child: CustomPaint(painter: _ScrimPainter(windowWidth: w, windowHeight: h))),
            Center(
              child: Container(
                width: w, height: h,
                decoration: BoxDecoration(borderRadius: BorderRadius.circular(18), border: Border.all(color: color, width: 2.5)),
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
    final rect = Rect.fromCenter(center: Offset(size.width / 2, size.height * 0.42), width: windowWidth, height: windowHeight);
    final window = RRect.fromRectAndRadius(rect, const Radius.circular(18));
    final scrim = Path()..addRect(Offset.zero & size);
    final hole = Path()..addRRect(window);
    final cut = Path.combine(PathOperation.difference, scrim, hole);
    canvas.drawPath(cut, Paint()..color = Colors.black.withValues(alpha: 0.5));
  }

  @override
  bool shouldRepaint(covariant _ScrimPainter old) => old.windowWidth != windowWidth || old.windowHeight != windowHeight;
}
