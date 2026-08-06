import 'dart:async';

import 'package:flutter/foundation.dart';
import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:mobile_scanner/mobile_scanner.dart';
// Needed for the force-stop recovery: the platform singleton keeps its texture
// id when a start() fails midway, and the public stop() refuses to run while
// the controller thinks it isn't running — the only unwedge is stop(force:true)
// on the method channel, the same escape hatch the package itself uses for
// hot-restart recovery.
// ignore: implementation_imports
import 'package:mobile_scanner/src/method_channel/mobile_scanner_method_channel.dart';
import 'package:permission_handler/permission_handler.dart';

import 'package:invo/shared/utils/components/theme/index.dart';

part 'continuous_scanner_states.dart';

part 'continuous_scanner_views.dart';

/// The kind of outcome a single scan produced — drives the feed line colour.
enum ScanStatus { ok, warn, error }

/// One scan outcome, shown in the scanner's live feed and running tally.
///
/// A consumer's [ContinuousScannerScreen.onScan] handler returns one of these
/// per accepted barcode. It never has to touch the camera, feed or tally — it
/// just does its lookup (increment a count, add to a cart…) and describes the
/// result.
class ScanFeedback {
  const ScanFeedback({
    required this.title,
    this.detail,
    this.status = ScanStatus.ok,
    this.undo,
  });

  /// Primary line — usually the product name, or the raw code when unknown.
  final String title;

  /// Optional secondary line, e.g. `now 3 · system 5`.
  final String? detail;

  final ScanStatus status;

  /// When non-null, the scanner offers an **Undo** for this (most recent
  /// successful) scan. The closure performs the reversal and returns a
  /// confirmation line to show, or null if it couldn't be undone.
  final Future<ScanFeedback?> Function()? undo;

  bool get isOk => status == ScanStatus.ok;
  bool get isError => status == ScanStatus.error;

  /// Convenience for a failed scan (unknown code, network error…).
  factory ScanFeedback.error(String title, [String? detail]) =>
      ScanFeedback(title: title, detail: detail, status: ScanStatus.error);
}

/// A feed row, remembering whether it came from an undo (rendered neutral).
class _FeedLine {
  _FeedLine(this.fb, {this.undone = false});
  final ScanFeedback fb;
  final bool undone;
}

/// What the camera area is currently showing. The permission phases come
/// FIRST: the camera is never asked to start until the permission is settled,
/// so mobile_scanner never runs its own permission dance (whose mid-start
/// dialog is what used to wedge the platform camera state).
enum _CamPhase {
  /// Checking the stored permission status — sub-second, quiet placeholder.
  boot,

  /// Permission not granted yet and still askable — friendly explainer with
  /// an "Enable camera" button that triggers the real OS dialog.
  primer,

  /// Permission permanently denied — numbered OS-specific steps plus an
  /// Open Settings deep link; re-checked automatically on return.
  settings,

  /// Permission granted, camera being brought up.
  starting,

  /// Preview running, detection active.
  live,

  /// start() failed after the retry budget — user-driven "Try again".
  failed,

  /// This device cannot scan (no camera / unsupported) — manual entry only.
  unsupported,
}

/// Continuous, stay-open barcode scanner shared by every scan-to-count flow
/// (stock check, quick sale, …).
///
/// Each accepted barcode is handed to [onScan], which does the real work and
/// returns a [ScanFeedback]. The screen owns everything else: the permission
/// flow, the camera and its lifecycle, torch, a same-barcode cooldown, a
/// running tally, a live feed, undo, manual entry, and honest error states.
/// It pops with the number of successful scans so the caller can decide
/// whether to refresh.
class ContinuousScannerScreen extends StatefulWidget {
  const ContinuousScannerScreen({
    super.key,
    required this.onScan,
    this.title = 'SCANNING',
    this.tallyLabel = 'SCANNED THIS SESSION',
    this.emptyHint =
        'Point the camera at a barcode to add it.\nFor several identical items, move each away and re-present it.',
  });

