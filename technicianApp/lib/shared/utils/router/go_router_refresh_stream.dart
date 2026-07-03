import 'dart:async';

import 'package:flutter/foundation.dart';

/// Adapts a [Stream] (e.g. a Cubit's state stream) into a [Listenable] so it can
/// drive `GoRouter.refreshListenable` — re-evaluating redirects whenever the
/// stream emits (used to react to auth state changes).
class GoRouterRefreshStream extends ChangeNotifier {
  GoRouterRefreshStream(Stream<dynamic> stream) {
    notifyListeners();
    _subscription = stream.asBroadcastStream().listen((_) => notifyListeners());
  }

  late final StreamSubscription<dynamic> _subscription;

  @override
  void dispose() {
    _subscription.cancel();
    super.dispose();
  }
}
