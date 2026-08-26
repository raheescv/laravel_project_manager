import 'package:flutter/widgets.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import 'package:go_router/go_router.dart';

import '../../../shared/utils/router/routes.dart';
import '../logic/funnel_cubit/funnel_cubit.dart';

/// Reopening an earlier funnel step: drop what came after it, then navigate.
///
/// Shared because the funnel column, the phone breadcrumbs and the results
/// toolbar all offer the same affordance, and they must behave identically —
/// going back is never destructive to the steps before the one reopened.
Future<void> reopenFunnelStep(BuildContext context, FunnelStep step) async {
  final funnel = context.read<FunnelCubit>();
  await funnel.backTo(step);
  if (!context.mounted) return;
  context.go(switch (step) {
    FunnelStep.category => Routes.browse,
    FunnelStep.size => Routes.size,
    FunnelStep.brand => Routes.brand,
    FunnelStep.results => Routes.results,
  });
}