  /// Called once per accepted barcode. Should not throw — a thrown error is
  /// caught and shown as a generic failure line — but returning a
  /// [ScanFeedback.error] gives the user a clearer message.
  final Future<ScanFeedback> Function(String code) onScan;

  final String title;
  final String tallyLabel;
  final String emptyHint;

  /// Opens the scanner and resolves to the number of successful scans (0 if the
  /// user backed out without counting anything).
  static Future<int> open(
    BuildContext context, {
    required Future<ScanFeedback> Function(String code) onScan,
    String title = 'SCANNING',
    String tallyLabel = 'SCANNED THIS SESSION',
    String emptyHint =
        'Point the camera at a barcode to add it.\nFor several identical items, move each away and re-present it.',
  }) async {
    final count = await Navigator.of(context).push<int>(
      MaterialPageRoute(
        builder: (_) => ContinuousScannerScreen(
          onScan: onScan,
          title: title,
          tallyLabel: tallyLabel,
          emptyHint: emptyHint,
        ),
      ),
    );
    return count ?? 0;
  }

  @override
  State<ContinuousScannerScreen> createState() => _ContinuousScannerScreenState();
}

class _ContinuousScannerScreenState extends State<ContinuousScannerScreen>
    with WidgetsBindingObserver, TickerProviderStateMixin {
  /// Created fresh on every attach — reusing a controller whose start() died
  /// midway is the classic wedge (every later start throws
  /// controllerAlreadyInitialized forever).
  MobileScannerController? _controller;

  _CamPhase _phase = _CamPhase.boot;

  /// The last start failure, surfaced as a small technical line so a
  /// screenshot of the error card is a diagnosable report.
  String? _lastErrorDetail;

  /// Serializes every camera operation. Two start() calls that overlap before
  /// the camera is up both bind the platform camera and collide into a
  /// persistent genericError — a lifecycle `resumed` racing the initial start
  /// is the classic trigger. Dropping one of them (the old boolean-guard
  /// approach) can leave the camera permanently down; queueing cannot.
  Future<void> _ops = Future<void>.value();

  /// True while _attachOp is running its start attempts — tells the
  /// errorBuilder fallback to stay quiet and let the retry loop finish.
  bool _attaching = false;

  /// True while the OS permission dialog is up via permission_handler — the
  /// `resumed` it fires on close must not re-enter the boot flow.
  bool _requestingPermission = false;

  // Same-barcode cooldown: one physical presentation = one hit. To count another
  // unit of the SAME product, move it out of frame and re-present after this
  // gap. Different barcodes are never blocked, so mixed piles scan at full speed.
  static const _sameCodeCooldownMs = 1000;

  late final AnimationController _scanLine = AnimationController(
    vsync: this,
    duration: const Duration(milliseconds: 1700),
  )..repeat(reverse: true);

  /// One-shot flash of the viewfinder brackets on each scan result — value
  /// rests at 1 (no flash); forward(from: 0) plays a decaying pulse.
  late final AnimationController _pulse = AnimationController(
    vsync: this,
    duration: const Duration(milliseconds: 550),
    value: 1,
  );
  Color _pulseColor = Colors.transparent;

  final List<_FeedLine> _feed = [];
  int _count = 0;
  bool _busy = false;
  bool _torch = false;

  /// The most recent successful scan that can be undone (its handler returned a
  /// non-null `undo`). Drives the Undo button.
  ScanFeedback? _undoable;

  String? _lastCode;
  DateTime _lastAt = DateTime.fromMillisecondsSinceEpoch(0);

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addObserver(this);
    WidgetsBinding.instance.addPostFrameCallback((_) {
      if (mounted) _boot();
    });
  }

  @override
  void dispose() {
    WidgetsBinding.instance.removeObserver(this);
    _scanLine.dispose();
    _pulse.dispose();
    // Release through the op queue so this can never overlap an in-flight
    // attach; a controller created after this point disposes itself (attachOp
    // re-checks mounted).
    final c = _controller;
    _controller = null;
    _ops = _ops.then((_) async {
      try {
        await c?.dispose();
      } catch (_) {
        // Disposing an already-dead platform camera throws; nothing to do.
      }
    });
    super.dispose();
  }

  @override
  void didChangeAppLifecycleState(AppLifecycleState state) {
    switch (state) {
      case AppLifecycleState.resumed:
        // Fires when the user comes back from the OS Settings app after
        // granting camera access — re-run the boot flow so the grant is
        // picked up without any tap. Skipped while our own permission dialog
        // is closing (the request() await handles that path itself).
        if (_requestingPermission) return;
        switch (_phase) {
          case _CamPhase.primer:
          case _CamPhase.settings:
          case _CamPhase.boot:
            _boot();
            break;
          case _CamPhase.starting:
          case _CamPhase.live:
          case _CamPhase.failed:
            _attach(); // re-acquire — the OS may have revoked the sensor
            break;
          case _CamPhase.unsupported:
            break;
        }
        break;
      case AppLifecycleState.paused:
      case AppLifecycleState.hidden:
      case AppLifecycleState.detached:
        _enqueue(() async {
          try {
            await _controller?.stop();
          } catch (_) {/* already stopped */}
        });
        break;
      case AppLifecycleState.inactive:
        // Deliberately ignored: fires for brief, in-place focus loss (the OS
        // permission dialog, the notification shade) and stopping the camera
        // there would race the initial start and churn the preview.
        break;
    }
  }

  /// Runs [op] after every previously queued camera operation has finished.
  /// Ops re-check [mounted] themselves — the page may be gone by then.
  Future<void> _enqueue(Future<void> Function() op) {
    final run = _ops.then((_) => op());
    _ops = run.then<void>((_) {}, onError: (_) {});
    return run;
  }

  // ── Permission-first boot ──────────────────────────────────────────────────

  /// Settle the camera permission BEFORE touching mobile_scanner. Keeping the
  /// OS dialog out of the camera start path is what makes the start reliable.
  Future<void> _boot() async {
    PermissionStatus status;
    try {
      status = await Permission.camera.status;
    } catch (_) {
      // No permission plugin on this platform — let the camera try anyway.
      _attach();
      return;
    }
    if (!mounted) return;
    if (status.isGranted || status.isLimited) {
      _attach();
    } else if (status.isPermanentlyDenied || status.isRestricted) {
      setState(() => _phase = _CamPhase.settings);
    } else {
      setState(() => _phase = _CamPhase.primer);
    }
  }

  /// Show the real OS permission dialog (from the primer's Enable button).
  Future<void> _requestPermission() async {
    if (_requestingPermission) return;
    _requestingPermission = true;
    PermissionStatus status;
    try {
      status = await Permission.camera.request();
    } catch (_) {
      status = PermissionStatus.denied;
    }
    _requestingPermission = false;
    if (!mounted) return;
    if (status.isGranted || status.isLimited) {
      _attach();
    } else if (status.isPermanentlyDenied || status.isRestricted) {
      setState(() => _phase = _CamPhase.settings);
    } else {
      // Soft deny — stay on the primer; the manual-entry path still works.
      setState(() => _phase = _CamPhase.primer);
    }
  }

  // ── Camera attach / recovery ───────────────────────────────────────────────

  void _attach() => _enqueue(_attachOp);

  /// Tears down whatever controller exists, clears wedged platform state, and
  /// brings up a fresh camera with a bounded retry. Only ever runs inside the
  /// op queue; never throws — failures land in [_phase] so the UI can offer
  /// the right next step.
  Future<void> _attachOp() async {
    if (!mounted) return;
    final existing = _controller;
    if (existing != null && existing.value.isRunning) {
      if (_phase != _CamPhase.live) setState(() => _phase = _CamPhase.live);
      return;
    }
    _attaching = true;
    setState(() => _phase = _CamPhase.starting);

    // Replace, don't reuse: a controller whose start() died midway refuses
    // every later start. Bounded dispose so a wedged one can't clog the queue.
    _controller = null;
    if (existing != null) {
      try {
        await existing.dispose().timeout(const Duration(seconds: 4));
      } catch (_) {
        // Disposing an already-dead platform camera throws; nothing to do.
      }
    }
    await _forceStopPlatform();

    final controller = MobileScannerController(
      detectionSpeed: DetectionSpeed.normal,
      autoStart: false,
    );
    if (!mounted) {
      _attaching = false;
      try {
        await controller.dispose();
      } catch (_) {
        // Disposing an already-dead platform camera throws; nothing to do.
      }
      return;
    }
    _controller = controller;
    _torch = false;
    setState(() {}); // put the MobileScanner widget in the tree before start

    for (var attempt = 0; attempt < 3; attempt++) {
      try {
        await controller.start();
        _attaching = false;
        if (!mounted || !identical(_controller, controller)) {
          try {
            await controller.dispose();
          } catch (_) {
        // Disposing an already-dead platform camera throws; nothing to do.
      }
          return;
        }
        setState(() {
          _phase = _CamPhase.live;
          _lastErrorDetail = null;
        });
        return;
      } on MobileScannerException catch (e) {
        if (e.errorCode == MobileScannerErrorCode.permissionDenied) {
          await _failAttach(controller, _CamPhase.settings, null);
          return;
        }
        if (e.errorCode == MobileScannerErrorCode.unsupported) {
          await _failAttach(controller, _CamPhase.unsupported, null);
          return;
        }
        _lastErrorDetail = e.errorDetails?.message ?? e.errorCode.name;
      } catch (e) {
        _lastErrorDetail = '$e';
      }
      // Transient CameraX/AVFoundation hiccup or leftover wedged state —
      // clear the platform side and give it a moment before the next try.
      await _forceStopPlatform();
      await Future<void>.delayed(Duration(milliseconds: 350 * (attempt + 1)));
      if (!mounted || !identical(_controller, controller)) {
        _attaching = false;
        try {
          await controller.dispose();
        } catch (_) {
        // Disposing an already-dead platform camera throws; nothing to do.
      }
        return;
      }
    }
    await _failAttach(controller, _CamPhase.failed, _lastErrorDetail);
  }

  Future<void> _failAttach(
    MobileScannerController controller,
    _CamPhase phase,
    String? detail,
  ) async {
    _attaching = false;
    if (identical(_controller, controller)) _controller = null;
    try {
      await controller.dispose().timeout(const Duration(seconds: 4));
    } catch (_) {
        // Disposing an already-dead platform camera throws; nothing to do.
      }
    if (!mounted) return;
    setState(() {
      _phase = phase;
      _lastErrorDetail = detail;
    });
  }

  /// Clears platform-side camera state unconditionally.
  ///
  /// After a FAILED start the controller's value has `isRunning: false`, so the
  /// public `stop()` no-ops — but the platform singleton may still hold its
  /// texture (a start that died midway, or a raced second start), and every
  /// later start() then throws `controllerAlreadyInitialized` forever. Only a
  /// forced stop resets that state.
  Future<void> _forceStopPlatform() async {
    try {
      if (MobileScannerPlatform.instance
          case final MethodChannelMobileScanner impl) {
        await impl.stop(force: true);
      }
    } catch (_) {/* nothing was running — that's fine */}
  }

  // ── Detection ──────────────────────────────────────────────────────────────

  void _onDetect(BarcodeCapture capture) {
    if (_busy) return;
    final code = capture.barcodes.isEmpty ? null : capture.barcodes.first.rawValue?.trim();
    if (code == null || code.isEmpty) return;
    final now = DateTime.now();
    if (code == _lastCode && now.difference(_lastAt).inMilliseconds < _sameCodeCooldownMs) return;
    _lastCode = code;
    _lastAt = now;
    _handle(code);
  }

  /// Run a single barcode through the consumer's handler and reflect the result.
  Future<void> _handle(String code) async {
    if (_busy) return;
    setState(() => _busy = true);
    ScanFeedback fb;
    try {
      fb = await widget.onScan(code);
    } catch (_) {
      fb = ScanFeedback.error(code, 'Scan failed — check connection');
    }
    if (!mounted) return;
    unawaited(HapticFeedback.mediumImpact());
    if (fb.isError) {
      unawaited(HapticFeedback.vibrate());
    }
    // Flash the viewfinder in the result colour — the "it registered" moment
    // the user sees without looking down at the feed.
    _pulseColor = switch (fb.status) {
      ScanStatus.ok => const Color(0xFF4ADE80),
      ScanStatus.warn => const Color(0xFFE6C16B),
      ScanStatus.error => AstraPalette.danger,
    };
    unawaited(_pulse.forward(from: 0));
    setState(() {
      if (!fb.isError) {
        _count++;
        _undoable = fb.undo != null ? fb : null;
      }
      _pushFeed(_FeedLine(fb));
      _busy = false;
    });
  }

  /// Undo the most recent undoable scan (a mis-scan / double count).
  Future<void> _undo() async {
    final target = _undoable;
    if (target == null || target.undo == null || _busy) return;
    setState(() => _busy = true);
    ScanFeedback? confirm;
    try {
      confirm = await target.undo!();
    } catch (_) {
      confirm = null;
    }
    if (!mounted) return;
    if (confirm == null) {
      setState(() => _busy = false);
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Could not undo the last scan.')),
      );
      return;
    }
    unawaited(HapticFeedback.selectionClick());
    setState(() {
      if (_count > 0) _count--;
      _pushFeed(_FeedLine(confirm!, undone: true));
      _undoable = null;
      _lastCode = null; // allow immediately re-scanning the same code
      _busy = false;
    });
  }

  void _pushFeed(_FeedLine line) {
    _feed.insert(0, line);
    if (_feed.length > 40) _feed.removeLast();
  }

  /// Manual barcode entry for damaged / unreadable labels — and the fallback
  /// that keeps the whole flow usable when the camera can't run at all.
  Future<void> _manualEntry() async {
    final p = context.astra;
    final ctl = TextEditingController();
    final code = await showDialog<String>(
      context: context,
      builder: (ctx) => AlertDialog(
        backgroundColor: p.cardSolid,
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(20)),
        title: Text('Enter barcode', style: serif(size: 18, color: p.ink)),
        content: TextField(
          controller: ctl,
          autofocus: true,
          style: ui(size: 15, weight: FontWeight.w700, color: p.ink),
          decoration: const InputDecoration(hintText: 'Barcode / code'),
          onSubmitted: (s) => Navigator.pop(ctx, s.trim()),
        ),
        actions: [
          TextButton(onPressed: () => Navigator.pop(ctx), child: Text('Cancel', style: ui(size: 13, weight: FontWeight.w700, color: p.textSecondary))),
          TextButton(onPressed: () => Navigator.pop(ctx, ctl.text.trim()), child: Text('Add', style: ui(size: 13, weight: FontWeight.w800, color: p.primary))),
        ],
      ),
    );
    ctl.dispose(); // owned by this method, not by the State
    if (code != null && code.isNotEmpty) {
      _lastCode = null; // manual entry is intentional — never blocked by cooldown
      unawaited(_handle(code));
    }
  }

  void _toggleTorch() {
    final c = _controller;
    if (c == null || _phase != _CamPhase.live) return;
    c.toggleTorch();
    HapticFeedback.selectionClick();
    setState(() => _torch = !_torch);
  }

  void _close() => Navigator.of(context).pop(_count);

  // ── Build ──────────────────────────────────────────────────────────────────

  bool get _cameraInTree =>
      _controller != null && (_phase == _CamPhase.starting || _phase == _CamPhase.live);

  @override
  Widget build(BuildContext context) {
    final p = context.astra;
    return PopScope(
      canPop: false,
      onPopInvokedWithResult: (didPop, _) {
        if (!didPop) _close();
      },
      child: Scaffold(
        backgroundColor: Colors.black,
        body: Column(
          children: [
            Expanded(
              child: Stack(
                fit: StackFit.expand,
                children: [
                  if (_cameraInTree)
                    MobileScanner(
                      controller: _controller!,
                      onDetect: _onDetect,
                      // Fallback only: attach failures are handled by the op
                      // queue before this can fire. It catches errors that
                      // arrive AFTER a healthy start (camera stolen by
                      // another app, sensor fault).
                      errorBuilder: (_, error) {
                        if (!_attaching) {
                          WidgetsBinding.instance.addPostFrameCallback((_) {
                            if (!mounted || _attaching) return;
                            if (_phase != _CamPhase.live && _phase != _CamPhase.starting) return;
                            setState(() {
                              _lastErrorDetail = error.errorDetails?.message ?? error.errorCode.name;
                              _phase = switch (error.errorCode) {
                                MobileScannerErrorCode.permissionDenied => _CamPhase.settings,
                                MobileScannerErrorCode.unsupported => _CamPhase.unsupported,
                                _ => _CamPhase.failed,
                              };
                            });
                          });
                        }
                        return const ColoredBox(color: Colors.black);
                      },
                    ),
                  if (_phase == _CamPhase.live)
                    _ReticleOverlay(
                      accent: p.accent,
                      scanLine: _scanLine,
                      pulse: _pulse,
                      pulseColor: _pulseColor,
                    ),
                  if (_phase == _CamPhase.boot || _phase == _CamPhase.starting)
                    const _StartingVeil(),
                  if (_phase == _CamPhase.primer) _primerState(p),
                  if (_phase == _CamPhase.settings) _settingsState(p),
                  if (_phase == _CamPhase.failed) _failedState(p),
                  if (_phase == _CamPhase.unsupported) _unsupportedState(p),
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
                              _circleBtn(Icons.close, _close, semantic: 'Close scanner'),
                              const Spacer(),
                              Text(widget.title, style: ui(size: 10, weight: FontWeight.w800, color: p.accent, letterSpacing: 2)),
                              const Spacer(),
                              if (_phase == _CamPhase.live)
                                _circleBtn(
                                  _torch ? Icons.flash_on : Icons.flash_off,
                                  _toggleTorch,
                                  gold: _torch,
                                  semantic: _torch ? 'Turn flashlight off' : 'Turn flashlight on',
                                )
                              else
                                const SizedBox(width: 44, height: 44),
                            ],
                          ),
                        ),
                      ),
                    ),
                  ),
                  // Running tally.
                  if (_phase == _CamPhase.live || _phase == _CamPhase.starting)
                    Positioned(
                      top: 70, left: 0, right: 0,
                      child: SafeArea(
                        bottom: false,
                        child: Column(
                          children: [
                            Text('$_count', style: serif(size: 40, color: Colors.white)),
                            Text(widget.tallyLabel, style: ui(size: 9.5, weight: FontWeight.w800, color: p.accent, letterSpacing: 2)),
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
      ),
    );
  }

  // ── Camera-area states ─────────────────────────────────────────────────────

  /// OS-specific, numbered steps to turn the camera permission back on.
  List<(int, String)> _permissionSteps() {
    switch (defaultTargetPlatform) {
      case TargetPlatform.iOS:
        return const [
          (1, 'Tap Open Settings below'),
          (2, 'Turn Camera on for this app'),
          (3, "Come back — it'll start automatically"),
        ];
      case TargetPlatform.android:
        return const [
          (1, 'Tap Open Settings below'),
          (2, 'Permissions → Camera → Allow'),
          (3, "Come back — it'll start automatically"),
        ];
      default:
        return const [
          (1, 'Open your device Settings'),
          (2, 'Allow camera access for this app'),
          (3, 'Return here and try again'),
        ];
    }
  }

  // ── Feed panel ─────────────────────────────────────────────────────────────

  Color _lineColor(_FeedLine l) {
    if (l.undone) return const Color(0xFF9AA6A0);
    return switch (l.fb.status) {
      ScanStatus.error => AstraPalette.danger,
      ScanStatus.warn => const Color(0xFFE6C16B),
      ScanStatus.ok => context.astra.accent,
    };
  }

  IconData _lineIcon(_FeedLine l) {
    if (l.undone) return Icons.undo;
    return switch (l.fb.status) {
      ScanStatus.error => Icons.error_outline,
      ScanStatus.warn => Icons.warning_amber_rounded,
      ScanStatus.ok => Icons.add,
    };
  }

}

// ── STATE VIEWS ───────────────────────────────────────────────────────────────
