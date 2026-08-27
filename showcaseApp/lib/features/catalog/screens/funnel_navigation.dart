import 'package:flutter/widgets.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import 'package:go_router/go_router.dart';

import '../../../shared/utils/router/routes.dart';
import '../logic/funnel_cubit/funnel_cubit.dart';

/// The funnel is a stack, not a set of destinations.
///
/// `size → brand → results` is strictly linear, so each step is *pushed* and
/// going back is a *pop*. That is what makes the movement read correctly:
/// `context.go` replaces the whole stack, so every step — forward or back —
/// animated as a fresh push in from the right, and returning to the size run
/// slid the same way as leaving it. A real pop also restores the platform's
/// reverse animation and the iOS edge-swipe, and it keeps the screen behind
/// alive so coming back does not refetch.
extension FunnelNavigation on BuildContext {
  /// Advance to [step]. Always a push, so the way back exists.
  void goToFunnelStep(FunnelStep step) => push(_pathFor(step));

  /// Leave the current step for the one behind it. Falls back to a replace for
  /// a cold deep link, where there is no stack to pop.
  void leaveFunnelStep(FunnelStep previous) =>
      canPop() ? pop() : go(_pathFor(previous));
}

String _pathFor(FunnelStep step) => switch (step) {
      FunnelStep.size => Routes.size,
      FunnelStep.brand => Routes.brand,
      FunnelStep.results => Routes.results,
    };

FunnelStep? _stepAt(String path) => switch (path) {
      Routes.size => FunnelStep.size,
      Routes.brand => FunnelStep.brand,
      Routes.results => FunnelStep.results,
      _ => null,
    };

/// Reopening an earlier funnel step: drop what came after it, then unwind the
/// stack to it.
///
/// Shared because the funnel column and the phone breadcrumbs offer the same
/// affordance, and they must behave identically — going back is never
/// destructive to the steps before the one reopened.
Future<void> reopenFunnelStep(BuildContext context, FunnelStep step) async {
  // Read where we are before the state changes: the funnel's own `step` getter
  // is derived from the answers, and `backTo` is about to clear them.
  final router = GoRouter.of(context);
  final current = _stepAt(GoRouterState.of(context).uri.path);

  await context.read<FunnelCubit>().backTo(step);
  if (!context.mounted) return;

  // Unwind rather than jump, so the screens the customer is returning to are
  // the ones they left — with their scroll position and their loaded page.
  if (current != null && current.index > step.index) {
    for (var i = current.index; i > step.index && router.canPop(); i--) {
      router.pop();
    }
    return;
  }
  router.go(_pathFor(step));
}
