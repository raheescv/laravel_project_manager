import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import 'package:mobile_scanner/mobile_scanner.dart';
import 'package:permission_handler/permission_handler.dart';

import '../../../shared/domain/constants/global_variables.dart';
import '../../../shared/domain/repository/catalog_repository.dart';
import '../../../shared/utils/components/theme/pearl_theme.dart';
import '../../../shared/utils/router/http_utils/common_exception.dart';
import '../../../shared/utils/router/routes.dart';
import '../../../shared/widgets/pearl_widgets.dart';

/// Barcode lookup — a colleague scans the box and the customer sees the product
/// page, spin frames and all.
///
/// Permission-first: the primer is shown and `permission_handler` asks, before
/// the camera widget is ever built. Letting `mobile_scanner` request the
/// permission itself gives a bare system dialog with no explanation, and a
/// denial there is very hard to recover from.
class ScanScreen extends StatefulWidget {
  const ScanScreen({super.key});

  @override
  State<ScanScreen> createState() => _ScanScreenState();
}

enum _Stage { primer, denied, scanning, looking }

class _ScanScreenState extends State<ScanScreen> {
  MobileScannerController? _controller;
  _Stage _stage = _Stage.primer;
  String? _error;
  bool _handling = false;

  @override
  void initState() {
    super.initState();
    _check();
  }

  @override
  void dispose() {
    _controller?.dispose();
    super.dispose();
  }

  Future<void> _check() async {
    final status = await Permission.camera.status;
    if (!mounted) return;
    if (status.isGranted) {
      _start();
    } else if (status.isPermanentlyDenied) {
      setState(() => _stage = _Stage.denied);
    }
  }

  Future<void> _request() async {
    final status = await Permission.camera.request();
    if (!mounted) return;
    if (status.isGranted) {
      _start();
    } else {
      setState(() => _stage = _Stage.denied);
    }
  }

  void _start() {
    setState(() {
      _controller = MobileScannerController(
        detectionSpeed: DetectionSpeed.noDuplicates,
        formats: const [BarcodeFormat.all],
      );
      _stage = _Stage.scanning;
    });
  }

  /// One lookup at a time: the detector fires repeatedly while the code is in
  /// frame, and without the latch every frame would start another request.
  Future<void> _onDetect(BarcodeCapture capture) async {
    if (_handling) return;
    final code = capture.barcodes
        .map((b) => b.rawValue ?? '')
        .firstWhere((v) => v.isNotEmpty, orElse: () => '');
    if (code.isEmpty) return;

    _handling = true;
    setState(() {
      _stage = _Stage.looking;
      _error = null;
    });
    await _controller?.stop();

    try {
      final product = await serviceLocator<CatalogRepository>().productByBarcode(code);
      if (!mounted) return;
      context.pushReplacement(Routes.productById(product.id));
    } on ApiException catch (e) {
      if (!mounted) return;
      setState(() {
        _error = e.isNotFound ? 'No product carries the barcode $code.' : e.message;
        _stage = _Stage.scanning;
      });
      await _controller?.start();
    } finally {
      _handling = false;
    }
  }

  @override
  Widget build(BuildContext context) {
    final p = context.pearl;
    return Scaffold(
      backgroundColor: p.bg,
      body: SafeArea(
        child: Column(
          children: [
            Container(
              padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 12),
              decoration: BoxDecoration(
                border: Border(bottom: BorderSide(color: p.line)),
              ),
              child: Row(
                children: [
                  IconSquare(Icons.arrow_back, size: 38, onTap: () => context.pop()),
                  const SizedBox(width: 14),
                  Expanded(
                    child: Text(
                      'Scan a barcode'.toUpperCase(),
                      style: PearlText.section.copyWith(color: p.ink),
                    ),
                  ),
                ],
              ),
            ),
            Expanded(child: _body(context)),
          ],
        ),
      ),
    );
  }

  Widget _body(BuildContext context) {
    final p = context.pearl;
    return switch (_stage) {
      _Stage.primer => MessageState(
          title: 'Camera access',
          detail: 'The scanner reads the barcode on a shoe box and opens that product. '
              'The camera is only used while this screen is open.',
          actionLabel: 'Allow the camera',
          onAction: _request,
        ),
      _Stage.denied => const MessageState(
          title: 'Camera is blocked',
          detail: 'Turn the camera on for this app in Settings, then come back.',
          actionLabel: 'Open settings',
          onAction: openAppSettings,
        ),
      _Stage.looking => Center(
          child: Text(
            'Looking it up'.toUpperCase(),
            style: PearlText.section.copyWith(color: p.ink),
          ),
        ),
      _Stage.scanning => Stack(
          children: [
            Positioned.fill(
              child: _controller == null
                  ? const SizedBox.shrink()
                  : MobileScanner(controller: _controller!, onDetect: _onDetect),
            ),
            Positioned.fill(
              child: IgnorePointer(
                child: Center(
                  child: Container(
                    width: 260,
                    height: 150,
                    decoration: BoxDecoration(border: Border.all(color: p.bg, width: 2)),
                  ),
                ),
              ),
            ),
            if (_error != null)
              Positioned(
                left: PearlMetrics.pad,
                right: PearlMetrics.pad,
                bottom: PearlMetrics.pad,
                child: Container(
                  padding: const EdgeInsets.all(14),
                  color: p.ink,
                  child: Text(
                    _error!,
                    style: PearlText.body(12).copyWith(color: p.bg),
                  ),
                ),
              ),
          ],
        ),
    };
  }
}
