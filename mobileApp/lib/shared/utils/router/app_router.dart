import 'package:go_router/go_router.dart';
import 'package:invo/features/admin/screens/v3/day_session_screen.dart';
import 'package:invo/features/auth/logic/auth_cubit/auth_cubit.dart';
import 'package:invo/features/auth/screens/v3/login_screen.dart';
import 'package:invo/features/profile/screens/v3/change_password_screen.dart';
import 'package:invo/features/profile/screens/v3/change_pin_screen.dart';
import 'package:invo/features/profile/screens/v3/edit_profile_screen.dart';
import 'package:invo/features/profile/screens/v3/profile_screen.dart';
import 'package:invo/features/sale/screens/v3/cart_screen.dart';
import 'package:invo/features/sale/screens/v3/invoice_screen.dart';
import 'package:invo/features/sale/screens/v3/new_sale_screen.dart';
import 'package:invo/features/sale/screens/v3/pending_sales_screen.dart';
import 'package:invo/features/sale/screens/v3/review_pay_screen.dart';
import 'package:invo/features/sale_return/screens/v3/new_sale_return_screen.dart';
import 'package:invo/features/sale_return/screens/v3/return_pick_invoice_screen.dart';
import 'package:invo/features/sale_return/screens/v3/return_receipt_screen.dart';
import 'package:invo/features/sale_return/screens/v3/return_review_screen.dart';
import 'package:invo/features/sales/screens/v3/sales_list_screen.dart';
import 'package:invo/features/sales_returns/screens/v3/sales_returns_list_screen.dart';
import 'package:invo/features/stock_check/domain/models/stock_check_models.dart';
import 'package:invo/features/stock_check/screens/v3/new_stock_check_screen.dart';
import 'package:invo/features/stock_check/screens/v3/stock_check_count_screen.dart';
import 'package:invo/features/stock_check/screens/v3/stock_check_list_screen.dart';
import 'package:invo/features/settings/screens/v3/offline_data_screen.dart';
import 'package:invo/features/settings/screens/v3/permissions_screen.dart';
import 'package:invo/features/settings/screens/v3/print_settings_screen.dart';
import 'package:invo/features/shell/screens/v3/home_shell.dart';
import 'package:invo/shared/widgets/astra_side_rail.dart';
import 'package:invo/shared/domain/constants/mobile_permissions.dart';
import 'package:invo/shared/domain/models/index.dart';

import 'go_router_refresh_stream.dart';
import 'routes.dart';

