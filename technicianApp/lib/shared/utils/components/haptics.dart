import 'package:flutter/gestures.dart';
import 'package:flutter/material.dart';
import 'package:flutter/services.dart';

/// The app's standard tap feedback — a light, crisp "tick". Call [Haptics.tap]
/// anywhere; most of the app gets it for free through [HapticTapDetector].
class Haptics {
  Haptics._();

  /// Light tap tick — the default feedback for buttons, chips, list rows.
  static void tap() => HapticFeedback.selectionClick();
}

/// Wraps the whole app so a light haptic fires on **every genuine tap, anywhere**
/// without wiring feedback into each tap handler.
///
/// Uses a [Listener] (outside the gesture arena) so it ticks no matter which
/// descendant wins the tap. A press that moves more than [_tapSlop] before
/// lifting is treated as a scroll/drag and stays silent.
class HapticTapDetector extends StatefulWidget {
  const HapticTapDetector({super.key, required this.child});

  final Widget child;

  @override
  State<HapticTapDetector> createState() => _HapticTapDetectorState();
}

class _HapticTapDetectorState extends State<HapticTapDetector> {
  final Map<int, Offset> _downAt = {};

  static const double _tapSlop = kTouchSlop + 6;

  void _onDown(PointerDownEvent e) => _downAt[e.pointer] = e.position;

  void _onUp(PointerUpEvent e) {
    final start = _downAt.remove(e.pointer);
    if (start == null) return;
    if ((e.position - start).distance <= _tapSlop) Haptics.tap();
  }

  void _onCancel(PointerCancelEvent e) => _downAt.remove(e.pointer);

  @override
  Widget build(BuildContext context) {
    return Listener(
      onPointerDown: _onDown,
      onPointerUp: _onUp,
      onPointerCancel: _onCancel,
      child: widget.child,
    );
  }
}
