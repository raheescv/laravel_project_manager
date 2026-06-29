import 'package:flutter/gestures.dart';
import 'package:flutter/material.dart';
import 'package:flutter/services.dart';

/// The app's standard tap feedback — a light, crisp "tick" (a subtle selection
/// click on iOS, a soft tick on Android). Call [Haptics.tap] anywhere you want
/// an explicit tap to buzz; most of the app gets it for free through the
/// app-wide [HapticTapDetector].
class Haptics {
  Haptics._();

  /// Light tap tick. The default feedback for buttons, chips, list rows, etc.
  static void tap() => HapticFeedback.selectionClick();
}

/// Wraps the whole app so a light haptic fires on **every genuine tap, anywhere**
/// — without having to wire feedback into each of the app's ~160 tap handlers
/// (and so new screens get it automatically).
///
/// It uses a [Listener] rather than a [GestureDetector]: a Listener sits outside
/// the gesture arena and sees the raw pointer stream, so it ticks no matter which
/// descendant widget ultimately "wins" the tap. A press that moves more than
/// [_tapSlop] before lifting is treated as a scroll/drag/fling and stays silent,
/// so scrolling a list never buzzes.
class HapticTapDetector extends StatefulWidget {
  const HapticTapDetector({super.key, required this.child});

  final Widget child;

  @override
  State<HapticTapDetector> createState() => _HapticTapDetectorState();
}

class _HapticTapDetectorState extends State<HapticTapDetector> {
  // Where each active pointer first touched down (keyed by pointer id so
  // multi-touch is tracked per finger).
  final Map<int, Offset> _downAt = {};

  // A press that travels farther than this between down and up is a scroll/drag,
  // not a tap. A hair above the framework's scroll slop so deliberate taps with
  // a tiny wobble still count.
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
    // deferToChild (the default): only react when the pointer actually landed on
    // something in the tree, so taps on real content tick and nothing fires in
    // genuine dead space.
    return Listener(
      onPointerDown: _onDown,
      onPointerUp: _onUp,
      onPointerCancel: _onCancel,
      child: widget.child,
    );
  }
}
