import 'package:go_router/go_router.dart';
import 'package:flutter/widgets.dart';

import 'package:invo/features/auth/logic/auth_cubit/auth_cubit.dart';
import 'package:invo/shared/domain/constants/mobile_permissions.dart';

import 'routes.dart';

/// No selling into a closed day — the web's `RequireOpenDaySession` on the app.
///
/// The web refuses its POS, create and edit pages until the branch has an open
/// day session; here the whole sale flow (New Sale — which is also where an
/// edit happens — the cart and Review & Pay) is held the same way. Whoever may
/// open the day is taken straight to Day Session, which carries on to New Sale
/// once it is open; everyone else gets [Routes.dayClosed], which tells them to
/// ask an admin and moves on by itself when one has.
///
/// Decided on the signed-in user's cached day status. That copy is refreshed
/// by New Sale itself on the way in, by both gate screens, and by the
/// dashboard, so a day opened or closed from another till or the web is picked
/// up without signing in again.
abstract final class DayGate {
  static const _saleFlow = {Routes.sale, Routes.cart, Routes.review};

  /// Whether [location] is a screen that rings up (or edits) a sale.
  static bool guards(String location) => _saleFlow.contains(location);

  /// Where a sale-flow route goes instead, or null while the day is open.
  static String? redirectFor(AuthCubit auth) {
    if (auth.user?.dayOpen ?? false) return null;
    return auth.hasPermission(PermissionSlug.daySession)
        ? Routes.daySessionForSale
        : Routes.dayClosed;
  }

  /// Leaves the current screen for New Sale — which the router turns into the
  /// right gate while the day is still closed. Replaces the screen when there is
  /// a back stack (so Back still returns to where the sale was started from),
  /// resets to it when there is none.
  static void toSale(BuildContext context) {
    if (context.canPop()) {
      context.pushReplacement(Routes.sale);
    } else {
      context.go(Routes.sale);
    }
  }
}
