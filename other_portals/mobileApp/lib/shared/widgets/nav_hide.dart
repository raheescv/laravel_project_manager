import 'package:flutter/widgets.dart';

/// Gives the bottom nav the "the screen just got bigger" behaviour. Two ways to
/// send the bar away, both landing in the same place:
///
///  * scroll down into a page and it slides off the bottom edge, returning on
///    the first upward drag;
///  * or drag the bar itself downwards — it follows the finger and, past about
///    a third of the way (or on a flick), stays gone until you pull the page
///    back up.
///
/// Wrap the whole `Scaffold` — the body's scroll notifications have to bubble
/// up to here, and the bar (a sibling of the body, in the `bottomNavigationBar`
/// slot) has to sit inside to hear the result. Pair it with `extendBody: true`
/// and go on using [AstraNavBar] / [AstraNavFab] as before: they look the scope
/// up themselves and stay put when there isn't one.
class NavHide extends StatefulWidget {
  const NavHide({super.key, required this.child});

  final Widget child;

  /// How far the nav is pushed off the bottom right now: 0 on show, 1 gone.
  /// Null outside a [NavHide].
  static Animation<double>? of(BuildContext context) => _scopeOf(context)?.hidden;

  static _NavHideScope? _scopeOf(BuildContext context) =>
      context.dependOnInheritedWidgetOfExactType<_NavHideScope>();

  @override
  State<NavHide> createState() => _NavHideState();
}

class _NavHideState extends State<NavHide> with SingleTickerProviderStateMixin {
  late final _hidden = AnimationController(vsync: this, duration: const Duration(milliseconds: 260));

  /// Distance travelled since the last flip in direction. Accumulating the
  /// deltas rather than reacting to `UserScrollNotification` alone means a
  /// slow drag hides the bar too, and a twitch in the other direction doesn't.
  double _carried = 0;

  /// The bar was put away by hand, so a page sitting at its top doesn't get to
  /// pull it straight back — only the reader does.
  bool _flickedAway = false;

  static const _hideAfter = 26.0; // px of downward travel before it leaves
  static const _showAfter = 12.0; // a shorter pull back is enough to call it
  static const _tooShortToScroll = 60.0;

  @override
  void dispose() {
    _hidden.dispose();
    super.dispose();
  }

  void _settle(double to) =>
      _hidden.animateTo(to, duration: const Duration(milliseconds: 240), curve: Curves.easeOutCubic);

  void _show() {
    _flickedAway = false;
    _carried = 0;
    if (_hidden.value != 0) _settle(0);
  }

  void _hide() {
    if (_hidden.value != 1) _settle(1);
  }

  // ---- dragging the bar itself ----

  void _dragStart() => _hidden.stop();

  void _dragUpdate(double dy, double travel) {
    if (travel <= 0) return;
    _hidden.value = (_hidden.value + dy / travel).clamp(0.0, 1.0);
  }

  void _dragEnd(double velocity) {
    // A downward flick sends it away whatever it managed to travel; otherwise
    // whichever end it is nearer to wins, with a bias towards letting go.
    final away = velocity > 240 || (velocity > -240 && _hidden.value > 0.35);
    _flickedAway = away;
    _settle(away ? 1 : 0);
  }

  // ---- following the page ----

  bool _onScroll(ScrollNotification n) {
    // Depth 0 only: a horizontal chip row or an inner list inside the page is
    // not the reader moving through the page, so it must not drive the bar.
    if (n.depth != 0 || n.metrics.axis != Axis.vertical) return false;
    if (n is ScrollUpdateNotification) {
      final d = n.scrollDelta ?? 0;
      if (d != 0) _travelled(d, n.metrics);
    } else if (n is OverscrollNotification) {
      // Pulling the page past its top is how a bar that was flicked away comes
      // back on a page with nothing left to scroll.
      _travelled(n.overscroll, n.metrics);
    }
    return false;
  }

  void _travelled(double d, ScrollMetrics m) {
    _carried = _carried.sign == d.sign ? _carried + d : d;
    // Never strand the nav: a page that doesn't scroll has nothing hiding under
    // the bar anyway, so any gesture on it brings the bar back.
    if (m.maxScrollExtent <= _tooShortToScroll) return _show();
    if (_carried < -_showAfter) return _show();
    if (_flickedAway) return;
    if (m.pixels <= m.minScrollExtent + 8) return _show();
    if (_carried > _hideAfter) _hide();
  }

  @override
  Widget build(BuildContext context) => NotificationListener<ScrollNotification>(
        onNotification: _onScroll,
        child: _NavHideScope(hidden: _hidden, state: this, child: widget.child),
      );
}

class _NavHideScope extends InheritedWidget {
  const _NavHideScope({required this.hidden, required this.state, required super.child});

  final Animation<double> hidden;
  final _NavHideState state;

  @override
  bool updateShouldNotify(_NavHideScope old) => hidden != old.hidden;
}

/// Carries [child] off the bottom edge as the surrounding [NavHide] hides the
/// nav. A plain pass-through outside a [NavHide], so a screen that hasn't opted
/// in looks exactly as it did.
class NavHideSlide extends StatelessWidget {
  const NavHideSlide({super.key, required this.child, this.offscreen = 1.15, this.fade = false, this.drag = false});

  final Widget child;

  /// How far to travel, as a fraction of the child's own height — a little over
  /// 1 clears the bar's shadow as well as the bar.
  final double offscreen;

  /// Fade on the way out as well (the docked "+" reads better that way; the
  /// bar itself just leaves).
  final bool fade;

  /// Let the reader push this off the screen by dragging it downwards. Only the
  /// bar wants this — the docked "+" is a button, and rides along with it.
  final bool drag;

  @override
  Widget build(BuildContext context) {
    final scope = NavHide._scopeOf(context);
    if (scope == null) return child;
    return AnimatedBuilder(
      animation: scope.hidden,
      child: drag ? _NavHideDragger(state: scope.state, travel: offscreen, child: child) : child,
      builder: (context, child) {
        final t = scope.hidden.value;
        return IgnorePointer(
          ignoring: t > 0.9,
          child: FractionalTranslation(
            translation: Offset(0, t * offscreen),
            child: fade ? Opacity(opacity: (1 - t).clamp(0.0, 1.0), child: child) : child,
          ),
        );
      },
    );
  }
}

/// Makes the bar itself draggable. A tap on a tab still wins — the drag only
/// takes the pointer once it has moved past the touch slop.
class _NavHideDragger extends StatelessWidget {
  const _NavHideDragger({required this.state, required this.travel, required this.child});

  final _NavHideState state;
  final double travel;
  final Widget child;

  /// The whole bar, centre gap included, is a handle.
  double _extent(BuildContext context) => (context.size?.height ?? 0) * travel;

  @override
  Widget build(BuildContext context) => GestureDetector(
        behavior: HitTestBehavior.opaque,
        onVerticalDragStart: (_) => state._dragStart(),
        onVerticalDragUpdate: (d) => state._dragUpdate(d.primaryDelta ?? 0, _extent(context)),
        onVerticalDragEnd: (d) => state._dragEnd(d.primaryVelocity ?? 0),
        child: child,
      );
}
