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
import 'package:invo/features/settings/screens/v3/permissions_screen.dart';
import 'package:invo/features/settings/screens/v3/print_settings_screen.dart';
import 'package:invo/features/shell/screens/v3/home_shell.dart';
import 'package:invo/shared/domain/constants/mobile_permissions.dart';
import 'package:invo/shared/domain/models/index.dart';

import 'go_router_refresh_stream.dart';

GoRouter createRouter(AuthCubit auth) {
  return GoRouter(
    initialLocation: '/login',
    refreshListenable: GoRouterRefreshStream(auth.stream),
    redirect: (context, state) {
      final loggedIn = auth.status == AuthStatus.signedIn;
      final atLogin = state.matchedLocation == '/login';
      if (auth.status == AuthStatus.unknown) return null;
      if (!loggedIn) return atLogin ? null : '/login';
      final canViewAdmin = auth.hasPermission(PermissionSlug.salesOverview);
      if (atLogin) return canViewAdmin ? '/home' : '/sale';
      if (state.matchedLocation == '/home' && !canViewAdmin) return '/sale';
      if (state.matchedLocation == '/day-session' &&
          !auth.hasPermission(PermissionSlug.daySession)) {
        return '/sale';
      }
      // Sale-return module: viewing the list needs `.view`; the authoring flow
      // (pick/compose/review, shared by create AND edit) needs create OR edit.
      final loc = state.matchedLocation;
      if (loc == '/sales-returns' && !auth.hasPermission(PermissionSlug.saleReturnView)) {
        return '/sale';
      }
      final canAuthorReturn = auth.hasPermission(PermissionSlug.saleReturnCreate) ||
          auth.hasPermission(PermissionSlug.saleReturnEdit);
      if (loc.startsWith('/sale-return') && !canAuthorReturn) {
        return '/sale';
      }
      // Stock Check module — gated on the same permission as the web feature.
      if (loc.startsWith('/stock-check') && !auth.hasPermission(PermissionSlug.stockCheck)) {
        return '/sale';
      }
      return null;
    },
    routes: [
      GoRoute(path: '/login', builder: (_, __) => const LoginScreen()),
      GoRoute(
        path: '/home',
        builder: (_, state) => HomeShell(
            initialTab:
                int.tryParse(state.uri.queryParameters['tab'] ?? '') ?? 0),
      ),
      GoRoute(path: '/sale', builder: (_, __) => const NewSaleScreen()),
      GoRoute(path: '/cart', builder: (_, __) => const CartScreen()),
      GoRoute(path: '/review', builder: (_, __) => const ReviewPayScreen()),
      GoRoute(
        path: '/invoice',
        builder: (_, state) => InvoiceScreen(sale: state.extra as Sale),
      ),
      GoRoute(path: '/sales', builder: (_, __) => const SalesListScreen()),
      GoRoute(
          path: '/sales-returns',
          builder: (_, __) => const SalesReturnListScreen()),
      GoRoute(
          path: '/stock-check', builder: (_, __) => const StockCheckListScreen()),
      GoRoute(
          path: '/stock-check/new', builder: (_, __) => const NewStockCheckScreen()),
      GoRoute(
          path: '/stock-check/count',
          builder: (_, state) => StockCheckCountScreen(detail: state.extra as StockCheckDetail)),
      GoRoute(
          path: '/sale-return', builder: (_, __) => const NewSaleReturnScreen()),
      GoRoute(
          path: '/sale-return/pick',
          builder: (_, __) => const ReturnPickInvoiceScreen()),
      GoRoute(
          path: '/sale-return/review',
          builder: (_, __) => const ReturnReviewScreen()),
      GoRoute(
        path: '/return-receipt',
        builder: (_, state) =>
            ReturnReceiptScreen(saleReturn: state.extra as SaleReturn),
      ),
      GoRoute(path: '/profile', builder: (_, __) => const ProfileScreen()),
      GoRoute(
          path: '/day-session', builder: (_, __) => const DaySessionScreen()),
      GoRoute(path: '/change-pin', builder: (_, __) => const ChangePinScreen()),
      GoRoute(
          path: '/change-password',
          builder: (_, __) => const ChangePasswordScreen()),
      GoRoute(
          path: '/edit-profile',
          builder: (_, __) => const EditProfileScreen()),
      GoRoute(
          path: '/print-settings',
          builder: (_, __) => const PrintSettingsScreen()),
      GoRoute(
          path: '/permissions', builder: (_, __) => const PermissionsScreen()),
    ],
  );
}
