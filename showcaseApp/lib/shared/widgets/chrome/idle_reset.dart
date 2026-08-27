import 'dart:async';

import 'package:flutter/services.dart';
import 'package:flutter/widgets.dart';

/// Sends the tablet back to the start when nobody is using it.
///
/// A showcase tablet is handed from customer to customer and nobody signs out.
/// Without this the next person walks up to the last one's half-finished
/// funnel — someone else's size, someone else's brand, three screens deep —
/// and has to work out how to get back before they can start.
///
/// Anything that counts as using the panel puts the clock back: a tap, a
/// scroll, a key, and a keystroke reported through [keepAlive]. So it never
/// fires under someone who is reading rather than tapping, and never under
/// someone typing a search.
class IdleReset extends StatefulWidget {
  const IdleReset({
    super.key,
    required this.child,
    required this.onIdle,
    this.after = const Duration(minutes: 10),
  });

  final Widget child;
  final VoidCallback onIdle;
  final Duration after;

  /// Report use the pointer stream cannot see.
  ///
  /// The soft keyboard is the platform's, not ours: its taps go to the input
  /// method and never reach this widget's [Listener], so a customer typing a
  /// product code sat still as far as the timer was concerned and had the
  /// screen pulled out from under them mid-word. Text fields call this from
  /// `onChanged`, which is the one place a keystroke is always visible however
  /// it was produced.
  ///
  /// Safe to call from anywhere — off a panel that has no idle timer above it,
  /// as in a widget test, it does nothing.
  static void keepAlive(BuildContext context) =>
      context.findAncestorStateOfType<_IdleResetState>()?._restart();

  @override
  State<IdleReset> createState() => _IdleResetState();
}

class _IdleResetState extends State<IdleReset> {
  Timer? _timer;

  @override
  void initState() {
    super.initState();
    // Physical keyboards, and the soft keyboards that do send key events —
    // enough IMEs commit text without one that [IdleReset.keepAlive] is the
    // guarantee and this is the belt.
    HardwareKeyboard.instance.addHandler(_onKey);
    _restart();
  }

  @override
  void didUpdateWidget(IdleReset old) {
    super.didUpdateWidget(old);
    if (old.after != widget.after) _restart();
  }

  @override
  void dispose() {
    HardwareKeyboard.instance.removeHandler(_onKey);
    _timer?.cancel();
    super.dispose();
  }

  /// Never consumed: this is watching the keyboard, not answering it.
  bool _onKey(KeyEvent event) {
    _restart();
    return false;
  }

  void _restart() {
    _timer?.cancel();
    // Through the widget rather than by handing `widget.onIdle` to the timer:
    // the callback is a closure rebuilt on every frame above this one, and an
    // armed timer holding the one from ten minutes ago would be calling into a
    // build that has since been replaced.
    _timer = Timer(widget.after, _fire);
  }

  /// Reset, then start waiting again.
  ///
  /// Re-arming matters because the reset can fail to take — the panel is
  /// offline, the size run will not load, the app was mid-navigation. A timer
  /// that only ever fired once left the tablet stranded on the last customer's
  /// screen with nothing left to try. Standing at the start it costs one
  /// reload of the size run per wait, which is the panel picking up the day's
  /// stock changes rather than showing the run it loaded when the shop opened.
  void _fire() {
    widget.onIdle();
    _restart();
  }

  @override
  Widget build(BuildContext context) {
    // Listener, not GestureDetector: this must see the touch without competing
    // for it. A gesture arena entry here would swallow taps from the buttons
    // underneath, and behind an opaque child a HitTestBehavior would miss the
    // ones that land on them.
    //
    // Both ends of a gesture, and not what happens between them. A drag reports
    // a pointer move on every frame — a hundred and twenty times a second under
    // every scroll in the app — and each one was cancelling a timer and
    // allocating another to say the same thing the one before it said. The
    // finger going down and coming up bracket the whole gesture however long it
    // ran, which is the fact this timer is actually interested in; the only case
    // that loses is a finger held against the glass for the whole wait without
    // moving off it, and a resting hand is not somebody using the panel.
    return Listener(
      onPointerDown: (_) => _restart(),
      onPointerUp: (_) => _restart(),
      onPointerCancel: (_) => _restart(),
      onPointerSignal: (_) => _restart(),
      child: widget.child,
    );
  }
}