GoRouter createRouter(AuthCubit auth) {
  return GoRouter(
    initialLocation: Routes.login,
    refreshListenable: GoRouterRefreshStream(auth.stream),
    redirect: (context, state) {
      final loggedIn = auth.status == AuthStatus.signedIn;
      final atLogin = state.matchedLocation == Routes.login;
      if (auth.status == AuthStatus.unknown) return null;
      if (!loggedIn) return atLogin ? null : Routes.login;
      final canViewAdmin = auth.hasPermission(PermissionSlug.salesOverview);
      if (atLogin) return canViewAdmin ? Routes.home : Routes.sale;
      if (state.matchedLocation == Routes.home && !canViewAdmin) return Routes.sale;
      if (state.matchedLocation == Routes.daySession &&
          !auth.hasPermission(PermissionSlug.daySession)) {
        return Routes.sale;
      }
      // Sale-return module: viewing the list needs `.view`; the authoring flow
      // (pick/compose/review, shared by create AND edit) needs create OR edit.
      final loc = state.matchedLocation;
      if (loc == Routes.salesReturns && !auth.hasPermission(PermissionSlug.saleReturnView)) {
        return Routes.sale;
      }
      final canAuthorReturn = auth.hasPermission(PermissionSlug.saleReturnCreate) ||
          auth.hasPermission(PermissionSlug.saleReturnEdit);
      if (loc.startsWith(Routes.saleReturn) && !canAuthorReturn) {
        return Routes.sale;
      }
      // Stock Check module — gated on the same permission as the web feature.
      if (loc.startsWith(Routes.stockCheck) && !auth.hasPermission(PermissionSlug.stockCheck)) {
        return Routes.sale;
      }
      return null;
    },
    routes: [
      GoRoute(path: Routes.login, builder: (_, __) => const LoginScreen()),
      GoRoute(
        path: Routes.home,
        builder: (_, state) => HomeShell(
            initialTab:
                int.tryParse(state.uri.queryParameters['tab'] ?? '') ?? 0),
      ),
      GoRoute(path: Routes.sale, builder: (_, __) => const NewSaleScreen()),
      GoRoute(path: Routes.cart, builder: (_, __) => const CartScreen()),
      GoRoute(path: Routes.review, builder: (_, __) => const ReviewPayScreen()),
      // `extra` is not preserved across a process restore and is absent on a deep
      // link, so the three routes that carry a model guard the cast and fall back
      // to their list instead of throwing on a bare `as`.
      GoRoute(
        path: Routes.invoice,
        redirect: (_, state) => state.extra is Sale ? null : Routes.sales,
        builder: (_, state) =>
            TabletRailScaffold(activeTab: 1, child: InvoiceScreen(sale: state.extra as Sale)),
      ),
      GoRoute(path: Routes.sales, builder: (_, __) => const SalesListScreen()),
      GoRoute(
          path: Routes.pendingSales,
          builder: (_, __) => const TabletRailScaffold(
              activeTab: 1, child: PendingSalesScreen())),
      // Pushed *destinations* keep the tablet side-rail (see [TabletRailScaffold])
      // so navigation stays reachable — a full-bleed screen with no rail strands
      // the user until they hit back. Task flows (New Sale, cart, review, the
      // return wizard) deliberately don't get it: they're meant to be finished
      // or cancelled, not navigated away from.
      GoRoute(
          path: Routes.salesReturns,
          builder: (_, __) => const TabletRailScaffold(
              activeTab: kReturnsTab, child: SalesReturnListScreen())),
      // On tablet Stock Check is a shell destination (`/home?tab=4`); this route
      // still serves phones and any deep link, and keeps the rail so the two
      // look the same.
      GoRoute(
          path: Routes.stockCheck,
          builder: (_, __) => const TabletRailScaffold(
              activeTab: kStockCheckTab, child: StockCheckListScreen())),
      GoRoute(
          path: Routes.stockCheckNew,
          builder: (_, __) => const TabletRailScaffold(
              activeTab: kStockCheckTab, child: NewStockCheckScreen())),
      GoRoute(
          path: Routes.stockCheckCount,
          redirect: (_, state) =>
              state.extra is StockCheckDetail ? null : Routes.stockCheck,
          builder: (_, state) => TabletRailScaffold(
              activeTab: kStockCheckTab,
              child: StockCheckCountScreen(detail: state.extra as StockCheckDetail))),
      GoRoute(
          path: Routes.saleReturn, builder: (_, __) => const NewSaleReturnScreen()),
      GoRoute(
          path: Routes.saleReturnPick,
          builder: (_, __) => const ReturnPickInvoiceScreen()),
      GoRoute(
          path: Routes.saleReturnReview,
          builder: (_, __) => const ReturnReviewScreen()),
      GoRoute(
        path: Routes.returnReceipt,
        redirect: (_, state) => state.extra is SaleReturn ? null : Routes.salesReturns,
        builder: (_, state) => TabletRailScaffold(
            activeTab: kReturnsTab, child: ReturnReceiptScreen(saleReturn: state.extra as SaleReturn)),
      ),
      // The account screens are *destinations*, not a task flow, so they all
      // keep the rail. On a tablet the last three normally never appear as
      // routes: Profile hosts them in its detail pane (see [ProfileScreen]) so
      // editing never takes the window. These entries still serve phones and
      // deep links, hence the rail here too.
      GoRoute(
          path: Routes.profile,
          builder: (_, __) => const TabletRailScaffold(child: ProfileScreen())),
      GoRoute(
          path: Routes.daySession,
          builder: (_, __) => const TabletRailScaffold(
              activeTab: kDaySessionTab, child: DaySessionScreen())),
      GoRoute(
          path: Routes.changePin,
          builder: (_, __) => const TabletRailScaffold(child: ChangePinScreen())),
      GoRoute(
          path: Routes.changePassword,
          builder: (_, __) => const TabletRailScaffold(child: ChangePasswordScreen())),
      GoRoute(
          path: Routes.editProfile,
          builder: (_, __) => const TabletRailScaffold(child: EditProfileScreen())),
      GoRoute(
          path: Routes.printSettings,
          builder: (_, __) => const TabletRailScaffold(child: PrintSettingsScreen())),
      GoRoute(
          path: Routes.permissions,
          builder: (_, __) => const TabletRailScaffold(child: PermissionsScreen())),
      GoRoute(
          path: Routes.offlineData,
          builder: (_, __) => const TabletRailScaffold(child: OfflineDataScreen())),
    ],
  );
}
