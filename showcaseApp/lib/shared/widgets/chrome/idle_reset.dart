import 'dart:async';

import 'package:flutter/widgets.dart';

/// Sends the tablet back to the start when nobody is using it.
///
/// A showcase tablet is handed from customer to customer and nobody signs out.
/// Without this the next person walks up to the last one's half-finished
/// funnel — someone else's size, someone else's brand, three screens deep —
/// and has to work out how to get back before they can start.
///
/// Any touch resets the clock, including a scroll, so it never fires under
/// someone who is reading rather than tapping.
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

  @override
  State<IdleReset> createState() => _IdleResetState();
}

class _IdleResetState extends State<IdleReset> {
  Timer? _timer;

  @override
  void initState() {
    super.initState();
    _restart();
  }

  @override
  void didUpdateWidget(IdleReset old) {
    super.didUpdateWidget(old);
    if (old.after != widget.after) _restart();
  }

  @override
  void dispose() {
    _timer?.cancel();
    super.dispose();
  }

  void _restart() {
    _timer?.cancel();
    _timer = Timer(widget.after, widget.onIdle);
  }

  @override
  Widget build(BuildContext context) {
    // Listener, not GestureDetector: this must see the touch without competing
    // for it. A gesture arena entry here would swallow taps from the buttons
    // underneath, and behind an opaque child a HitTestBehavior would miss the
    // ones that land on them.
    return Listener(
      onPointerDown: (_) => _restart(),
      onPointerMove: (_) => _restart(),
      onPointerSignal: (_) => _restart(),
      child: widget.child,
    );
  }
}
