import 'package:flutter/widgets.dart';

/// The app's one route observer, attached to the router in `createRouter`.
///
/// Page routes only: a screen that subscribes hears `didPopNext` when a *page*
/// pushed over it comes off — New Sale, Day Session, a report — and not when
/// one of its own dialogs or bottom sheets closes. Those are popup routes, and
/// counting them would refetch the screen behind them on every "Cancel".
///
/// A page removed declaratively (`context.go` back to the shell) runs through
/// the same pop as `context.pop()` — Flutter's Navigator marks the last
/// exiting page route for a pop — so both ways back are heard.
final RouteObserver<PageRoute<dynamic>> routeObserver =
    RouteObserver<PageRoute<dynamic>>();
