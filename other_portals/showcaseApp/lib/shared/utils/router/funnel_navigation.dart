import 'package:flutter/widgets.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import 'package:go_router/go_router.dart';

import '../../domain/constants/global_variables.dart';
import '../../logic/branch_cubit/branch_cubit.dart';
import 'routes.dart';
import '../../logic/funnel_cubit/funnel_cubit.dart';

/// The funnel is a stack, not a set of destinations.
///
/// `size → brand → results` is strictly linear, so each step is *pushed* and
/// going back is a *pop*. That is what makes the movement read correctly:
/// `context.go` replaces the whole stack, so every step — forward or back —
/// animated as a fresh push in from the right, and returning to the size run
/// slid the same way as leaving it. A real pop also restores the platform's
/// reverse animation and the iOS edge-swipe, and it keeps the screen behind
/// alive so coming back does not refetch.
/// The last funnel destination pushed, and when.
///
/// A size chip is a small target and people tap it twice. Both taps used to
/// push, so the brand step arrived on the stack twice: the first Back then
/// landed on an identical screen, which reads as the app ignoring you.
String? _lastPushed;
DateTime? _lastPushedAt;

/// Long enough to swallow a double tap, short enough that a customer who
/// genuinely goes back and forward again is not blocked.
const Duration _doubleTapWindow = Duration(milliseconds: 900);

extension FunnelNavigation on BuildContext {
  /// Advance to [step]. Always a push, so the way back exists — but only once
  /// per tap, however many times the tap arrives.
  void goToFunnelStep(FunnelStep step) {
    final path = _pathFor(step);
    final now = DateTime.now();
    final repeat = _lastPushed == path &&
        _lastPushedAt != null &&
        now.difference(_lastPushedAt!) < _doubleTapWindow;
    if (repeat) return;
    _lastPushed = path;
    _lastPushedAt = now;
    push(path);
  }

  /// Leave the current step for the one behind it. Falls back to a replace for
  /// a cold deep link, where there is no stack to pop.
  void leaveFunnelStep(FunnelStep previous) {
    // Going back clears the guard: the step just left is a legitimate place to
    // push to again straight away.
    _lastPushed = null;
    canPop() ? pop() : go(_pathFor(previous));
  }
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
/// Shared because the breadcrumbs and the back control offer the same
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

/// Hand the panel back to nobody in particular.
///
/// Everything the last customer chose goes: their size, their brand, their
/// shop, and the stock filter goes back on. The language, the palette and the
/// text size stay — those are set by whoever runs the shop, and clearing them
/// would undo a member of staff's work rather than a customer's.
///
/// Shared with the idle timer in `app.dart` on purpose. Ten minutes of nobody
/// touching the panel and a deliberate tap on Home have to leave it in exactly
/// the same state, or "start again" means two different things depending on
/// how you got there.
Future<void> clearForNextCustomer(BuildContext context) async {
  final funnel = context.read<FunnelCubit>();
  final branch = serviceLocator<BranchCubit>();
  // Branch first: it notifies the funnel, and the funnel's own reset is what
  // settles the size run afterwards.
  await branch.selectAll();
  await funnel.resetForNextCustomer();
  // The next push is a fresh one however soon it comes.
  _lastPushed = null;
}

/// [clearForNextCustomer], and then step one.
///
/// `go`, not a pop back to the root: a customer three screens deep who asks to
/// start again should not be able to walk back into the last visit, and the
/// screens behind this one are still holding the answers that were just
/// cleared.
Future<void> goHome(BuildContext context) async {
  // Resolved before the await: the widget that was tapped may be gone by the
  // time the reset finishes.
  final router = GoRouter.of(context);
  await clearForNextCustomer(context);
  router.go(Routes.size);
}
