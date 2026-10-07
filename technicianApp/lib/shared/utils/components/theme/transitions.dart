import 'package:flutter/cupertino.dart';
import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';

import 'package:invo/shared/domain/helpers/responsive.dart';

/// The app's one transition vocabulary. Every route uses the theme's
/// [astraPageTransitionsTheme] (so a `MaterialPageRoute` and a plain
/// `GoRoute` animate identically); go_router pages only opt into
/// [astraFadeThroughPage] where a swap should not read as "drilling in" —
/// signing in / out and the shell itself.
///
/// Timings sit at 260–300ms with ease-out curves: long enough to read the
/// direction, short enough to never feel like waiting.
class AstraMotion {
  AstraMotion._();

  static const Duration fast = Duration(milliseconds: 180);
  static const Duration medium = Duration(milliseconds: 260);
  static const Curve curve = Curves.easeOutCubic;
}

/// Android / desktop: the incoming page fades in while sliding a short way in
/// from the right, and the outgoing page drifts slightly left — the same read
/// as iOS's slide, without the full-width travel. A full-screen modal
/// (`fullscreenDialog`) rises from below instead.
class AstraPageTransitionsBuilder extends PageTransitionsBuilder {
  const AstraPageTransitionsBuilder();

  @override
  Widget buildTransitions<T>(
    PageRoute<T> route,
    BuildContext context,
    Animation<double> animation,
    Animation<double> secondaryAnimation,
    Widget child,
  ) {
    final incoming = CurvedAnimation(parent: animation, curve: AstraMotion.curve, reverseCurve: Curves.easeInCubic);
    if (route.fullscreenDialog) {
      return SlideTransition(
        position: Tween(begin: const Offset(0, 0.06), end: Offset.zero).animate(incoming),
        child: FadeTransition(opacity: incoming, child: child),
      );
    }
    final outgoing = CurvedAnimation(parent: secondaryAnimation, curve: AstraMotion.curve);
    return SlideTransition(
      position: Tween(begin: const Offset(0.06, 0), end: Offset.zero).animate(incoming),
      child: FadeTransition(
        opacity: incoming,
        child: SlideTransition(
          position: Tween(begin: Offset.zero, end: const Offset(-0.025, 0)).animate(outgoing),
          child: child,
        ),
      ),
    );
  }
}

/// Picks the transition per device class. Phones: Cupertino slide on Apple
/// platforms (keeps the edge-swipe back, and fullscreen dialogs rise), the
/// Astra fade-slide elsewhere. Tablets: a plain cross-fade for ordinary
/// routes — every railed screen draws the rail in the same place, so a fade
/// keeps the rail visually still where a slide would carry the whole window
/// (rail included) across the screen.
class AstraAdaptivePageTransitionsBuilder extends PageTransitionsBuilder {
  const AstraAdaptivePageTransitionsBuilder({this.cupertino = false});

  final bool cupertino;

  @override
  Widget buildTransitions<T>(
    PageRoute<T> route,
    BuildContext context,
    Animation<double> animation,
    Animation<double> secondaryAnimation,
    Widget child,
  ) {
    final tablet = MediaQuery.sizeOf(context).shortestSide >= Breakpoints.tablet;
    if (tablet && !route.fullscreenDialog) {
      return FadeTransition(
        opacity: CurvedAnimation(parent: animation, curve: AstraMotion.curve, reverseCurve: Curves.easeIn),
        child: child,
      );
    }
    final PageTransitionsBuilder inner =
        cupertino ? const CupertinoPageTransitionsBuilder() : const AstraPageTransitionsBuilder();
    return inner.buildTransitions(route, context, animation, secondaryAnimation, child);
  }
}

const astraPageTransitionsTheme = PageTransitionsTheme(builders: {
  TargetPlatform.iOS: AstraAdaptivePageTransitionsBuilder(cupertino: true),
  TargetPlatform.macOS: AstraAdaptivePageTransitionsBuilder(cupertino: true),
  TargetPlatform.android: AstraAdaptivePageTransitionsBuilder(),
  TargetPlatform.fuchsia: AstraAdaptivePageTransitionsBuilder(),
  TargetPlatform.linux: AstraAdaptivePageTransitionsBuilder(),
  TargetPlatform.windows: AstraAdaptivePageTransitionsBuilder(),
});

/// A fade-through go_router page: the new screen fades in over a 2% scale-up.
/// For swaps that are not a step deeper — sign-in ↔ home.
CustomTransitionPage<void> astraFadeThroughPage(GoRouterState state, Widget child) => CustomTransitionPage<void>(
      key: state.pageKey,
      transitionDuration: AstraMotion.medium,
      reverseTransitionDuration: AstraMotion.fast,
      child: child,
      transitionsBuilder: (_, animation, __, child) {
        final a = CurvedAnimation(parent: animation, curve: AstraMotion.curve);
        return FadeTransition(
          opacity: a,
          child: ScaleTransition(scale: Tween(begin: 0.98, end: 1.0).animate(a), child: child),
        );
      },
    );

/// Short fade + 2% rise used when a pane's content swaps (tablet detail panes,
/// settings / profile sections). Keyed children animate; equal keys don't.
Widget astraPaneSwitcher({required Widget child}) => AnimatedSwitcher(
      duration: AstraMotion.medium,
      reverseDuration: AstraMotion.fast,
      switchInCurve: AstraMotion.curve,
      switchOutCurve: Curves.easeIn,
      layoutBuilder: (current, previous) => Stack(
        alignment: Alignment.topCenter,
        children: [...previous, if (current != null) current],
      ),
      transitionBuilder: (child, animation) => FadeTransition(
        opacity: animation,
        child: SlideTransition(
          position: Tween(begin: const Offset(0, 0.015), end: Offset.zero).animate(animation),
          child: child,
        ),
      ),
      child: child,
    );
